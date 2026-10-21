<?php

require_once __DIR__ . "/../Models/AdminModel.php";
require_once __DIR__ . "/../Models/ContenidoModel.php";
require_once __DIR__ . "/../Models/QuizModel.php";

/**
 * AdminController
 *
 * Panel de administración (src/admin/index.php?page=...).
 * Entran el admin y el super administrador (protegerRol("admin") deja
 * pasar a ambos). Lo exclusivo del super administrador está en
 * SuperAdminController y se valida otra vez en el servidor.
 *
 * Páginas: inicio, usuarios, cursos, idiomas, lecciones, actividades,
 *          preguntas (de una actividad), solicitudes, perfil.
 * POST:    accion=crear (modal "Nuevo"), accion=editar, accion=eliminar,
 *          accion=guardar_pregunta, accion=eliminar_pregunta
 */
class AdminController
{
    private PDO $c;

    public function __construct()
    {
        protegerRol("admin");
        $this->c = Conexion::conectar();
    }

    /** Opciones comunes de todas las páginas del admin. */
    private function render(string $vista, string $activa, string $titulo, array $datos = [], bool $graficos = false): void
    {
        Panel::render(__DIR__ . "/../Views/admin/{$vista}.php", [
            "titulo"     => $titulo,
            "activa"     => $activa,
            "graficos"   => $graficos,
            "pendientes" => AdminModel::solicitudesPendientes($this->c),
            "datos"      => $datos + [
                "esSuper"   => esSuperAdmin(),
                "modal"     => $this->datosModal(),
            ],
        ]);
    }

    /** Listas para los selects del modal "Nuevo". */
    private function datosModal(): array
    {
        return [
            "roles"     => AdminModel::rolesPermitidos($this->c, esSuperAdmin()),
            "cursos"    => AdminModel::opcionesCursos($this->c),
            "lecciones" => AdminModel::opcionesLecciones($this->c),
            "docentes"  => AdminModel::opcionesDocentes($this->c),
            "idiomas"   => ContenidoModel::idiomasActivos($this->c),
            "reglas"    => Seguridad::reglasPassword(),
        ];
    }

    /* =========================================================
       PÁGINAS
    ========================================================= */
    public function inicio(): void
    {
        $this->render("inicio", "inicio", "Inicio", [
            "stats"          => AdminModel::estadisticas($this->c),
            "cursosPorMes"   => AdminModel::cursosPorMes($this->c),
            "ultimosUsuarios"=> array_slice(AdminModel::usuarios($this->c, 5), 0, 5),
            "ultimosCursos"  => array_slice(AdminModel::cursos($this->c), 0, 5),
            "pendientesN"    => AdminModel::solicitudesPendientes($this->c),
        ], true);
    }

    public function usuarios(): void
    {
        $this->render("usuarios", "usuarios", "Usuarios", [
            "usuarios" => AdminModel::usuarios($this->c),
            "porRol"   => AdminModel::usuariosPorRol($this->c),
        ], true);
    }

    public function cursos(): void
    {
        $this->render("cursos", "cursos", "Cursos", [
            "cursos"   => AdminModel::cursos($this->c),
            "porNivel" => AdminModel::cursosPorNivel($this->c),
        ], true);
    }

    public function idiomas(): void
    {
        $this->render("idiomas", "idiomas", "Idiomas", [
            "idiomas"   => AdminModel::idiomas($this->c),
            "porEstado" => AdminModel::idiomasPorEstado($this->c),
        ], true);
    }

    public function lecciones(): void
    {
        $this->render("lecciones", "lecciones", "Lecciones", [
            "lecciones" => AdminModel::lecciones($this->c),
            "porCurso"  => AdminModel::leccionesPorCurso($this->c),
        ], true);
    }

    public function actividades(): void
    {
        $actividades = AdminModel::actividades($this->c);

        $this->render("actividades", "actividades", "Actividades", [
            "actividades"  => $actividades,
            "porTipo"      => AdminModel::actividadesPorTipo($this->c),
            // null = aún no se aplicó el SQL de preguntas (no se muestra el botón)
            "numPreguntas" => QuizModel::disponible($this->c)
                ? QuizModel::contarPreguntas($this->c, array_column($actividades, "id"))
                : null,
        ], true);
    }

