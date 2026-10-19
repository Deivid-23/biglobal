<?php
/* Guarda los datos o la contraseña de la propia cuenta (admin, super admin, docente). Solo POST. */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/CuentaController.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.html");
    exit;
}

$cuenta = new CuentaController();

($_POST["accion"] ?? "") === "password" ? $cuenta->guardarPassword() : $cuenta->guardarPerfil();
