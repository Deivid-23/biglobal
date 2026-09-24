<?php

require_once __DIR__ . "/../../Models/CursoModel.php";

/**
 * CursosController (admin)
 *
 * index()    -> catalogo de cursos (antes cursos.php)
 * editar()   -> ver/actualizar un curso (antes editar_curso.php)
 * crear()    -> crear curso nuevo (antes guardar_curso.php)
 * eliminar() -> borrar curso (antes eliminar_curso.php)
 */
class CursosController
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

        $cursos = CursoModel::obtenerTodos($conexion);
        $instructores = CursoModel::obtenerInstructoresActivos($conexion);

        $totalCursos = count($cursos);
        $cursosPublicados = count(array_filter($cursos, fn($c) => $c["estado"] === "Publicado"));
        $cursosBorrador = count(array_filter($cursos, fn($c) => $c["estado"] === "Borrador"));
        $cursosInactivos = count(array_filter($cursos, fn($c) => $c["estado"] === "Inactivo"));

        require __DIR__ . "/../../Views/admin/cursos/index.php";
    }

    public function editar(): void
    {
        $conexion = $this->conexion;

        $id = $_GET["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: cursos.php");
            exit;
        }

        $id = (int) $id;

        $curso = CursoModel::obtenerPorId($conexion, $id);

        if (!$curso) {
            die("Curso no encontrado.");
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            verificarCsrfFormulario($_POST["csrf_token"] ?? "");

            $nombre = trim($_POST["nombre"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            $nivel = $_POST["nivel"] ?? "";
            $estado = $_POST["estado"] ?? "Borrador";
            $instructorId = $_POST["instructor_id"] ?? "";

            if (empty($nombre) || empty($nivel)) {
                die("El nombre y el nivel son obligatorios.");
            }

            $instructorId = $instructorId === "" ? null : $instructorId;

            try {
                CursoModel::actualizar($conexion, $id, [
                    "nombre" => $nombre,
                    "descripcion" => $descripcion,
                    "nivel" => $nivel,
                    "estado" => $estado,
                    "instructorId" => $instructorId,
                ]);

                header("Location: cursos.php?editado=1");
                exit;
            } catch (PDOException $e) {
                die("Error al actualizar el curso: " . $e->getMessage());
            }
        }

        $instructores = CursoModel::obtenerInstructoresActivos($conexion);

        require __DIR__ . "/../../Views/admin/cursos/editar.php";
    }

    public function crear(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: cursos.php");
            exit;
        }

        $conexion = $this->conexion;

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $nivel = trim($_POST["nivel"] ?? "");
        $estado = $_POST["estado"] ?? "Borrador";
        $instructorId = $_POST["instructor_id"] ?? "";

        if (empty($nombre) || empty($nivel)) {
            die("El nombre y el nivel del curso son obligatorios.");
        }

        $nivelesPermitidos = ["Básico", "Intermedio", "Avanzado"];
        $estadosPermitidos = ["Borrador", "Publicado", "Inactivo"];

        if (!in_array($nivel, $nivelesPermitidos, true)) {
            die("El nivel seleccionado no es válido.");
        }

        if (!in_array($estado, $estadosPermitidos, true)) {
            die("El estado seleccionado no es válido.");
        }

        try {
            if ($instructorId !== "") {

                if (!is_numeric($instructorId)) {
                    die("El instructor seleccionado no es válido.");
                }

                if (!CursoModel::instructorValido($conexion, (int) $instructorId)) {
                    die("El instructor seleccionado no existe o no está activo.");
                }
            } else {
                $instructorId = null;
            }

            CursoModel::crear($conexion, [
                "nombre" => $nombre,
                "descripcion" => $descripcion,
                "nivel" => $nivel,
                "estado" => $estado,
                "instructorId" => $instructorId,
            ]);

            header("Location: cursos.php?creado=1");
            exit;
        } catch (PDOException $e) {
            die("Error al crear el curso: " . $e->getMessage());
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: cursos.php");
            exit;
        }

        $conexion = $this->conexion;

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $id = $_POST["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: cursos.php");
            exit;
        }

        try {
            CursoModel::eliminar($conexion, (int) $id);

            header("Location: cursos.php?eliminado=1");
            exit;
        } catch (PDOException $e) {
            die("Error al eliminar el curso: " . $e->getMessage());
        }
    }
}
