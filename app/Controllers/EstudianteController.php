<?php

require_once __DIR__ . "/../Models/EstudianteModel.php";
require_once __DIR__ . "/../Models/GamificacionModel.php";

/**
 * EstudianteController
 *
 * Una acción pública por cada página del panel del estudiante.
 * Todas usan el mismo layout (barra lateral + encabezado) gracias a
 * render(), así el diseño es igual en todas las pantallas.
 *
 * Rutas (todas siguen funcionando):
 *   estudiante/index.php?page=inicio        -> inicio()
 *   estudiante/index.php?page=cursos        -> cursos()       (también cursos.php)
 *   estudiante/index.php?page=mis-cursos    -> misCursos()
 *   estudiante/index.php?page=curso&id=N    -> curso()        (también curso.php?id=N)
 *   estudiante/index.php?page=leccion&id=N  -> leccion()
 *   estudiante/index.php?page=certificados  -> certificados() (también certificados.php)
 *   estudiante/completar_leccion.php (POST) -> completarLeccion()
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

    /* =========================================================
       RENDER: arma la página con el layout común
       $vista  = nombre del archivo en app/Views/estudiante/
       $datos  = variables que la vista puede usar
    ========================================================= */
    private function render(string $vista, string $paginaActiva, string $titulo, array $datos = []): void
    {
        $usuario = EstudianteModel::obtenerDatosUsuario($this->conexion, $this->usuarioId) ?: [
            "nombre"   => $_SESSION["nombre"] ?? "Estudiante",
            "apellido" => $_SESSION["apellido"] ?? "",
            "correo"   => $_SESSION["correo"] ?? "",
            "google_avatar" => null,
        ];

        // "No especificado" lo guarda el registro cuando no hay apellido
        $apellido = ($usuario["apellido"] ?? "") === "No especificado" ? "" : ($usuario["apellido"] ?? "");

        $estudiante = [
            "nombre"        => trim(($usuario["nombre"] ?? "") . " " . $apellido) ?: "Estudiante",
            "primerNombre"  => explode(" ", trim($usuario["nombre"] ?? "Estudiante"))[0] ?: "Estudiante",
            "correo"        => $usuario["correo"] ?? "",
            "iniciales"     => mb_strtoupper(mb_substr($usuario["nombre"] ?? "E", 0, 1) . mb_substr($apellido, 0, 1)) ?: "E",
        ];

        // Mensaje de una sola vez (ej. "¡Lección completada!")
        $flash = $_SESSION["flash_estudiante"] ?? null;
        unset($_SESSION["flash_estudiante"]);

        extract($datos, EXTR_SKIP);

        require __DIR__ . "/../Views/estudiante/_layout_inicio.php";
        require __DIR__ . "/../Views/estudiante/{$vista}.php";
        require __DIR__ . "/../Views/estudiante/_layout_fin.php";
    }

    /* =========================================================
       INICIO
    ========================================================= */
    public function inicio(): void
    {
        $c = $this->conexion;
        $u = $this->usuarioId;

        $leccionesCompletadas = EstudianteModel::contarLeccionesCompletadas($c, $u);
        $totalCertificados    = EstudianteModel::contarCertificados($c, $u);
        $misCursos            = EstudianteModel::obtenerMisCursos($c, $u);

        // Curso para "Continuar": el último en el que avanzó y que no terminó
        $continuar = null;
        foreach ($misCursos as $curso) {
            if ((int) $curso["lecciones_hechas"] < (int) $curso["total_lecciones"]) {
                $continuar = $curso;
                $continuar["siguiente"] = EstudianteModel::obtenerSiguienteLeccion($c, (int) $curso["id"], $u);
                break;
            }
        }

        // Si aún no empezó nada, sugerimos cursos publicados
        $sugeridos = [];
        if (empty($misCursos)) {
            $sugeridos = array_slice(EstudianteModel::obtenerCursosPublicados($c, $u), 0, 3);
        }

        // Racha, XP y medallas (null si las tablas aún no existen)
        $gamificacion = null;
        if (GamificacionModel::disponible($c)) {
            $gamificacion = GamificacionModel::obtenerResumen($c, $u);
            $gamificacion["medallas"] = GamificacionModel::obtenerMedallas($c, $u);
        }

        $this->render("inicio", "inicio", "Inicio", [
            "gamificacion"         => $gamificacion,
            "leccionesCompletadas" => $leccionesCompletadas,
            "totalCertificados"    => $totalCertificados,
            "cursosEnProgreso"     => count(array_filter($misCursos, fn($m) => (int) $m["lecciones_hechas"] < (int) $m["total_lecciones"])),
            "misCursos"            => array_slice($misCursos, 0, 3),
            "continuar"            => $continuar,
            "sugeridos"            => $sugeridos,
        ]);
    }

    /* =========================================================
       CATÁLOGO DE CURSOS
    ========================================================= */
    public function cursos(): void
    {
        $cursos = EstudianteModel::obtenerCursosPublicados($this->conexion, $this->usuarioId);

        $this->render("cursos", "cursos", "Explorar cursos", ["cursos" => $cursos]);
    }

    /* =========================================================
       MIS CURSOS (los que ya empezó)
    ========================================================= */
    public function misCursos(): void
    {
        $cursos = EstudianteModel::obtenerMisCursos($this->conexion, $this->usuarioId);

        $this->render("mis_cursos", "mis-cursos", "Mis cursos", ["cursos" => $cursos]);
    }

    /* =========================================================
       DETALLE DE UN CURSO (lista de lecciones)
    ========================================================= */
    public function curso(): void
    {
        $c = $this->conexion;
        $u = $this->usuarioId;

        $cursoId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

        if (!$cursoId) {
            header("Location: index.php?page=cursos");
            exit;
        }

        $curso = EstudianteModel::obtenerCursoPublicadoPorId($c, $cursoId);

        if (!$curso) {
            $this->render("no_encontrado", "cursos", "Curso no disponible", [
                "mensaje" => "Este curso no existe o todavía no está publicado.",
            ]);
            return;
        }

        $lecciones = EstudianteModel::obtenerLeccionesDeCurso($c, $cursoId, $u);

        $totalLecciones = count($lecciones);
        $leccionesHechas = count(array_filter($lecciones, fn($l) => (int) $l["completado"] === 1));

        $porcentaje = $totalLecciones > 0 ? (int) round(($leccionesHechas / $totalLecciones) * 100) : 0;

        $siguiente = EstudianteModel::obtenerSiguienteLeccion($c, $cursoId, $u);

        // Lecciones en orden: solo se abren las completadas y la siguiente.
        // Las demás quedan bloqueadas hasta terminar la anterior.
        $idSiguiente = $siguiente ? (int) $siguiente["id"] : 0;
        foreach ($lecciones as &$l) {
            $l["bloqueada"] = (int) $l["completado"] !== 1 && (int) $l["id"] !== $idSiguiente;
        }
        unset($l);

        $certificado = EstudianteModel::obtenerCertificadoDeCurso($c, $u, $cursoId);

        $this->render("curso", "mis-cursos", $curso["nombre"], compact(
            "curso", "lecciones", "totalLecciones", "leccionesHechas", "porcentaje", "siguiente", "certificado"
        ));
    }

    /* =========================================================
       UNA LECCIÓN (contenido + actividades)
    ========================================================= */
    public function leccion(): void
    {
        $leccionId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

        if (!$leccionId) {
            header("Location: index.php?page=mis-cursos");
            exit;
        }

        $leccion = EstudianteModel::obtenerLeccionPublicada($this->conexion, $leccionId, $this->usuarioId);

        if (!$leccion) {
            $this->render("no_encontrado", "mis-cursos", "Lección no disponible", [
                "mensaje" => "Esta lección no existe o todavía no está publicada.",
            ]);
            return;
        }

        // Si todavía no terminó la lección anterior, lo devolvemos al curso
        if ((int) $leccion["completado"] !== 1
            && !EstudianteModel::leccionDesbloqueada($this->conexion, $leccionId, $this->usuarioId)) {
            $this->redirigirLeccionBloqueada((int) $leccion["curso_id"]);
        }

        $actividades = EstudianteModel::obtenerActividadesDeLeccion($this->conexion, $leccionId);

        $this->render("leccion", "mis-cursos", $leccion["titulo"], compact("leccion", "actividades"));
    }

    /* =========================================================
       CERTIFICADOS
    ========================================================= */
    public function certificados(): void
    {
        $certificados = EstudianteModel::obtenerCertificadosDeUsuario($this->conexion, $this->usuarioId);

        $this->render("certificados", "certificados", "Certificados", ["certificados" => $certificados]);
    }

    /* =========================================================
       MARCAR LECCIÓN COMO COMPLETADA (POST)
    ========================================================= */
    public function completarLeccion(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?page=mis-cursos");
            exit;
        }

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $conexion = $this->conexion;
        $usuarioId = $this->usuarioId;

        $leccionId = filter_input(INPUT_POST, "leccion_id", FILTER_VALIDATE_INT);
        $cursoId   = filter_input(INPUT_POST, "curso_id", FILTER_VALIDATE_INT);

        if (!$leccionId || !$cursoId) {
            header("Location: index.php?page=mis-cursos");
            exit;
        }

        if (!EstudianteModel::leccionValidaEnCurso($conexion, $leccionId, $cursoId)) {
            header("Location: index.php?page=curso&id=" . $cursoId);
            exit;
        }

        // No se puede completar una lección saltándose la anterior
        if (!EstudianteModel::leccionDesbloqueada($conexion, $leccionId, $usuarioId)) {
            $this->redirigirLeccionBloqueada($cursoId);
        }

        $conGamificacion = GamificacionModel::disponible($conexion);

        try {
            // Todo junto o nada: progreso, certificado, XP, racha y medallas
            $conexion->beginTransaction();

            // Si ya la había completado, no vuelve a sumar XP
            $yaEstabaHecha = EstudianteModel::leccionYaCompletada($conexion, $usuarioId, $leccionId);

            EstudianteModel::marcarLeccionCompletada($conexion, $usuarioId, $leccionId);

            $progreso = EstudianteModel::contarLeccionesTotalYCompletadas($conexion, $cursoId, $usuarioId);
            $cursoTerminado = $progreso["total"] > 0 && $progreso["hechas"] >= $progreso["total"];

            $certificadoNuevo = false;
            if ($cursoTerminado && !EstudianteModel::certificadoExiste($conexion, $usuarioId, $cursoId)) {
                $codigo = "BG-" . strtoupper(bin2hex(random_bytes(4)));
                EstudianteModel::emitirCertificado($conexion, $usuarioId, $cursoId, $codigo);
                $certificadoNuevo = true;
            }

            $xpGanado = 0;
            $medallasNuevas = [];

            if ($conGamificacion && !$yaEstabaHecha) {
                $racha = GamificacionModel::registrarActividad(
                    $conexion, $usuarioId, GamificacionModel::XP_LECCION, "leccion", $leccionId
                );
                $xpGanado = GamificacionModel::XP_LECCION;

                if ($certificadoNuevo) {
                    GamificacionModel::sumarXp($conexion, $usuarioId, GamificacionModel::XP_CURSO, "curso", $cursoId);
                    $xpGanado += GamificacionModel::XP_CURSO;
                }

                // Medallas que corresponden según lo que acaba de pasar
                $codigos = ["primera_leccion"];
                if ($racha >= 3) $codigos[] = "racha_3";
                if ($racha >= 7) $codigos[] = "racha_7";
                if ($cursoTerminado) $codigos[] = "primer_curso";

                foreach ($codigos as $codigo) {
                    $nombre = GamificacionModel::otorgarMedalla($conexion, $usuarioId, $codigo);
                    if ($nombre !== null) {
                        $medallasNuevas[] = $nombre;
                    }
                }
            }

            $conexion->commit();

            $textoXp = $xpGanado > 0 ? " +{$xpGanado} XP." : "";
            $textoMedalla = $medallasNuevas
                ? " Ganaste la medalla «" . implode("» y «", $medallasNuevas) . "»."
                : "";

            if ($cursoTerminado) {
                $_SESSION["flash_estudiante"] = ["tipo" => "logro",
                    "mensaje" => "¡Terminaste el curso!{$textoXp} Tu certificado ya está en «Certificados».{$textoMedalla}"];
            } elseif ($medallasNuevas) {
                $_SESSION["flash_estudiante"] = ["tipo" => "logro",
                    "mensaje" => "¡Lección completada!{$textoXp}{$textoMedalla}"];
            } else {
                $_SESSION["flash_estudiante"] = ["tipo" => "exito",
                    "mensaje" => "¡Lección completada!{$textoXp} Vas {$progreso['hechas']} de {$progreso['total']}."];
            }

            header("Location: index.php?page=curso&id=" . $cursoId);
            exit;

        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            manejarErrorBD($e, "No pudimos guardar tu progreso. Vuelve atrás e inténtalo de nuevo.");
        }
    }

    /* =========================================================
       AYUDANTE: lección bloqueada -> volver al curso con aviso
    ========================================================= */
    private function redirigirLeccionBloqueada(int $cursoId): void
    {
        $siguiente = EstudianteModel::obtenerSiguienteLeccion($this->conexion, $cursoId, $this->usuarioId);

        $_SESSION["flash_estudiante"] = [
            "tipo"    => "info",
            "mensaje" => $siguiente
                ? "Esa lección está bloqueada. Primero completa «{$siguiente['titulo']}»."
                : "Esa lección está bloqueada. Completa las anteriores primero.",
        ];

        header("Location: index.php?page=curso&id=" . $cursoId);
        exit;
    }
}
