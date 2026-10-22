<?php

/**
 * Wompi
 *
 * Cliente mínimo de la API de Wompi (pasarela de pagos de Colombia),
 * sin librerías externas. Documentación: https://docs.wompi.co
 *
 * Llaves (SOLO variables de entorno, ver config/config.php):
 *   WOMPI_LLAVE_PUBLICA     -> el navegador tokeniza la tarjeta y el checkout
 *   WOMPI_LLAVE_PRIVADA     -> el servidor crea la fuente de pago y cobra
 *   WOMPI_LLAVE_EVENTOS     -> verificar la firma de los webhooks
 *   WOMPI_LLAVE_INTEGRIDAD  -> firmar cada transacción (el monto no se puede cambiar)
 *   WOMPI_AMBIENTE          -> "sandbox" (pruebas) o "produccion"
 *
 * Reglas de seguridad que cumple esta clase:
 * - Los datos de la tarjeta nunca llegan a este servidor: el navegador los
 *   manda directo a Wompi y aquí solo llega el token (tok_...).
 * - La firma de integridad se calcula aquí, en el servidor.
 * - Un pago solo se da por bueno con el webhook firmado (ver PagoModel).
 * - Las llaves nunca se escriben en el log ni en la página.
 */
class Wompi
{
    private const API = [
        "sandbox"    => "https://sandbox.wompi.co/v1",
        "produccion" => "https://production.wompi.co/v1",
    ];

    public const CHECKOUT_URL = "https://checkout.wompi.co/p/";

    private static function llave(string $cual): string
    {
        return (string) (obtenerConfig()["wompi"][$cual] ?? "");
    }

    /** ¿Están las 4 llaves? Sin ellas no se muestra la opción de pagar. */
    public static function configurado(): bool
    {
        foreach (["publica", "privada", "eventos", "integridad"] as $cual) {
            if (self::llave($cual) === "") {
                return false;
            }
        }
        return true;
    }

    public static function ambiente(): string
    {
        return self::llave("ambiente") === "produccion" ? "produccion" : "sandbox";
    }

    public static function urlApi(): string
    {
        return self::API[self::ambiente()];
    }

    public static function llavePublica(): string
    {
        return self::llave("publica");
    }

    /* =========================================================
       FIRMAS
    ========================================================= */

    /** Firma de integridad: SHA256(referencia + centavos + moneda + secreto). */
    public static function firmaIntegridad(string $referencia, int $centavos, string $moneda = "COP"): string
    {
        return hash("sha256", $referencia . $centavos . $moneda . self::llave("integridad"));
    }

    /**
     * ¿El webhook viene de verdad de Wompi?
     * checksum = SHA256( valores de signature.properties (en orden) + timestamp + llave de eventos )
     */
    public static function eventoValido(array $evento): bool
    {
        $propiedades = $evento["signature"]["properties"] ?? null;
        $checksum    = (string) ($evento["signature"]["checksum"] ?? "");
        $timestamp   = $evento["timestamp"] ?? null;

        if (!is_array($propiedades) || !$propiedades || $checksum === "" || $timestamp === null || self::llave("eventos") === "") {
            return false;
        }

        $cadena = "";
        foreach ($propiedades as $ruta) {
            $valor = self::valorEnRuta($evento["data"] ?? [], (string) $ruta);
            if ($valor === null || is_array($valor)) {
                return false;
            }
            $cadena .= is_bool($valor) ? ($valor ? "true" : "false") : (string) $valor;
        }
        $cadena .= $timestamp . self::llave("eventos");

        return hash_equals(strtolower($checksum), hash("sha256", $cadena));
    }

    /** "transaction.amount_in_cents" -> $data["transaction"]["amount_in_cents"] */
    private static function valorEnRuta(array $datos, string $ruta)
    {
        foreach (explode(".", $ruta) as $parte) {
            if (!is_array($datos) || !array_key_exists($parte, $datos)) {
                return null;
            }
            $datos = $datos[$parte];
        }
        return $datos;
    }

    /* =========================================================
       ACEPTACIÓN DE TÉRMINOS (obligatoria antes de pagar)
    ========================================================= */

