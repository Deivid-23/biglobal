<?php
/* Cancela el registro con Google a medio camino (antes de elegir rol). */
require_once __DIR__ . "/sesion.php";

unset($_SESSION["google_nuevo"]);
header("Location: login.html");
exit;
