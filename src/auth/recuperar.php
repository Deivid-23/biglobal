<?php
/* "¿Olvidaste tu contraseña?": pide el correo y envía el enlace. */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/RecuperacionController.php";

(new RecuperacionController())->solicitar();
