<?php

/**
 * LeccionModel
 *
 * Acceso a datos para el modulo Lecciones del admin. Mismas
 * consultas que ya estaban en lecciones.php, editar_leccion.php,
 * guardar_leccion.php y eliminar_leccion.php, movidas aqui.
 */
class LeccionModel
{
    public static function obtenerCursos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT id, nombre
            FROM cursos
            ORDER BY nombre ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function obtenerPorCurso(PDO $conexion, $cursoId): array
    {
        $stmt = $conexion->prepare("
            SELECT
                l.id,
                l.curso_id,
                l.titulo,
                l.descripcion,
                l.orden,
                l.estado,
                l.fecha_creacion,
                c.nombre AS curso_nombre,
                (
                    SELECT COUNT(*)
                    FROM actividades a
                    WHERE a.leccion_id = l.id
                ) AS total_actividades
            FROM lecciones l
            INNER JOIN cursos c ON l.curso_id = c.id
            WHERE l.curso_id = :curso_id
            ORDER BY l.orden ASC, l.id ASC
        ");
        $stmt->execute([":curso_id" => $cursoId]);

        return $stmt->fetchAll();
    }

    public static function obtenerTodas(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                l.id,
                l.curso_id,
                l.titulo,
                l.descripcion,
                l.orden,
                l.estado,
                l.fecha_creacion,
                c.nombre AS curso_nombre,
                (
                    SELECT COUNT(*)
                    FROM actividades a
                    WHERE a.leccion_id = l.id
                ) AS total_actividades
            FROM lecciones l
            INNER JOIN cursos c ON l.curso_id = c.id
            ORDER BY c.nombre ASC, l.orden ASC, l.id ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("
            SELECT id, curso_id, titulo, descripcion, orden, estado
            FROM lecciones
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $id]);

        return $stmt->fetch();
    }

    public static function cursoExiste(PDO $conexion, $cursoId): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM cursos WHERE id = :id LIMIT 1
        ");
        $stmt->execute([":id" => $cursoId]);

        return (bool) $stmt->fetch();
    }

    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO lecciones (curso_id, titulo, descripcion, orden, estado)
            VALUES (:curso_id, :titulo, :descripcion, :orden, :estado)
        ");
        $stmt->execute([
            ":curso_id" => $datos["cursoId"],
            ":titulo" => $datos["titulo"],
            ":descripcion" => $datos["descripcion"],
            ":orden" => $datos["orden"],
            ":estado" => $datos["estado"],
        ]);
    }

    public static function actualizar(PDO $conexion, int $id, array $datos): void
    {
        $stmt = $conexion->prepare("
            UPDATE lecciones
            SET curso_id = :curso_id, titulo = :titulo, descripcion = :descripcion,
                orden = :orden, estado = :estado
            WHERE id = :id
        ");
        $stmt->execute([
            ":curso_id" => $datos["cursoId"],
            ":titulo" => $datos["titulo"],
            ":descripcion" => $datos["descripcion"],
            ":orden" => $datos["orden"],
            ":estado" => $datos["estado"],
            ":id" => $id,
        ]);
    }

    public static function eliminar(PDO $conexion, int $id): void
    {
        $stmt = $conexion->prepare("DELETE FROM lecciones WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}