<?php

/**
 * RecuperacionModel
 *
 * Usa la tabla que ya existía: recuperacion_password
 *   (id, usuario_id, token, expiracion, usado, fecha_creacion)
 *
 * Seguridad: en la columna "token" NO se guarda el token que va en el
 * enlace, sino su huella SHA-256. Si alguien lee la base de datos no
 * puede usar esos valores para cambiar contraseñas.
 */
class RecuperacionModel
{
    /** Busca un usuario activo por correo (para enviarle el enlace). */
    public static function buscarUsuarioPorCorreo(PDO $conexion, string $correo)
    {
        $stmt = $conexion->prepare("
            SELECT id, nombre, correo, activo
            FROM usuarios
            WHERE correo = :correo
            LIMIT 1
        ");
        $stmt->execute([":correo" => $correo]);
        return $stmt->fetch();
    }

    /** ¿Cuántos enlaces pidió este usuario en los últimos 60 minutos? */
    public static function solicitudesRecientes(PDO $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) FROM recuperacion_password
            WHERE usuario_id = :id
              AND fecha_creacion >= (NOW() - INTERVAL 60 MINUTE)
        ");
        $stmt->execute([":id" => $usuarioId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crea un token nuevo (válido 60 minutos) e invalida los anteriores.
     * Devuelve el token en texto plano: SOLO para ponerlo en el enlace.
     */
    public static function crearToken(PDO $conexion, int $usuarioId): string
    {
        $token = bin2hex(random_bytes(32)); // 64 caracteres imposibles de adivinar

        // Los enlaces viejos dejan de servir
        $stmt = $conexion->prepare("UPDATE recuperacion_password SET usado = 1 WHERE usuario_id = :id AND usado = 0");
        $stmt->execute([":id" => $usuarioId]);

        $stmt = $conexion->prepare("
            INSERT INTO recuperacion_password (usuario_id, token, expiracion, usado)
            VALUES (:id, :huella, (NOW() + INTERVAL 60 MINUTE), 0)
        ");
        $stmt->execute([":id" => $usuarioId, ":huella" => hash("sha256", $token)]);

        return $token;
    }

    /** Devuelve la solicitud si el token existe, no se usó y no ha vencido. */
    public static function buscarTokenValido(PDO $conexion, string $token)
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        $stmt = $conexion->prepare("
            SELECT r.id, r.usuario_id, u.nombre, u.correo
            FROM recuperacion_password r
            INNER JOIN usuarios u ON u.id = r.usuario_id
            WHERE r.token = :huella
              AND r.usado = 0
              AND r.expiracion > NOW()
              AND u.activo <> 0
            LIMIT 1
        ");
        $stmt->execute([":huella" => hash("sha256", $token)]);
        return $stmt->fetch();
    }

    /** Guarda la nueva contraseña y marca el token como usado (todo junto). */
    public static function cambiarPassword(PDO $conexion, int $solicitudId, int $usuarioId, string $passwordNueva): void
    {
        $conexion->beginTransaction();

        try {
            $stmt = $conexion->prepare("UPDATE usuarios SET password = :hash WHERE id = :id");
            $stmt->execute([":hash" => password_hash($passwordNueva, PASSWORD_DEFAULT), ":id" => $usuarioId]);

            $stmt = $conexion->prepare("UPDATE recuperacion_password SET usado = 1 WHERE usuario_id = :id");
            $stmt->execute([":id" => $usuarioId]);

            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            throw $e;
        }
    }
}
