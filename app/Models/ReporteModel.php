<?php

/**
 * ReporteModel
 *
 * Acceso a datos para el modulo Reportes del admin (solo lectura +
 * una accion rapida de cambio de estado). Mismas consultas que ya
 * estaban en reportes.php, movidas aqui.
 */
class ReporteModel
{
    public static function obtenerTodos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                rc.id,
                rc.tipo,
                rc.referencia,
                rc.motivo,
                rc.estado,
                rc.fecha_creacion,
                u.nombre AS creado_por_nombre,
                u.apellido AS creado_por_apellido
            FROM reportes_contenido rc
            LEFT JOIN usuarios u ON rc.creado_por = u.id
            ORDER BY rc.fecha_creacion DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function actualizarEstado(PDO $conexion, $id, string $estado): void
    {
        $stmt = $conexion->prepare("
            UPDATE reportes_contenido
            SET estado = :estado
            WHERE id = :id
        ");
        $stmt->execute([":estado" => $estado, ":id" => $id]);
    }
}