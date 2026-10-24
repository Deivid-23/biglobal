<?php
/**
 * Tarea programada: recordatorio de racha.
 *
 * La llama GitHub Actions cada hora (.github/workflows/recordatorio_racha.yml):
 *   POST /tareas/recordatorio_racha.php
 *   Cabecera: X-Tarea-Token: <valor de TAREAS_TOKEN>
 *
 * Avisa (campana + correo) a quien tiene la racha viva y hoy no ha
 * estudiado, entre las 7 y las 10 p. m. de su zona horaria, una vez al día.
 * Las reglas están en GamificacionModel::recordatoriosRacha.
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/GamificacionModel.php";

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

set_time_limit(110);
try {
    $responder(200, ["ok" => true, "resultado" => GamificacionModel::recordatoriosRacha(Conexion::conectar())]);
} catch (Throwable $e) {
    error_log("[BiGlobal][tareas][recordatorio_racha] " . $e->getMessage());
    $responder(500, ["ok" => false]);
}
