<?php

require_once __DIR__ . "/../../Models/CertificadoModel.php";

/**
 * CertificadosController (admin)
 *
 * index() -> historial de certificados emitidos (antes certificados.php)
 */
class CertificadosController
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

        $certificados = CertificadoModel::obtenerTodos($conexion);

        $totalCertificados = count($certificados);

        $esteMes = 0;
        foreach ($certificados as $c) {
            if (date("Y-m", strtotime($c["fecha_emision"])) === date("Y-m")) {
                $esteMes++;
            }
        }

        require __DIR__ . "/../../Views/admin/certificados/index.php";
    }
}