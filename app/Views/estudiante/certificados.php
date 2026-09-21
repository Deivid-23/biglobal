<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Certificados</title>

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

            <a href="cursos.php" class="menu-item">
                <i class="fa-solid fa-book"></i>
                <span>Mis cursos</span>
            </a>

            <a href="certificados.php" class="menu-item active">
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
                    <h1>Certificados</h1>
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
                    <span class="section-label">Logros</span>
                    <h2>Mis certificados</h2>
                    <p>Se emiten automáticamente cuando completas todas las lecciones de un curso.</p>
                </div>
            </div>

            <?php if (count($certificados) > 0): ?>

                <?php foreach ($certificados as $c): ?>

                    <div class="certificate-card">
                        <div class="certificate-icon">
                            <i class="fa-solid fa-certificate"></i>
                        </div>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 4px 0; font-family:'Manrope',sans-serif; color:var(--text-dark);">
                                <?= htmlspecialchars($c["curso_nombre"]) ?>
                            </h3>
                            <span style="font-size:12px; color:var(--text-light);">
                                Nivel <?= htmlspecialchars($c["nivel"]) ?>
                                · Código <?= htmlspecialchars($c["codigo"]) ?>
                                · Emitido el <?= date("d M Y", strtotime($c["fecha_emision"])) ?>
                            </span>
                        </div>
                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="empty-state-soft">
                    <i class="fa-solid fa-certificate"></i>
                    <p>Todavía no tienes certificados. Completa un curso para ganar el primero.</p>
                    <a href="cursos.php" class="course-card-btn" style="display:inline-flex;">Ver cursos</a>
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