    /** Preguntas de una actividad (?page=preguntas&id=...). */
    public function preguntas(): void
    {
        if (!QuizModel::disponible($this->c)) {
            Panel::volver("index.php?page=actividades", "error", "Las preguntas aún no están disponibles: falta aplicar database/actividades_resolubles.sql.");
        }

        $actividad = QuizModel::actividad($this->c, (int) ($_GET["id"] ?? 0));
        if (!$actividad) {
            Panel::volver("index.php?page=actividades", "error", "No se encontró la actividad.");
        }

        $this->render("../panel/preguntas", "actividades", "Preguntas", [
            "actividad"      => $actividad,
            "preguntas"      => QuizModel::preguntas($this->c, (int) $actividad["id"]),
            "intentos"       => QuizModel::contarIntentos($this->c, (int) $actividad["id"]),
            "volverUrl"      => "index.php?page=actividades",
            "formAction"     => "index.php",
            "accionGuardar"  => "guardar_pregunta",
            "accionEliminar" => "eliminar_pregunta",
        ]);
    }

    public function solicitudes(): void
    {
        $this->render("solicitudes", "solicitudes", "Solicitudes de docentes", [
            "pendientes" => AdminModel::docentesPorEstado($this->c, 2),
            "aprobados"  => AdminModel::docentesPorEstado($this->c, 1),
            "rechazados" => AdminModel::docentesPorEstado($this->c, 0),
        ]);
    }

    public function perfil(): void
    {
        $this->render("perfil", "perfil", "Mi perfil", [
            "perfil" => AdminModel::perfil($this->c, (int) $_SESSION["usuario_id"]),
            "reglas" => Seguridad::reglasPassword(),
        ]);
    }

    /* =========================================================
       CREAR REGISTROS (modal "Nuevo")
       El campo oculto se llama "registro" para no chocar con el
       campo "tipo" de las actividades (Quiz, Tarea...).
    ========================================================= */
    public function crear(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $registro = $_POST["registro"] ?? "";
        $paginas = ["usuarios", "cursos", "idiomas", "lecciones", "actividades"];
        $volver = "index.php?page=" . (in_array($registro, $paginas, true) ? $registro : "inicio");

        try {
            switch ($registro) {
                case "usuarios":    $this->crearUsuario($volver); break;
                case "cursos":      $this->crearCurso($volver); break;
                case "idiomas":     $this->crearIdioma($volver); break;
                case "lecciones":   $this->crearLeccion($volver); break;
                case "actividades": $this->crearActividad($volver); break;
                default: Panel::volver("index.php", "error", "Tipo de registro no válido.");
            }
        } catch (PDOException $e) {
            error_log("[BiGlobal][admin crear] " . $e->getMessage());
            Panel::volver($volver, "error", "No se pudo guardar. Revisa los datos e inténtalo de nuevo.");
        }
    }

