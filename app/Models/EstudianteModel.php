<?php

/**
 * EstudianteModel
 *
 * Todo el acceso a datos del modulo estudiante: progreso, cursos,
 * lecciones y certificados. Cada metodo recibe la conexion PDO ya
 * abierta (la crea el Controller) y devuelve datos planos (int,
 * array), nunca HTML. Las consultas son exactamente las que ya
 * estaban en cada pagina, solo movidas aqui.
 */
class EstudianteModel
{
    public static function contarLeccionesCompletadas(PDO $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) AS total
            FROM progreso
            WHERE usuario_id = :usuario_id AND completado = 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);

        return (int) $stmt->fetch()["total"];
    }

    public static function contarCertificados(PDO $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) AS total
            FROM certificados
            WHERE usuario_id = :usuario_id
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);

        return (int) $stmt->fetch()["total"];
    }

    public static function contarCursosEnProgreso(PDO $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(DISTINCT l.curso_id) AS total
            FROM progreso p
            INNER JOIN lecciones l ON p.leccion_id = l.id
            WHERE p.usuario_id = :usuario_id
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);

        return (int) $stmt->fetch()["total"];
    }

    public static function contarCursosDisponibles(PDO $conexion): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) AS total FROM cursos WHERE estado = 'Publicado'
        ");
        $stmt->execute();

        return (int) $stmt->fetch()["total"];
    }

    public static function obtenerCursosParaContinuar(PDO $conexion, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT
                c.id,
                c.nombre,
                c.nivel,
                (
                    SELECT COUNT(*) FROM lecciones l2
                    WHERE l2.curso_id = c.id AND l2.estado = 'Publicado'
                ) AS total_lecciones,
                (
                    SELECT COUNT(*) FROM progreso p2
                    INNER JOIN lecciones l3 ON p2.leccion_id = l3.id
                    WHERE l3.curso_id = c.id
                      AND p2.usuario_id = :usuario_id
                      AND p2.completado = 1
                ) AS lecciones_hechas
            FROM cursos c
            WHERE c.estado = 'Publicado'
            HAVING lecciones_hechas > 0 AND lecciones_hechas < total_lecciones
            ORDER BY c.id DESC
            LIMIT 3
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);

        return $stmt->fetchAll();
    }

    public static function obtenerCursosPublicados(PDO $conexion, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT
                c.id,
                c.nombre,
                c.descripcion,
                c.nivel,
                (
                    SELECT COUNT(*) FROM lecciones l
                    WHERE l.curso_id = c.id AND l.estado = 'Publicado'
                ) AS total_lecciones,
                (
                    SELECT COUNT(*) FROM progreso p
                    INNER JOIN lecciones l2 ON p.leccion_id = l2.id
                    WHERE l2.curso_id = c.id
                      AND p.usuario_id = :usuario_id
                      AND p.completado = 1
                ) AS lecciones_hechas
            FROM cursos c
            WHERE c.estado = 'Publicado'
            ORDER BY c.nombre ASC
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);

        return $stmt->fetchAll();
    }

    public static function obtenerCursoPublicadoPorId(PDO $conexion, int $cursoId)
    {
        $stmt = $conexion->prepare("
            SELECT id, nombre, descripcion, nivel
            FROM cursos
            WHERE id = :id AND estado = 'Publicado'
            LIMIT 1
        ");
        $stmt->execute([":id" => $cursoId]);

        return $stmt->fetch();
    }

    public static function obtenerLeccionesDeCurso(PDO $conexion, int $cursoId, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT
                l.id,
                l.titulo,
                l.descripcion,
                l.orden,
                (
                    SELECT COUNT(*) FROM actividades a
                    WHERE a.leccion_id = l.id AND a.estado = 'Publicado'
                ) AS total_actividades,
                (
                    SELECT p.completado FROM progreso p
                    WHERE p.usuario_id = :usuario_id AND p.leccion_id = l.id
                    LIMIT 1
                ) AS completado
            FROM lecciones l
            WHERE l.curso_id = :curso_id AND l.estado = 'Publicado'
            ORDER BY l.orden ASC, l.id ASC
        ");
        $stmt->execute([
            ":usuario_id" => $usuarioId,
            ":curso_id" => $cursoId,
        ]);

        return $stmt->fetchAll();
    }

    public static function obtenerCertificadoDeCurso(PDO $conexion, int $usuarioId, int $cursoId)
    {
        $stmt = $conexion->prepare("
            SELECT codigo FROM certificados
            WHERE usuario_id = :usuario_id AND curso_id = :curso_id
            LIMIT 1
        ");
        $stmt->execute([
            ":usuario_id" => $usuarioId,
            ":curso_id" => $cursoId,
        ]);

        return $stmt->fetch();
    }

    public static function obtenerCertificadosDeUsuario(PDO $conexion, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT
                cert.codigo,
                cert.fecha_emision,
                cu.nombre AS curso_nombre,
                cu.nivel
            FROM certificados cert
            INNER JOIN cursos cu ON cert.curso_id = cu.id
            WHERE cert.usuario_id = :usuario_id
            ORDER BY cert.fecha_emision DESC
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);

        return $stmt->fetchAll();
    }

    public static function leccionValidaEnCurso(PDO $conexion, int $leccionId, int $cursoId): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM lecciones
            WHERE id = :leccion_id AND curso_id = :curso_id AND estado = 'Publicado'
            LIMIT 1
        ");
        $stmt->execute([
            ":leccion_id" => $leccionId,
            ":curso_id" => $cursoId,
        ]);

        return (bool) $stmt->fetch();
    }

    public static function marcarLeccionCompletada(PDO $conexion, int $usuarioId, int $leccionId): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO progreso (usuario_id, leccion_id, completado, fecha_completado)
            VALUES (:usuario_id, :leccion_id, 1, NOW())
            ON DUPLICATE KEY UPDATE
                completado = 1,
                fecha_completado = NOW()
        ");
        $stmt->execute([
            ":usuario_id" => $usuarioId,
            ":leccion_id" => $leccionId,
        ]);
    }

    public static function contarLeccionesTotalYCompletadas(PDO $conexion, int $cursoId, int $usuarioId): array
    {
        $stmtTotal = $conexion->prepare("
            SELECT COUNT(*) AS total FROM lecciones
            WHERE curso_id = :curso_id AND estado = 'Publicado'
        ");
        $stmtTotal->execute([":curso_id" => $cursoId]);
        $total = (int) $stmtTotal->fetch()["total"];

        $stmtHechas = $conexion->prepare("
            SELECT COUNT(*) AS total
            FROM progreso p
            INNER JOIN lecciones l ON p.leccion_id = l.id
            WHERE l.curso_id = :curso_id
              AND p.usuario_id = :usuario_id
              AND p.completado = 1
        ");
        $stmtHechas->execute([
            ":curso_id" => $cursoId,
            ":usuario_id" => $usuarioId,
        ]);
        $hechas = (int) $stmtHechas->fetch()["total"];

        return ["total" => $total, "hechas" => $hechas];
    }

    public static function certificadoExiste(PDO $conexion, int $usuarioId, int $cursoId): bool
    {
        $stmt = $conexion->prepare("
            SELECT id FROM certificados
            WHERE usuario_id = :usuario_id AND curso_id = :curso_id
            LIMIT 1
        ");
        $stmt->execute([
            ":usuario_id" => $usuarioId,
            ":curso_id" => $cursoId,
        ]);

        return (bool) $stmt->fetch();
    }

    public static function emitirCertificado(PDO $conexion, int $usuarioId, int $cursoId, string $codigo): void
    {
        $stmt = $conexion->prepare("
            INSERT INTO certificados (usuario_id, curso_id, codigo)
            VALUES (:usuario_id, :curso_id, :codigo)
        ");
        $stmt->execute([
            ":usuario_id" => $usuarioId,
            ":curso_id" => $cursoId,
            ":codigo" => $codigo,
        ]);
    }
}
