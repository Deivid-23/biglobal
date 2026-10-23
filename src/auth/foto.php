<?php
/**
 * Entrega la foto de perfil de un usuario: foto.php?u=ID&v=VERSIÓN.
 * Solo con sesión (cualquier rol). Sin foto o sin sesión: 404.
 * Como la dirección cambia cuando cambia la foto (v=), se puede guardar en caché.
 */
require_once __DIR__ . "/../../app/bootstrap.php";

if (!isset($_SESSION["usuario_id"])) {
    http_response_code(404);
    exit;
}

$foto = FotoPerfil::obtener((int) ($_GET["u"] ?? 0));
if (!$foto) {
    http_response_code(404);
    exit;
}
Archivo::servir($foto, true);
