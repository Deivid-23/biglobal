<?php

/**
 * IdiomaModel
 *
 * Acceso a datos para el modulo Idiomas del admin. Mismas consultas
 * que ya estaban en idiomas.php, editar_idioma.php, guardar_idioma.php
 * y eliminar_idioma.php, movidas aqui.
 */
class IdiomaModel
{
    public static function obtenerTodos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT id, nombre, codigo, estado, fecha_creacion
            FROM idiomas
            ORDER BY id DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("
            SELECT id, nombre, codigo, estado
            FROM idiomas
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $id]);

        return $stmt->fetch();
    }

    public static function existeDuplicado(PDO $conexion, string $nombre, string $codigo): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM idiomas
            WHERE nombre = :nombre OR codigo = :codigo
            LIMIT 1
        ");
        $stmt->execute([":nombre" => $nombre, ":codigo" => $codigo]);

        return (bool) $stmt->fetch();
    }

    public static function existeDuplicadoExcluyendo(PDO $conexion, string $nombre, string $codigo, int $id): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM idiomas
            WHERE (nombre = :nombre OR codigo = :codigo)
              AND id != :id
            LIMIT 1
        ");
        $stmt->execute([":nombre" => $nombre, ":codigo" => $codigo, ":id" => $id]);

        return (bool) $stmt->fetch();
    }

    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO idiomas (nombre, codigo, estado)
            VALUES (:nombre, :codigo, :estado)
        ");
        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":codigo" => $datos["codigo"],
            ":estado" => $datos["estado"],
        ]);
    }

    public static function actualizar(PDO $conexion, int $id, array $datos): void
    {
        $stmt = $conexion->prepare("
            UPDATE idiomas
            SET nombre = :nombre, codigo = :codigo, estado = :estado
            WHERE id = :id
        ");
        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":codigo" => $datos["codigo"],
            ":estado" => $datos["estado"],
            ":id" => $id,
        ]);
    }

    public static function eliminar(PDO $conexion, int $id): void
    {
        $stmt = $conexion->prepare("DELETE FROM idiomas WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}