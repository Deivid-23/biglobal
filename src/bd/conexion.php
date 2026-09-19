<?php

require_once __DIR__ . "/../../config/config.php";

class Conexion
{
    private static $conexion = null;

    public static function conectar()
    {
        if (self::$conexion === null) {

            $config = obtenerConfig()["db"];

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $config["host"],
                $config["port"],
                $config["nombre"],
                $config["charset"]
            );

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            // Solo se activa si DB_SSL=true; en local (sin esa variable) el
            // comportamiento no cambia.
            if ($config["ssl"]) {
                if (!empty($config["ssl_ca"])) {
                    $opciones[PDO::MYSQL_ATTR_SSL_CA] = $config["ssl_ca"];
                }
                $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = !empty($config["ssl_ca"]);
            }

            try {

                self::$conexion = new PDO(
                    $dsn,
                    $config["usuario"],
                    $config["clave"],
                    $opciones
                );

            } catch (PDOException $e) {

                $debug = obtenerConfig()["app"]["debug"];

                die($debug
                    ? "Error de conexión: " . $e->getMessage()
                    : "No fue posible conectar con la base de datos.");
            }
        }

        return self::$conexion;
    }
}