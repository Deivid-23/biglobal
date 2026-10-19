<?php

/**
 * ActividadModel
 *
 * Acceso a datos para las actividades de una lección.
 *
 * La tabla real `actividades` tiene: id, leccion_id, titulo, descripcion,
 * tipo, fecha_creacion. NO tiene contenido, orden ni estado.
 * Para no romper las vistas que esperan esos campos:
 *   - "contenido" se guarda y se lee en la columna descripcion.
 *   - "orden" y "estado" se devuelven con valores fijos (no existen en la BD).
 */
class ActividadModel
{
    public static function obtenerLeccionInfo(PDO $conexion, $leccionId)
    {
        $stmt = $conexion->prepare("
            SELECT l.id, l.curso_id, l.titulo, l.descripcion, l.estado,
                   c.nombre AS curso_nombre
            FROM lecciones l
            INNER JOIN cursos c ON l.curso_id = c.id
            WHERE l.id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $leccionId]);

        return $stmt->fetch();
    }

    public static function obtenerLeccionResumen(PDO $conexion, $leccionId)
    {
        $stmt = $conexion->prepare("
            SELECT l.id, l.titulo, c.nombre AS curso_nombre
            FROM lecciones l
            INNER JOIN cursos c ON l.curso_id = c.id
            WHERE l.id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $leccionId]);

        return $stmt->fetch();
    }

    public static function obtenerPorLeccion(PDO $conexion, $leccionId): array
    {
        $stmt = $conexion->prepare("
            SELECT id, titulo, tipo,
                   descripcion AS contenido,
                   1 AS orden,
                   'Publicado' AS estado,
                   fecha_creacion
            FROM actividades
            WHERE leccion_id = :leccion_id
            ORDER BY id ASC
        ");
        $stmt->execute([":leccion_id" => $leccionId]);

        return $stmt->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("
            SELECT a.id, a.leccion_id, a.titulo, a.tipo,
                   a.descripcion AS contenido,
                   1 AS orden,
                   'Publicado' AS estado,
                   l.titulo AS leccion_titulo,
                   c.nombre AS curso_nombre
            FROM actividades a
            INNER JOIN lecciones l ON a.leccion_id = l.id
            INNER JOIN cursos c ON l.curso_id = c.id
            WHERE a.id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $id]);

        return $stmt->fetch();
    }

    public static function obtenerLeccionIdDeActividad(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("SELECT leccion_id FROM actividades WHERE id = :id");
        $stmt->execute([":id" => $id]);

        return $stmt->fetch();
    }

    /** $datos: leccionId, titulo, tipo, contenido (orden/estado se ignoran). */
    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO actividades (leccion_id, titulo, descripcion, tipo)
            VALUES (:leccion_id, :titulo, :descripcion, :tipo)
        ");
        $stmt->execute([
            ":leccion_id"  => $datos["leccionId"],
            ":titulo"      => $datos["titulo"],
            ":descripcion" => $datos["contenido"] ?? "",
            ":tipo"        => $datos["tipo"],
        ]);
    }

    public static function actualizar(PDO $conexion, int $id, array $datos): void
    {
        $stmt = $conexion->prepare("
            UPDATE actividades
            SET titulo = :titulo, tipo = :tipo, descripcion = :descripcion
            WHERE id = :id
        ");
        $stmt->execute([
            ":titulo"      => $datos["titulo"],
            ":tipo"        => $datos["tipo"],
            ":descripcion" => $datos["contenido"] ?? "",
            ":id"          => $id,
        ]);
    }

    public static function eliminar(PDO $conexion, int $id): void
    {
        $stmt = $conexion->prepare("DELETE FROM actividades WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}
