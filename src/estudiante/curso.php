<?php

require_once "../auth/proteger.php";

protegerRol("estudiante");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

$usuarioId = $_SESSION["usuario_id"];

$cursoId = $_GET["id"] ?? null;

if (!$cursoId || !is_numeric($cursoId)) {
    header("Location: cursos.php");
    exit;
}

$stmtCurso = $conexion->prepare("
    SELECT id, nombre, descripcion, nivel
    FROM cursos
    WHERE id = :id AND estado = 'Publicado'
    LIMIT 1
");
$stmtCurso->execute([":id" => $cursoId]);
$curso = $stmtCurso->fetch();

if (!$curso) {
    header("Location: cursos.php");
    exit;
}

$stmtLecciones = $conexion->prepare("
    SELECT
        l.id,
        l.titulo,
        l.descripcion,
        l.orden,
        (
            SELECT COUNT(*) FROM actividades a
            WHERE a.leccion_id = l.id AND a.estado = 'Publicado'
        ) AS total_actividades,
        (
            SELECT p.completado FROM progreso p
            WHERE p.usuario_id = :usuario_id AND p.leccion_id = l.id
            LIMIT 1
        ) AS completado
    FROM lecciones l
    WHERE l.curso_id = :curso_id AND l.estado = 'Publicado'
    ORDER BY l.orden ASC, l.id ASC
");
$stmtLecciones->execute([
    ":usuario_id" => $usuarioId,
    ":curso_id" => $cursoId
]);
$lecciones = $stmtLecciones->fetchAll();

$totalLecciones = count($lecciones);
$leccionesHechas = 0;
foreach ($lecciones as $l) {
    if ((int)$l["completado"] === 1) {
        $leccionesHechas++;
    }
}
$porcentaje = $totalLecciones > 0 ? round(($leccionesHechas / $totalLecciones) * 100) : 0;

$stmtCertificado = $conexion->prepare("
    SELECT codigo FROM certificados
    WHERE usuario_id = :usuario_id AND curso_id = :curso_id
    LIMIT 1
");
$stmtCertificado->execute([":usuario_id" => $usuarioId, ":curso_id" => $cursoId]);
$certificado = $stmtCertificado->fetch();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | <?= htmlspecialchars($curso["nombre"]) ?></title>
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="css/estudiante.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Manrope:wght@300;400;600;800&display=swap" rel="stylesheet">
</head>

<body>

<div class="admin-container">

    <main class="main-content" style="width: 100%; margin-left: 0;">

        <header class="topbar">
            <div class="topbar-left">
                <a href="cursos.php" class="menu-toggle" style="display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <p class="panel-label">Mi aprendizaje</p>
                    <h1><?= htmlspecialchars($curso["nombre"]) ?></h1>
                </div>
            </div>
        </header>

        <section class="users-page">

            <div class="users-card" style="padding:22px; margin-bottom:20px;">

                <span class="role admin-role"><?= htmlspecialchars($curso["nivel"]) ?></span>

                <p style="color:var(--text-light); margin:10px 0 16px;">
                    <?= htmlspecialchars($curso["descripcion"] ?: "Sin descripción disponible.") ?>
                </p>

                <div class="course-progress-track">
                    <div class="course-progress-fill" style="width: <?= $porcentaje ?>%;"></div>
                </div>
                <div class="course-progress-label" style="margin-top:6px;">
                    <span><?= $leccionesHechas ?>/<?= $totalLecciones ?> lecciones completadas</span>
                    <span><?= $porcentaje ?>%</span>
                </div>

                <?php if ($certificado): ?>
                    <div style="margin-top:16px; background:var(--purple-light); color:var(--purple); padding:12px 16px; border-radius:10px; font-size:13px;">
                        <i class="fa-solid fa-certificate"></i>
                        ¡Completaste este curso! Certificado: <strong><?= htmlspecialchars($certificado["codigo"]) ?></strong>
                        — <a href="certificados.php" style="color:var(--purple); text-decoration:underline;">verlo</a>
                    </div>
                <?php endif; ?>

            </div>

            <h3 style="margin-bottom:10px;">Lecciones</h3>

            <?php if (count($lecciones) > 0): ?>

                <?php foreach ($lecciones as $l): ?>

                    <div class="lesson-row">

                        <div class="lesson-row-check <?= (int)$l["completado"] === 1 ? "done" : "pending" ?>">
                            <i class="fa-solid <?= (int)$l["completado"] === 1 ? "fa-check" : "fa-lock-open" ?>"></i>
                        </div>

                        <div class="lesson-row-info">
                            <h4><?= htmlspecialchars($l["titulo"]) ?></h4>
                            <span>
                                <?= htmlspecialchars($l["descripcion"] ?: "Sin descripción") ?>
                                · <?= (int)$l["total_actividades"] ?> actividades
                            </span>
                        </div>

                        <?php if ((int)$l["completado"] === 1): ?>

                            <span class="status active-status">Completada</span>

                        <?php else: ?>

                            <form action="completar_leccion.php" method="POST">
                                <input type="hidden" name="leccion_id" value="<?= $l["id"] ?>">
                                <input type="hidden" name="curso_id" value="<?= $cursoId ?>">
                                <button type="submit" class="course-card-btn" style="padding:8px 14px; font-size:12px;">
                                    Marcar como completada
                                </button>
                            </form>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state-soft">
                    <i class="fa-solid fa-book-open"></i>
                    <p>Este curso todavía no tiene lecciones publicadas.</p>
                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
