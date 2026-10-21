<?php

/**
 * Panel del docente.
 * La lógica (consultas) es la misma de antes; el HTML ahora usa el
 * layout común de los paneles (app/Views/panel/) y las vistas de
 * app/Views/profesor/.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/ContenidoModel.php";
require_once __DIR__ . "/../../app/Models/QuizModel.php";

protegerRol("docente");

/* Solo docentes APROBADOS: un docente pendiente (activo = 2) o rechazado
   (activo = 0) no puede entrar escribiendo la URL. */
require_once __DIR__ . "/verificar_aprobado.php";

$conexion = Conexion::conectar();
$docenteId = (int) $_SESSION["usuario_id"];

/* =========================================================
   DATOS DEL DOCENTE
========================================================= */
$stmt = $conexion->prepare("SELECT nombre, apellido, correo FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$docenteId]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    "nombre"   => $_SESSION["nombre"]   ?? "Docente",
    "apellido" => $_SESSION["apellido"] ?? "",
    "correo"   => $_SESSION["correo"]   ?? "",
];

$nombreDocente = trim($docente["nombre"] . " " . $docente["apellido"]);
$avatarDocente = strtoupper(substr($docente["nombre"] ?? "D", 0, 1));

/* =========================================================
   HELPER DE CONTEO
========================================================= */
function contarSeguroDoc(PDO $c, string $sql): int {
    try { return (int) ($c->query($sql)->fetch()["t"] ?? 0); }
    catch (PDOException $e) { return 0; }
}

/* =========================================================
   FLASH
========================================================= */
$flash = $_SESSION["flash_docente"] ?? null;
unset($_SESSION["flash_docente"]);

/* =========================================================
   PÁGINA ACTUAL
========================================================= */
$pagina = $_GET["page"] ?? "inicio";
$paginasValidas = ["inicio", "cursos", "curso", "lecciones", "actividades", "preguntas", "perfil"];
if (!in_array($pagina, $paginasValidas, true)) $pagina = "inicio";

$cursoIdActual = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

