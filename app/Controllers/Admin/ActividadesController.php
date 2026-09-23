<?php

require_once __DIR__ . "/../../Models/ActividadModel.php";

/**
 * ActividadesController (admin)
 *
 * index()    -> actividades de una leccion (antes actividades.php)
 * crear()    -> ver formulario/crear actividad (antes guardar_actividad.php, GET+POST)
 * editar()   -> ver/actualizar actividad (antes editar_actividad.php)
 * eliminar() -> borrar actividad (antes eliminar_actividad.php)
 */
class ActividadesController
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

        $leccionId = $_GET["leccion"] ?? null;

        if (!$leccionId || !is_numeric($leccionId)) {
            header("Location: lecciones.php");
            exit;
        }

        $leccion = ActividadModel::obtenerLeccionInfo($conexion, $leccionId);

        if (!$leccion) {
            die("La lección no existe.");
        }

        $actividades = ActividadModel::obtenerPorLeccion($conexion, $leccionId);

        require __DIR__ . "/../../Views/admin/actividades/index.php";
    }

    public function crear(): void
    {
        $conexion = $this->conexion;

        $leccionId = $_GET["leccion"] ?? $_POST["leccion_id"] ?? null;

        if (!$leccionId || !is_numeric($leccionId)) {
            header("Location: lecciones.php");
            exit;
        }

        $leccion = ActividadModel::obtenerLeccionResumen($conexion, $leccionId);

        if (!$leccion) {
            die("La lección no existe.");
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $titulo = trim($_POST["titulo"] ?? "");
            $tipo = trim($_POST["tipo"] ?? "");
            $contenido = trim($_POST["contenido"] ?? "");
            $orden = $_POST["orden"] ?? 1;
            $estado = $_POST["estado"] ?? "Borrador";

            $estadosPermitidos = ["Borrador", "Publicado", "Inactivo"];

            if (empty($titulo) || empty($tipo) || !is_numeric($orden)) {
                die("El título, el tipo y el orden son obligatorios.");
            }

            if (!in_array($estado, $estadosPermitidos, true)) {
                die("El estado seleccionado no es válido.");
            }

            try {
                ActividadModel::crear($conexion, [
                    "leccionId" => $leccionId,
                    "titulo" => $titulo,
                    "tipo" => $tipo,
                    "contenido" => $contenido,
                    "orden" => $orden,
                    "estado" => $estado,
                ]);

                header("Location: actividades.php?leccion=" . urlencode($leccionId) . "&creado=1");
                exit;

            } catch (PDOException $e) {
                die("Error al crear la actividad: " . $e->getMessage());
            }
        }

        require __DIR__ . "/../../Views/admin/actividades/crear.php";
    }

    public function editar(): void
    {
        $conexion = $this->conexion;

        $id = $_GET["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: lecciones.php");
            exit;
        }

        $id = (int) $id;

        $actividad = ActividadModel::obtenerPorId($conexion, $id);

        if (!$actividad) {
            die("La actividad no existe.");
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $titulo = trim($_POST["titulo"] ?? "");
            $tipo = trim($_POST["tipo"] ?? "");
            $contenido = trim($_POST["contenido"] ?? "");
            $orden = $_POST["orden"] ?? 1;
            $estado = $_POST["estado"] ?? "Borrador";

            $estadosPermitidos = ["Borrador", "Publicado", "Inactivo"];

            if (empty($titulo) || empty($tipo) || !is_numeric($orden)) {
                die("El título, el tipo y el orden son obligatorios.");
            }

            if (!in_array($estado, $estadosPermitidos, true)) {
                die("El estado seleccionado no es válido.");
            }

            try {
                ActividadModel::actualizar($conexion, $id, [
                    "titulo" => $titulo,
                    "tipo" => $tipo,
                    "contenido" => $contenido,
                    "orden" => $orden,
                    "estado" => $estado,
                ]);

                header("Location: actividades.php?leccion=" . urlencode($actividad["leccion_id"]) . "&editado=1");
                exit;

            } catch (PDOException $e) {
                die("Error al actualizar la actividad: " . $e->getMessage());
            }
        }

        require __DIR__ . "/../../Views/admin/actividades/editar.php";
    }

    public function eliminar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: lecciones.php");
            exit;
        }

        $conexion = $this->conexion;

        $id = $_POST["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: lecciones.php");
            exit;
        }

        $actividad = ActividadModel::obtenerLeccionIdDeActividad($conexion, (int) $id);

        if (!$actividad) {
            header("Location: lecciones.php");
            exit;
        }

        try {
            ActividadModel::eliminar($conexion, (int) $id);

            header("Location: actividades.php?leccion=" . urlencode($actividad["leccion_id"]) . "&eliminado=1");
            exit;

        } catch (PDOException $e) {
            die("Error al eliminar la actividad: " . $e->getMessage());
        }
    }
}