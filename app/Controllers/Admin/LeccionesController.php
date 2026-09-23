<?php

require_once __DIR__ . "/../../Models/LeccionModel.php";

/**
 * LeccionesController (admin)
 *
 * index()    -> lista de lecciones, filtrable por curso (antes lecciones.php)
 * editar()   -> ver/actualizar una lección (antes editar_leccion.php)
 * crear()    -> crear lección nueva (antes guardar_leccion.php)
 * eliminar() -> borrar lección (antes eliminar_leccion.php)
 */
class LeccionesController
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

        $cursos = LeccionModel::obtenerCursos($conexion);

        $cursoSeleccionado = $_GET["curso"] ?? "";

        if ($cursoSeleccionado !== "" && is_numeric($cursoSeleccionado)) {
            $lecciones = LeccionModel::obtenerPorCurso($conexion, $cursoSeleccionado);
        } else {
            $lecciones = LeccionModel::obtenerTodas($conexion);
        }

        $totalLecciones = count($lecciones);
        $publicadas = count(array_filter($lecciones, fn($l) => $l["estado"] === "Publicado"));
        $borradores = count(array_filter($lecciones, fn($l) => $l["estado"] === "Borrador"));
        $inactivas = count(array_filter($lecciones, fn($l) => $l["estado"] === "Inactivo"));

        $totalActividades = 0;
        foreach ($lecciones as $leccion) {
            $totalActividades += (int) $leccion["total_actividades"];
        }

        require __DIR__ . "/../../Views/admin/lecciones/index.php";
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

        $leccion = LeccionModel::obtenerPorId($conexion, $id);

        if (!$leccion) {
            die("La lección no existe.");
        }

        $cursos = LeccionModel::obtenerCursos($conexion);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $cursoId = $_POST["curso_id"] ?? "";
            $titulo = trim($_POST["titulo"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            $orden = $_POST["orden"] ?? 1;
            $estado = $_POST["estado"] ?? "Borrador";

            if (empty($cursoId) || empty($titulo) || empty($orden)) {
                die("El curso, el título y el orden son obligatorios.");
            }

            if (!is_numeric($cursoId) || !is_numeric($orden)) {
                die("El curso o el orden no son válidos.");
            }

            $estadosPermitidos = ["Borrador", "Publicado", "Inactivo"];

            if (!in_array($estado, $estadosPermitidos, true)) {
                die("El estado seleccionado no es válido.");
            }

            try {
                if (!LeccionModel::cursoExiste($conexion, $cursoId)) {
                    die("El curso seleccionado no existe.");
                }

                LeccionModel::actualizar($conexion, $id, [
                    "cursoId" => $cursoId,
                    "titulo" => $titulo,
                    "descripcion" => $descripcion,
                    "orden" => $orden,
                    "estado" => $estado,
                ]);

                header("Location: lecciones.php?curso=" . urlencode($cursoId) . "&editado=1");
                exit;

            } catch (PDOException $e) {
                die("Error al actualizar la lección: " . $e->getMessage());
            }
        }

        require __DIR__ . "/../../Views/admin/lecciones/editar.php";
    }

    public function crear(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: lecciones.php");
            exit;
        }

        $conexion = $this->conexion;

        $cursoId = $_POST["curso_id"] ?? "";
        $titulo = trim($_POST["titulo"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $orden = $_POST["orden"] ?? 1;
        $estado = $_POST["estado"] ?? "Borrador";

        if (empty($cursoId) || empty($titulo) || empty($orden)) {
            die("El curso, el título y el orden son obligatorios.");
        }

        if (!is_numeric($cursoId) || !is_numeric($orden)) {
            die("El curso o el orden no son válidos.");
        }

        $estadosPermitidos = ["Borrador", "Publicado", "Inactivo"];

        if (!in_array($estado, $estadosPermitidos, true)) {
            die("El estado seleccionado no es válido.");
        }

        try {
            if (!LeccionModel::cursoExiste($conexion, $cursoId)) {
                die("El curso seleccionado no existe.");
            }

            LeccionModel::crear($conexion, [
                "cursoId" => $cursoId,
                "titulo" => $titulo,
                "descripcion" => $descripcion,
                "orden" => $orden,
                "estado" => $estado,
            ]);

            header("Location: lecciones.php?curso=" . urlencode($cursoId) . "&creado=1");
            exit;

        } catch (PDOException $e) {
            die("Error al crear la lección: " . $e->getMessage());
        }
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

        try {
            LeccionModel::eliminar($conexion, (int) $id);

            header("Location: lecciones.php?eliminado=1");
            exit;

        } catch (PDOException $e) {
            die("Error al eliminar la lección: " . $e->getMessage());
        }
    }
}