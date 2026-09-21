<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Mis cursos</title>

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

            <a href="index.php" class="menu-item">
                <i class="fa-solid fa-house"></i>
                <span>Inicio</span>
            </a>

            <a href="cursos.php" class="menu-item active">
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
                    <h1>Mis cursos</h1>
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
                    <span class="section-label">Catálogo</span>
                    <h2>Cursos disponibles</h2>
                    <p>Inglés, español y lengua de señas: elige un curso y empieza a avanzar lección por lección.</p>
                </div>
            </div>

            <?php if (count($cursos) > 0): ?>

                <div class="course-grid">

                    <?php foreach ($cursos as $c): ?>

                        <?php
                        $porcentaje = $c["total_lecciones"] > 0
                            ? round(($c["lecciones_hechas"] / $c["total_lecciones"]) * 100)
                            : 0;

                        $etiquetaBoton = $porcentaje > 0 ? "Continuar" : "Empezar";
                        ?>

                        <div class="course-card">

                            <div class="course-card-top">
                                <div class="course-card-icon"><i class="fa-solid fa-book"></i></div>
                                <span class="role admin-role"><?= htmlspecialchars($c["nivel"]) ?></span>
                            </div>

                            <h3><?= htmlspecialchars($c["nombre"]) ?></h3>

                            <p><?= htmlspecialchars($c["descripcion"] ?: "Sin descripción disponible.") ?></p>

                            <?php if ($c["total_lecciones"] > 0): ?>

                                <div class="course-progress-track">
                                    <div class="course-progress-fill" style="width: <?= $porcentaje ?>%;"></div>
                                </div>
                                <div class="course-progress-label">
                                    <span><?= $c["lecciones_hechas"] ?>/<?= $c["total_lecciones"] ?> lecciones</span>
                                    <span><?= $porcentaje ?>%</span>
                                </div>

                                <a href="curso.php?id=<?= $c["id"] ?>" class="course-card-btn">
                                    <?= $etiquetaBoton ?> <i class="fa-solid fa-arrow-right"></i>
                                </a>

                            <?php else: ?>

                                <p style="font-size:12px; color:var(--muted);">Este curso todavía no tiene lecciones publicadas.</p>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php else: ?>

                <div class="empty-state-soft">
                    <i class="fa-solid fa-book-open"></i>
                    <p>Todavía no hay cursos publicados. Vuelve pronto.</p>
                </div>

            <?php endif; ?>

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

