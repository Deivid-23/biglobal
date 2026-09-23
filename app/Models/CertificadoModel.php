<?php

/**
 * CertificadoModel
 *
 * Acceso a datos para el modulo Certificados del admin (solo
 * lectura). Misma consulta que ya estaba en certificados.php,
 * movida aqui.
 */
class CertificadoModel
{
    public static function obtenerTodos(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT
                c.id,
                c.codigo,
                c.fecha_emision,
                u.nombre AS usuario_nombre,
                u.apellido AS usuario_apellido,
                u.correo AS usuario_correo,
                cu.nombre AS curso_nombre
            FROM certificados c
            INNER JOIN usuarios u ON c.usuario_id = u.id
            INNER JOIN cursos cu ON c.curso_id = cu.id
            ORDER BY c.fecha_emision DESC
        ");
        $stmt->execute();

        return $stmt->fetchAll();
    }
}