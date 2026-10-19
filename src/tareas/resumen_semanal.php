<?php
/**
 * Tarea programada: resumen semanal para el equipo.
 *
 * La llama GitHub Actions cada lunes (.github/workflows/resumen_semanal.yml):
 *   POST /tareas/resumen_semanal.php
 *   Cabecera: X-Tarea-Token: <valor de TAREAS_TOKEN>
 *
 * - TAREAS_TOKEN es una variable de entorno de Render (no va en el código).
 *   Usa un valor largo y aleatorio (mínimo 32 caracteres).
 * - Sin token correcto responde 403 y no hace nada.
 * - Si el resumen ya salió esta semana, no lo repite.
 */
require_once __DIR__ . "/../../app/bootstrap.php";

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

$tokenRecibido = (string) ($_SERVER["HTTP_X_TAREA_TOKEN"] ?? "");
if (!hash_equals($tokenServidor, $tokenRecibido)) {
    $responder(403, ["ok" => false]);
}

try {
    $resultado = Notificador::resumenSemanal(Conexion::conectar());
    $responder(200, ["ok" => true, "resultado" => $resultado]);
} catch (Throwable $e) {
    error_log("[BiGlobal][tareas][resumen] " . $e->getMessage());
    $responder(500, ["ok" => false]);
}
