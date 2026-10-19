<?php

/**
 * PerfilModel
 *
 * Datos de la cuenta del propio usuario (página "Mi perfil").
 * Solo lee y actualiza la fila del usuario que tiene la sesión abierta.
 */
class PerfilModel
{
    /** Datos que se muestran en el perfil. */
    public static function obtener(PDO $conexion, int $usuarioId)
    {
        $stmt = $conexion->prepare("
            SELECT
                nombre,
                apellido,
                correo,
                fecha_registro,
                (google_id IS NOT NULL) AS con_google,
                (password IS NOT NULL AND password <> '') AS tiene_password
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $usuarioId]);
        return $stmt->fetch();
    }

    /** ¿Otro usuario ya usa este correo? */
    public static function correoEnUso(PDO $conexion, string $correo, int $usuarioId): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM usuarios
            WHERE correo = :correo AND id <> :id
            LIMIT 1
        ");
        $stmt->execute([":correo" => $correo, ":id" => $usuarioId]);
        return (bool) $stmt->fetch();
    }

    public static function actualizarDatos(PDO $conexion, int $usuarioId, string $nombre, string $apellido, string $correo): void
    {
        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET nombre = :nombre, apellido = :apellido, correo = :correo
            WHERE id = :id
        ");
        $stmt->execute([
            ":nombre"   => $nombre,
            ":apellido" => $apellido,
            ":correo"   => $correo,
            ":id"       => $usuarioId,
        ]);
    }

    /** Hash de la contraseña actual (null si la cuenta es solo de Google). */
    public static function obtenerHash(PDO $conexion, int $usuarioId): ?string
    {
        $stmt = $conexion->prepare("SELECT password FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([":id" => $usuarioId]);
        $hash = $stmt->fetchColumn();
        return ($hash === false || $hash === null || $hash === "") ? null : $hash;
    }

    public static function actualizarPassword(PDO $conexion, int $usuarioId, string $passwordNueva): void
    {
        $stmt = $conexion->prepare("UPDATE usuarios SET password = :hash WHERE id = :id");
        $stmt->execute([
            ":hash" => password_hash($passwordNueva, PASSWORD_DEFAULT),
            ":id"   => $usuarioId,
        ]);
    }
}
