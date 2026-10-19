<?php

/**
 * Correo
 *
 * Envía correos sin librerías externas (no usamos Composer). Dos formas:
 *
 *   1. API de Brevo (por HTTPS). Es la que sirve en Render gratis: desde el
 *      26-sep-2025 Render bloquea los puertos SMTP (25, 465, 587) en el plan
 *      gratis. Se activa sola si existe la variable de entorno BREVO_API_KEY.
 *   2. SMTP: STARTTLS (587), SSL directo (465) o sin cifrado. Útil en tu
 *      computador o en un plan pago de Render.
 *
 * ¿De dónde salen los datos?
 *   - BREVO_API_KEY: solo variable de entorno (es un secreto, nunca va a la BD).
 *   - Remitente: CORREO_REMITENTE o SMTP_FROM del entorno, o el campo
 *     "Correo remitente" de Configuración > Correo. En Brevo debe ser un
 *     remitente verificado en tu cuenta.
 *   - SMTP: variables SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS,
 *     SMTP_ENCRYPTION o, si no existen, la pestaña Configuración > Correo.
 *
 * Uso:
 *   Correo::enviar("ana@correo.com", "Asunto", "<p>Hola</p>", "Hola");
 *   Correo::ultimoError()  → por qué falló el último envío (sin secretos)
 */
class Correo
{
    private const BREVO_URL = "https://api.brevo.com/v3/smtp/email";

    /** Explicación del último fallo, para mostrarla en el botón de prueba. */
    private static string $ultimoError = "";

    /* =========================================================
       CONFIGURACIÓN
    ========================================================= */

    /** "brevo", "smtp" o "" (nada configurado). */
    public static function proveedor(): string
    {
        if (self::claveBrevo() !== "") {
            return "brevo";
        }
        $d = self::datosSmtp();
        return ($d["host"] !== "" && $d["usuario"] !== "" && $d["clave"] !== "") ? "smtp" : "";
    }

    /** ¿Hay alguna forma de enviar correos lista? */
    public static function estaConfigurado(): bool
    {
        if (self::proveedor() === "brevo") {
            return filter_var(self::remitente(), FILTER_VALIDATE_EMAIL) !== false;
        }
        return self::proveedor() === "smtp";
    }

    public static function ultimoError(): string
    {
        return self::$ultimoError;
    }

    /** Correo que aparece como remitente. */
    public static function remitente(): string
    {
        $env = obtenerConfig()["correo"];
        return (string) ($env["remitente"] ?? Config::get("smtp_remitente", ""));
    }

    private static function claveBrevo(): string
    {
        return (string) (obtenerConfig()["correo"]["brevo_api_key"] ?? "");
    }

    /** Junta la configuración SMTP (entorno primero, luego BD). */
    private static function datosSmtp(): array
    {
        $env = obtenerConfig()["smtp"];

        return [
            "host"         => $env["host"]         ?? Config::get("smtp_host", ""),
            "puerto"       => (int) ($env["puerto"] ?? Config::get("smtp_puerto", 587)),
            "usuario"      => $env["usuario"]      ?? Config::get("smtp_usuario", ""),
            "clave"        => $env["clave"]        ?? Config::get("smtp_password", ""),
            "encriptacion" => strtolower($env["encriptacion"] ?? Config::get("smtp_encriptacion", "tls")),
        ];
    }

    /* =========================================================
       ENVIAR
    ========================================================= */

    /**
     * Envía un correo. Devuelve true si el servicio lo aceptó.
     * Los errores se escriben en el log del servidor (nunca las claves).
     */
    public static function enviar(string $para, string $asunto, string $html, string $texto): bool
    {
        return self::enviarAVarios([$para], $asunto, $html, $texto);
    }

