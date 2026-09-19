<?php

/**
 * Carga la configuración de la aplicación desde un archivo .env
 * ubicado en la raíz del proyecto (fuera de /src, para que nunca
 * quede accesible desde el navegador).
 *
 * No usamos ninguna librería externa: para un proyecto de este
 * tamaño, un parser simple es suficiente y evita depender de
 * Composer solo para esto.
 */

function cargarEnv(string $rutaArchivo): void
{
    if (!file_exists($rutaArchivo)) {
        // Si no existe .env, seguimos con los valores por defecto
        // definidos en obtenerConfig(). Esto permite que el
        // proyecto funcione igual que antes en un entorno local
        // recién clonado, sin romper nada.
        return;
    }

    $lineas = file($rutaArchivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lineas as $linea) {

        $linea = trim($linea);

        // Ignorar comentarios
        if ($linea === "" || $linea[0] === "#") {
            continue;
        }

        if (!str_contains($linea, "=")) {
            continue;
        }

        [$clave, $valor] = explode("=", $linea, 2);

        $clave = trim($clave);
        $valor = trim($valor);

        // Quitar comillas envolventes si las hay
        $valor = trim($valor, "\"'");

        if ($clave !== "" && getenv($clave) === false) {
            putenv("{$clave}={$valor}");
            $_ENV[$clave] = $valor;
        }
    }
}

/**
 * Devuelve la configuración completa de la aplicación,
 * combinando variables de entorno con valores por defecto
 * (los mismos que ya traía el proyecto, para no romper
 * el entorno local de nadie del equipo).
 */
function obtenerConfig(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    cargarEnv(__DIR__ . "/../.env");

    $config = [
        "db" => [
            "host"    => getenv("DB_HOST") ?: "localhost",
            "port"    => getenv("DB_PORT") ?: "3306",
            "nombre"  => getenv("DB_NAME") ?: "biglobal",
            "usuario" => getenv("DB_USER") ?: "root",
            "clave"   => getenv("DB_PASS") ?: "",
            "charset" => getenv("DB_CHARSET") ?: "utf8mb4",
            "ssl"     => filter_var(getenv("DB_SSL") ?: "false", FILTER_VALIDATE_BOOLEAN),
            "ssl_ca"  => getenv("DB_SSL_CA") ?: null,
        ],
        "app" => [
            "entorno" => getenv("APP_ENV") ?: "local",
            "debug"   => filter_var(getenv("APP_DEBUG") ?: "true", FILTER_VALIDATE_BOOLEAN),
        ],
    ];

    return $config;
}
