<?php
/**
 * Abre un archivo de los grupos (material; tareas y entregas en la parte B).
 * GrupoModel::archivoConPermiso revisa según el contexto quién puede verlo:
 * el docente del grupo, sus estudiantes o el equipo de administración.
 * Cualquier otro caso responde 404 (no se dice si el archivo existe).
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/GrupoModel.php";

protegerRol(["estudiante", "docente", "admin"]);

$conexion = Conexion::conectar();
$archivo = GrupoModel::disponible($conexion)
    ? GrupoModel::archivoConPermiso($conexion, (int) ($_GET["id"] ?? 0), (int) $_SESSION["usuario_id"], rolActual())
    : null;

if (!$archivo) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}
Archivo::servir($archivo);
