<?php

require_once __DIR__ . "/../../Models/ConfiguracionModel.php";

/**
 * ConfiguracionController (admin)
 *
 * index() -> ver/guardar ajustes generales de la plataforma
 * (antes configuracion.php)
 */
class ConfiguracionController
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

        $guardado = false;

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $nombreSitio = trim($_POST["nombre_sitio"] ?? "");
            $correoContacto = trim($_POST["correo_contacto"] ?? "");
            $modoMantenimiento = isset($_POST["modo_mantenimiento"]) ? "1" : "0";

            if ($nombreSitio !== "" && filter_var($correoContacto, FILTER_VALIDATE_EMAIL)) {
                ConfiguracionModel::guardar($conexion, $nombreSitio, $correoContacto, $modoMantenimiento);
                $guardado = true;
            }
        }

        $config = ConfiguracionModel::obtenerTodo($conexion);

        $nombreSitio = $config["nombre_sitio"] ?? "BiGlobal";
        $correoContacto = $config["correo_contacto"] ?? "";
        $modoMantenimiento = ($config["modo_mantenimiento"] ?? "0") === "1";

        require __DIR__ . "/../../Views/admin/configuracion/index.php";
    }
}