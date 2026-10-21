<?php

require_once __DIR__ . "/../../config/config.php";

/**
 * Conexión única a la base de datos (patrón "singleton").
 *
 * Los datos salen de config/config.php, que a su vez lee el .env
 * (en tu computador) o las variables de entorno (en Render).
 * Con DB_SSL=true la conexión va cifrada, que es lo que pide TiDB Cloud.
 */
class Conexion
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion !== null) {
            return self::$conexion;
        }

        $db = obtenerConfig()["db"];

        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $db["host"],
            $db["port"],
            $db["nombre"],
            $db["charset"]
        );

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        if ($db["ssl"]) {
            // Activa SSL y verifica el certificado del servidor
            $opciones[PDO::MYSQL_ATTR_SSL_CA] = $db["ssl_ca"];
            $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        }

        try {
            self::$conexion = new PDO($dsn, $db["usuario"], $db["clave"], $opciones);

            // Hora de Colombia (UTC-5, sin horario de verano) para NOW(),
            // CURRENT_TIMESTAMP y la lectura de columnas TIMESTAMP.
            // Sin esto TiDB usa UTC y las fechas salían 5 horas adelantadas.
            self::$conexion->exec("SET time_zone = '-05:00'");
        } catch (PDOException $e) {
            // El detalle real va al log del servidor, nunca al navegador
            error_log("[BiGlobal][conexion] " . $e->getMessage());
            http_response_code(503);
            die("No pudimos conectar con la base de datos. Intenta de nuevo en unos minutos.");
        }

        return self::$conexion;
    }
}
