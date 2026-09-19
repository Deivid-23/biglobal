<?php

require_once "csrf.php";

header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    "csrf_token" => generarCsrfToken()
]);
