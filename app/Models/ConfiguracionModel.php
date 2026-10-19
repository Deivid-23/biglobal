<?php

/**
 * ConfiguracionModel
 *
 * Acceso a datos para el modulo Configuración del admin (clave/valor
 * en la tabla configuracion). Mismas consultas que ya estaban en
 * configuracion.php, movidas aqui.
 */
class ConfiguracionModel
{
    public static function obtenerTodo(PDO $conexion): array
    {
        $stmt = $conexion->prepare("SELECT clave, valor FROM configuracion");
        $stmt->execute();

        $config = [];
        foreach ($stmt->fetchAll() as $fila) {
            $config[$fila["clave"]] = $fila["valor"];
        }

        return $config;
    }

    public static function guardar(PDO $conexion, string $nombreSitio, string $correoContacto, string $modoMantenimiento): void
    {
        /* Claves reales de la tabla configuracion: sitio_nombre, sitio_correo.
           VALUES(valor) evita repetir el mismo parámetro dos veces. */
        $stmt = $conexion->prepare("
            INSERT INTO configuracion (clave, valor)
            VALUES (:clave, :valor)
            ON DUPLICATE KEY UPDATE valor = VALUES(valor)
        ");

        $stmt->execute([":clave" => "sitio_nombre", ":valor" => $nombreSitio]);
        $stmt->execute([":clave" => "sitio_correo", ":valor" => $correoContacto]);
        $stmt->execute([":clave" => "modo_mantenimiento", ":valor" => $modoMantenimiento]);
    }
}