    /**
     * Tokens y enlaces de los términos de Wompi del comercio.
     * ["aceptacion" => [token, enlace], "datos" => [token, enlace]] o null.
     */
    public static function terminos(): ?array
    {
        $r = self::pedir("GET", "/merchants/" . rawurlencode(self::llavePublica()), null, false);
        $d = $r["data"] ?? null;
        if (!is_array($d) || empty($d["presigned_acceptance"]["acceptance_token"])) {
            return null;
        }
        return [
            "aceptacion" => [
                "token"  => (string) $d["presigned_acceptance"]["acceptance_token"],
                "enlace" => (string) ($d["presigned_acceptance"]["permalink"] ?? ""),
            ],
            "datos" => [
                "token"  => (string) ($d["presigned_personal_data_auth"]["acceptance_token"] ?? ""),
                "enlace" => (string) ($d["presigned_personal_data_auth"]["permalink"] ?? ""),
            ],
        ];
    }

    /* =========================================================
       TARJETA: fuente de pago y cobros (renovación automática)
    ========================================================= */

    /** Crea la fuente de pago con el token de la tarjeta. Devuelve el id o null. */
    public static function crearFuentePago(string $tokenTarjeta, string $correo, string $tokenAceptacion, string $tokenDatos): ?string
    {
        $cuerpo = [
            "type"             => "CARD",
            "token"            => $tokenTarjeta,
            "customer_email"   => $correo,
            "acceptance_token" => $tokenAceptacion,
        ];
        if ($tokenDatos !== "") {
            $cuerpo["accept_personal_auth"] = $tokenDatos;
        }
        $r = self::pedir("POST", "/payment_sources", $cuerpo, true);
        $id = $r["data"]["id"] ?? null;
        return $id !== null ? (string) $id : null;
    }

    /**
     * Cobra con una fuente de pago guardada. Devuelve la transacción de Wompi
     * (id, status...) o null. OJO: el estado final llega por el webhook.
     */
    public static function cobrarFuente(string $fuentePagoId, int $centavos, string $referencia, string $correo): ?array
    {
        $r = self::pedir("POST", "/transactions", [
            "amount_in_cents"   => $centavos,
            "currency"          => "COP",
            "signature"         => self::firmaIntegridad($referencia, $centavos),
            "customer_email"    => $correo,
            "payment_method"    => ["installments" => 1],
            "reference"         => $referencia,
            "payment_source_id" => (int) $fuentePagoId,
            "recurrent"         => true,
        ], true);
        return is_array($r["data"] ?? null) ? $r["data"] : null;
    }

    /* =========================================================
       CHECKOUT WEB (PSE, Nequi, Botón Bancolombia, tarjeta sin guardar)
    ========================================================= */

    /** Dirección del checkout de Wompi para un pago único. */
    public static function urlCheckout(string $referencia, int $centavos, string $correo, string $volverA): string
    {
        return self::CHECKOUT_URL . "?" . http_build_query([
            "public-key"          => self::llavePublica(),
            "currency"            => "COP",
            "amount-in-cents"     => $centavos,
            "reference"           => $referencia,
            "signature:integrity" => self::firmaIntegridad($referencia, $centavos),
            "redirect-url"        => $volverA,
            "customer-data:email" => $correo,
        ]);
    }

    /* =========================================================
       HTTP
    ========================================================= */

    /** Petición a la API. Devuelve el JSON como arreglo (o [] si falló). */
    private static function pedir(string $metodo, string $ruta, ?array $cuerpo, bool $privada): array
    {
        if (!self::configurado()) {
            return [];
        }

        $curl = curl_init(self::urlApi() . $ruta);
        $cabeceras = ["Accept: application/json"];
        if ($privada) {
            $cabeceras[] = "Authorization: Bearer " . self::llave("privada");
        }
        if ($cuerpo !== null) {
            $cabeceras[] = "Content-Type: application/json";
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($cuerpo));
        }
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $metodo,
            CURLOPT_HTTPHEADER     => $cabeceras,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $respuesta = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $errorCurl = curl_error($curl);
        curl_close($curl);

        $json = is_string($respuesta) ? json_decode($respuesta, true) : null;
        if ($codigo < 200 || $codigo >= 300 || !is_array($json)) {
            // Solo el código y el tipo de error: nunca las llaves ni el cuerpo enviado
            $tipo = is_array($json) ? (string) ($json["error"]["type"] ?? "") : "";
            error_log("[BiGlobal][Wompi] {$metodo} {$ruta} -> HTTP {$codigo} {$tipo} {$errorCurl}");
            return [];
        }
        return $json;
    }
}
