<?php

require_once __DIR__ . "/sesion.php";

/* =========================================================
   ROLES DE BIGLOBAL
   - superadmin : todo lo del admin + seguridad, correo, configuración
                  global, gestión de administradores y borrado definitivo.
   - admin      : contenido, usuarios estudiante/docente, solicitudes.
   - docente    : su panel de cursos (src/profesor).
   - estudiante : su panel de aprendizaje.
========================================================= */

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
   NOMBRE DEL ROL
   Se aceptan nombres antiguos ("administrador", "instructor"...)
   para que ningún archivo viejo quede bloqueado por error.
========================================================= */
function normalizarRol(string $rol): string
{
    $rol = strtolower(trim($rol));

    $alias = [
        "administrador"       => "admin",
        "instructor"          => "docente",
        "usuario"             => "estudiante",
        "super administrador" => "superadmin",
        "super_admin"         => "superadmin",
        "super-admin"         => "superadmin",
    ];

    return $alias[$rol] ?? $rol;
}

/** Rol de la sesión actual, ya normalizado ("" si no hay sesión). */
function rolActual(): string
{
    return isset($_SESSION["rol"]) ? normalizarRol($_SESSION["rol"]) : "";
}

function esSuperAdmin(): bool
{
    return rolActual() === "superadmin";
}

/**
 * Página a la que va cada rol al iniciar sesión.
 * Las rutas son relativas a la carpeta src/auth/.
 */
function panelDeRol(string $rol): string
{
    switch (normalizarRol($rol)) {
        case "superadmin": return "../admin/index.php"; // provisional, hasta crear su panel
        case "admin":      return "../admin/index.php";
        case "docente":    return "../profesor/index.php";
        case "estudiante": return "../estudiante/index.php";
        default:           return "../index.html";
    }
}

/* =========================================================
   VERIFICAR ROL
   protegerRol("estudiante")                -> solo estudiantes
   protegerRol(["admin", "superadmin"])      -> cualquiera de la lista

   El super administrador puede entrar a TODO lo que es del admin:
   protegerRol("admin") también lo deja pasar.
========================================================= */
function protegerRol($rolesPermitidos): void
{
    protegerSesion();

    if (!isset($_SESSION["rol"])) {
        session_destroy();
        header("Location: ../auth/login.html");
        exit;
    }

    $permitidos = array_map("normalizarRol", (array) $rolesPermitidos);

    if (in_array("admin", $permitidos, true)) {
        $permitidos[] = "superadmin";
    }

    if (!in_array(rolActual(), $permitidos, true)) {
        http_response_code(403);
        require __DIR__ . "/acceso_denegado.php";
        exit;
    }
}

/**
 * Para acciones que solo puede hacer el super administrador.
 * Corta con 403 aunque la persona sea admin y llegue por URL directa.
 */
function protegerSuperAdmin(): void
{
    protegerRol("superadmin");
}
