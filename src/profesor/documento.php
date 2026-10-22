<?php
/**
 * El docente abre UNO de sus propios documentos (solo los suyos).
 * Sirve tanto al docente pendiente como al aprobado.
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/DocenteSolicitudModel.php";

protegerRol("docente");

$doc = DocenteSolicitudModel::documento(Conexion::conectar(), (int) ($_GET["id"] ?? 0), (int) $_SESSION["usuario_id"]);
if (!$doc) {
    http_response_code(404);
    exit("Documento no encontrado.");
}
DocenteSolicitudModel::servir($doc);
