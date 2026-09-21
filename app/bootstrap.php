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
