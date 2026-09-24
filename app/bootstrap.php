<?php

/**
 * Bootstrap de la aplicacion.
 *
 * Carga lo que cualquier controlador necesita antes de atender un
 * request: sesion/proteccion de rutas (protegerRol, protegerSesion)
 * y la conexion a la base de datos (clase Conexion). No cambia nada
 * de como funcionan esos archivos, solo los deja disponibles desde
 * un solo lugar para no repetir requires en cada Controller.
 */

require_once __DIR__ . "/../src/auth/proteger.php";
require_once __DIR__ . "/../src/bd/conexion.php";
require_once __DIR__ . "/../src/auth/csrf.php";

/**
 * Loguea el error real de la BD en el servidor (nunca al navegador)
 * y corta la ejecución con un mensaje genérico para el usuario.
 */
function manejarErrorBD(Throwable $e, string $mensaje): void
{
    error_log("[BiGlobal] " . $e->getMessage());
    die($mensaje);
}