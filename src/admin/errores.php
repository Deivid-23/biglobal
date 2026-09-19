<?php

require_once "../auth/proteger.php";

protegerRol("admin");

/*
 * "Reportes y errores" se consolidó dentro de la sección de
 * Reportes para no duplicar la misma información en dos pantallas.
 */

header("Location: reportes.php");
exit;
