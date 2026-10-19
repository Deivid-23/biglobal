<?php

require_once __DIR__ . "/sesion.php";

/* =========================================================
   VERIFICAR SI HAY SESIÓN
   Si no hay usuario logueado, lo manda al login.
========================================================= */
function protegerSesion(): void
{
    if (!isset($_SESSION["usuario_id"])) {
        header("Location: ../auth/login.html");
        exit;
    }
}

/* =========================================================
   VERIFICAR ROL
   Los roles reales de la BD son: admin, docente, estudiante.
   Se aceptan los nombres antiguos ("administrador", "instructor")
   para que ningún archivo viejo quede bloqueado por error.
========================================================= */
function normalizarRol(string $rol): string
{
    $rol = strtolower(trim($rol));

    $alias = [
        "administrador" => "admin",
        "instructor"    => "docente",
        "usuario"       => "estudiante",
    ];

    return $alias[$rol] ?? $rol;
}

function protegerRol(string $rolPermitido): void
{
    protegerSesion();

    if (!isset($_SESSION["rol"])) {
        session_destroy();
        header("Location: ../auth/login.html");
        exit;
    }

    if (normalizarRol($_SESSION["rol"]) !== normalizarRol($rolPermitido)) {
        http_response_code(403);
        require __DIR__ . "/acceso_denegado.php";
        exit;
    }
}
