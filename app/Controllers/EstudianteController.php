<?php

require_once __DIR__ . "/../Models/EstudianteModel.php";
require_once __DIR__ . "/../Models/GamificacionModel.php";
require_once __DIR__ . "/../Models/PerfilModel.php";
require_once __DIR__ . "/../Models/QuizModel.php";
require_once __DIR__ . "/../Models/PagoModel.php";
require_once __DIR__ . "/../Models/ClaseModel.php";
require_once __DIR__ . "/../Models/ContenidoModel.php";
require_once __DIR__ . "/../Models/TareaModel.php";
require_once __DIR__ . "/../Models/RutaModel.php";
require_once __DIR__ . "/../Views/estudiante/_helpers.php";

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
 *   estudiante/index.php?page=plan          -> plan()   (Mi plan y pagos)
 *   estudiante/pagar.php (POST)             -> pagar()
 *   estudiante/index.php?page=grupos        -> grupos()   (Mis grupos)
 *   estudiante/index.php?page=grupo&id=N    -> grupo()
 *   estudiante/grupo.php (POST)             -> accionGrupo()
 *   estudiante/index.php?page=ruta[&idioma=EN][&nivel=A1] -> ruta()  (Mi ruta)
 *   estudiante/index.php?page=ruta-leccion&id=N           -> rutaLeccion()
 *   estudiante/ruta_responder.php (POST, JSON)            -> rutaResponder()
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

        // Contador de la campana
        $avisosSinLeer = Notificador::noLeidas($this->usuarioId);

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
            cerrarSesion();
            header("Location: ../index.html");
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

        // Plan Gratuito o Premium (null = Gratuito)
        $suscripcion = Plan::suscripcionVigente($conexion, $id);

        $this->render("perfil", "perfil", "Mi perfil", compact("cuenta", "valores", "errores", "reglas", "suscripcion"));
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
            "ruta"                 => $this->resumenRuta(),
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
       MI PLAN: Premium, pagos con Wompi e historial
       GET  index.php?page=plan[&elegir=premium_anual][&ref=BG-...]
       POST pagar.php (accion=tarjeta|checkout|cancelar_renovacion|reactivar_renovacion)
    ========================================================= */
    public function plan(): void
    {
        $c = $this->conexion;
        $disponible = PagoModel::disponible($c);

        $elegido = (string) ($_GET["elegir"] ?? Plan::MENSUAL);
        if (!isset(Plan::PLANES[$elegido])) {
            $elegido = Plan::MENSUAL;
        }

        $suscripcion = Plan::suscripcionVigente($c, $this->usuarioId);
        $conRenovacion = $disponible ? PagoModel::suscripcionConRenovacion($c, $this->usuarioId) : null;

        // Volvió de pagar: se muestra el estado del pago (lo decide el webhook, no esta página)
        $pagoConsultado = null;
        if ($disponible && !empty($_GET["ref"])) {
            $pago = PagoModel::porReferencia($c, (string) $_GET["ref"]);
            if ($pago && (int) $pago["usuario_id"] === $this->usuarioId) {
                $pagoConsultado = $pago;
            }
        }

        // Términos de Wompi: se muestran siempre antes de pagar
        $puedeComprar = $disponible && Wompi::configurado() && !$conRenovacion;
        $terminos = $puedeComprar ? Wompi::terminos() : null;

        $this->render("plan", "perfil", "Mi plan", [
            "disponible"     => $disponible,
            "configurado"    => Wompi::configurado(),
            "suscripcion"    => $suscripcion,
            "conRenovacion"  => $conRenovacion,
            "reactivable"    => $disponible ? $this->suscripcionReactivable() : null,
            "pagoConsultado" => $pagoConsultado,
            "historial"      => PagoModel::historial($c, $this->usuarioId),
            "elegido"        => $elegido,
            "puedeComprar"   => $puedeComprar,
            "terminos"       => $terminos,
            "wompiApi"       => Wompi::urlApi(),
            "wompiPublica"   => Wompi::llavePublica(),
        ]);
    }

    public function pagar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?page=plan");
            exit;
        }
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $c = $this->conexion;
        $volver = function (string $tipo, string $mensaje, string $extra = "") {
            $_SESSION["flash_estudiante"] = ["tipo" => $tipo, "mensaje" => $mensaje];
            header("Location: index.php?page=plan" . $extra);
            exit;
        };

        if (!PagoModel::disponible($c)) {
            $volver("error", "Los pagos todavía no están disponibles.");
        }

        $accion = (string) ($_POST["accion"] ?? "");

        if ($accion === "cancelar_renovacion") {
            $n = PagoModel::cancelarRenovacion($c, $this->usuarioId);
            $s = Plan::suscripcionVigente($c, $this->usuarioId);
            $volver($n ? "exito" : "info", $n
                ? ($s ? "Cancelaste la renovación automática. Sigues siendo Premium hasta el " . fechaCorta($s["fin"]) . "." : "Cancelaste la renovación. Pasaste al plan Gratuito sin perder tu progreso.")
                : "No tenías una renovación automática activa.");
        }

        if ($accion === "reactivar_renovacion") {
            $n = PagoModel::reactivarRenovacion($c, $this->usuarioId);
            $volver($n ? "exito" : "info", $n ? "Listo: tu Premium se renovará solo en la fecha de corte." : "No hay una suscripción para reactivar.");
        }

        if (!in_array($accion, ["tarjeta", "checkout"], true)) {
            $volver("error", "Acción no reconocida.");
        }

        // ---------- Comprar Premium ----------
        $plan = (string) ($_POST["plan"] ?? "");
        if (!isset(Plan::PLANES[$plan])) {
            $volver("error", "Elige el plan mensual o el anual.");
        }
        $elegir = "&elegir=" . $plan;

        if (!Wompi::configurado()) {
            $volver("error", "Los pagos todavía no están disponibles.", $elegir);
        }
        if (empty($_POST["acepta_terminos"]) || empty($_POST["acepta_datos"])) {
            $volver("error", "Para pagar debes aceptar los términos y la autorización de datos de Wompi.", $elegir);
        }
        if (PagoModel::suscripcionConRenovacion($c, $this->usuarioId)) {
            $volver("info", "Ya tienes Premium con renovación automática.");
        }
        if (!LimiteIntentos::permitir("pago:usuario:" . $this->usuarioId, 8, 3600)) {
            $volver("error", "Hiciste muchos intentos de pago seguidos. Espera un rato y vuelve a intentarlo.", $elegir);
        }

        // Los tokens de aceptación se piden de nuevo aquí: no se confía en lo que mande el navegador
        $terminos = Wompi::terminos();
        if ($terminos === null) {
            $volver("error", "No pudimos conectarnos con Wompi. Inténtalo en unos minutos.", $elegir);
        }

        $usuario = EstudianteModel::obtenerDatosUsuario($c, $this->usuarioId);
        $correo = (string) ($usuario["correo"] ?? "");

        if ($accion === "checkout") {
            // PSE, Nequi, Botón Bancolombia (o tarjeta sin guardar): pago único en el checkout de Wompi
            $pago = PagoModel::crear($c, $this->usuarioId, $plan, "inicial", "checkout", null, $terminos);
            $volverA = $this->urlSitio() . "/estudiante/index.php?page=plan&ref=" . rawurlencode($pago["referencia"]);
            header("Location: " . Wompi::urlCheckout($pago["referencia"], (int) $pago["centavos"], $correo, $volverA));
            exit;
        }

        // Tarjeta con renovación automática: aquí solo llega el TOKEN (tok_...), nunca el número
        $token = (string) ($_POST["token_tarjeta"] ?? "");
        if (!preg_match('/^tok_[A-Za-z0-9_]{6,80}$/', $token)) {
            $volver("error", "No recibimos los datos de la tarjeta. Revísalos e inténtalo de nuevo.", $elegir);
        }

        $fuente = Wompi::crearFuentePago($token, $correo, $terminos["aceptacion"]["token"], $terminos["datos"]["token"]);
        if ($fuente === null) {
            $volver("error", "Wompi no pudo validar la tarjeta. Revisa los datos o prueba con otro medio de pago.", $elegir);
        }

        $pago = PagoModel::crear($c, $this->usuarioId, $plan, "inicial", "tarjeta", null, $terminos, $fuente);
        $transaccion = Wompi::cobrarFuente($fuente, (int) $pago["centavos"], $pago["referencia"], $correo);
        PagoModel::guardarTransaccion($c, (int) $pago["id"], $transaccion, "Wompi no respondió al cobro.");

        header("Location: index.php?page=plan&ref=" . rawurlencode($pago["referencia"]));
        exit;
    }

    /* =========================================================
       NOTIFICACIONES (la campana)
       Muestra las últimas y, al abrirlas, quedan como leídas.
    ========================================================= */
    public function avisos(): void
    {
        $avisos = Notificador::recientes($this->usuarioId, 50);
        try {
            Notificador::marcarLeidas($this->usuarioId);
        } catch (Throwable $e) {
            error_log("[BiGlobal][avisos] " . $e->getMessage());
        }
        $this->render("avisos", "avisos", "Notificaciones", ["avisos" => $avisos]);
    }

    /* =========================================================
       CLASES EN VIVO
       GET  index.php?page=clases
       POST clase.php        (accion=inscribir|retirar, clase_id)
       GET  entrar_clase.php?id=N  -> redirige al enlace solo si puede entrar
    ========================================================= */
    public function clases(): void
    {
        $c = $this->conexion;
        $disponible = ClaseModel::disponible($c);
        $this->render("clases", "clases", "Clases en vivo", [
            "disponible" => $disponible,
            "clases"     => $disponible ? ClaseModel::paraEstudiante($c, $this->usuarioId) : [],
            "invitaciones" => $disponible ? ClaseModel::invitacionesPendientes($c, $this->usuarioId) : [],
            "premium"    => Plan::esPremium($c, $this->usuarioId),
        ]);
    }

    public function accionClase(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?page=clases");
            exit;
        }
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $c = $this->conexion;
        $claseId = (int) ($_POST["clase_id"] ?? 0);
        $volver = "index.php?page=clases#clase-" . $claseId;

        if (!ClaseModel::disponible($c) || $claseId <= 0) {
            header("Location: index.php?page=clases");
            exit;
        }
        if (!LimiteIntentos::permitir("clases:usuario:" . $this->usuarioId, 40, 600)) {
            $_SESSION["flash_estudiante"] = ["tipo" => "error", "mensaje" => "Hiciste muchos cambios seguidos. Espera unos minutos."];
            header("Location: " . $volver);
            exit;
        }

        $accion = (string) ($_POST["accion"] ?? "");
        if ($accion === "inscribir") {
            $r = ClaseModel::inscribir($c, $claseId, $this->usuarioId);
            $_SESSION["flash_estudiante"] = ["tipo" => !$r["ok"] ? "error" : ($r["estado"] === "inscrito" ? "exito" : "info"), "mensaje" => $r["mensaje"]];
        } elseif ($accion === "aceptar_invitacion" || $accion === "rechazar_invitacion") {
            $r = ClaseModel::responderInvitacion($c, $claseId, $this->usuarioId, $accion === "aceptar_invitacion");
            $_SESSION["flash_estudiante"] = ["tipo" => !$r["ok"] ? "error" : (($r["estado"] ?? "") === "inscrito" ? "exito" : "info"), "mensaje" => $r["mensaje"]];
        } elseif ($accion === "retirar") {
            $ok = ClaseModel::retirar($c, $claseId, $this->usuarioId);
            $_SESSION["flash_estudiante"] = ["tipo" => $ok ? "exito" : "error",
                "mensaje" => $ok ? "Te retiraste de la clase. Si había lista de espera, tu cupo pasó a la siguiente persona." : "No estabas inscrito en esta clase."];
        }

        header("Location: " . $volver);
        exit;
    }

    public function entrarClase(): void
    {
        $c = $this->conexion;
        $claseId = (int) ($_GET["id"] ?? 0);
        $r = ClaseModel::disponible($c) ? ClaseModel::enlaceParaEntrar($c, $claseId, $this->usuarioId) : ["mensaje" => "Las clases no están disponibles."];

        if (isset($r["enlace"])) {
            // El enlace solo sale aquí, después de revisar inscripción, plan y hora
            header("Referrer-Policy: no-referrer");
            header("Location: " . $r["enlace"]);
            exit;
        }
        $_SESSION["flash_estudiante"] = ["tipo" => "info", "mensaje" => $r["mensaje"]];
        header("Location: index.php?page=clases#clase-" . $claseId);
        exit;
    }

    /* =========================================================
       GRUPOS (tipo Classroom)
       GET  index.php?page=grupos               -> Mis grupos + entrar con código
       GET  index.php?page=grupo&id=N[&tab=tareas|material][&tarea=N] -> tablón, tareas o material
       POST grupo.php (accion=unirse|salir|comentar|borrar_comentario|entregar)
    ========================================================= */
    public function grupos(): void
    {
        $disponible = GrupoModel::disponible($this->conexion);
        $this->render("grupos", "grupos", "Mis grupos", [
            "disponible" => $disponible,
            "grupos"     => $disponible ? GrupoModel::gruposEstudiante($this->conexion, $this->usuarioId) : [],
        ]);
    }

    public function grupo(): void
    {
        $c = $this->conexion;
        $grupo = GrupoModel::disponible($c) ? GrupoModel::paraEstudiante($c, (int) ($_GET["id"] ?? 0), $this->usuarioId) : null;
        if (!$grupo) {
            $_SESSION["flash_estudiante"] = ["tipo" => "error", "mensaje" => "Ese grupo no existe, se archivó o ya no estás en él."];
            header("Location: index.php?page=grupos");
            exit;
        }
        $grupoId = (int) $grupo["id"];
        $tareasDisponibles = TareaModel::disponible($c);
        $tab = in_array($_GET["tab"] ?? "", ["tareas", "material"], true) ? $_GET["tab"] : "tablon";
        if ($tab === "tareas" && !$tareasDisponibles) {
            $tab = "tablon";
        }

        $tarea = null;
        $entrega = null;
        if ($tab === "tareas" && (int) ($_GET["tarea"] ?? 0) > 0) {
            $tarea = TareaModel::deGrupo($c, (int) $_GET["tarea"], $grupoId);
            if (!$tarea) {
                $_SESSION["flash_estudiante"] = ["tipo" => "error", "mensaje" => "Esa tarea ya no existe."];
                header("Location: index.php?page=grupo&id=" . $grupoId . "&tab=tareas");
                exit;
            }
            $entrega = TareaModel::entregaDe($c, (int) $tarea["id"], $this->usuarioId);
        }

        $this->render("grupo", "grupos", $tarea ? $tarea["titulo"] : $grupo["nombre"], [
            "grupo"    => $grupo,
            "tab"      => $tab,
            "anuncios" => $tab === "tablon" ? GrupoModel::anuncios($c, $grupoId) : [],
            "material" => $tab === "material" ? GrupoModel::material($c, $grupoId) : [],
            "tareasDisponibles" => $tareasDisponibles,
            "tareas"   => $tab === "tareas" && !$tarea ? TareaModel::tareasEstudiante($c, $grupoId, $this->usuarioId) : [],
            "tarea"    => $tarea,
            "entrega"  => $entrega,
            "adjuntosTarea"   => $tarea ? (TareaModel::adjuntos($c, "tarea", [(int) $tarea["id"]])[(int) $tarea["id"]] ?? []) : [],
            "adjuntosEntrega" => $entrega ? (TareaModel::adjuntos($c, "entrega", [(int) $entrega["id"]])[(int) $entrega["id"]] ?? []) : [],
        ]);
    }

    public function accionGrupo(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?page=grupos");
            exit;
        }
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $c = $this->conexion;
        $volver = function (string $tipo, string $mensaje, string $url = "index.php?page=grupos"): void {
            $_SESSION["flash_estudiante"] = ["tipo" => $tipo, "mensaje" => $mensaje];
            header("Location: " . $url);
            exit;
        };
        if (!GrupoModel::disponible($c)) {
            $volver("error", "Los grupos aún no están disponibles.");
        }

        $accion = (string) ($_POST["accion"] ?? "");

        if ($accion === "unirse") {
            // Pocos intentos: así nadie adivina códigos probando
            $clave = "grupo_codigo:usuario:" . $this->usuarioId;
            if (!LimiteIntentos::permitir($clave, 10, 900)) {
                $volver("error", "Probaste muchos códigos seguidos. Espera unos minutos e inténtalo de nuevo.");
            }
            $r = GrupoModel::unirse($c, (string) ($_POST["codigo"] ?? ""), $this->usuarioId);
            $volver($r["ok"] ? "exito" : "error", $r["mensaje"], $r["ok"] ? "index.php?page=grupo&id=" . $r["grupo_id"] : "index.php?page=grupos");
        }

        // El resto: tiene que estar en el grupo (y el grupo activo)
        $grupo = GrupoModel::paraEstudiante($c, (int) ($_POST["grupo_id"] ?? 0), $this->usuarioId);
        if (!$grupo) {
            $volver("error", "Ese grupo no existe, se archivó o ya no estás en él.");
        }
        $grupoId = (int) $grupo["id"];
        $anuncioId = (int) ($_POST["anuncio_id"] ?? 0);
        $base = "index.php?page=grupo&id=" . $grupoId;

        if ($accion === "salir") {
            GrupoModel::salir($c, $grupoId, $this->usuarioId);
            $volver("info", "Saliste del grupo «" . $grupo["nombre"] . "». Puedes volver con el código.");
        }
        if ($accion === "comentar") {
            if (!LimiteIntentos::permitir("grupo_comentario:usuario:" . $this->usuarioId, 30, 600)) {
                $volver("error", "Escribiste muchos comentarios seguidos. Espera unos minutos.", $base . "#anuncio-" . $anuncioId);
            }
            $r = GrupoModel::comentar($c, $grupoId, $anuncioId, $this->usuarioId, (string) ($_POST["texto"] ?? ""));
            $volver($r === true ? "exito" : "error", $r === true ? "Comentario publicado." : $r, $base . "#anuncio-" . $anuncioId);
        }
        if ($accion === "borrar_comentario") {
            // Solo los comentarios propios
            $ok = GrupoModel::borrarComentario($c, $grupoId, (int) ($_POST["comentario_id"] ?? 0), $this->usuarioId);
            $volver($ok ? "exito" : "error", $ok ? "Comentario borrado." : "Solo puedes borrar tus propios comentarios.", $base . "#anuncio-" . $anuncioId);
        }
        if ($accion === "entregar") {
            $tarea = TareaModel::disponible($c) ? TareaModel::deGrupo($c, (int) ($_POST["tarea_id"] ?? 0), $grupoId) : null;
            if (!$tarea) {
                $volver("error", "Esa tarea ya no existe.", $base . "&tab=tareas");
            }
            $urlTarea = $base . "&tab=tareas&tarea=" . (int) $tarea["id"];
            if (!LimiteIntentos::permitir("tarea_entrega:usuario:" . $this->usuarioId, 30, 600)) {
                $volver("error", "Enviaste muchas veces seguidas. Espera unos minutos.", $urlTarea);
            }
            try {
                $r = TareaModel::entregar($c, $grupo, $tarea, $this->usuarioId, (string) ($_POST["texto"] ?? ""), $_FILES["archivos"] ?? null);
            } catch (Throwable $e) {
                error_log("[BiGlobal][entregar] " . $e->getMessage());
                $r = "No pudimos guardar tu entrega. Inténtalo de nuevo.";
            }
            $volver($r === true ? "exito" : "error", $r === true ? "¡Entrega enviada! Tu docente ya recibió el aviso." : $r, $urlTarea);
        }
        $volver("error", "Acción no válida.", $base);
    }

    /* =========================================================
       RUTA DE APRENDIZAJE
    ========================================================= */

    /** Para el inicio: idioma reciente y su siguiente lección (o null si no hay ruta). */
    private function resumenRuta(): ?array
    {
        $c = $this->conexion;
        if (!RutaModel::disponible($c)) {
            return null;
        }
        $idiomas = RutaModel::idiomas($c, true);
        if (!$idiomas) {
            return null;
        }
        $reciente = RutaModel::idiomaReciente($c, $this->usuarioId);
        $idioma = $idiomas[0];
        foreach ($idiomas as $i) {
            if ((int) $i["id"] === $reciente) {
                $idioma = $i;
            }
        }
        return [
            "idioma"    => $idioma,
            "empezo"    => $reciente !== null,
            "siguiente" => RutaModel::siguiente($c, (int) $idioma["id"], $this->usuarioId, Plan::esPremium($c, $this->usuarioId)),
        ];
    }

    public function ruta(): void
    {
        $c = $this->conexion;
        $disponible = RutaModel::disponible($c);
        $idiomas = $disponible ? RutaModel::idiomas($c, true) : [];
        $datos = ["disponible" => $disponible, "idiomas" => $idiomas, "idioma" => null, "mapa" => [], "nivelActivo" => null];

        if ($idiomas) {
            $pedido = strtoupper(trim((string) ($_GET["idioma"] ?? "")));
            $reciente = RutaModel::idiomaReciente($c, $this->usuarioId);
            $idioma = $idiomas[0];
            foreach ($idiomas as $i) {
                if ($pedido !== "" ? strtoupper($i["codigo"]) === $pedido : (int) $i["id"] === $reciente) {
                    $idioma = $i;
                }
            }
            $mapa = RutaModel::mapa($c, (int) $idioma["id"], $this->usuarioId, Plan::esPremium($c, $this->usuarioId));

            // Nivel que se muestra: el pedido, o el que tiene la siguiente lección
            $nivelPedido = strtoupper(trim((string) ($_GET["nivel"] ?? "")));
            $nivelActivo = $mapa[0]["codigo"] ?? null;
            foreach ($mapa as $n) {
                if ($nivelPedido !== "" && $n["codigo"] === $nivelPedido) {
                    $nivelActivo = $n["codigo"];
                    break;
                }
                if ($nivelPedido === "" && $n["estado"] === "abierto" && $n["hechas"] < $n["jugables"]) {
                    $nivelActivo = $n["codigo"];
                    break;
                }
            }
            $datos = ["idioma" => $idioma, "mapa" => $mapa, "nivelActivo" => $nivelActivo] + $datos;
        }
        $this->render("ruta", "ruta", "Mi ruta", $datos);
    }

    public function rutaLeccion(): void
    {
        $c = $this->conexion;
        $leccion = RutaModel::disponible($c) ? RutaModel::leccion($c, (int) ($_GET["id"] ?? 0)) : null;
        $volver = function (string $tipo, string $mensaje, string $url = "index.php?page=ruta"): void {
            $_SESSION["flash_estudiante"] = ["tipo" => $tipo, "mensaje" => $mensaje];
            header("Location: " . $url);
            exit;
        };
        if (!$leccion || (int) $leccion["publicado"] !== 1 || (int) $leccion["unidad_publicada"] !== 1 || (int) $leccion["nivel_publicado"] !== 1) {
            $volver("error", "Esa lección no existe o todavía no está publicada.");
        }
        $urlRuta = "index.php?page=ruta&idioma=" . rawurlencode($leccion["idioma_codigo"]) . "&nivel=" . rawurlencode($leccion["nivel_codigo"]);

        $estado = RutaModel::estadoLeccion($c, $leccion, $this->usuarioId, Plan::esPremium($c, $this->usuarioId));
        if ($estado === "premium") {
            $volver("info", "El nivel " . $leccion["nivel_codigo"] . " es parte de Premium.", "index.php?page=plan");
        }
        if ($estado === "bloqueada") {
            $volver("info", "Primero completa las lecciones anteriores.", $urlRuta);
        }
        if (!in_array($estado, ["disponible", "completada"], true)) {
            $volver("info", "Esa lección está en preparación.", $urlRuta);
        }

        $ejercicios = RutaModel::ejercicios($c, (int) $leccion["id"], true);
        RutaModel::iniciarIntento((int) $leccion["id"], $ejercicios);
        $this->render("ruta_leccion", "ruta", $leccion["titulo"], [
            "leccion"    => $leccion,
            "urlRuta"    => $urlRuta,
            "video"      => Video::reproductor($leccion["video_url"]),
            "ejercicios" => array_map(fn($e) => RutaModel::vistaPublica($e, (string) $leccion["idioma_codigo"]), $ejercicios),
            "repaso"     => $estado === "completada",
        ]);
    }

    /** POST (fetch) con una respuesta. Contesta JSON. */
    public function rutaResponder(): void
    {
        header("Content-Type: application/json; charset=utf-8");
        header("Cache-Control: no-store");
        $responder = function (array $datos, int $codigo = 200): void {
            http_response_code($codigo);
            echo json_encode($datos, JSON_UNESCAPED_UNICODE);
            exit;
        };
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || !RutaModel::disponible($this->conexion)) {
            $responder(["error" => "Petición no válida."], 405);
        }
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");
        if (!LimiteIntentos::permitir("ruta:usuario:" . $this->usuarioId, 400, 600)) {
            $responder(["error" => "Vas muy rápido. Espera unos minutos."], 429);
        }

        $respuesta = $_POST["respuesta"] ?? "";
        if (isset($_POST["respuesta_json"])) {
            $json = (string) $_POST["respuesta_json"];
            $respuesta = strlen($json) <= 4000 ? json_decode($json, true) : null;
        }
        if (is_string($respuesta) && mb_strlen($respuesta) > 500) {
            $respuesta = mb_substr($respuesta, 0, 500);
        }

        try {
            $r = RutaModel::responder($this->conexion, $this->usuarioId, (int) ($_POST["leccion_id"] ?? 0), (int) ($_POST["ejercicio_id"] ?? 0), $respuesta);
        } catch (Throwable $e) {
            error_log("[BiGlobal][ruta] " . $e->getMessage());
            $responder(["error" => "No pudimos guardar tu respuesta. Inténtalo de nuevo."], 500);
        }
        $responder($r, isset($r["error"]) ? 409 : 200);
    }

    /** Suscripción cancelada que todavía se puede volver a renovar sola. */
    private function suscripcionReactivable(): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT * FROM suscripciones
            WHERE usuario_id = ? AND estado = 'cancelada' AND fin > NOW() AND fuente_pago_id IS NOT NULL
            ORDER BY fin DESC LIMIT 1
        ");
        $stmt->execute([$this->usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** https://biglobal.onrender.com (APP_URL o, si falta, la dirección actual). */
    private function urlSitio(): string
    {
        $base = obtenerConfig()["app"]["url"] ?? "";
        if ($base !== "") {
            return $base;
        }
        $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
        return ($https ? "https" : "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost");
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
