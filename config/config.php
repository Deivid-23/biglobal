<?php

/**
 * Carga la configuración de la aplicación.
 *
 * Orden de prioridad para cada valor:
 *   1. Variable de entorno real (la que pones en Render > Environment).
 *   2. Archivo .env en la raíz del proyecto (solo para tu computador).
 *   3. Valor por defecto de este archivo (nunca contraseñas ni secretos).
 *
 * El .env está fuera de /src, así que nunca se puede abrir desde el
 * navegador. No usamos librerías externas: un lector simple basta.
 */

function cargarEnv(string $rutaArchivo): void
{
    if (!file_exists($rutaArchivo)) {
        // Sin .env (por ejemplo en Render) se usan las variables de
        // entorno del servidor o los valores por defecto.
        return;
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {
        $linea = trim($linea);

        // Ignorar líneas vacías y comentarios
        if ($linea === "" || $linea[0] === "#" || !str_contains($linea, "=")) {
            continue;
        }

        [$clave, $valor] = explode("=", $linea, 2);
        $clave = trim($clave);
        $valor = trim(trim($valor), "\"'"); // quitar comillas envolventes

        // Si la variable ya existe en el servidor, gana la del servidor
        if ($clave !== "" && getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

/**
 * Lee una variable de entorno. Devuelve $porDefecto si no existe o está vacía.
 */
function env(string $clave, ?string $porDefecto = null): ?string
{
    $valor = getenv($clave);
    return ($valor === false || $valor === "") ? $porDefecto : $valor;
}

/**
 * Devuelve la configuración completa de la aplicación (se calcula una vez).
 */
function obtenerConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    cargarEnv(__DIR__ . "/../.env");

    $entorno = env("APP_ENV", "local");

    $config = [
        "db" => [
            "host"    => env("DB_HOST", "localhost"),
            "port"    => env("DB_PORT", "3306"),
            "nombre"  => env("DB_NAME", "biglobal"),
            "usuario" => env("DB_USER", "root"),
            "clave"   => env("DB_PASS", ""),
            "charset" => env("DB_CHARSET", "utf8mb4"),
            // TiDB Cloud exige SSL: en Render pon DB_SSL=true
            "ssl"     => filter_var(env("DB_SSL", "false"), FILTER_VALIDATE_BOOLEAN),
            // Certificados de la imagen Docker (paquete ca-certificates)
            "ssl_ca"  => env("DB_SSL_CA", "/etc/ssl/certs/ca-certificates.crt"),
        ],
        "app" => [
            "entorno" => $entorno,
            // En producción el modo depuración queda apagado por defecto
            "debug"   => filter_var(env("APP_DEBUG", $entorno === "local" ? "true" : "false"), FILTER_VALIDATE_BOOLEAN),
            // URL pública, se usa para armar enlaces de los correos
            // (ej. https://biglobal.onrender.com). Sin barra al final.
            "url"     => rtrim((string) env("APP_URL", ""), "/"),
        ],
        "google" => [
            // Nunca escribas estos valores aquí: van en .env o en Render
            "client_id"     => env("GOOGLE_CLIENT_ID", ""),
            "client_secret" => env("GOOGLE_CLIENT_SECRET", ""),
            "redirect_uri"  => env("GOOGLE_REDIRECT_URI", "http://localhost/BiGlobal/src/auth/google_callback.php"),
        ],
        "correo" => [
            // API de Brevo: en Render gratis el SMTP está bloqueado, así que
            // esta es la forma recomendada. La clave va SOLO en el entorno.
            "brevo_api_key" => env("BREVO_API_KEY"),
            // Remitente (en Brevo debe estar verificado)
            "remitente"     => env("CORREO_REMITENTE", env("SMTP_FROM")),
        ],
        "smtp" => [
            // Si están vacías se usan las de la tabla configuracion (panel admin)
            "host"        => env("SMTP_HOST"),
            "puerto"      => env("SMTP_PORT"),
            "usuario"     => env("SMTP_USER"),
            "clave"       => env("SMTP_PASS"),
            "encriptacion"=> env("SMTP_ENCRYPTION"),
            "remitente"   => env("SMTP_FROM"),
        ],
    ];

    return $config;
}
