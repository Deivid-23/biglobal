<?php

/**
 * Panel de administración: punto de entrada.
 *
 * Este archivo solo decide qué página mostrar según ?page=...
 * La lógica está en app/Controllers/AdminController.php y el HTML en
 * app/Views/admin/ (con el layout común de app/Views/panel/).
 *
 * Las opciones exclusivas del super administrador viven en
 * src/superadmin/index.php. Los enlaces viejos a
 * ?page=configuracion de este panel se redirigen allí.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/AdminController.php";

$pagina = $_GET["page"] ?? "inicio";

// Compatibilidad con enlaces antiguos
if ($pagina === "configuracion") {
    $tab = $_GET["tab"] ?? "perfil";
    header("Location: " . ($tab === "perfil" ? "index.php?page=perfil" : "../superadmin/index.php?page=configuracion&tab=" . urlencode($tab)));
    exit;
}

$controlador = new AdminController();

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "crear") {
    $controlador->crear();
    exit;
}

$paginas = ["inicio", "usuarios", "cursos", "idiomas", "lecciones", "actividades", "solicitudes", "perfil"];

$controlador->{in_array($pagina, $paginas, true) ? $pagina : "inicio"}();
