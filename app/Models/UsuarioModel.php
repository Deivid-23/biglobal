<?php

/**
 * UsuarioModel
 *
 * Acceso a datos para el modulo de Usuarios y roles del admin.
 * Mismas consultas que ya estaban en usuarios.php, editar_usuario.php,
 * guardar_usuario.php y eliminar_usuario.php, movidas aqui.
 */
class UsuarioModel
{
    public static function obtenerTodos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                u.id,
                u.nombre,
                u.apellido,
                u.correo,
                u.estado,
                u.fecha_registro,
                r.nombre AS rol
            FROM usuarios u

            LEFT JOIN roles r
                ON u.rol_id = r.id

            ORDER BY u.id DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("
            SELECT
                u.id,
                u.nombre,
                u.apellido,
                u.correo,
                u.estado,
                r.nombre AS rol
            FROM usuarios u

            LEFT JOIN roles r
                ON u.rol_id = r.id

            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $id]);

        return $stmt->fetch();
    }

    public static function obtenerRolIdPorNombre(PDO $conexion, string $nombreRol)
    {
        $stmt = $conexion->prepare("
            SELECT id FROM roles WHERE nombre = :rol LIMIT 1
        ");
        $stmt->execute([":rol" => $nombreRol]);

        $rol = $stmt->fetch();

        return $rol ? (int) $rol["id"] : null;
    }

    public static function existeCorreo(PDO $conexion, string $correo): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM usuarios WHERE correo = :correo LIMIT 1
        ");
        $stmt->execute([":correo" => $correo]);

        return (bool) $stmt->fetch();
    }

    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO usuarios (
                nombre, apellido, correo, password, rol_id, estado
            )
            VALUES (
                :nombre, :apellido, :correo, :password, :rol_id, :estado
            )
        ");

        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":apellido" => $datos["apellido"],
            ":correo" => $datos["correo"],
            ":password" => $datos["passwordHash"],
            ":rol_id" => $datos["rolId"],
            ":estado" => $datos["estado"],
        ]);
    }

    public static function actualizarDatos(PDO $conexion, int $id, array $datos): void
    {
        if (!empty($datos["passwordHash"])) {
            $stmt = $conexion->prepare("
                UPDATE usuarios
                SET nombre = :nombre, apellido = :apellido, correo = :correo,
                    password = :password, estado = :estado
                WHERE id = :id
            ");
            $stmt->execute([
                ":nombre" => $datos["nombre"],
                ":apellido" => $datos["apellido"],
                ":correo" => $datos["correo"],
                ":password" => $datos["passwordHash"],
                ":estado" => $datos["estado"],
                ":id" => $id,
            ]);
            return;
        }

        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET nombre = :nombre, apellido = :apellido, correo = :correo,
                estado = :estado
            WHERE id = :id
        ");
        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":apellido" => $datos["apellido"],
            ":correo" => $datos["correo"],
            ":estado" => $datos["estado"],
            ":id" => $id,
        ]);
    }

    public static function actualizarRol(PDO $conexion, int $id, int $rolId): void
    {
        $stmt = $conexion->prepare("
            UPDATE usuarios SET rol_id = :rol_id WHERE id = :id
        ");
        $stmt->execute([":rol_id" => $rolId, ":id" => $id]);
    }

    public static function eliminar(PDO $conexion, int $id): void
    {
        $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}
