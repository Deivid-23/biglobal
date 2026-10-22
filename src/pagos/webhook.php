<?php
/**
 * Webhook de Wompi: aquí llega el resultado de cada pago.
 * URL para registrar en Wompi (comercios.wompi.co > Desarrolladores > Eventos):
 *   https://biglobal.onrender.com/pagos/webhook.php
 *
 * Es el ÚNICO lugar que activa o renueva el Premium:
 *   1. Verifica la firma del evento con WOMPI_LLAVE_EVENTOS.
 *   2. Guarda el evento una sola vez (si Wompi lo reenvía, no se repite nada).
 *   3. PagoModel revisa monto y moneda y aplica el resultado.
 *
 * Siempre responde rápido; Wompi reintenta si no recibe un 200.
 */
require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/PagoModel.php";

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store");

$responder = function (int $codigo, string $mensaje): void {
    http_response_code($codigo);
    echo json_encode(["ok" => $codigo === 200, "mensaje" => $mensaje]);
    exit;
};

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    $responder(405, "Solo POST");
}

$cuerpo = (string) file_get_contents("php://input", false, null, 0, 65536);
$evento = json_decode($cuerpo, true);
if (!is_array($evento)) {
    $responder(400, "JSON inválido");
}

$conexion = Conexion::conectar();
if (!PagoModel::disponible($conexion) || !Wompi::configurado()) {
    error_log("[BiGlobal][webhook] Llegó un evento pero los pagos no están configurados.");
    $responder(503, "Pagos no configurados");
}

$valido = Wompi::eventoValido($evento);

try {
    $esNuevo = PagoModel::registrarEvento($conexion, $evento, $cuerpo, $valido);
} catch (Throwable $e) {
    error_log("[BiGlobal][webhook] No se pudo guardar el evento: " . $e->getMessage());
    $responder(500, "Error");
}

if (!$valido) {
    error_log("[BiGlobal][webhook] Firma inválida: evento ignorado.");
    $responder(401, "Firma inválida");
}
if (!$esNuevo) {
    $responder(200, "Evento repetido: ya se había procesado");
}

// Un evento de pruebas no puede tocar producción (y al revés)
$entornoEvento = (string) ($evento["environment"] ?? "");
if ($entornoEvento !== "" && ($entornoEvento === "prod") !== (Wompi::ambiente() === "produccion")) {
    PagoModel::resultadoEvento($conexion, $evento, "ignorado: ambiente distinto ({$entornoEvento})");
    $responder(200, "Ambiente distinto");
}

if (($evento["event"] ?? "") !== "transaction.updated" || !is_array($evento["data"]["transaction"] ?? null)) {
    PagoModel::resultadoEvento($conexion, $evento, "ignorado: tipo de evento no usado");
    $responder(200, "Evento no usado");
}

try {
    $resultado = PagoModel::procesarTransaccion($conexion, $evento["data"]["transaction"]);
    PagoModel::resultadoEvento($conexion, $evento, $resultado);
    $responder(200, $resultado);
} catch (Throwable $e) {
    error_log("[BiGlobal][webhook] " . $e->getMessage());
    $responder(500, "Error");   // Wompi lo reintentará
}
