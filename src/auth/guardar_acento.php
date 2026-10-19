<?php
/* Guarda el color de acento personal (lo llama la tarjeta "Personalización" de Mi perfil). Solo POST con sesión. */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Controllers/CuentaController.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION["usuario_id"])) {
    http_response_code(401);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["ok" => false]);
    exit;
}

(new CuentaController())->guardarAcento();
