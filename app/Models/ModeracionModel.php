<?php

/**
 * ModeracionModel
 *
 * Acceso a datos para el modulo Moderación del admin. Misma
 * consulta que ya estaba en moderacion.php, movida aqui. El cambio
 * de estado reutiliza ReporteModel::actualizarEstado() porque es
 * exactamente la misma consulta que ya usa reportes.php.
 */
class ModeracionModel
{
    public static function obtenerPendientes(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                rc.id,
                rc.tipo,
                rc.referencia,
                rc.motivo,
                rc.fecha_creacion,
                u.nombre AS creado_por_nombre,
                u.apellido AS creado_por_apellido
            FROM reportes_contenido rc
            LEFT JOIN usuarios u ON rc.creado_por = u.id
            WHERE rc.estado = 'Pendiente'
            ORDER BY rc.fecha_creacion ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}