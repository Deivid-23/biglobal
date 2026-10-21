<?php

/**
 * EstudianteModel
 *
 * Todo el acceso a datos del modulo estudiante: progreso, cursos,
 * lecciones y certificados.
 */
class EstudianteModel
{
    public static function contarLeccionesCompletadas(PDO $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) AS total FROM progreso
            WHERE usuario_id = :usuario_id AND completado = 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        return (int) $stmt->fetch()["total"];
    }

    public static function contarCertificados(PDO $conexion, int $usuarioId): int
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*) AS total FROM certificados
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
            SELECT COUNT(*) AS total FROM cursos WHERE estado = 1
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
                (SELECT i.codigo FROM idiomas i WHERE i.id = c.idioma_id) AS idioma_codigo,
                (
                    SELECT COUNT(*) FROM lecciones l2
                    WHERE l2.curso_id = c.id AND l2.estado = 1
                ) AS total_lecciones,
                (
                    SELECT COUNT(*) FROM progreso p2
                    INNER JOIN lecciones l3 ON p2.leccion_id = l3.id
                    WHERE l3.curso_id = c.id
                      AND p2.usuario_id = :usuario_id
                      AND p2.completado = 1
                ) AS lecciones_hechas
            FROM cursos c
            WHERE c.estado = 1
            HAVING lecciones_hechas > 0 AND lecciones_hechas < total_lecciones
            ORDER BY c.id DESC
            LIMIT 3
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        return $stmt->fetchAll();
    }

    /** $codigoIdioma: "EN", "ES", "LSC"… para filtrar; null = todos. */
    public static function obtenerCursosPublicados(PDO $conexion, int $usuarioId, ?string $codigoIdioma = null): array
    {
        $filtro = $codigoIdioma !== null
            ? "AND c.idioma_id = (SELECT i2.id FROM idiomas i2 WHERE i2.codigo = :codigo LIMIT 1)"
            : "";

        $stmt = $conexion->prepare("
            SELECT
                c.id,
                c.nombre,
                c.descripcion,
                c.nivel,
                (SELECT i.codigo FROM idiomas i WHERE i.id = c.idioma_id) AS idioma_codigo,
                (
                    SELECT COUNT(*) FROM lecciones l
                    WHERE l.curso_id = c.id AND l.estado = 1
                ) AS total_lecciones,
                (
                    SELECT COUNT(*) FROM progreso p
                    INNER JOIN lecciones l2 ON p.leccion_id = l2.id
                    WHERE l2.curso_id = c.id
                      AND p.usuario_id = :usuario_id
                      AND p.completado = 1
                ) AS lecciones_hechas
            FROM cursos c
            WHERE c.estado = 1 {$filtro}
            ORDER BY c.nombre ASC
        ");
        $parametros = [":usuario_id" => $usuarioId];
        if ($codigoIdioma !== null) {
            $parametros[":codigo"] = $codigoIdioma;
        }
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /**
     * Idiomas activos que tienen cursos publicados (para los filtros
     * de "Explorar cursos"): codigo, nombre, total.
     */
    public static function idiomasConCursos(PDO $conexion): array
    {
        try {
            return $conexion->query("
                SELECT i.codigo, i.nombre, COUNT(c.id) AS total
                FROM idiomas i
                INNER JOIN cursos c ON c.idioma_id = i.id AND c.estado = 1
                WHERE i.estado = 1
                GROUP BY i.id, i.codigo, i.nombre
                ORDER BY i.nombre
            ")->fetchAll();
        } catch (PDOException $e) {
            error_log("[BiGlobal][idiomasConCursos] " . $e->getMessage());
            return [];
        }
    }

    public static function obtenerCursoPublicadoPorId(PDO $conexion, int $cursoId)
    {
        $stmt = $conexion->prepare("
            SELECT c.id, c.nombre, c.descripcion, c.nivel, (SELECT i.codigo FROM idiomas i WHERE i.id = c.idioma_id) AS idioma_codigo
            FROM cursos c
            WHERE c.id = :id AND c.estado = 1
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
                    WHERE a.leccion_id = l.id
                ) AS total_actividades,
                (
                    SELECT p.completado FROM progreso p
                    WHERE p.usuario_id = :usuario_id AND p.leccion_id = l.id
                    LIMIT 1
                ) AS completado
            FROM lecciones l
            WHERE l.curso_id = :curso_id AND l.estado = 1
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
            WHERE id = :leccion_id AND curso_id = :curso_id AND estado = 1
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
            WHERE curso_id = :curso_id AND estado = 1
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

    /* =========================================================
       NUEVAS CONSULTAS (panel unificado del estudiante)
    ========================================================= */

    /** Datos básicos del estudiante para el encabezado del panel. */
    public static function obtenerDatosUsuario(PDO $conexion, int $usuarioId)
    {
        $stmt = $conexion->prepare("
            SELECT nombre, apellido, correo, google_avatar
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => $usuarioId]);
        return $stmt->fetch();
    }

    /** Total de lecciones publicadas en cursos publicados. */
    public static function contarLeccionesPublicadas(PDO $conexion): int
    {
        $stmt = $conexion->query("
            SELECT COUNT(*) FROM lecciones l
            INNER JOIN cursos c ON c.id = l.curso_id
            WHERE l.estado = 1 AND c.estado = 1
        ");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Cursos en los que el estudiante ya completó al menos una lección
     * (incluye los terminados), con su avance.
     */
    public static function obtenerMisCursos(PDO $conexion, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT
                c.id, c.nombre, c.descripcion, c.nivel,
                (SELECT i.codigo FROM idiomas i WHERE i.id = c.idioma_id) AS idioma_codigo,
                (SELECT COUNT(*) FROM lecciones l
                  WHERE l.curso_id = c.id AND l.estado = 1) AS total_lecciones,
                (SELECT COUNT(*) FROM progreso p
                  INNER JOIN lecciones l2 ON p.leccion_id = l2.id
                  WHERE l2.curso_id = c.id AND l2.estado = 1
                    AND p.usuario_id = :usuario_id AND p.completado = 1) AS lecciones_hechas,
                (SELECT MAX(p3.fecha_completado) FROM progreso p3
                  INNER JOIN lecciones l3 ON p3.leccion_id = l3.id
                  WHERE l3.curso_id = c.id AND p3.usuario_id = :usuario_id2) AS ultima_actividad
            FROM cursos c
            WHERE c.estado = 1
            HAVING lecciones_hechas > 0
            ORDER BY ultima_actividad DESC
        ");
        $stmt->execute([":usuario_id" => $usuarioId, ":usuario_id2" => $usuarioId]);
        return $stmt->fetchAll();
    }

    /** Primera lección (por orden) que el estudiante aún no completa en un curso. */
    public static function obtenerSiguienteLeccion(PDO $conexion, int $cursoId, int $usuarioId)
    {
        $stmt = $conexion->prepare("
            SELECT l.id, l.titulo
            FROM lecciones l
            LEFT JOIN progreso p
                   ON p.leccion_id = l.id AND p.usuario_id = :usuario_id AND p.completado = 1
            WHERE l.curso_id = :curso_id AND l.estado = 1 AND p.id IS NULL
            ORDER BY l.orden ASC, l.id ASC
            LIMIT 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId, ":curso_id" => $cursoId]);
        return $stmt->fetch();
    }

    /** Una lección publicada de un curso publicado, con el estado del estudiante. */
    public static function obtenerLeccionPublicada(PDO $conexion, int $leccionId, int $usuarioId)
    {
        $stmt = $conexion->prepare("
            SELECT
                l.id, l.curso_id, l.titulo, l.descripcion, l.contenido, l.video_url, l.orden,
                c.nombre AS curso_nombre,
                (SELECT p.completado FROM progreso p
                  WHERE p.usuario_id = :usuario_id AND p.leccion_id = l.id
                  LIMIT 1) AS completado,
                /* Posición real en el curso (1, 2, 3...), aunque dos lecciones
                   tengan el mismo valor en la columna orden */
                (SELECT COUNT(*) + 1 FROM lecciones l2
                  WHERE l2.curso_id = l.curso_id AND l2.estado = 1
                    AND (l2.orden < l.orden OR (l2.orden = l.orden AND l2.id < l.id))) AS posicion
            FROM lecciones l
            INNER JOIN cursos c ON c.id = l.curso_id
            WHERE l.id = :id AND l.estado = 1 AND c.estado = 1
            LIMIT 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId, ":id" => $leccionId]);
        return $stmt->fetch();
    }

    /** ¿El estudiante ya había completado esta lección? */
    public static function leccionYaCompletada(PDO $conexion, int $usuarioId, int $leccionId): bool
    {
        $stmt = $conexion->prepare("
            SELECT 1 FROM progreso
            WHERE usuario_id = :usuario_id AND leccion_id = :leccion_id AND completado = 1
            LIMIT 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId, ":leccion_id" => $leccionId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Una lección está desbloqueada cuando todas las lecciones publicadas
     * que van ANTES en el curso ya están completadas.
     * ("Antes" = menor orden; si empatan en orden, menor id.)
     */
    public static function leccionDesbloqueada(PDO $conexion, int $leccionId, int $usuarioId): bool
    {
        $stmt = $conexion->prepare("
            SELECT COUNT(*)
            FROM lecciones l
            INNER JOIN lecciones actual ON actual.id = :leccion_id
            LEFT JOIN progreso p
                   ON p.leccion_id = l.id AND p.usuario_id = :usuario_id AND p.completado = 1
            WHERE l.curso_id = actual.curso_id
              AND l.estado = 1
              AND (l.orden < actual.orden OR (l.orden = actual.orden AND l.id < actual.id))
              AND p.id IS NULL
        ");
        $stmt->execute([":leccion_id" => $leccionId, ":usuario_id" => $usuarioId]);
        return (int) $stmt->fetchColumn() === 0;
    }

    /** Actividades de una lección (por ahora solo se leen). */
    public static function obtenerActividadesDeLeccion(PDO $conexion, int $leccionId): array
    {
        $stmt = $conexion->prepare("
            SELECT id, titulo, descripcion, tipo
            FROM actividades
            WHERE leccion_id = :leccion_id
            ORDER BY id ASC
        ");
        $stmt->execute([":leccion_id" => $leccionId]);
        return $stmt->fetchAll();
    }
}
