<?php

/**
 * Correo
 *
 * Envía correos por SMTP sin librerías externas (no usamos Composer).
 * Soporta: STARTTLS (puerto 587), SSL directo (465) o sin cifrado.
 *
 * ¿De dónde salen los datos del servidor SMTP?
 *   1. Variables de entorno SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS,
 *      SMTP_ENCRYPTION, SMTP_FROM   (recomendado: la clave no queda en la BD)
 *   2. Si no existen, la pestaña Configuración > Correo del panel admin
 *      (tabla configuracion: smtp_host, smtp_puerto, ...).
 *
 * Uso:
 *   Correo::enviar("ana@correo.com", "Asunto", "<p>Hola</p>", "Hola");
 */
class Correo
{
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
            "remitente"    => $env["remitente"]    ?? Config::get("smtp_remitente", ""),
            "nombre"       => Config::get("sitio_nombre", "BiGlobal"),
        ];
    }

    /** ¿Hay un servidor SMTP con usuario configurado? */
    public static function estaConfigurado(): bool
    {
        $d = self::datosSmtp();
        return $d["host"] !== "" && $d["usuario"] !== "" && $d["clave"] !== "";
    }

    /**
     * Envía un correo. Devuelve true si el servidor lo aceptó.
     * Los errores se escriben en el log del servidor (nunca la contraseña).
     */
    public static function enviar(string $para, string $asunto, string $html, string $texto): bool
    {
        return self::enviarAVarios([$para], $asunto, $html, $texto);
    }

    /**
     * Envía el mismo correo a varias personas en una sola conexión.
     * Nadie ve las direcciones de los demás (van como copia oculta).
     */
    public static function enviarAVarios(array $destinatarios, string $asunto, string $html, string $texto): bool
    {
        $d = self::datosSmtp();

        if (!self::estaConfigurado()) {
            error_log("[BiGlobal][Correo] SMTP no configurado: no se envió el correo.");
            return false;
        }

        $destinatarios = array_values(array_unique(array_filter(
            $destinatarios,
            fn($correo) => filter_var($correo, FILTER_VALIDATE_EMAIL)
        )));

        if (!$destinatarios) {
            return false;
        }

        // Con una sola persona se muestra su correo en "Para"; con varias, ninguno
        $para = count($destinatarios) === 1 ? "<{$destinatarios[0]}>" : "undisclosed-recipients:;";

        $remitente = filter_var($d["remitente"], FILTER_VALIDATE_EMAIL) ? $d["remitente"] : $d["usuario"];

        // SSL directo usa el prefijo ssl://; STARTTLS empieza sin cifrar
        $destino = ($d["encriptacion"] === "ssl" ? "ssl://" : "tcp://") . $d["host"] . ":" . $d["puerto"];
        $contexto = stream_context_create(["ssl" => ["verify_peer" => true, "verify_peer_name" => true]]);

        $socket = @stream_socket_client($destino, $errNum, $errTexto, 15, STREAM_CLIENT_CONNECT, $contexto);

        if (!$socket) {
            error_log("[BiGlobal][Correo] No se pudo conectar a {$d['host']}:{$d['puerto']} ({$errTexto})");
            return false;
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

            $mensaje = self::armarMensaje($remitente, $d["nombre"], $para, $asunto, $html, $texto);
            // Una línea que empieza con "." se duplica (regla del protocolo SMTP)
            $mensaje = preg_replace('/^\./m', '..', $mensaje);

            fwrite($socket, $mensaje . "\r\n.\r\n");
            self::esperar($socket, 250);

            self::comando($socket, "QUIT", 221);
            fclose($socket);
            return true;

        } catch (Throwable $e) {
            error_log("[BiGlobal][Correo] " . $e->getMessage());
            fclose($socket);
            return false;
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
