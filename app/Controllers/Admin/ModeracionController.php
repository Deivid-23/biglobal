<?php

require_once __DIR__ . "/../../Models/ModeracionModel.php";
require_once __DIR__ . "/../../Models/ReporteModel.php";

/**
 * ModeracionController (admin)
 *
 * index() -> cola de reportes pendientes, con aprobar/descartar
 * via POST (antes moderacion.php)
 */
class ModeracionController
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

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"], $_POST["id"])) {

            verificarCsrfFormulario($_POST["csrf_token"] ?? "");

            $id = $_POST["id"];
            $accion = $_POST["accion"];

            $nuevoEstado = null;

            if ($accion === "aprobar") {
                $nuevoEstado = "Revisado";
            } elseif ($accion === "descartar") {
                $nuevoEstado = "Descartado";
            }

            if ($nuevoEstado && is_numeric($id)) {
                ReporteModel::actualizarEstado($conexion, $id, $nuevoEstado);
            }

            header("Location: moderacion.php");
            exit;
        }

        $pendientes = ModeracionModel::obtenerPendientes($conexion);

        require __DIR__ . "/../../Views/admin/moderacion/index.php";
    }
}