    private function crearUsuario(string $volver): void
    {
        $nombre   = trim($_POST["nombre"] ?? "");
        $apellido = trim($_POST["apellido"] ?? "");
        $correo   = mb_strtolower(trim($_POST["correo"] ?? ""));
        $password = $_POST["password"] ?? "";
        $rolId    = (int) ($_POST["rol_id"] ?? 0);

        if ($nombre === "" || $apellido === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            Panel::volver($volver, "error", "Revisa nombre, apellido y correo.");
        }
        if (($error = Seguridad::validarPassword($password)) !== null) {
            Panel::volver($volver, "error", $error);
        }

        // El rol debe estar en la lista permitida PARA QUIEN CREA:
        // un admin no puede crear administradores aunque edite el formulario.
        $nombresRoles = array_column(AdminModel::rolesPermitidos($this->c, esSuperAdmin()), "nombre", "id");
        $permitidos = array_keys($nombresRoles);
        if (!in_array($rolId, array_map("intval", $permitidos), true)) {
            http_response_code(403);
            Panel::volver($volver, "error", "No tienes permiso para crear usuarios con ese rol.");
        }

        $stmt = $this->c->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->execute([$correo]);
        if ($stmt->fetch()) {
            Panel::volver($volver, "error", "Ya existe un usuario con ese correo.");
        }

        $stmt = $this->c->prepare("
            INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, activo, estado)
            VALUES (?, ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([$nombre, $apellido, $correo, password_hash($password, PASSWORD_DEFAULT), $rolId]);

        Auditoria::registrar("cuenta.crear", "cuenta", (int) $this->c->lastInsertId(),
            trim("{$nombre} {$apellido}") . " ({$correo})", null, $nombresRoles[$rolId] ?? (string) $rolId);

        Panel::volver($volver, "ok", "Usuario creado correctamente.");
    }

    private function crearCurso(string $volver): void
    {
        $nombre = trim($_POST["nombre"] ?? "");
        if ($nombre === "") {
            Panel::volver($volver, "error", "El nombre del curso es obligatorio.");
        }
        [$idiomaId, $errorIdioma] = ContenidoModel::idiomaElegido($this->c, $_POST["idioma_id"] ?? "");
        if ($errorIdioma) {
            Panel::volver($volver, "error", $errorIdioma);
        }

        $stmt = $this->c->prepare("
            INSERT INTO cursos (nombre, descripcion, nivel, idioma_id, instructor_id, estado)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $nombre,
            trim($_POST["descripcion"] ?? ""),
            trim($_POST["nivel"] ?? "") ?: null,
            $idiomaId,
            (int) ($_POST["instructor_id"] ?? 0) ?: null,
        ]);

        // Aviso al resto del equipo (quien lo creó no se avisa a sí mismo)
        Notificador::avisar(
            "nuevo_curso",
            "Nuevo curso: " . $nombre,
            "Lo creó " . ($_SESSION["nombre"] ?? "un administrador") . " desde el panel de administración.",
            "admin/index.php?page=cursos",
            (int) ($_SESSION["usuario_id"] ?? 0)
        );

        Panel::volver($volver, "ok", "Curso creado correctamente.");
    }

    private function crearIdioma(string $volver): void
    {
        $nombre = trim($_POST["nombre"] ?? "");
        $codigo = strtoupper(trim($_POST["codigo"] ?? ""));
        if ($nombre === "" || $codigo === "") {
            Panel::volver($volver, "error", "El nombre y el código son obligatorios.");
        }

        $stmt = $this->c->prepare("SELECT id FROM idiomas WHERE nombre = ? OR codigo = ? LIMIT 1");
        $stmt->execute([$nombre, $codigo]);
        if ($stmt->fetch()) {
            Panel::volver($volver, "error", "Ya existe un idioma con ese nombre o código.");
        }

        $stmt = $this->c->prepare("INSERT INTO idiomas (nombre, codigo, estado) VALUES (?, ?, 1)");
        $stmt->execute([$nombre, $codigo]);
        Panel::volver($volver, "ok", "Idioma creado correctamente.");
    }

