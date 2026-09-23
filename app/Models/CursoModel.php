<?php

/**
 * CursoModel
 *
 * Acceso a datos para el modulo Catalogo de cursos del admin.
 * Mismas consultas que ya estaban en cursos.php, editar_curso.php,
 * guardar_curso.php y eliminar_curso.php, movidas aqui.
 */
class CursoModel
{
    public static function obtenerTodos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                c.id,
                c.nombre,
                c.descripcion,
                c.nivel,
                c.estado,
                c.fecha_creacion,
                u.nombre AS instructor_nombre,
                u.apellido AS instructor_apellido
            FROM cursos c

            LEFT JOIN usuarios u
                ON c.instructor_id = u.id

            ORDER BY c.id DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("
            SELECT
                id,
                nombre,
                descripcion,
                nivel,
                estado,
                instructor_id
            FROM cursos
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $id]);

        return $stmt->fetch();
    }

    public static function obtenerInstructoresActivos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                u.id,
                u.nombre,
                u.apellido
            FROM usuarios u
            INNER JOIN roles r
                ON u.rol_id = r.id
            WHERE
                r.nombre = 'Instructor'
                AND u.estado = 'Activo'
            ORDER BY u.nombre ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function instructorValido(PDO $conexion, int $instructorId): bool
    {
        $stmt = $conexion->prepare("
            SELECT u.id
            FROM usuarios u
            INNER JOIN roles r
                ON u.rol_id = r.id
            WHERE
                u.id = :id
                AND r.nombre = 'Instructor'
                AND u.estado = 'Activo'
            LIMIT 1
        ");
        $stmt->execute([":id" => $instructorId]);

        return (bool) $stmt->fetch();
    }

    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO cursos (
                nombre, descripcion, nivel, estado, instructor_id
            )
            VALUES (
                :nombre, :descripcion, :nivel, :estado, :instructor_id
            )
        ");
        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":descripcion" => $datos["descripcion"],
            ":nivel" => $datos["nivel"],
            ":estado" => $datos["estado"],
            ":instructor_id" => $datos["instructorId"],
        ]);
    }

    public static function actualizar(PDO $conexion, int $id, array $datos): void
    {
        $stmt = $conexion->prepare("
            UPDATE cursos
            SET
                nombre = :nombre,
                descripcion = :descripcion,
                nivel = :nivel,
                estado = :estado,
                instructor_id = :instructor_id
            WHERE id = :id
        ");
        $stmt->execute([
            ":nombre" => $datos["nombre"],
            ":descripcion" => $datos["descripcion"],
            ":nivel" => $datos["nivel"],
            ":estado" => $datos["estado"],
            ":instructor_id" => $datos["instructorId"],
            ":id" => $id,
        ]);
    }

    public static function eliminar(PDO $conexion, int $id): void
    {
        $stmt = $conexion->prepare("DELETE FROM cursos WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}