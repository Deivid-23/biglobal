<?php

require_once __DIR__ . "/../../Models/DashboardModel.php";

/**
 * DashboardController (admin)
 *
 * index() -> panel principal del admin (antes admin/index.php)
 */
class DashboardController
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

        $stats = DashboardModel::obtenerEstadisticas($conexion);

        $totalUsuarios = $stats["totalUsuarios"];
        $totalCursos = $stats["totalCursos"];
        $totalInstructores = $stats["totalInstructores"];
        $usuariosActivosHoy = $stats["usuariosActivosHoy"];

        $nombreAdmin = $_SESSION["nombre"] ?? "Administrador";
        $apellidoAdmin = $_SESSION["apellido"] ?? "";

        $nombreCompletoAdmin = trim($nombreAdmin . " " . $apellidoAdmin);

        if (empty($nombreCompletoAdmin)) {
            $nombreCompletoAdmin = "Administrador";
        }

        require __DIR__ . "/../../Views/admin/dashboard/index.php";
    }
}