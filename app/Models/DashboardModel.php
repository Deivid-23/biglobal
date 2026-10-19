<?php

/**
 * DashboardModel
 *
 * Estadisticas del panel administrativo (antes calculadas
 * directamente en admin/index.php).
 */
class DashboardModel
{
    public static function obtenerEstadisticas(PDO $conexion): array
    {
        $totalUsuarios = (int) $conexion->query("
            SELECT COUNT(*) AS total
            FROM usuarios
        ")->fetch()["total"];

        $totalCursos = (int) $conexion->query("
            SELECT COUNT(*) AS total
            FROM cursos
        ")->fetch()["total"];

        $totalInstructores = (int) $conexion->query("
            SELECT COUNT(*) AS total
            FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE LOWER(r.nombre) = 'docente'
              AND u.activo = 1
        ")->fetch()["total"];

        $usuariosActivosHoy = (int) $conexion->query("
            SELECT COUNT(*) AS total
            FROM usuarios
            WHERE activo = 1
              AND DATE(fecha_registro) = CURDATE()
        ")->fetch()["total"];

        return [
            "totalUsuarios" => $totalUsuarios,
            "totalCursos" => $totalCursos,
            "totalInstructores" => $totalInstructores,
            "usuariosActivosHoy" => $usuariosActivosHoy,
        ];
    }
}