/* =========================================================
   ESTADÍSTICAS
========================================================= */
$totalCursos      = contarSeguroDoc($conexion, "SELECT COUNT(*) AS t FROM cursos WHERE instructor_id = $docenteId");
$totalLecciones   = contarSeguroDoc($conexion, "
    SELECT COUNT(*) AS t FROM lecciones l
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
");
$totalActividades = contarSeguroDoc($conexion, "
    SELECT COUNT(*) AS t FROM actividades a
    INNER JOIN lecciones l ON a.leccion_id = l.id
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
");

/* =========================================================
   LISTADOS
========================================================= */
$cursosDocente = $conexion->query("
    SELECT id, nombre, descripcion, nivel, fecha_creacion
    FROM cursos
    WHERE instructor_id = $docenteId
    ORDER BY fecha_creacion DESC
")->fetchAll(PDO::FETCH_ASSOC);

$leccionesDocente = $conexion->query("
    SELECT l.id, l.titulo, l.descripcion, l.orden, c.nombre AS curso, c.id AS curso_id
    FROM lecciones l
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
    ORDER BY c.nombre ASC, l.orden ASC
")->fetchAll(PDO::FETCH_ASSOC);

$actividadesDocente = $conexion->query("
    SELECT a.id, a.titulo, a.tipo, a.descripcion, l.titulo AS leccion, c.nombre AS curso
    FROM actividades a
    INNER JOIN lecciones l ON a.leccion_id = l.id
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
    ORDER BY a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   CURSO ACTUAL
========================================================= */
$cursoActual = null;
$leccionesCurso = [];

if ($cursoIdActual > 0) {

    $stmt = $conexion->prepare("
        SELECT * FROM cursos
        WHERE id = :id AND instructor_id = :docente
        LIMIT 1
    ");
    $stmt->execute([":id" => $cursoIdActual, ":docente" => $docenteId]);
    $cursoActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cursoActual) {
        $_SESSION["flash_docente"] = [
            "tipo" => "error",
            "mensaje" => "El curso no existe o no tienes permiso para verlo."
        ];
        header("Location: index.php?page=cursos");
        exit;
    }

    $stmt = $conexion->prepare("
        SELECT * FROM lecciones
        WHERE curso_id = :curso_id
        ORDER BY orden ASC, id ASC
    ");
    $stmt->execute([":curso_id" => $cursoIdActual]);
    $leccionesCurso = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================================================
   GRÁFICO
========================================================= */
$leccionesPorCurso = $conexion->query("
    SELECT c.nombre AS curso, COUNT(l.id) AS total
    FROM cursos c
    LEFT JOIN lecciones l ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
    GROUP BY c.id, c.nombre
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$labelsLecciones  = array_map(fn($r) => $r["curso"], $leccionesPorCurso);
$valoresLecciones = array_map(fn($r) => (int) $r["total"], $leccionesPorCurso);

/* =========================================================
   CSRF
========================================================= */
$csrfToken = generarCsrfToken();


/* =========================================================
   MOSTRAR LA PÁGINA (layout común de los paneles)
========================================================= */

// Los avisos de acciones.php llegan en flash_docente; el layout usa flash_panel
if ($flash) {
    $_SESSION["flash_panel"] = $flash;
}

$titulos = [
    "inicio"      => "Inicio",
    "cursos"      => "Mis cursos",
    "curso"       => $cursoActual["nombre"] ?? "Curso",
    "lecciones"   => "Lecciones",
    "actividades" => "Actividades",
    "preguntas"   => "Preguntas",
    "perfil"      => "Mi perfil",
];

/* Actividades del curso que se está viendo (antes esta consulta estaba en el HTML) */
$actividadesCurso = [];
if ($cursoActual && !empty($leccionesCurso)) {
    $stmt = $conexion->prepare("
        SELECT a.id, a.titulo, a.tipo, a.descripcion, l.titulo AS leccion
        FROM actividades a
        INNER JOIN lecciones l ON a.leccion_id = l.id
        WHERE l.curso_id = :curso
        ORDER BY a.id DESC
    ");
    $stmt->execute([":curso" => (int) $cursoActual["id"]]);
    $actividadesCurso = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// En el menú, "curso" se marca como "Mis cursos" y "preguntas" como "Actividades"
$activa = $pagina === "curso" ? "cursos" : ($pagina === "preguntas" ? "actividades" : $pagina);

/* Número de preguntas de cada actividad del docente (null = aún no existen las tablas) */
$numPreguntas = QuizModel::disponible($conexion)
    ? QuizModel::contarPreguntas($conexion, array_column($actividadesDocente, "id"))
    : null;

$idiomasActivos = ContenidoModel::idiomasActivos($conexion);

$datosVista = compact(
    "idiomasActivos",
    "docente", "nombreDocente", "totalCursos", "totalLecciones", "totalActividades",
    "cursosDocente", "leccionesDocente", "actividadesDocente",
    "cursoActual", "leccionesCurso", "actividadesCurso", "labelsLecciones", "valoresLecciones", "csrfToken",
    "numPreguntas"
);

/* Preguntas de una actividad (?page=preguntas&actividad=...): solo si es de un curso del docente */
if ($pagina === "preguntas") {
    $actividadId = (int) ($_GET["actividad"] ?? 0);
    $cursoDeActividad = ContenidoModel::actividadDelDocente($conexion, $actividadId, $docenteId);

    if ($numPreguntas === null || !$cursoDeActividad) {
        $_SESSION["flash_docente"] = [
            "tipo" => "error",
            "mensaje" => $numPreguntas === null
                ? "Las preguntas aún no están disponibles."
                : "La actividad no existe o no es tuya.",
        ];
        header("Location: index.php?page=actividades");
        exit;
    }

    $datosVista += [
        "actividad"      => QuizModel::actividad($conexion, $actividadId),
        "preguntas"      => QuizModel::preguntas($conexion, $actividadId),
        "intentos"       => QuizModel::contarIntentos($conexion, $actividadId),
        "volverUrl"      => "index.php?page=actividades&id=" . $cursoDeActividad,
        "formAction"     => "acciones.php",
        "accionGuardar"  => "guardar_pregunta",
        "accionEliminar" => "eliminar_pregunta",
    ];
    $vista = __DIR__ . "/../../app/Views/panel/preguntas.php";
} elseif ($pagina === "perfil") {
    require_once __DIR__ . "/../../app/Models/AdminModel.php";
    $datosVista["perfil"] = AdminModel::perfil($conexion, $docenteId);
    $datosVista["reglas"] = Seguridad::reglasPassword();
    $vista = __DIR__ . "/../../app/Views/admin/perfil.php";   // el mismo perfil del admin
} else {
    $vista = __DIR__ . "/../../app/Views/profesor/" . $pagina . ".php";
}

Panel::render($vista, [
    "titulo"   => $titulos[$pagina],
    "activa"   => $activa,
    "graficos" => $pagina === "inicio",
    "datos"    => $datosVista,
]);
