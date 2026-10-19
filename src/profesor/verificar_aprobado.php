<?php

/**
 * Corta el paso a docentes que todavía no están aprobados.
 * Se incluye después de protegerRol("docente") en index.php y acciones.php.
 *   activo = 1 -> sigue
 *   activo = 2 -> pendiente: va a la pantalla de espera
 *   activo = 0 -> rechazado o desactivado: cierra la sesión
 */

$stmtAprobado = Conexion::conectar()->prepare("SELECT activo FROM usuarios WHERE id = ? LIMIT 1");
$stmtAprobado->execute([(int) $_SESSION["usuario_id"]]);
$estadoDocente = (int) $stmtAprobado->fetchColumn();

if ($estadoDocente === 2) {
    header("Location: pendiente.php");
    exit;
}

if ($estadoDocente !== 1) {
    header("Location: ../auth/logout.php");
    exit;
}
