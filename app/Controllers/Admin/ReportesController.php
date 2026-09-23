<?php

require_once __DIR__ . "/../../Models/ReporteModel.php";

/**
 * ReportesController (admin)
 *
 * index() -> historial de reportes, con accion rapida de cambio de
 * estado via POST (antes reportes.php)
 */
class ReportesController
{
    private PDO $conexion;

    public function __construct()
    {
        protegerRol("administrador");
        $this->conexion = Conexion::conectar();
    }

    public function index(): void
    {
        $conexion = $this->conexion;

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion_estado"])) {

            $id = $_POST["id"] ?? null;
            $nuevoEstado = $_POST["accion_estado"];

            $estadosPermitidos = ["Pendiente", "Revisado", "Descartado"];

            if ($id && is_numeric($id) && in_array($nuevoEstado, $estadosPermitidos, true)) {
                ReporteModel::actualizarEstado($conexion, $id, $nuevoEstado);
            }

            header("Location: reportes.php");
            exit;
        }

        $reportes = ReporteModel::obtenerTodos($conexion);

        $total = count($reportes);
        $pendientes = count(array_filter($reportes, fn($r) => $r["estado"] === "Pendiente"));
        $revisados = count(array_filter($reportes, fn($r) => $r["estado"] === "Revisado"));
        $descartados = count(array_filter($reportes, fn($r) => $r["estado"] === "Descartado"));

        require __DIR__ . "/../../Views/admin/reportes/index.php";
    }
}