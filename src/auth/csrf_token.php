<?php
/* Entrega el token CSRF (y las reglas de contraseña) al login en JSON. */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/AuthController.php";

(new AuthController())->csrfToken();
