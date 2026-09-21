<?php

require_once __DIR__ . "/../Models/EstudianteModel.php";

/**
 * EstudianteController
 *
 * Una accion publica por cada pagina/endpoint que antes era un
 * archivo suelto en src/estudiante/. El constructor hace lo que
 * cada archivo original hacia al principio: protegerRol("estudiante")
 * y abrir la conexion. Cada metodo hace la misma consulta/logica que
 * tenia el archivo original y despues incluye la Vista correspondiente
 * (las variables locales del metodo quedan visibles para la Vista).
 */
class EstudianteController
{
    private PDO $conexion;
    private int $usuarioId;

    public function __construct()
    {
        protegerRol("estudiante");

        $this->conexion = Conexion::conectar();
        $this->usuarioId = (int) $_SESSION["usuario_id"];
    }

    public function inicio(): void
    {
        $conexion = $this->conexion;
        $usuarioId = $this->usuarioId;

        $leccionesCompletadas = EstudianteModel::contarLeccionesCompletadas($conexion, $usuarioId);
        $totalCertificados = EstudianteModel::contarCertificados($conexion, $usuarioId);
        $cursosEnProgreso = EstudianteModel::contarCursosEnProgreso($conexion, $usuarioId);
        $totalCursosDisponibles = EstudianteModel::contarCursosDisponibles($conexion);
        $cursosContinuar = EstudianteModel::obtenerCursosParaContinuar($conexion, $usuarioId);

        require __DIR__ . "/../Views/estudiante/inicio.php";
    }

    public function cursos(): void
    {
        $usuarioId = $this->usuarioId;

        $cursos = EstudianteModel::obtenerCursosPublicados($this->conexion, $usuarioId);

        require __DIR__ . "/../Views/estudiante/cursos.php";
    }

    public function curso(): void
    {
        $conexion = $this->conexion;
        $usuarioId = $this->usuarioId;

        $cursoId = $_GET["id"] ?? null;

        if (!$cursoId || !is_numeric($cursoId)) {
            header("Location: cursos.php");
            exit;
        }

        $cursoId = (int) $cursoId;

        $curso = EstudianteModel::obtenerCursoPublicadoPorId($conexion, $cursoId);

        if (!$curso) {
            header("Location: cursos.php");
            exit;
        }

        $lecciones = EstudianteModel::obtenerLeccionesDeCurso($conexion, $cursoId, $usuarioId);

        $totalLecciones = count($lecciones);
        $leccionesHechas = 0;

        foreach ($lecciones as $l) {
            if ((int) $l["completado"] === 1) {
                $leccionesHechas++;
            }
        }

        $porcentaje = $totalLecciones > 0
            ? round(($leccionesHechas / $totalLecciones) * 100)
            : 0;

        $certificado = EstudianteModel::obtenerCertificadoDeCurso($conexion, $usuarioId, $cursoId);

        require __DIR__ . "/../Views/estudiante/curso.php";
    }

    public function certificados(): void
    {
        $certificados = EstudianteModel::obtenerCertificadosDeUsuario($this->conexion, $this->usuarioId);

        require __DIR__ . "/../Views/estudiante/certificados.php";
    }

    public function completarLeccion(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: cursos.php");
            exit;
        }

        $conexion = $this->conexion;
        $usuarioId = $this->usuarioId;

        $leccionId = $_POST["leccion_id"] ?? null;
        $cursoId = $_POST["curso_id"] ?? null;

        if (!$leccionId || !is_numeric($leccionId) || !$cursoId || !is_numeric($cursoId)) {
            header("Location: cursos.php");
            exit;
        }

        $leccionId = (int) $leccionId;
        $cursoId = (int) $cursoId;

        if (!EstudianteModel::leccionValidaEnCurso($conexion, $leccionId, $cursoId)) {
            header("Location: curso.php?id=" . urlencode($cursoId));
            exit;
        }

        try {
            $conexion->beginTransaction();

            EstudianteModel::marcarLeccionCompletada($conexion, $usuarioId, $leccionId);

            $progreso = EstudianteModel::contarLeccionesTotalYCompletadas($conexion, $cursoId, $usuarioId);

            if ($progreso["total"] > 0 && $progreso["hechas"] >= $progreso["total"]) {

                if (!EstudianteModel::certificadoExiste($conexion, $usuarioId, $cursoId)) {

                    $codigo = "BG-" . strtoupper(bin2hex(random_bytes(4)));

                    EstudianteModel::emitirCertificado($conexion, $usuarioId, $cursoId, $codigo);
                }
            }

            $conexion->commit();

            header("Location: curso.php?id=" . urlencode($cursoId));
            exit;

        } catch (PDOException $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            die("Error al actualizar tu progreso: " . $e->getMessage());
        }
    }
}
