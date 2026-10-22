<?php

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../app/Models/ContenidoModel.php";
require_once __DIR__ . "/../../app/Models/QuizModel.php";
require_once __DIR__ . "/../../app/Models/ClaseModel.php";

protegerRol("docente");
require_once __DIR__ . "/verificar_aprobado.php";

$conexion = Conexion::conectar();
$docenteId = (int) $_SESSION["usuario_id"];

/* Redirigir con mensaje */
function volverCon(string $tipo, string $mensaje, string $url = "index.php?page=cursos"): void
{
    $_SESSION["flash_docente"] = [
        "tipo"    => $tipo,
        "mensaje" => $mensaje,
    ];
    header("Location: " . $url);
    exit;
}

verificarCsrfFormulario($_POST["csrf_token"] ?? $_GET["csrf_token"] ?? "");

$accion = $_POST["accion"] ?? "";

try {

    switch ($accion) {

        /* =========================================================
           CREAR CURSO
        ========================================================= */
        case "crear_curso":

            $nombre      = trim($_POST["nombre"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            $nivel       = trim($_POST["nivel"] ?? "");

            if ($nombre === "" || $nivel === "") {
                volverCon("error", "Nombre y nivel son obligatorios.");
            }
            [$idiomaId, $errorIdioma] = ContenidoModel::idiomaElegido($conexion, $_POST["idioma_id"] ?? "");
            if ($errorIdioma) {
                volverCon("error", $errorIdioma);
            }

            $stmt = $conexion->prepare("
                INSERT INTO cursos (nombre, descripcion, nivel, idioma_id, instructor_id, estado)
                VALUES (:nombre, :descripcion, :nivel, :idioma, :instructor, 1)
            ");
            $stmt->execute([
                ":nombre"      => $nombre,
                ":descripcion" => $descripcion,
                ":nivel"       => $nivel,
                ":idioma"      => $idiomaId,
                ":instructor"  => $docenteId,
            ]);

            /* Aviso al equipo de administración (campana + correo) */
            Notificador::avisar(
                "nuevo_curso",
                "Nuevo curso: " . $nombre,
                "Lo creó el docente " . ($_SESSION["nombre"] ?? "") . " desde su panel.",
                "admin/index.php?page=cursos"
            );

            volverCon("ok", "Curso creado correctamente.");

        /* =========================================================
           CREAR LECCIÓN
        ========================================================= */
        case "crear_leccion":

            $cursoId = (int) ($_POST["curso_id"] ?? 0);

            if ($cursoId <= 0) {
                volverCon("error", "El título es obligatorio.");
            }

            /* Mismas reglas que al editar (título, video de YouTube o Vimeo...) */
            $datos = ContenidoModel::datosLeccion($_POST);
            if (is_string($datos)) {
                volverCon("error", $datos, "index.php?page=lecciones&id=" . $cursoId);
            }

            /* Verificar que el curso pertenece al docente */
            $stmt = $conexion->prepare("SELECT id FROM cursos WHERE id = ? AND instructor_id = ? LIMIT 1");
            $stmt->execute([$cursoId, $docenteId]);

            if (!$stmt->fetch()) {
                volverCon("error", "El curso no existe o no es tuyo.");
            }

            $stmt = $conexion->prepare("
                INSERT INTO lecciones (curso_id, titulo, descripcion, contenido, video_url, orden, estado)
                VALUES (:curso_id, :titulo, :descripcion, :contenido, :video_url, :orden, 1)
            ");
            $stmt->execute([
                ":curso_id"    => $cursoId,
                ":titulo"      => $datos["titulo"],
                ":descripcion" => $datos["descripcion"],
                ":contenido"   => $datos["contenido"],
                ":video_url"   => $datos["video_url"],
                ":orden"       => $datos["orden"],
            ]);

            volverCon("ok", "Lección creada correctamente.", "index.php?page=lecciones&id=" . $cursoId);

        /* =========================================================
           ELIMINAR LECCIÓN
        ========================================================= */
        case "eliminar_leccion":

            $leccionId = (int) ($_POST["leccion_id"] ?? 0);

            if ($leccionId <= 0) {
                volverCon("error", "ID inválido.");
            }

            /* Verificar que la lección pertenece a un curso del docente */
            $stmt = $conexion->prepare("
                SELECT l.curso_id FROM lecciones l
                INNER JOIN cursos c ON l.curso_id = c.id
                WHERE l.id = ? AND c.instructor_id = ?
                LIMIT 1
            ");
            $stmt->execute([$leccionId, $docenteId]);
            $cursoId = $stmt->fetchColumn();

            if (!$cursoId) {
                volverCon("error", "La lección no existe o no es tuya.");
            }

            /* Solo si ningún estudiante ha avanzado (ver ContenidoModel) */
            $error = ContenidoModel::eliminarLeccion($conexion, $leccionId);
            volverCon($error ? "error" : "ok", $error ?? "Lección eliminada.", "index.php?page=lecciones&id=" . $cursoId);

        /* =========================================================
           ACCIÓN DESCONOCIDA
        ========================================================= */
                        /* =========================================================
           CREAR ACTIVIDAD
        ========================================================= */
        case "crear_actividad":

            $leccionId   = (int) ($_POST["leccion_id"] ?? 0);
            $titulo      = trim($_POST["titulo"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            $tipo        = trim($_POST["tipo"] ?? "");

            if ($leccionId <= 0 || $titulo === "" || $tipo === "") {
                volverCon("error", "Lección, título y tipo son obligatorios.");
            }
            if (!in_array($tipo, ContenidoModel::TIPOS_ACTIVIDAD, true)) {
                volverCon("error", "Elige un tipo de actividad de la lista.");
            }

            /* Verificar que la lección pertenece a un curso del docente */
            $stmt = $conexion->prepare("
                SELECT l.curso_id FROM lecciones l
                INNER JOIN cursos c ON l.curso_id = c.id
                WHERE l.id = ? AND c.instructor_id = ?
                LIMIT 1
            ");
            $stmt->execute([$leccionId, $docenteId]);
            $cursoId = (int) $stmt->fetchColumn();

            if (!$cursoId) {
                volverCon("error", "La lección no existe o no es tuya.");
            }

            $stmt = $conexion->prepare("
                INSERT INTO actividades (leccion_id, titulo, descripcion, tipo)
                VALUES (:leccion_id, :titulo, :descripcion, :tipo)
            ");
            $stmt->execute([
                ":leccion_id"  => $leccionId,
                ":titulo"      => $titulo,
                ":descripcion" => $descripcion,
                ":tipo"        => $tipo,
            ]);

            volverCon("ok", "Actividad creada correctamente.", "index.php?page=actividades&id=" . $cursoId);

        /* =========================================================
           ELIMINAR ACTIVIDAD
        ========================================================= */
        case "eliminar_actividad":

            $actividadId = (int) ($_POST["actividad_id"] ?? 0);
            $cursoId     = (int) ($_POST["curso_id"] ?? 0);

            if ($actividadId <= 0) {
                volverCon("error", "ID inválido.");
            }

            /* Verificar que la actividad pertenece a un curso del docente */
            $stmt = $conexion->prepare("
                SELECT a.id FROM actividades a
                INNER JOIN lecciones l ON a.leccion_id = l.id
                INNER JOIN cursos c ON l.curso_id = c.id
                WHERE a.id = ? AND c.instructor_id = ?
                LIMIT 1
            ");
            $stmt->execute([$actividadId, $docenteId]);

            if (!$stmt->fetch()) {
                volverCon("error", "La actividad no existe o no es tuya.");
            }

            /* Solo si ningún estudiante la ha respondido (ver ContenidoModel) */
            $error = ContenidoModel::eliminarActividad($conexion, $actividadId);
            volverCon($error ? "error" : "ok", $error ?? "Actividad eliminada.", "index.php?page=actividades&id=" . $cursoId);



        /* =========================================================
           EDITAR CURSO (solo si es del docente)
        ========================================================= */
        case "editar_curso":

            $cursoId = (int) ($_POST["curso_id"] ?? 0);
            if (!ContenidoModel::cursoDelDocente($conexion, $cursoId, $docenteId)) {
                volverCon("error", "El curso no existe o no es tuyo.");
            }

            $datos = ContenidoModel::datosCurso($_POST);
            if (is_string($datos)) {
                volverCon("error", $datos, "index.php?page=curso&id=" . $cursoId);
            }
            [$datos["idioma_id"], $errorIdioma] = ContenidoModel::idiomaElegido($conexion, $_POST["idioma_id"] ?? "");
            if ($errorIdioma) {
                volverCon("error", $errorIdioma, "index.php?page=curso&id=" . $cursoId);
            }

            ContenidoModel::actualizarCurso($conexion, $cursoId, $datos);
            volverCon("ok", "Curso actualizado.", "index.php?page=curso&id=" . $cursoId);

        /* =========================================================
           ELIMINAR CURSO (solo si es del docente y nadie ha avanzado)
        ========================================================= */
        case "eliminar_curso":

            $cursoId = (int) ($_POST["curso_id"] ?? 0);
            if (!ContenidoModel::cursoDelDocente($conexion, $cursoId, $docenteId)) {
                volverCon("error", "El curso no existe o no es tuyo.");
            }

            $error = ContenidoModel::eliminarCurso($conexion, $cursoId);
            if ($error) {
                volverCon("error", $error, "index.php?page=curso&id=" . $cursoId);
            }
            volverCon("ok", "Curso eliminado.");

        /* =========================================================
           EDITAR LECCIÓN (solo si es de un curso del docente)
        ========================================================= */
        case "editar_leccion":

            $leccionId = (int) ($_POST["leccion_id"] ?? 0);
            $cursoId = ContenidoModel::leccionDelDocente($conexion, $leccionId, $docenteId);
            if (!$cursoId) {
                volverCon("error", "La lección no existe o no es tuya.");
            }

            $datos = ContenidoModel::datosLeccion($_POST);
            if (is_string($datos)) {
                volverCon("error", $datos, "index.php?page=lecciones&id=" . $cursoId);
            }

            ContenidoModel::actualizarLeccion($conexion, $leccionId, $datos);
            volverCon("ok", "Lección actualizada.", "index.php?page=lecciones&id=" . $cursoId);

        /* =========================================================
           EDITAR ACTIVIDAD (solo si es de un curso del docente)
        ========================================================= */
        case "editar_actividad":

            $actividadId = (int) ($_POST["actividad_id"] ?? 0);
            $cursoId = ContenidoModel::actividadDelDocente($conexion, $actividadId, $docenteId);
            if (!$cursoId) {
                volverCon("error", "La actividad no existe o no es tuya.");
            }

            $datos = ContenidoModel::datosActividad($_POST);
            if (is_string($datos)) {
                volverCon("error", $datos, "index.php?page=actividades&id=" . $cursoId);
            }

            ContenidoModel::actualizarActividad($conexion, $actividadId, $datos);
            volverCon("ok", "Actividad actualizada.", "index.php?page=actividades&id=" . $cursoId);

        /* =========================================================
           PREGUNTAS DE UNA ACTIVIDAD (solo si es de un curso del docente)
        ========================================================= */
        case "guardar_pregunta":
        case "eliminar_pregunta":

            $actividadId = (int) ($_POST["actividad_id"] ?? 0);
            $preguntaId  = (int) ($_POST["pregunta_id"] ?? 0);

            if (!QuizModel::disponible($conexion)) {
                volverCon("error", "Las preguntas aún no están disponibles.", "index.php?page=actividades");
            }
            if (!ContenidoModel::actividadDelDocente($conexion, $actividadId, $docenteId)) {
                volverCon("error", "La actividad no existe o no es tuya.", "index.php?page=actividades");
            }

            $volver = "index.php?page=preguntas&actividad=" . $actividadId;

            /* La pregunta tiene que ser de esta misma actividad */
            if ($preguntaId > 0 && QuizModel::actividadDePregunta($conexion, $preguntaId) !== $actividadId) {
                volverCon("error", "No se encontró la pregunta.", $volver);
            }

            if ($accion === "eliminar_pregunta") {
                if ($preguntaId <= 0) {
                    volverCon("error", "No se encontró la pregunta.", $volver);
                }
                $error = QuizModel::eliminarPregunta($conexion, $actividadId, $preguntaId);
                volverCon($error ? "error" : "ok", $error ?? "Pregunta borrada.", $volver);
            }

            $actividad = QuizModel::actividad($conexion, $actividadId);
            if (!QuizModel::tipoConPreguntas($actividad["tipo"] ?? "")) {
                volverCon("error", "Solo las actividades de tipo " . implode(", ", QuizModel::TIPOS_CON_PREGUNTAS) . " llevan preguntas.", $volver);
            }

            $datos = QuizModel::datosPregunta($_POST);
            if (is_string($datos)) {
                volverCon("error", $datos, $volver);
            }

            QuizModel::guardarPregunta($conexion, $actividadId, $preguntaId, $datos);
            volverCon("ok", $preguntaId > 0 ? "Pregunta actualizada." : "Pregunta agregada.", $volver);

        /* =========================================================
           CLASES EN VIVO (solo las del docente)
        ========================================================= */
        case "crear_clase":
        case "editar_clase":
        case "cancelar_clase":

            if (!ClaseModel::disponible($conexion)) {
                volverCon("error", "Las clases en vivo aún no están disponibles.", "index.php?page=clases");
            }

            $claseId = (int) ($_POST["clase_id"] ?? 0);
            $clase = null;
            if ($accion !== "crear_clase") {
                $clase = ClaseModel::deDocente($conexion, $claseId, $docenteId);
                if (!$clase) {
                    volverCon("error", "La clase no existe o no es tuya.", "index.php?page=clases");
                }
            }
            $volverClase = "index.php?page=clases" . ($clase ? "&clase=" . $claseId : "");

            if ($accion === "cancelar_clase") {
                if ($clase["estado"] === "cancelada") {
                    volverCon("error", "Esta clase ya estaba cancelada.", $volverClase);
                }
                $avisados = ClaseModel::cancelar($conexion, $claseId, (string) ($_POST["motivo"] ?? ""));
                volverCon("ok", "Clase cancelada. Avisamos a " . plural_panel($avisados, "estudiante", "estudiantes") . ".", $volverClase);
            }

            $inscritos = 0;
            if ($clase) {
                if ($clase["estado"] === "cancelada") {
                    volverCon("error", "Una clase cancelada no se puede editar.", $volverClase);
                }
                $inscritos = count(array_filter(ClaseModel::participantes($conexion, $claseId), fn($p) => $p["estado"] === "inscrito"));
            }

            $datos = ClaseModel::datosClase($conexion, $_POST, $inscritos);
            if (is_string($datos)) {
                volverCon("error", $datos, $volverClase);
            }
            if (ClaseModel::yaPaso($conexion, $datos["inicio"]) && (!$clase || $clase["inicio"] !== $datos["inicio"])) {
                volverCon("error", "La fecha y hora de inicio deben ser en el futuro.", $volverClase);
            }

            if ($accion === "crear_clase") {
                $nuevaId = ClaseModel::crear($conexion, $docenteId, $datos);
                volverCon("ok", "Clase creada. Los estudiantes ya pueden inscribirse.", "index.php?page=clases&clase=" . $nuevaId);
            }

            ClaseModel::editar($conexion, $claseId, $datos);
            volverCon("ok", "Clase actualizada.", $volverClase);

        default:

            volverCon("error", "Acción no reconocida.");
    }

} catch (PDOException $e) {
    error_log("[BiGlobal][profesor/acciones] " . $e->getMessage());
    volverCon("error", "Error de base de datos. Intenta de nuevo.");
}