<?php

require_once "../auth/proteger.php";

protegerRol("estudiante");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

$usuarioId = $_SESSION["usuario_id"];

/* =========================================================
   ESTADISTICAS DEL ESTUDIANTE
========================================================= */

$stmtLeccionesCompletadas = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM progreso
    WHERE usuario_id = :usuario_id AND completado = 1
");
$stmtLeccionesCompletadas->execute([":usuario_id" => $usuarioId]);
$leccionesCompletadas = (int) $stmtLeccionesCompletadas->fetch()["total"];

$stmtCertificados = $conexion->prepare("
    SELECT COUNT(*) AS total
    FROM certificados
    WHERE usuario_id = :usuario_id
");
$stmtCertificados->execute([":usuario_id" => $usuarioId]);
$totalCertificados = (int) $stmtCertificados->fetch()["total"];

$stmtCursosEnProgreso = $conexion->prepare("
    SELECT COUNT(DISTINCT l.curso_id) AS total
    FROM progreso p
    INNER JOIN lecciones l ON p.leccion_id = l.id
    WHERE p.usuario_id = :usuario_id
");
$stmtCursosEnProgreso->execute([":usuario_id" => $usuarioId]);
$cursosEnProgreso = (int) $stmtCursosEnProgreso->fetch()["total"];

$stmtTotalCursos = $conexion->prepare("
    SELECT COUNT(*) AS total FROM cursos WHERE estado = 'Publicado'
");
$stmtTotalCursos->execute();
$totalCursosDisponibles = (int) $stmtTotalCursos->fetch()["total"];

/* =========================================================
   CURSOS PARA CONTINUAR (con progreso pero sin completar)
========================================================= */

$stmtContinuar = $conexion->prepare("
    SELECT
        c.id,
        c.nombre,
        c.nivel,
        (
            SELECT COUNT(*) FROM lecciones l2
            WHERE l2.curso_id = c.id AND l2.estado = 'Publicado'
        ) AS total_lecciones,
        (
            SELECT COUNT(*) FROM progreso p2
            INNER JOIN lecciones l3 ON p2.leccion_id = l3.id
            WHERE l3.curso_id = c.id
              AND p2.usuario_id = :usuario_id
              AND p2.completado = 1
        ) AS lecciones_hechas
    FROM cursos c
    WHERE c.estado = 'Publicado'
    HAVING lecciones_hechas > 0 AND lecciones_hechas < total_lecciones
    ORDER BY c.id DESC
    LIMIT 3
");
$stmtContinuar->execute([":usuario_id" => $usuarioId]);
$cursosContinuar = $stmtContinuar->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Inicio</title>

    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="css/estudiante.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link
        href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Manrope:wght@300;400;600;800&display=swap"
        rel="stylesheet"
    >

</head>

<body>

<div class="admin-container">


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <div class="logo">

                <span class="logo-icon">
                    <i class="fa-solid fa-language"></i>
                </span>

                <span class="logo-text">
                    BiGlobal
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">

            <p class="menu-title">
                MI APRENDIZAJE
            </p>

            <a href="index.php" class="menu-item active">
                <i class="fa-solid fa-house"></i>
                <span>Inicio</span>
            </a>

            <a href="cursos.php" class="menu-item">
                <i class="fa-solid fa-book"></i>
                <span>Mis cursos</span>
            </a>

            <a href="certificados.php" class="menu-item">
                <i class="fa-solid fa-certificate"></i>
                <span>Certificados</span>
            </a>

        </nav>


        <div class="sidebar-footer">

            <a href="../index.html" class="back-home">
                <i class="fa-solid fa-arrow-left"></i>
                Volver al inicio
            </a>

            <a href="../auth/logout.php" class="back-home" style="margin-top:6px;">
                <i class="fa-solid fa-right-from-bracket"></i>
                Cerrar sesión
            </a>

        </div>

    </aside>


    <!-- =========================
         CONTENIDO
    ========================== -->

    <main class="main-content">

        <header class="topbar">

            <div class="topbar-left">

                <button class="menu-toggle" id="menuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <p class="panel-label">Mi aprendizaje</p>
                    <h1>Inicio</h1>
                </div>

            </div>

            <div class="topbar-right">

                <div class="admin-profile">
                    <div class="profile-avatar"><?= strtoupper(substr($_SESSION["nombre"] ?? "E", 0, 1)) ?></div>
                    <div class="profile-info">
                        <strong><?= htmlspecialchars(($_SESSION["nombre"] ?? "") . " " . ($_SESSION["apellido"] ?? "")) ?></strong>
                        <span>Estudiante</span>
                    </div>
                </div>

            </div>

        </header>


        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">Bienvenido</span>
                    <h2><?= htmlspecialchars($_SESSION["nombre"] ?? "") ?>, sigue aprendiendo</h2>
                    <p>Este es tu progreso en BiGlobal.</p>
                </div>
                <a href="cursos.php" class="add-user-btn">
                    <i class="fa-solid fa-book"></i>
                    Ver todos los cursos
                </a>
            </div>

            <div class="user-stats">

                <div class="user-stat-card">
                    <div class="user-stat-icon blue"><i class="fa-solid fa-book"></i></div>
                    <div>
                        <span>Cursos disponibles</span>
                        <strong><?= $totalCursosDisponibles ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon orange"><i class="fa-solid fa-spinner"></i></div>
                    <div>
                        <span>Cursos en progreso</span>
                        <strong><?= $cursosEnProgreso ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon green"><i class="fa-solid fa-list-check"></i></div>
                    <div>
                        <span>Lecciones completadas</span>
                        <strong><?= $leccionesCompletadas ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon purple"><i class="fa-solid fa-certificate"></i></div>
                    <div>
                        <span>Certificados obtenidos</span>
                        <strong><?= $totalCertificados ?></strong>
                    </div>
                </div>

            </div>

            <div class="users-card" style="padding:22px;">

                <h3 style="margin-top:0;">Continúa donde lo dejaste</h3>

                <?php if (count($cursosContinuar) > 0): ?>

                    <div class="course-grid">

                        <?php foreach ($cursosContinuar as $c): ?>

                            <?php $porcentaje = $c["total_lecciones"] > 0
                                ? round(($c["lecciones_hechas"] / $c["total_lecciones"]) * 100)
                                : 0; ?>

                            <div class="course-card">
                                <div class="course-card-top">
                                    <div class="course-card-icon"><i class="fa-solid fa-book"></i></div>
                                    <span class="role admin-role"><?= htmlspecialchars($c["nivel"]) ?></span>
                                </div>
                                <h3><?= htmlspecialchars($c["nombre"]) ?></h3>
                                <div class="course-progress-track">
                                    <div class="course-progress-fill" style="width: <?= $porcentaje ?>%;"></div>
                                </div>
                                <div class="course-progress-label">
                                    <span><?= $c["lecciones_hechas"] ?>/<?= $c["total_lecciones"] ?> lecciones</span>
                                    <span><?= $porcentaje ?>%</span>
                                </div>
                                <a href="curso.php?id=<?= $c["id"] ?>" class="course-card-btn">
                                    Continuar <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <div class="empty-state-soft">
                        <i class="fa-solid fa-graduation-cap"></i>
                        <p>Todavía no has empezado ningún curso. ¡Explora el catálogo y comienza hoy!</p>
                        <a href="cursos.php" class="course-card-btn" style="display:inline-flex;">Explorar cursos</a>
                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

</div>


<script>

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");

    if (menuToggle) {

        menuToggle.addEventListener("click", () => {

            sidebar.classList.toggle("show");

        });

    }

</script>

</body>

</html>

