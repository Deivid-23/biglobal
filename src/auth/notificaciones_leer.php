<?php
/**
 * Marca notificaciones de la campana como leídas.
 * Solo POST, con sesión (cualquier rol) y token CSRF.
 * Cada persona solo puede marcar las suyas (el WHERE usa su usuario_id).
 *
 *   id=5          → marca solo esa
 *   (sin id)      → marca todas
 *   volver=/ruta  → después redirige ahí (formulario sin JavaScript)
 *   sin volver    → responde JSON (lo usa js/panel.js)
 */
require_once __DIR__ . "/../../app/bootstrap.php";

$responder = function (int $codigo, array $datos): void {
    http_response_code($codigo);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($datos);
    exit;
};

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION["usuario_id"])) {
    $responder(401, ["ok" => false]);
}


if (!csrfValido($_POST["csrf_token"] ?? "")) {
    $responder(419, ["ok" => false, "mensaje" => "Tu sesión expiró. Recarga la página."]);
}

$id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);

try {
    Notificador::marcarLeidas((int) $_SESSION["usuario_id"], $id === false ? null : $id);
} catch (PDOException $e) {
    error_log("[BiGlobal][notificaciones_leer] " . $e->getMessage());
    $responder(500, ["ok" => false]);
}

// Solo rutas internas: empieza con "/" pero no con "//" (evita redirigir a otro sitio)
$volver = (string) ($_POST["volver"] ?? "");
if (preg_match('#^/(?![/\\\\])[^\s\\\\]*$#', $volver)) {
    header("Location: " . $volver);
    exit;
}

$responder(200, ["ok" => true, "noLeidas" => Notificador::noLeidas((int) $_SESSION["usuario_id"])]);
