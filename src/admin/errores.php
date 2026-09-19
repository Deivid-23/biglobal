<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

/*
 * "Reportes y errores" se consolidó dentro de la sección de
 * Reportes para no duplicar la misma información en dos pantallas.
 */

header("Location: reportes.php");
exit;
