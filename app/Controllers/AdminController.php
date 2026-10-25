<?php

require_once __DIR__ . "/../Models/AdminModel.php";
require_once __DIR__ . "/../Models/ContenidoModel.php";
require_once __DIR__ . "/../Models/QuizModel.php";
require_once __DIR__ . "/../Models/ClaseModel.php";
require_once __DIR__ . "/../Models/GrupoModel.php";
require_once __DIR__ . "/../Models/DocenteSolicitudModel.php";
require_once __DIR__ . "/../Models/RevisionModel.php";
require_once __DIR__ . "/../Models/RutaModel.php";
require_once __DIR__ . "/../Models/RutaEstadisticasModel.php";
require_once __DIR__ . "/../Models/InsigniaModel.php";
require_once __DIR__ . "/../Models/VideosLscModel.php";

/**
 * AdminController
 *
 * Panel de administración (src/admin/index.php?page=...).
 * Entran el admin y el super administrador (protegerRol("admin") deja
 * pasar a ambos). Lo exclusivo del super administrador está en
 * SuperAdminController y se valida otra vez en el servidor.
 *
 * Páginas: inicio, usuarios, cursos, idiomas, lecciones, actividades,
 *          preguntas (de una actividad), solicitudes, ruta (ruta de aprendizaje), videos (de LSC), insignias,
 *          clases, grupos, estadisticas (de la ruta), perfil.
 * POST:    accion=crear (modal "Nuevo"), accion=editar, accion=eliminar,
 *          accion=guardar_pregunta, accion=eliminar_pregunta, accion=abrir_clase,
 *          accion=gratuito_grupo
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
            "revisiones" => RevisionModel::contarPendientes($this->c),
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

    /** Clases en vivo de todos los docentes (el admin supervisa y puede cancelar). */
    public function clases(): void
    {
        $disponible = ClaseModel::disponible($this->c);
        $this->render("clases", "clases", "Clases en vivo", [
            "disponible" => $disponible,
            "clases"     => $disponible ? ClaseModel::listado($this->c) : [],
        ]);
    }

    /** Grupos de Classroom de todos los docentes: el admin decide cuáles son gratuitos. */
    public function grupos(): void
    {
        $disponible = GrupoModel::disponible($this->c);
        $this->render("grupos", "grupos", "Grupos", [
            "disponible" => $disponible,
            "grupos"     => $disponible ? GrupoModel::listadoAdmin($this->c) : [],
        ]);
    }

    /** Marca o quita "gratuito para todos" en un grupo (solo el admin). */
    public function gratuitoGrupo(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");
        $volver = "index.php?page=grupos";
        $grupo = GrupoModel::disponible($this->c) ? GrupoModel::porId($this->c, (int) ($_POST["grupo_id"] ?? 0)) : null;
        if (!$grupo) {
            Panel::volver($volver, "error", "El grupo no existe.");
        }
        $gratuito = (int) ($_POST["gratuito"] ?? 0) === 1;
        GrupoModel::marcarGratuito($this->c, (int) $grupo["id"], $gratuito);
        Auditoria::registrar("grupo.gratuito", "grupo", (int) $grupo["id"], $grupo["nombre"],
            (int) $grupo["gratuito"] === 1 ? "gratuito para todos" : "solo Premium", $gratuito ? "gratuito para todos" : "solo Premium");
        Panel::volver($volver, "ok", $gratuito
            ? "El grupo «" . $grupo["nombre"] . "» ahora es gratuito para todos."
            : "El grupo «" . $grupo["nombre"] . "» vuelve a ser solo para Premium. Los miembros del plan Gratuito no lo ven hasta tener Premium.");
    }

    public function cancelarClase(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");
        $volver = "index.php?page=clases";
        $clase = ClaseModel::disponible($this->c) ? ClaseModel::porId($this->c, (int) ($_POST["clase_id"] ?? 0)) : null;
        if (!$clase || $clase["estado"] === "cancelada") {
            Panel::volver($volver, "error", "La clase no existe o ya estaba cancelada.");
        }
        $avisados = ClaseModel::cancelar($this->c, (int) $clase["id"], (string) ($_POST["motivo"] ?? ""));
        Auditoria::registrar("clase.cancelar", "clase", (int) $clase["id"], $clase["titulo"] . " · " . ClaseModel::fechaHora($clase["inicio"]));
        Panel::volver($volver, "ok", "Clase cancelada. Avisamos a " . plural_panel($avisados, "estudiante", "estudiantes") . ".");
    }

    /* =========================================================
       ESTADÍSTICAS DE LA RUTA (solo lectura)
       ?page=estadisticas[&idioma=ID][&nivel=ID][&periodo=30|90|todo]
    ========================================================= */
    public function estadisticas(): void
    {
        $c = $this->c;
        $disponible = RutaModel::disponible($c) && RutaEstadisticasModel::disponible($c);
        $idiomas = $disponible ? RutaModel::idiomas($c, true) : [];
        $idiomaId = (int) ($_GET["idioma"] ?? 0);
        if (!in_array($idiomaId, array_map("intval", array_column($idiomas, "id")), true)) {
            $idiomaId = (int) ($idiomas[0]["id"] ?? 0);
        }
        $niveles = $idiomaId ? RutaModel::niveles($c, $idiomaId, true) : [];
        $nivelId = (int) ($_GET["nivel"] ?? 0);
        if (!in_array($nivelId, array_map("intval", array_column($niveles, "id")), true)) {
            $nivelId = 0;   // otro idioma o un nivel que no existe: todos
        }
        $periodo = (string) ($_GET["periodo"] ?? "30");
        if (!isset(RutaEstadisticasModel::PERIODOS[$periodo])) {
            $periodo = "30";
        }
        $nivel = $nivelId ?: null;
        $hay = $disponible && $idiomaId;
        $this->render("estadisticas_ruta", "ruta", "Estadísticas de la ruta", [
            "disponible" => $disponible,
            "idiomas"    => $idiomas,
            "idiomaId"   => $idiomaId,
            "niveles"    => $niveles,
            "nivelId"    => $nivelId,
            "periodo"    => $periodo,
            "resumen"    => $hay ? RutaEstadisticasModel::resumen($c, $idiomaId, $nivel, $periodo) : ["activos7" => 0, "activos30" => 0, "empezadas" => 0, "terminadas" => 0, "abandonadas" => 0, "abandono" => null],
            "dificiles"  => $hay ? RutaEstadisticasModel::ejerciciosDificiles($c, $idiomaId, $nivel, $periodo) : [],
            "abandono"   => $hay ? RutaEstadisticasModel::leccionesAbandono($c, $idiomaId, $nivel, $periodo) : [],
            "avance"     => $hay ? RutaEstadisticasModel::avancePorNivel($c, $idiomaId) : [],
        ]);
    }

    /* =========================================================
       RUTA DE APRENDIZAJE
       ?page=ruta[&idioma=ID][&nivel=ID]          -> unidades y lecciones del nivel
       ?page=ruta&leccion=ID[&ejercicio=ID]       -> ejercicios de la lección
       Los cambios van por POST a src/admin/ruta_acciones.php
    ========================================================= */
    public function ruta(): void
    {
        $c = $this->c;
        $datos = ["disponible" => RutaModel::disponible($c), "idiomas" => [], "niveles" => [], "nivel" => null,
                  "arbol" => [], "leccion" => null, "ejercicios" => [], "ejercicioEditar" => null, "pendientesVideo" => []];
        if (!$datos["disponible"]) {
            $this->render("ruta", "ruta", "Ruta de aprendizaje", $datos);
            return;
        }

        $leccion = (int) ($_GET["leccion"] ?? 0) > 0 ? RutaModel::leccion($c, (int) $_GET["leccion"]) : null;
        if (isset($_GET["leccion"]) && !$leccion) {
            Panel::volver("index.php?page=ruta", "error", "La lección ya no existe.");
        }
        $nivel = $leccion ? RutaModel::nivel($c, (int) $leccion["nivel_id"]) : null;

        $datos["idiomas"] = RutaModel::idiomas($c, false);
        $idiomaId = $nivel ? (int) $nivel["idioma_id"] : (int) ($_GET["idioma"] ?? 0);
        if (!in_array($idiomaId, array_map("intval", array_column($datos["idiomas"], "id")), true)) {
            $idiomaId = (int) ($datos["idiomas"][0]["id"] ?? 0);
        }
        $datos["idiomaId"] = $idiomaId;
        $datos["niveles"] = $idiomaId ? RutaModel::niveles($c, $idiomaId, false) : [];

        if (!$nivel) {
            $pedido = (int) ($_GET["nivel"] ?? 0);
            foreach ($datos["niveles"] as $n) {
                if ((int) $n["id"] === $pedido) {
                    $nivel = RutaModel::nivel($c, $pedido);
                }
            }
            if (!$nivel && $datos["niveles"]) {
                $nivel = RutaModel::nivel($c, (int) $datos["niveles"][0]["id"]);
            }
        }
        $datos["nivel"] = $nivel;

        if ($leccion) {
            $datos["leccion"] = $leccion;
            $datos["ejercicios"] = RutaModel::ejercicios($c, (int) $leccion["id"], false);
            $editar = (int) ($_GET["ejercicio"] ?? 0);
            foreach ($datos["ejercicios"] as $e) {
                if ((int) $e["id"] === $editar) {
                    $datos["ejercicioEditar"] = $e;
                }
            }
        } elseif ($nivel) {
            $datos["arbol"] = RutaModel::arbolAdmin($c, (int) $nivel["id"]);
            if (strtoupper((string) $nivel["idioma_codigo"]) === "LSC") {
                $datos["pendientesVideo"] = RutaModel::pendientesVideo($c, $idiomaId);
            }
        }
        $this->render("ruta", "ruta", $leccion ? $leccion["titulo"] : "Ruta de aprendizaje", $datos);
    }

    /* =========================================================
       VIDEOS DE LSC
       ?page=videos  (descarga, subida y vista previa en src/admin/videos_acciones.php)
    ========================================================= */
    public function videos(): void
    {
        $this->render("videos", "ruta", "Videos de LSC", [
            "disponible" => RutaModel::disponible($this->c),
            "resumen"    => RutaModel::disponible($this->c) ? VideosLscModel::resumen($this->c) : [],
            "previa"     => $_SESSION["videos_lsc"] ?? null,
        ]);
    }

    /* =========================================================
       INSIGNIAS
       ?page=insignias[&editar=ID]  (los cambios van a src/admin/insignias_acciones.php)
    ========================================================= */
    public function insignias(): void
    {
        $c = $this->c;
        $disponible = InsigniaModel::disponible($c);
        $editar = null;
        if ($disponible && (int) ($_GET["editar"] ?? 0) > 0) {
            $editar = InsigniaModel::porId($c, (int) $_GET["editar"]);
        }
        $form = $_SESSION["insignia_form"] ?? null;   // lo que escribió si hubo un error
        unset($_SESSION["insignia_form"]);
        $this->render("insignias", "insignias", "Insignias", [
            "disponible" => $disponible,
            "insignias"  => $disponible ? InsigniaModel::listado($c) : [],
            "editar"     => $editar,
            "form"       => $form ?? $editar ?? ["icono" => "fa-star", "color" => "azul", "categoria" => "ruta", "condicion" => "ruta_lecciones", "meta" => 1, "orden" => 100],
            "niveles"    => RutaModel::disponible($c) ? $c->query("
                SELECT n.idioma_id, n.codigo, i.nombre AS idioma FROM ruta_niveles n
                INNER JOIN idiomas i ON i.id = n.idioma_id ORDER BY i.nombre, n.orden
            ")->fetchAll(PDO::FETCH_ASSOC) : [],
        ]);
    }

    /* =========================================================
       REVISIÓN DE CONTENIDO (cursos y lecciones de los docentes)
    ========================================================= */
    public function revision(): void
    {
        $this->render("revision", "revision", "Revisión de contenido", [
            "disponible" => RevisionModel::disponible($this->c),
            "pendientes" => RevisionModel::pendientes($this->c),
        ]);
    }

    public function revisar(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");
        $volver = "index.php?page=revision";
        $tipo = (string) ($_POST["tipo"] ?? "");
        $id = (int) ($_POST["id"] ?? 0);
        $decision = (string) ($_POST["decision"] ?? "");

        if (!RevisionModel::disponible($this->c) || !isset(RevisionModel::TABLAS[$tipo]) || $id <= 0) {
            Panel::volver($volver, "error", "No se encontró el contenido.");
        }
        $fila = RevisionModel::fila($this->c, $tipo, $id);
        $error = RevisionModel::revisar($this->c, $tipo, $id, $decision, (string) ($_POST["comentario"] ?? ""), (int) $_SESSION["usuario_id"]);
        if ($error) {
            Panel::volver($volver, "error", $error);
        }

        $nombre = ($tipo === "curso" ? "Curso: " : "Lección: ") . ($fila[$tipo === "curso" ? "nombre" : "titulo"] ?? "#{$id}");
        $despues = ["aprobar" => "publicado", "cambios" => "cambios solicitados", "rechazar" => "rechazado"][$decision];
        Auditoria::registrar("contenido.revision", $tipo, $id, $nombre, "en revisión", $despues);
        Panel::volver($volver, "ok", [
            "aprobar"  => "Aprobado: ya lo ven los estudiantes. Le avisamos al docente.",
            "cambios"  => "Pediste cambios. Le enviamos tu comentario al docente.",
            "rechazar" => "Rechazado. Le enviamos tu comentario al docente.",
        ][$decision]);
    }

    /** Marca o quita "gratuita para todos". Al marcarla se invita al plan Gratuito. */
    public function abrirClase(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");
        $volver = "index.php?page=clases";
        $clase = ClaseModel::disponible($this->c) ? ClaseModel::porId($this->c, (int) ($_POST["clase_id"] ?? 0)) : null;
        if (!$clase || $clase["estado"] === "cancelada" || (int) $clase["terminada"] === 1) {
            Panel::volver($volver, "error", "La clase no existe, se canceló o ya terminó.");
        }
        $abrir = (int) ($_POST["abierta"] ?? 0) === 1;
        $invitados = ClaseModel::marcarAbierta($this->c, (int) $clase["id"], $abrir);
        Auditoria::registrar("clase.abierta", "clase", (int) $clase["id"], $clase["titulo"] . " · " . ClaseModel::fechaHora($clase["inicio"]),
            (int) $clase["abierta_para_todos"] === 1 ? "gratuita para todos" : "solo Premium", $abrir ? "gratuita para todos" : "solo Premium");
        Panel::volver($volver, "ok", $abrir
            ? "La clase ahora es gratuita para todos." . ($invitados ? " Invitamos a " . plural_panel($invitados, "estudiante", "estudiantes") . " del plan Gratuito." : "")
            : "La clase vuelve a ser solo para Premium. Los inscritos del plan Gratuito no pueden entrar.");
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
        $pendientes = AdminModel::docentesPorEstado($this->c, 2);
        $rechazados = AdminModel::docentesPorEstado($this->c, 0);
        $ids = array_merge(array_column($pendientes, "id"), array_column($rechazados, "id"));

        $this->render("solicitudes", "solicitudes", "Solicitudes de docentes", [
            "pendientes"  => $pendientes,
            "aprobados"   => AdminModel::docentesPorEstado($this->c, 1),
            "rechazados"  => $rechazados,
            // Idioma, motivo y documentos de cada docente (vacío si aún no existe la tabla)
            "solicitudes" => DocenteSolicitudModel::solicitudesDe($this->c, $ids),
            "documentos"  => DocenteSolicitudModel::documentosDeVarios($this->c, array_column($pendientes, "id")),
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
        RevisionModel::alGuardarAdmin($this->c, "curso", (int) $this->c->lastInsertId());

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
        if ($cursoId <= 0) {
            Panel::volver($volver, "error", "El curso y el título son obligatorios.");
        }
        $datos = ContenidoModel::datosLeccion($_POST);
        if (is_string($datos)) {
            Panel::volver($volver, "error", $datos);
        }

        $stmt = $this->c->prepare("
            INSERT INTO lecciones (curso_id, titulo, descripcion, contenido, video_url, orden, estado)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$cursoId, $datos["titulo"], $datos["descripcion"], $datos["contenido"], $datos["video_url"], $datos["orden"]]);
        RevisionModel::alGuardarAdmin($this->c, "leccion", (int) $this->c->lastInsertId());
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
                    RevisionModel::alGuardarAdmin($this->c, "curso", $id);
                    Panel::volver($volver, "ok", "Curso actualizado.");

                case "lecciones":
                    $datos = ContenidoModel::datosLeccion($_POST);
                    if (is_string($datos)) Panel::volver($volver, "error", $datos);
                    $cursoId = (int) ($_POST["curso_id"] ?? 0);
                    if (!in_array($cursoId, array_map("intval", array_column(AdminModel::opcionesCursos($this->c), "id")), true)) {
                        Panel::volver($volver, "error", "Elige un curso de la lista.");
                    }
                    ContenidoModel::actualizarLeccion($this->c, $id, $datos, $cursoId);
                    RevisionModel::alGuardarAdmin($this->c, "leccion", $id);
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
