<?php

require_once __DIR__ . "/../../app/bootstrap.php";

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

            $stmt = $conexion->prepare("
                INSERT INTO cursos (nombre, descripcion, nivel, instructor_id, estado)
                VALUES (:nombre, :descripcion, :nivel, :instructor, 1)
            ");
            $stmt->execute([
                ":nombre"      => $nombre,
                ":descripcion" => $descripcion,
                ":nivel"       => $nivel,
                ":instructor"  => $docenteId,
            ]);

            volverCon("ok", "Curso creado correctamente.");

        /* =========================================================
           CREAR LECCIÓN
        ========================================================= */
        case "crear_leccion":

            $cursoId     = (int) ($_POST["curso_id"] ?? 0);
            $titulo      = trim($_POST["titulo"] ?? "");
            $descripcion = trim($_POST["descripcion"] ?? "");
            $contenido   = trim($_POST["contenido"] ?? "");
            $orden       = (int) ($_POST["orden"] ?? 1);

            if ($cursoId <= 0 || $titulo === "") {
                volverCon("error", "El título es obligatorio.");
            }

            /* Verificar que el curso pertenece al docente */
            $stmt = $conexion->prepare("SELECT id FROM cursos WHERE id = ? AND instructor_id = ? LIMIT 1");
            $stmt->execute([$cursoId, $docenteId]);

            if (!$stmt->fetch()) {
                volverCon("error", "El curso no existe o no es tuyo.");
            }

            $stmt = $conexion->prepare("
                INSERT INTO lecciones (curso_id, titulo, descripcion, contenido, orden, estado)
                VALUES (:curso_id, :titulo, :descripcion, :contenido, :orden, 1)
            ");
            $stmt->execute([
                ":curso_id"    => $cursoId,
                ":titulo"      => $titulo,
                ":descripcion" => $descripcion,
                ":contenido"   => $contenido,
                ":orden"       => max(1, $orden),
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

            $stmt = $conexion->prepare("DELETE FROM lecciones WHERE id = ?");
            $stmt->execute([$leccionId]);

            volverCon("ok", "Lección eliminada.", "index.php?page=lecciones&id=" . $cursoId);

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

            $stmt = $conexion->prepare("DELETE FROM actividades WHERE id = ?");
            $stmt->execute([$actividadId]);

            volverCon("ok", "Actividad eliminada.", "index.php?page=actividades&id=" . $cursoId);



        default:

            volverCon("error", "Acción no reconocida.");
    }

} catch (PDOException $e) {
    error_log("[BiGlobal][profesor/acciones] " . $e->getMessage());
    volverCon("error", "Error de base de datos. Intenta de nuevo.");
}