<?php

/**
 * AuthModel
 *
 * Acceso a datos para autenticación (login y registro). Mismas
 * consultas que ya estaban en login.php y registro.php, movidas
 * aquí.
 */
class AuthModel
{
    public static function buscarPorCorreo(PDO $conexion, string $correo)
    {
        $stmt = $conexion->prepare("
            SELECT
                u.id, u.nombre, u.apellido, u.correo, u.password,
                u.rol_id, u.estado, r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE u.correo = :correo
            LIMIT 1
        ");
        $stmt->execute([":correo" => $correo]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function correoExiste(PDO $conexion, string $correo): bool
    {
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = :correo LIMIT 1");
        $stmt->execute([":correo" => $correo]);

        return (bool) $stmt->fetch();
    }

    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, estado)
            VALUES (:nombre, :apellido, :correo, :password, :rol_id, :estado)
        ");
        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":apellido" => $datos["apellido"],
            ":correo" => $datos["correo"],
            ":password" => $datos["password"],
            ":rol_id" => $datos["rolId"],
            ":estado" => $datos["estado"],
        ]);
    }
}