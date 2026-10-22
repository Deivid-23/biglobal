<?php
/**
 * Tarea programada diaria: borra las cuentas que pidieron eliminarse
 * hace 30 días (anonimiza sus datos personales).
 *
 * La llama GitHub Actions todos los días (.github/workflows/renovaciones.yml):
 *   POST /tareas/eliminaciones.php
 *   Cabecera: X-Tarea-Token: <valor de TAREAS_TOKEN>
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/CuentaEliminacionModel.php";

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

try {
    $responder(200, ["ok" => true, "resultado" => ["cuentas_borradas" => CuentaEliminacionModel::borrarVencidas($conexion)]]);
} catch (Throwable $e) {
    error_log("[BiGlobal][tareas][eliminaciones] " . $e->getMessage());
    $responder(500, ["ok" => false]);
}
