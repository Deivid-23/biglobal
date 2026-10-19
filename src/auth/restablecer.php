<?php
/* Página del enlace del correo: permite crear la nueva contraseña. */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/RecuperacionController.php";

(new RecuperacionController())->restablecer();
