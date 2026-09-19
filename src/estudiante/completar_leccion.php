<?php

require_once "../auth/proteger.php";

protegerRol("estudiante");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cursos.php");
    exit;
}

$conexion = Conexion::conectar();

$usuarioId = $_SESSION["usuario_id"];
$leccionId = $_POST["leccion_id"] ?? null;
$cursoId = $_POST["curso_id"] ?? null;

if (!$leccionId || !is_numeric($leccionId) || !$cursoId || !is_numeric($cursoId)) {
    header("Location: cursos.php");
    exit;
}

/* =========================================================
   VALIDAR QUE LA LECCIÓN PERTENECE AL CURSO Y ESTÁ PUBLICADA
========================================================= */

$stmtLeccion = $conexion->prepare("
    SELECT id FROM lecciones
    WHERE id = :leccion_id AND curso_id = :curso_id AND estado = 'Publicado'
    LIMIT 1
");
$stmtLeccion->execute([
    ":leccion_id" => $leccionId,
    ":curso_id" => $cursoId
]);

if (!$stmtLeccion->fetch()) {
    header("Location: curso.php?id=" . urlencode($cursoId));
    exit;
}

try {

    $conexion->beginTransaction();

    /* =====================================================
       MARCAR LA LECCIÓN COMO COMPLETADA (UPSERT)
    ===================================================== */

    $stmt = $conexion->prepare("
        INSERT INTO progreso (usuario_id, leccion_id, completado, fecha_completado)
        VALUES (:usuario_id, :leccion_id, 1, NOW())
        ON DUPLICATE KEY UPDATE
            completado = 1,
            fecha_completado = NOW()
    ");

    $stmt->execute([
        ":usuario_id" => $usuarioId,
        ":leccion_id" => $leccionId
    ]);

    /* =====================================================
       ¿SE COMPLETÓ TODO EL CURSO? -> EMITIR CERTIFICADO
    ===================================================== */

    $stmtTotal = $conexion->prepare("
        SELECT COUNT(*) AS total FROM lecciones
        WHERE curso_id = :curso_id AND estado = 'Publicado'
    ");
    $stmtTotal->execute([":curso_id" => $cursoId]);
    $totalLecciones = (int) $stmtTotal->fetch()["total"];

    $stmtHechas = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM progreso p
        INNER JOIN lecciones l ON p.leccion_id = l.id
        WHERE l.curso_id = :curso_id
          AND p.usuario_id = :usuario_id
          AND p.completado = 1
    ");
    $stmtHechas->execute([
        ":curso_id" => $cursoId,
        ":usuario_id" => $usuarioId
    ]);
    $leccionesHechas = (int) $stmtHechas->fetch()["total"];

    if ($totalLecciones > 0 && $leccionesHechas >= $totalLecciones) {

        $stmtExiste = $conexion->prepare("
            SELECT id FROM certificados
            WHERE usuario_id = :usuario_id AND curso_id = :curso_id
            LIMIT 1
        ");
        $stmtExiste->execute([
            ":usuario_id" => $usuarioId,
            ":curso_id" => $cursoId
        ]);

        if (!$stmtExiste->fetch()) {

            $codigo = "BG-" . strtoupper(bin2hex(random_bytes(4)));

            $stmtCertificado = $conexion->prepare("
                INSERT INTO certificados (usuario_id, curso_id, codigo)
                VALUES (:usuario_id, :curso_id, :codigo)
            ");

            $stmtCertificado->execute([
                ":usuario_id" => $usuarioId,
                ":curso_id" => $cursoId,
                ":codigo" => $codigo
            ]);
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