    /**
     * Envía el mismo correo a varias personas en un solo envío.
     * Nadie ve las direcciones de los demás (van como copia oculta).
     */
    public static function enviarAVarios(array $destinatarios, string $asunto, string $html, string $texto): bool
    {
        self::$ultimoError = "";

        if (!self::estaConfigurado()) {
            return self::fallo("El correo no está configurado: falta la clave de Brevo o los datos SMTP, o el remitente.");
        }

        $destinatarios = array_values(array_unique(array_filter(
            $destinatarios,
            fn($correo) => filter_var($correo, FILTER_VALIDATE_EMAIL)
        )));

        if (!$destinatarios) {
            return self::fallo("No hay un destinatario válido.");
        }

        return self::proveedor() === "brevo"
            ? self::enviarBrevo($destinatarios, $asunto, $html, $texto)
            : self::enviarSmtp($destinatarios, $asunto, $html, $texto);
    }

    /** Guarda el motivo, lo deja en el log y devuelve false. */
    private static function fallo(string $motivo): bool
    {
        self::$ultimoError = $motivo;
        error_log("[BiGlobal][Correo] " . $motivo);
        return false;
    }

    /* =========================================================
       BREVO (API por HTTPS, puerto 443)
    ========================================================= */
    private static function enviarBrevo(array $destinatarios, string $asunto, string $html, string $texto): bool
    {
        $remitente = self::remitente();
        $nombre = Config::get("sitio_nombre", "BiGlobal");

        // Con una persona va en "Para"; con varias, todas en copia oculta
        // y el "Para" es el mismo remitente (Brevo exige al menos un "to").
        $datos = [
            "sender"      => ["name" => $nombre, "email" => $remitente],
            "subject"     => $asunto,
            "htmlContent" => $html,
            "textContent" => $texto,
        ];
        if (count($destinatarios) === 1) {
            $datos["to"] = [["email" => $destinatarios[0]]];
        } else {
            $datos["to"]  = [["email" => $remitente, "name" => $nombre]];
            $datos["bcc"] = array_map(fn($c) => ["email" => $c], $destinatarios);
        }

        if (!function_exists("curl_init")) {
            return self::fallo("PHP no tiene la extensión curl para hablar con Brevo.");
        }

        $curl = curl_init(self::BREVO_URL);
        curl_setopt_array($curl, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                "accept: application/json",
                "content-type: application/json",
                "api-key: " . self::claveBrevo(),
            ],
            CURLOPT_POSTFIELDS     => json_encode($datos, JSON_UNESCAPED_UNICODE),
        ]);

        $respuesta = curl_exec($curl);
        $codigo = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorRed = curl_error($curl);
        curl_close($curl);

        if ($respuesta === false) {
            return self::fallo("No se pudo conectar con Brevo ({$errorRed}).");
        }

        if ($codigo === 201 || $codigo === 202) {
            return true;
        }

        // Brevo responde {"code": "...", "message": "..."}: lo traducimos lo justo
        $detalle = json_decode((string) $respuesta, true);
        $mensaje = is_array($detalle) ? (string) ($detalle["message"] ?? "") : "";
        $pista = match (true) {
            $codigo === 401 => "La clave de Brevo (BREVO_API_KEY) no es válida.",
            $codigo === 400 && stripos($mensaje, "sender") !== false
                => "El remitente {$remitente} no está verificado en Brevo (Senders, Domains & Dedicated IPs → Senders).",
            $codigo === 403 => "Brevo rechazó el envío: revisa que la cuenta esté activada para correos transaccionales.",
            default          => "Brevo respondió {$codigo}.",
        };

        return self::fallo(trim($pista . ($mensaje !== "" ? " Detalle: " . $mensaje : "")));
    }

    /* =========================================================
       SMTP (puertos 587 / 465)
    ========================================================= */
    private static function enviarSmtp(array $destinatarios, string $asunto, string $html, string $texto): bool
    {
        $d = self::datosSmtp();
        $nombre = Config::get("sitio_nombre", "BiGlobal");

        // Con una sola persona se muestra su correo en "Para"; con varias, ninguno
        $para = count($destinatarios) === 1 ? "<{$destinatarios[0]}>" : "undisclosed-recipients:;";

        $remitente = filter_var(self::remitente(), FILTER_VALIDATE_EMAIL) ? self::remitente() : $d["usuario"];

        // SSL directo usa el prefijo ssl://; STARTTLS empieza sin cifrar
        $destino = ($d["encriptacion"] === "ssl" ? "ssl://" : "tcp://") . $d["host"] . ":" . $d["puerto"];
        $contexto = stream_context_create(["ssl" => ["verify_peer" => true, "verify_peer_name" => true]]);

        $socket = @stream_socket_client($destino, $errNum, $errTexto, 10, STREAM_CLIENT_CONNECT, $contexto);

        if (!$socket) {
            return self::fallo("No se pudo conectar a {$d['host']}:{$d['puerto']} ({$errTexto}). "
                . "Si estás en Render gratis, el SMTP está bloqueado: usa Brevo.");
        }

        stream_set_timeout($socket, 15);

        try {
            self::esperar($socket, 220);
            $dominio = parse_url(obtenerConfig()["app"]["url"] ?: "http://localhost", PHP_URL_HOST) ?: "localhost";
            self::comando($socket, "EHLO {$dominio}", 250);

            if ($d["encriptacion"] === "tls") {
                self::comando($socket, "STARTTLS", 220);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException("No se pudo activar TLS");
                }
                self::comando($socket, "EHLO {$dominio}", 250);
            }

            self::comando($socket, "AUTH LOGIN", 334);
            self::comando($socket, base64_encode($d["usuario"]), 334);
            self::comando($socket, base64_encode($d["clave"]), 235, true);

            self::comando($socket, "MAIL FROM:<{$remitente}>", 250);
            foreach ($destinatarios as $correo) {
                self::comando($socket, "RCPT TO:<{$correo}>", [250, 251]);
            }
            self::comando($socket, "DATA", 354);

            $mensaje = self::armarMensaje($remitente, $nombre, $para, $asunto, $html, $texto);
            // Una línea que empieza con "." se duplica (regla del protocolo SMTP)
            $mensaje = preg_replace('/^\./m', '..', $mensaje);

            fwrite($socket, $mensaje . "\r\n.\r\n");
            self::esperar($socket, 250);

            self::comando($socket, "QUIT", 221);
            fclose($socket);
            return true;

        } catch (Throwable $e) {
            fclose($socket);
            return self::fallo($e->getMessage());
        }
    }

    /** Arma el correo con versión HTML y versión texto. */
    private static function armarMensaje(string $de, string $nombre, string $para, string $asunto, string $html, string $texto): string
    {
        $limite = "bg_" . bin2hex(random_bytes(8));
        $codificar = fn(string $s) => "=?UTF-8?B?" . base64_encode($s) . "?=";

        $cabeceras = [
            "Date: " . date(DATE_RFC2822),
            "From: " . $codificar($nombre) . " <{$de}>",
            "To: {$para}",
            "Subject: " . $codificar($asunto),
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$limite}\"",
        ];

        $cuerpo = "--{$limite}\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($texto))
            . "--{$limite}\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--{$limite}--";

        return implode("\r\n", $cabeceras) . "\r\n\r\n" . $cuerpo;
    }

    /** Envía un comando y verifica el código de respuesta. */
    private static function comando($socket, string $linea, $codigoEsperado, bool $secreto = false): void
    {
        fwrite($socket, $linea . "\r\n");
        self::esperar($socket, $codigoEsperado, $secreto ? "(credencial)" : $linea);
    }

    /** Lee la respuesta del servidor (puede venir en varias líneas). */
    private static function esperar($socket, $codigoEsperado, string $contexto = ""): void
    {
        $respuesta = "";
        while (($linea = fgets($socket, 515)) !== false) {
            $respuesta .= $linea;
            // La última línea tiene un espacio después del código: "250 OK"
            if (isset($linea[3]) && $linea[3] === " ") {
                break;
            }
        }

        $codigo = (int) substr($respuesta, 0, 3);
        $esperados = (array) $codigoEsperado;

        if (!in_array($codigo, $esperados, true)) {
            throw new RuntimeException("SMTP respondió {$codigo} a {$contexto}: " . trim($respuesta));
        }
    }
}