    private function crearLeccion(string $volver): void
    {
        $cursoId = (int) ($_POST["curso_id"] ?? 0);
        $titulo  = trim($_POST["titulo"] ?? "");
        if ($cursoId <= 0 || $titulo === "") {
            Panel::volver($volver, "error", "El curso y el título son obligatorios.");
        }

        $stmt = $this->c->prepare("
            INSERT INTO lecciones (curso_id, titulo, descripcion, contenido, orden, estado)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $cursoId,
            $titulo,
            trim($_POST["descripcion"] ?? ""),
            trim($_POST["contenido"] ?? ""),
            max(1, (int) ($_POST["orden"] ?? 1)),
        ]);
        Panel::volver($volver, "ok", "Lección creada correctamente.");
    }

    private function crearActividad(string $volver): void
    {
        $leccionId = (int) ($_POST["leccion_id"] ?? 0);
        $titulo    = trim($_POST["titulo"] ?? "");
        if ($leccionId <= 0 || $titulo === "") {
            Panel::volver($volver, "error", "La lección y el título son obligatorios.");
        }
        if (!in_array(trim($_POST["tipo"] ?? ""), ContenidoModel::TIPOS_ACTIVIDAD, true)) {
            Panel::volver($volver, "error", "Elige un tipo de actividad de la lista.");
        }

        $stmt = $this->c->prepare("
            INSERT INTO actividades (leccion_id, titulo, descripcion, tipo)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $leccionId,
            $titulo,
            trim($_POST["descripcion"] ?? ""),
            trim($_POST["tipo"] ?? "") ?: null,
        ]);
        Panel::volver($volver, "ok", "Actividad creada correctamente.");
    }

    /* =========================================================
       EDITAR (modal "Editar" de cada tabla)
    ========================================================= */
    public function editar(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $registro = $_POST["registro"] ?? "";
        $id = (int) ($_POST["id"] ?? 0);
        $paginas = ["cursos", "idiomas", "lecciones", "actividades"];
        $volver = "index.php?page=" . (in_array($registro, $paginas, true) ? $registro : "inicio");

        if ($id <= 0 || !in_array($registro, $paginas, true)) {
            Panel::volver($volver, "error", "No se encontró el registro.");
        }

        try {
            switch ($registro) {
                case "cursos":
                    $datos = ContenidoModel::datosCurso($_POST);
                    if (is_string($datos)) Panel::volver($volver, "error", $datos);
                    [$datos["idioma_id"], $errorIdioma] = ContenidoModel::idiomaElegido($this->c, $_POST["idioma_id"] ?? "");
                    if ($errorIdioma) Panel::volver($volver, "error", $errorIdioma);
                    $docente = (int) ($_POST["instructor_id"] ?? 0);
                    if ($docente > 0 && !in_array($docente, array_map("intval", array_column(AdminModel::opcionesDocentes($this->c), "id")), true)) {
                        Panel::volver($volver, "error", "Elige un docente activo de la lista.");
                    }
                    ContenidoModel::actualizarCurso($this->c, $id, $datos, $docente ?: null);
                    Panel::volver($volver, "ok", "Curso actualizado.");

                case "lecciones":
                    $datos = ContenidoModel::datosLeccion($_POST);
                    if (is_string($datos)) Panel::volver($volver, "error", $datos);
                    $cursoId = (int) ($_POST["curso_id"] ?? 0);
                    if (!in_array($cursoId, array_map("intval", array_column(AdminModel::opcionesCursos($this->c), "id")), true)) {
                        Panel::volver($volver, "error", "Elige un curso de la lista.");
                    }
                    ContenidoModel::actualizarLeccion($this->c, $id, $datos, $cursoId);
                    Panel::volver($volver, "ok", "Lección actualizada.");

                case "actividades":
                    $datos = ContenidoModel::datosActividad($_POST);
                    if (is_string($datos)) Panel::volver($volver, "error", $datos);
                    $leccionId = (int) ($_POST["leccion_id"] ?? 0);
                    if (!in_array($leccionId, array_map("intval", array_column(AdminModel::opcionesLecciones($this->c), "id")), true)) {
                        Panel::volver($volver, "error", "Elige una lección de la lista.");
                    }
                    ContenidoModel::actualizarActividad($this->c, $id, $datos, $leccionId);
                    Panel::volver($volver, "ok", "Actividad actualizada.");

                case "idiomas":
                    $datos = ContenidoModel::datosIdioma($_POST);
                    if (is_string($datos)) Panel::volver($volver, "error", $datos);
                    $error = ContenidoModel::actualizarIdioma($this->c, $id, $datos);
                    Panel::volver($volver, $error ? "error" : "ok", $error ?? "Idioma actualizado.");
            }
        } catch (PDOException $e) {
            error_log("[BiGlobal][admin editar] " . $e->getMessage());
            Panel::volver($volver, "error", "No se pudo guardar. Revisa los datos e inténtalo de nuevo.");
        }
    }

    /* =========================================================
       ELIMINAR (curso y lección solo si nadie ha avanzado)
    ========================================================= */
    public function eliminar(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $registro = $_POST["registro"] ?? "";
        $id = (int) ($_POST["id"] ?? 0);
        $paginas = ["cursos", "lecciones", "actividades"];
        $volver = "index.php?page=" . (in_array($registro, $paginas, true) ? $registro : "inicio");

        if ($id <= 0 || !in_array($registro, $paginas, true)) {
            Panel::volver($volver, "error", "No se encontró el registro.");
        }

        try {
            switch ($registro) {
                case "cursos":
                    $error = ContenidoModel::eliminarCurso($this->c, $id);
                    Panel::volver($volver, $error ? "error" : "ok", $error ?? "Curso eliminado.");

                case "lecciones":
                    $error = ContenidoModel::eliminarLeccion($this->c, $id);
                    Panel::volver($volver, $error ? "error" : "ok", $error ?? "Lección eliminada.");

                case "actividades":
                    $error = ContenidoModel::eliminarActividad($this->c, $id);
                    Panel::volver($volver, $error ? "error" : "ok", $error ?? "Actividad eliminada.");
            }
        } catch (PDOException $e) {
            error_log("[BiGlobal][admin eliminar] " . $e->getMessage());
            Panel::volver($volver, "error", "No se pudo eliminar. Inténtalo de nuevo.");
        }
    }

    /* =========================================================
       PREGUNTAS DE UNA ACTIVIDAD (opción múltiple)
    ========================================================= */
    public function guardarPregunta(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        [$actividadId, $preguntaId, $volver] = $this->preguntaElegida();

        $actividad = QuizModel::actividad($this->c, $actividadId);
        if (!QuizModel::tipoConPreguntas($actividad["tipo"] ?? "")) {
            Panel::volver($volver, "error", "Solo las actividades de tipo " . implode(", ", QuizModel::TIPOS_CON_PREGUNTAS) . " llevan preguntas.");
        }

        $datos = QuizModel::datosPregunta($_POST);
        if (is_string($datos)) Panel::volver($volver, "error", $datos);

        try {
            QuizModel::guardarPregunta($this->c, $actividadId, $preguntaId, $datos);
        } catch (PDOException $e) {
            error_log("[BiGlobal][admin guardar pregunta] " . $e->getMessage());
            Panel::volver($volver, "error", "No se pudo guardar la pregunta. Inténtalo de nuevo.");
        }
        Panel::volver($volver, "ok", $preguntaId > 0 ? "Pregunta actualizada." : "Pregunta agregada.");
    }

    public function eliminarPregunta(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        [$actividadId, $preguntaId, $volver] = $this->preguntaElegida();
        if ($preguntaId <= 0) Panel::volver($volver, "error", "No se encontró la pregunta.");

        try {
            $error = QuizModel::eliminarPregunta($this->c, $actividadId, $preguntaId);
        } catch (PDOException $e) {
            error_log("[BiGlobal][admin eliminar pregunta] " . $e->getMessage());
            $error = "No se pudo borrar la pregunta. Inténtalo de nuevo.";
        }
        Panel::volver($volver, $error ? "error" : "ok", $error ?? "Pregunta borrada.");
    }

    /**
     * Lee actividad_id y pregunta_id del POST y revisa que existan y que
     * la pregunta sea de esa actividad. Devuelve [actividadId, preguntaId, urlVolver].
     */
    private function preguntaElegida(): array
    {
        if (!QuizModel::disponible($this->c)) {
            Panel::volver("index.php?page=actividades", "error", "Las preguntas aún no están disponibles.");
        }

        $actividadId = (int) ($_POST["actividad_id"] ?? 0);
        $preguntaId  = (int) ($_POST["pregunta_id"] ?? 0);

        if (!QuizModel::actividad($this->c, $actividadId)) {
            Panel::volver("index.php?page=actividades", "error", "No se encontró la actividad.");
        }

        $volver = "index.php?page=preguntas&id=" . $actividadId;
        if ($preguntaId > 0 && QuizModel::actividadDePregunta($this->c, $preguntaId) !== $actividadId) {
            Panel::volver($volver, "error", "No se encontró la pregunta.");
        }

        return [$actividadId, $preguntaId, $volver];
    }
}
