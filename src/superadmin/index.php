<?php

/**
 * Panel del super administrador: punto de entrada.
 *
 * Solo entra el rol "superadmin" (lo revisa SuperAdminController).
 * Un admin normal que escriba esta URL recibe "Acceso denegado".
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/SuperAdminController.php";

$controlador = new SuperAdminController();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $controlador->accionCuenta();
    exit;
}

$paginas = ["inicio", "cuentas", "configuracion"];
$pagina = $_GET["page"] ?? "inicio";

$controlador->{in_array($pagina, $paginas, true) ? $pagina : "inicio"}();
