<?php

require_once __DIR__ . "/../Models/EstudianteModel.php";
require_once __DIR__ . "/../Models/GamificacionModel.php";
require_once __DIR__ . "/../Models/PerfilModel.php";
require_once __DIR__ . "/../Models/QuizModel.php";

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
 *   estudiante/index.php?page=perfil        -> perfil()       (GET muestra, POST guarda)
 *   estudiante/completar_leccion.php (POST) -> completarLeccion()
 *   estudiante/responder_actividad.php (POST) -> responderActividad()
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
       MI PERFIL
       GET  -> muestra los datos de la cuenta
       POST -> accion=datos     : cambia nombre, apellido y correo
               accion=password  : cambia la contraseña
       Si hay un error, la página se vuelve a mostrar con el mensaje
       y lo que el usuario escribió. Si todo sale bien, se redirige
       (así al recargar no se envía el formulario dos veces).
    ========================================================= */
    public function perfil(): void
    {
        $conexion = $this->conexion;
        $id = $this->usuarioId;

        $cuenta = PerfilModel::obtener($conexion, $id);

        if (!$cuenta) {
            header("Location: ../auth/logout.php");
            exit;
        }

        $errores = ["datos" => null, "password" => null];
        $valores = [
            "nombre"   => $cuenta["nombre"],
            "apellido" => $cuenta["apellido"] === "No especificado" ? "" : $cuenta["apellido"],
            "correo"   => $cuenta["correo"],
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            verificarCsrfFormulario($_POST["csrf_token"] ?? "");

            $accion = $_POST["accion"] ?? "";

            if ($accion === "datos") {
                $valores = [
                    "nombre"   => trim($_POST["nombre"] ?? ""),
                    "apellido" => trim($_POST["apellido"] ?? ""),
                    "correo"   => mb_strtolower(trim($_POST["correo"] ?? "")),
                ];
                $errores["datos"] = $this->guardarDatos($cuenta, $valores, $_POST["password_actual"] ?? "");
            } elseif ($accion === "password") {
                $errores["password"] = $this->guardarPassword(
                    $_POST["password_actual"] ?? "",
                    $_POST["password_nueva"] ?? "",
                    $_POST["password_confirmar"] ?? ""
                );
            }

            // Sin errores: guardado. Volvemos a la página con un aviso.
            if ($errores["datos"] === null && $errores["password"] === null) {
                header("Location: index.php?page=perfil");
                exit;
            }
        }

        $reglas = Seguridad::reglasPassword();

        $this->render("perfil", "perfil", "Mi perfil", compact("cuenta", "valores", "errores", "reglas"));
    }

    /** Guarda nombre, apellido y correo. Devuelve null si salió bien o el mensaje de error. */
    private function guardarDatos(array $cuenta, array $valores, string $passwordActual): ?string
    {
        $nombre   = $valores["nombre"];
        $apellido = $valores["apellido"];
        $correo   = $valores["correo"];

        if ($nombre === "") {
            return "Escribe tu nombre.";
        }
        if (mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
            return "El nombre y el apellido pueden tener máximo 100 caracteres.";
        }
        if (!mb_check_encoding($nombre . $apellido, "UTF-8")) {
            return "El nombre tiene caracteres no válidos.";
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
            return "Escribe un correo válido, por ejemplo nombre@correo.com.";
        }

        $cambiaCorreo = $correo !== mb_strtolower($cuenta["correo"]);

        if ($cambiaCorreo) {
            if (PerfilModel::correoEnUso($this->conexion, $correo, $this->usuarioId)) {
                return "Ese correo ya lo usa otra cuenta.";
            }

            // Para cambiar el correo pedimos la contraseña (si la cuenta tiene una)
            $hash = PerfilModel::obtenerHash($this->conexion, $this->usuarioId);
            if ($hash !== null && !$this->passwordCorrecta($passwordActual, $hash)) {
                return $this->mensajePasswordIncorrecta();
            }
        }

        try {
            PerfilModel::actualizarDatos(
                $this->conexion,
                $this->usuarioId,
                $nombre,
                $apellido !== "" ? $apellido : "No especificado",
                $correo
            );
        } catch (PDOException $e) {
            error_log("[BiGlobal][perfil datos] " . $e->getMessage());
            return "No pudimos guardar tus datos. Inténtalo de nuevo.";
        }

        // La barra lateral y el login usan estos datos de la sesión
        $_SESSION["nombre"]   = $nombre;
        $_SESSION["apellido"] = $apellido !== "" ? $apellido : "No especificado";
        $_SESSION["correo"]   = $correo;

        $_SESSION["flash_estudiante"] = ["tipo" => "exito", "mensaje" => "¡Listo! Guardamos tus datos."];
        return null;
    }

    /** Cambia la contraseña. Devuelve null si salió bien o el mensaje de error. */
    private function guardarPassword(string $actual, string $nueva, string $confirmar): ?string
    {
        $hash = PerfilModel::obtenerHash($this->conexion, $this->usuarioId);

        if ($hash === null) {
            return "Tu cuenta entra con Google y no tiene contraseña. Usa «Crear contraseña» para hacerla con tu correo.";
        }
        if ($actual === "" || $nueva === "" || $confirmar === "") {
            return "Completa los tres campos de contraseña.";
        }
        if (!$this->passwordCorrecta($actual, $hash)) {
            return $this->mensajePasswordIncorrecta();
        }
        if ($nueva !== $confirmar) {
            return "La nueva contraseña y su confirmación no coinciden.";
        }
        if (password_verify($nueva, $hash)) {
            return "La nueva contraseña debe ser distinta a la actual.";
        }
        if (($errorReglas = Seguridad::validarPassword($nueva)) !== null) {
            return $errorReglas;
        }

        try {
            PerfilModel::actualizarPassword($this->conexion, $this->usuarioId, $nueva);
        } catch (PDOException $e) {
            error_log("[BiGlobal][perfil password] " . $e->getMessage());
            return "No pudimos cambiar tu contraseña. Inténtalo de nuevo.";
        }

        // Nuevo identificador de sesión: si alguien tenía la sesión vieja, deja de servir
        session_regenerate_id(true);

        $_SESSION["flash_estudiante"] = ["tipo" => "exito", "mensaje" => "¡Contraseña actualizada!"];
        return null;
    }

    /**
     * Revisa la contraseña actual con un límite de intentos:
     * después de 5 fallos seguidos hay que esperar 15 minutos.
     */
    private function passwordCorrecta(string $password, string $hash): bool
    {
        $bloqueadoHasta = $_SESSION["perfil_bloqueo"] ?? 0;
        if (time() < $bloqueadoHasta) {
            return false;
        }

        if (password_verify($password, $hash)) {
            $_SESSION["perfil_intentos"] = 0;
            return true;
        }

        $_SESSION["perfil_intentos"] = ($_SESSION["perfil_intentos"] ?? 0) + 1;
        if ($_SESSION["perfil_intentos"] >= 5) {
            $_SESSION["perfil_bloqueo"] = time() + 15 * 60;
            $_SESSION["perfil_intentos"] = 0;
        }
        return false;
    }

    private function mensajePasswordIncorrecta(): string
    {
        if (time() < ($_SESSION["perfil_bloqueo"] ?? 0)) {
            return "Demasiados intentos. Espera unos minutos antes de volver a intentarlo.";
        }
        return "La contraseña actual no es correcta.";
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
        // Filtro por idioma (?idioma=EN): solo se acepta un código de la lista
        $idiomas = EstudianteModel::idiomasConCursos($this->conexion);
        $pedido  = mb_strtoupper(trim((string) ($_GET["idioma"] ?? "")));
        $filtro  = in_array($pedido, array_column($idiomas, "codigo"), true) ? $pedido : null;

        $cursos = EstudianteModel::obtenerCursosPublicados($this->conexion, $this->usuarioId, $filtro);

        $this->render("cursos", "cursos", "Explorar cursos", [
            "cursos"  => $cursos,
            "idiomas" => $idiomas,
            "filtro"  => $filtro,
        ]);
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

        // Actividades con preguntas (quiz) y el resultado del último envío
        $quiz = QuizModel::disponible($this->conexion)
            ? QuizModel::estadoLeccion($this->conexion, $actividades, $this->usuarioId)
            : [];
        $pendientes = count(array_filter($quiz, fn($q) => !$q["aprobada"]));

        $resultado = $_SESSION["resultado_quiz"] ?? null;
        unset($_SESSION["resultado_quiz"]);
        if ($resultado && !isset($quiz[(int) $resultado["actividad_id"]])) {
            $resultado = null;
        }

        $this->render("leccion", "mis-cursos", $leccion["titulo"], compact("leccion", "actividades", "quiz", "pendientes", "resultado"));
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

        // Ni sin aprobar sus actividades con preguntas (si ya estaba hecha, se respeta)
        if (QuizModel::disponible($conexion)
            && !EstudianteModel::leccionYaCompletada($conexion, $usuarioId, $leccionId)) {
            $faltan = QuizModel::pendientesEnLeccion($conexion, $leccionId, $usuarioId);
            if ($faltan > 0) {
                $_SESSION["flash_estudiante"] = ["tipo" => "info",
                    "mensaje" => $faltan === 1
                        ? "Te falta aprobar 1 actividad para terminar esta lección."
                        : "Te faltan aprobar {$faltan} actividades para terminar esta lección."];
                header("Location: index.php?page=leccion&id=" . $leccionId . "#titulo-actividades");
                exit;
            }
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
       RESPONDER UNA ACTIVIDAD CON PREGUNTAS (POST)
       Se califica aquí, en el servidor. El resultado se muestra en
       la lección (se guarda una sola vez en la sesión).
    ========================================================= */
    public function responderActividad(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?page=mis-cursos");
            exit;
        }

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $conexion = $this->conexion;
        $usuarioId = $this->usuarioId;
        $actividadId = filter_input(INPUT_POST, "actividad_id", FILTER_VALIDATE_INT);

        if (!$actividadId || !QuizModel::disponible($conexion)) {
            header("Location: index.php?page=mis-cursos");
            exit;
        }

        // La actividad tiene que ser de una lección publicada que el estudiante pueda ver
        $actividad = QuizModel::actividad($conexion, $actividadId);
        $leccion = $actividad
            ? EstudianteModel::obtenerLeccionPublicada($conexion, (int) $actividad["leccion_id"], $usuarioId)
            : false;

        if (!$leccion || !QuizModel::tipoConPreguntas($actividad["tipo"])) {
            header("Location: index.php?page=mis-cursos");
            exit;
        }

        $leccionId = (int) $leccion["id"];
        $volver = "index.php?page=leccion&id=" . $leccionId . "#actividad-" . $actividadId;

        if ((int) $leccion["completado"] !== 1
            && !EstudianteModel::leccionDesbloqueada($conexion, $leccionId, $usuarioId)) {
            $this->redirigirLeccionBloqueada((int) $leccion["curso_id"]);
        }

        // Tope para que nadie pruebe respuestas a toda velocidad
        if (!LimiteIntentos::permitir("actividad:usuario:" . $usuarioId, 30, 600)) {
            $_SESSION["flash_estudiante"] = ["tipo" => "error",
                "mensaje" => "Enviaste muchas respuestas seguidas. Espera unos minutos y vuelve a intentarlo."];
            header("Location: " . $volver);
            exit;
        }

        $respuestas = is_array($_POST["respuestas"] ?? null) ? $_POST["respuestas"] : [];
        $nota = QuizModel::calificar($conexion, $actividadId, $respuestas);

        if ($nota["total"] === 0) {
            header("Location: " . $volver);
            exit;
        }

        $aprobado = QuizModel::aprueba($nota["aciertos"], $nota["total"]);

        try {
            $conexion->beginTransaction();

            $yaAprobada = QuizModel::yaAprobada($conexion, $actividadId, $usuarioId);
            QuizModel::guardarIntento($conexion, $usuarioId, $actividadId, $nota["aciertos"], $nota["total"], $aprobado);

            // XP solo la primera vez que la aprueba (también cuenta para la racha)
            $xpGanado = 0;
            $medallasNuevas = [];
            if ($aprobado && !$yaAprobada && GamificacionModel::disponible($conexion)) {
                $racha = GamificacionModel::registrarActividad(
                    $conexion, $usuarioId, QuizModel::XP_ACTIVIDAD, "actividad", $actividadId
                );
                $xpGanado = QuizModel::XP_ACTIVIDAD;

                $codigos = [];
                if ($racha >= 3) $codigos[] = "racha_3";
                if ($racha >= 7) $codigos[] = "racha_7";
                foreach ($codigos as $codigo) {
                    $nombre = GamificacionModel::otorgarMedalla($conexion, $usuarioId, $codigo);
                    if ($nombre !== null) {
                        $medallasNuevas[] = $nombre;
                    }
                }
            }

            $conexion->commit();
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            manejarErrorBD($e, "No pudimos guardar tus respuestas. Vuelve atrás e inténtalo de nuevo.");
        }

        $_SESSION["resultado_quiz"] = [
            "actividad_id" => $actividadId,
            "aciertos"     => $nota["aciertos"],
            "total"        => $nota["total"],
            "aprobado"     => $aprobado,
            "detalle"      => $nota["detalle"],
            "xp"           => $xpGanado,
            "medallas"     => $medallasNuevas,
        ];

        header("Location: " . $volver);
        exit;
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
