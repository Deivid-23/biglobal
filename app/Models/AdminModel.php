<?php

/**
 * AdminModel
 *
 * Consultas del panel de administración. Son las mismas que antes
 * estaban mezcladas dentro de src/admin/index.php, ahora ordenadas.
 */
class AdminModel
{
    /** Ejecuta un COUNT simple; si falla devuelve 0 (el panel no se cae). */
    private static function contar(PDO $c, string $sql, array $params = []): int
    {
        try {
            $stmt = $c->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("[BiGlobal][AdminModel] " . $e->getMessage());
            return 0;
        }
    }

    private static function lista(PDO $c, string $sql, array $params = []): array
    {
        try {
            $stmt = $c->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("[BiGlobal][AdminModel] " . $e->getMessage());
            return [];
        }
    }

    /* ---------- Números del inicio ---------- */
    public static function estadisticas(PDO $c): array
    {
        return [
            "usuarios"    => self::contar($c, "SELECT COUNT(*) FROM usuarios"),
            "cursos"      => self::contar($c, "SELECT COUNT(*) FROM cursos"),
            "idiomas"     => self::contar($c, "SELECT COUNT(*) FROM idiomas"),
            "lecciones"   => self::contar($c, "SELECT COUNT(*) FROM lecciones"),
            "actividades" => self::contar($c, "SELECT COUNT(*) FROM actividades"),
            "docentes"    => self::contar($c, "
                SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id
                WHERE r.nombre = 'docente' AND u.activo = 1"),
            "hoy"         => self::contar($c, "
                SELECT COUNT(*) FROM usuarios WHERE DATE(fecha_registro) = CURDATE()"),
        ];
    }

    public static function solicitudesPendientes(PDO $c): int
    {
        return self::contar($c, "
            SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id
            WHERE r.nombre = 'docente' AND u.activo = 2");
    }

    /* ---------- Gráficos ---------- */
    public static function cursosPorMes(PDO $c): array
    {
        return self::lista($c, "
            SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') AS etiqueta, COUNT(*) AS total
            FROM cursos
            WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY etiqueta ORDER BY etiqueta ASC");
    }

    public static function usuariosPorRol(PDO $c): array
    {
        return self::lista($c, "
            SELECT r.nombre AS etiqueta, COUNT(u.id) AS total
            FROM roles r LEFT JOIN usuarios u ON u.rol_id = r.id
            GROUP BY r.id, r.nombre ORDER BY total DESC");
    }

    public static function cursosPorNivel(PDO $c): array
    {
        return self::lista($c, "
            SELECT COALESCE(nivel, 'Sin nivel') AS etiqueta, COUNT(*) AS total
            FROM cursos GROUP BY nivel ORDER BY total DESC");
    }

    public static function leccionesPorCurso(PDO $c): array
    {
        return self::lista($c, "
            SELECT cu.nombre AS etiqueta, COUNT(l.id) AS total
            FROM cursos cu LEFT JOIN lecciones l ON l.curso_id = cu.id
            GROUP BY cu.id, cu.nombre ORDER BY total DESC LIMIT 6");
    }

    public static function actividadesPorTipo(PDO $c): array
    {
        return self::lista($c, "
            SELECT COALESCE(tipo, 'Sin tipo') AS etiqueta, COUNT(*) AS total
            FROM actividades GROUP BY tipo ORDER BY total DESC");
    }

    public static function idiomasPorEstado(PDO $c): array
    {
        return [
            ["etiqueta" => "Activos",   "total" => self::contar($c, "SELECT COUNT(*) FROM idiomas WHERE estado = 1")],
            ["etiqueta" => "Inactivos", "total" => self::contar($c, "SELECT COUNT(*) FROM idiomas WHERE estado = 0")],
        ];
    }

    /* ---------- Listados ---------- */
    public static function usuarios(PDO $c, int $limite = 500): array
    {
        return self::lista($c, "
            SELECT u.id, u.nombre, u.apellido, u.correo, u.activo, u.fecha_registro, r.nombre AS rol
            FROM usuarios u LEFT JOIN roles r ON u.rol_id = r.id
            ORDER BY u.id DESC LIMIT " . (int) $limite);
    }

    public static function cursos(PDO $c): array
    {
        return self::lista($c, "
            SELECT cu.id, cu.nombre, cu.descripcion, cu.nivel, cu.estado, cu.fecha_creacion, cu.instructor_id,
                   cu.idioma_id, i.nombre AS idioma,
                   CONCAT(u.nombre, ' ', u.apellido) AS docente
            FROM cursos cu
            LEFT JOIN usuarios u ON u.id = cu.instructor_id
            LEFT JOIN idiomas i ON i.id = cu.idioma_id
            ORDER BY cu.id DESC");
    }

    public static function idiomas(PDO $c): array
    {
        return self::lista($c, "SELECT id, nombre, codigo, estado FROM idiomas ORDER BY id DESC");
    }

    public static function lecciones(PDO $c): array
    {
        return self::lista($c, "
            SELECT l.id, l.curso_id, l.titulo, l.descripcion, l.contenido, l.video_url, l.orden, l.estado, cu.nombre AS curso
            FROM lecciones l INNER JOIN cursos cu ON cu.id = l.curso_id
            ORDER BY l.id DESC LIMIT 200");
    }

    public static function actividades(PDO $c): array
    {
        return self::lista($c, "
            SELECT a.id, a.leccion_id, a.titulo, a.tipo, a.descripcion, l.titulo AS leccion
            FROM actividades a INNER JOIN lecciones l ON l.id = a.leccion_id
            ORDER BY a.id DESC LIMIT 200");
    }

    public static function docentesPorEstado(PDO $c, int $activo): array
    {
        return self::lista($c, "
            SELECT u.id, u.nombre, u.apellido, u.correo, u.fecha_registro
            FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id
            WHERE r.nombre = 'docente' AND u.activo = :activo
            ORDER BY u.fecha_registro DESC", [":activo" => $activo]);
    }

    /* ---------- Para los formularios del modal "Nuevo" ---------- */
    public static function rolesPermitidos(PDO $c, bool $esSuper): array
    {
        // El admin solo puede crear estudiantes y docentes
        $nombres = $esSuper ? ["estudiante", "docente", "admin", "superadmin"] : ["estudiante", "docente"];
        $marcas = implode(",", array_fill(0, count($nombres), "?"));
        return self::lista($c, "SELECT id, nombre FROM roles WHERE nombre IN ($marcas) ORDER BY FIELD(nombre, 'estudiante', 'docente', 'admin', 'superadmin')", $nombres);
    }

    public static function opcionesCursos(PDO $c): array
    {
        return self::lista($c, "SELECT id, nombre FROM cursos ORDER BY nombre");
    }

    public static function opcionesLecciones(PDO $c): array
    {
        return self::lista($c, "
            SELECT l.id, CONCAT(cu.nombre, ' · ', l.titulo) AS nombre
            FROM lecciones l INNER JOIN cursos cu ON cu.id = l.curso_id
            ORDER BY cu.nombre, l.orden");
    }

    public static function opcionesDocentes(PDO $c): array
    {
        return self::lista($c, "
            SELECT u.id, CONCAT(u.nombre, ' ', u.apellido) AS nombre
            FROM usuarios u INNER JOIN roles r ON u.rol_id = r.id
            WHERE r.nombre = 'docente' AND u.activo = 1
            ORDER BY u.nombre");
    }

    /* ---------- Perfil del propio admin ---------- */
    public static function perfil(PDO $c, int $id)
    {
        $stmt = $c->prepare("SELECT nombre, apellido, correo, (password IS NOT NULL AND password <> '') AS tiene_password FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
