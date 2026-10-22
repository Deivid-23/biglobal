<?php
/**
 * Tarea programada diaria: renovaciones del plan Premium.
 *
 * La llama GitHub Actions todos los días (.github/workflows/renovaciones.yml):
 *   POST /tareas/renovaciones.php
 *   Cabecera: X-Tarea-Token: <valor de TAREAS_TOKEN>
 *
 * 1. Cobra con la tarjeta tokenizada las suscripciones que llegaron a su
 *    fecha de corte (o a un reintento). El resultado llega por el webhook.
 * 2. Marca como vencidas las que terminaron sin renovación y avisa.
 * 3. Recuerda renovar a quien pagó con PSE, Nequi o Bancolombia.
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/PagoModel.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$responder = function (int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
};

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    $responder(405, ["ok" => false]);
}

$tokenServidor = (string) env("TAREAS_TOKEN", "");
if (strlen($tokenServidor) < 32) {
    error_log("[BiGlobal][tareas] TAREAS_TOKEN no está configurado o es muy corto.");
    $responder(503, ["ok" => false, "mensaje" => "Tarea no configurada."]);
}
if (!hash_equals($tokenServidor, (string) ($_SERVER["HTTP_X_TAREA_TOKEN"] ?? ""))) {
    $responder(403, ["ok" => false]);
}

$conexion = Conexion::conectar();
if (!PagoModel::disponible($conexion)) {
    $responder(200, ["ok" => true, "resultado" => "pagos no instalados"]);
}

try {
    $responder(200, ["ok" => true, "resultado" => PagoModel::tareaDiaria($conexion)]);
} catch (Throwable $e) {
    error_log("[BiGlobal][tareas][renovaciones] " . $e->getMessage());
    $responder(500, ["ok" => false]);
}
