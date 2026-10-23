<?php

/**
 * Panel del estudiante: punto de entrada.
 *
 * Este archivo solo decide qué página mostrar según ?page=...
 * Toda la lógica está en app/Controllers/EstudianteController.php
 * y el HTML en app/Views/estudiante/.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/EstudianteController.php";

$rutas = [
    "inicio"       => "inicio",
    "cursos"       => "cursos",
    "mis-cursos"   => "misCursos",
    "curso"        => "curso",
    "leccion"      => "leccion",
    "certificados" => "certificados",
    "perfil"       => "perfil",
    "plan"         => "plan",
    "clases"       => "clases",
    "avisos"       => "avisos",
    "grupos"       => "grupos",
    "grupo"        => "grupo",
    "ruta"         => "ruta",
    "ruta-leccion" => "rutaLeccion",
];

$pagina = $_GET["page"] ?? "inicio";

// "Mis cursos" antiguo apuntaba a ?page=curso sin id
if ($pagina === "curso" && empty($_GET["id"])) {
    $pagina = "mis-cursos";
}

$accion = $rutas[$pagina] ?? "inicio";

(new EstudianteController())->$accion();
