<?php

require_once __DIR__ . "/../../Models/IdiomaModel.php";

/**
 * IdiomasController (admin)
 *
 * index()    -> lista de idiomas (antes idiomas.php)
 * editar()   -> ver/actualizar un idioma (antes editar_idioma.php)
 * crear()    -> crear idioma nuevo (antes guardar_idioma.php)
 * eliminar() -> borrar idioma (antes eliminar_idioma.php)
 */
class IdiomasController
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

        $idiomas = IdiomaModel::obtenerTodos($conexion);

        $totalIdiomas = count($idiomas);
        $idiomasActivos = count(array_filter($idiomas, fn($i) => $i["estado"] === "Activo"));
        $idiomasInactivos = count(array_filter($idiomas, fn($i) => $i["estado"] !== "Activo"));

        require __DIR__ . "/../../Views/admin/idiomas/index.php";
    }

    public function editar(): void
    {
        $conexion = $this->conexion;

        $id = $_GET["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: idiomas.php");
            exit;
        }

        $id = (int) $id;

        $idioma = IdiomaModel::obtenerPorId($conexion, $id);

        if (!$idioma) {
            die("Idioma no encontrado.");
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            verificarCsrfFormulario($_POST["csrf_token"] ?? "");

            $nombre = trim($_POST["nombre"] ?? "");
            $codigo = strtoupper(trim($_POST["codigo"] ?? ""));
            $estado = $_POST["estado"] ?? "Activo";

            if (empty($nombre) || empty($codigo)) {
                die("El nombre y el código son obligatorios.");
            }

            if (!in_array($estado, ["Activo", "Inactivo"], true)) {
                die("El estado seleccionado no es válido.");
            }

            try {
                if (IdiomaModel::existeDuplicadoExcluyendo($conexion, $nombre, $codigo, $id)) {
                    die("Ya existe otro idioma con ese nombre o código.");
                }

                IdiomaModel::actualizar($conexion, $id, [
                    "nombre" => $nombre,
                    "codigo" => $codigo,
                    "estado" => $estado,
                ]);

                header("Location: idiomas.php?editado=1");
                exit;
            } catch (PDOException $e) {
                manejarErrorBD($e, "Error al actualizar el idioma. Intenta de nuevo.");
            }
        }

        require __DIR__ . "/../../Views/admin/idiomas/editar.php";
    }

    public function crear(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: idiomas.php");
            exit;
        }

        $conexion = $this->conexion;

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $nombre = trim($_POST["nombre"] ?? "");
        $codigo = strtoupper(trim($_POST["codigo"] ?? ""));
        $estado = $_POST["estado"] ?? "Activo";

        if (empty($nombre) || empty($codigo)) {
            die("El nombre y el código son obligatorios.");
        }

        if (!in_array($estado, ["Activo", "Inactivo"], true)) {
            die("El estado seleccionado no es válido.");
        }

        try {
            if (IdiomaModel::existeDuplicado($conexion, $nombre, $codigo)) {
                die("Ya existe un idioma con ese nombre o código.");
            }

            IdiomaModel::crear($conexion, [
                "nombre" => $nombre,
                "codigo" => $codigo,
                "estado" => $estado,
            ]);

            header("Location: idiomas.php?creado=1");
            exit;
        } catch (PDOException $e) {
            manejarErrorBD($e, "Error al crear el idioma. Intenta de nuevo.");
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: idiomas.php");
            exit;
        }

        $conexion = $this->conexion;

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $id = $_POST["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: idiomas.php");
            exit;
        }

        try {
            IdiomaModel::eliminar($conexion, (int) $id);

            header("Location: idiomas.php?eliminado=1");
            exit;
        } catch (PDOException $e) {
            manejarErrorBD($e, "Error al eliminar el idioma. Intenta de nuevo.");
        }
    }
}
