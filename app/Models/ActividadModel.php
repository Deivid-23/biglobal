<?php

/**
 * ActividadModel
 *
 * Acceso a datos para el submodulo Actividades (dentro de una
 * leccion). Mismas consultas que ya estaban en actividades.php,
 * editar_actividad.php, guardar_actividad.php y eliminar_actividad.php,
 * movidas aqui.
 */
class ActividadModel
{
    public static function obtenerLeccionInfo(PDO $conexion, $leccionId)
    {
        $stmt = $conexion->prepare("
            SELECT
                l.id,
                l.curso_id,
                l.titulo,
                l.descripcion,
                l.estado,
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
            SELECT id, titulo, tipo, contenido, orden, estado, fecha_creacion
            FROM actividades
            WHERE leccion_id = :leccion_id
            ORDER BY orden ASC, id ASC
        ");
        $stmt->execute([":leccion_id" => $leccionId]);

        return $stmt->fetchAll();
    }

    public static function obtenerPorId(PDO $conexion, int $id)
    {
        $stmt = $conexion->prepare("
            SELECT
                a.id,
                a.leccion_id,
                a.titulo,
                a.tipo,
                a.contenido,
                a.orden,
                a.estado,
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

    public static function crear(PDO $conexion, array $datos): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO actividades (leccion_id, titulo, tipo, contenido, orden, estado)
            VALUES (:leccion_id, :titulo, :tipo, :contenido, :orden, :estado)
        ");
        $stmt->execute([
            ":leccion_id" => $datos["leccionId"],
            ":titulo" => $datos["titulo"],
            ":tipo" => $datos["tipo"],
            ":contenido" => $datos["contenido"],
            ":orden" => $datos["orden"],
            ":estado" => $datos["estado"],
        ]);
    }

    public static function actualizar(PDO $conexion, int $id, array $datos): void
    {
        $stmt = $conexion->prepare("
            UPDATE actividades
            SET titulo = :titulo, tipo = :tipo, contenido = :contenido,
                orden = :orden, estado = :estado
            WHERE id = :id
        ");
        $stmt->execute([
            ":titulo" => $datos["titulo"],
            ":tipo" => $datos["tipo"],
            ":contenido" => $datos["contenido"],
            ":orden" => $datos["orden"],
            ":estado" => $datos["estado"],
            ":id" => $id,
        ]);
    }

    public static function eliminar(PDO $conexion, int $id): void
    {
        $stmt = $conexion->prepare("DELETE FROM actividades WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }
}