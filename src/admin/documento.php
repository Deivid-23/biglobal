<?php
/**
 * El admin (o super admin) abre un diploma o certificado de un docente
 * para revisar su solicitud. Ver DocenteSolicitudModel::servir.
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/DocenteSolicitudModel.php";

protegerRol("admin");

$doc = DocenteSolicitudModel::documento(Conexion::conectar(), (int) ($_GET["id"] ?? 0));
if (!$doc) {
    http_response_code(404);
    exit("Documento no encontrado.");
}
DocenteSolicitudModel::servir($doc);
