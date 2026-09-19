<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

$stmt = $conexion->prepare("
    SELECT
        c.id,
        c.codigo,
        c.fecha_emision,
        u.nombre AS usuario_nombre,
        u.apellido AS usuario_apellido,
        u.correo AS usuario_correo,
        cu.nombre AS curso_nombre
    FROM certificados c
    INNER JOIN usuarios u
        ON c.usuario_id = u.id
    INNER JOIN cursos cu
        ON c.curso_id = cu.id
    ORDER BY c.fecha_emision DESC
");

$stmt->execute();

$certificados = $stmt->fetchAll();

$totalCertificados = count($certificados);

$esteMes = 0;
foreach ($certificados as $c) {
    if (date("Y-m", strtotime($c["fecha_emision"])) === date("Y-m")) {
        $esteMes++;
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Certificados</title>

    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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
                PANEL PRINCIPAL
            </p>

            <a href="index.php" class="menu-item">

                <i class="fa-solid fa-house"></i>

                <span>
                    Inicio
                </span>

            </a>


            <p class="menu-title">
                GESTIÓN
            </p>


            <a href="usuarios.php" class="menu-item">

                <i class="fa-solid fa-users"></i>

                <span>
                    Usuarios y roles
                </span>

            </a>


            <a href="cursos.php" class="menu-item">

                <i class="fa-solid fa-book"></i>

                <span>
                    Catálogo de cursos
                </span>

            </a>


            <a href="idiomas.php" class="menu-item">

                <i class="fa-solid fa-globe"></i>

                <span>
                    Idiomas
                </span>

            </a>


            <a href="lecciones.php" class="menu-item">

                <i class="fa-solid fa-book-open"></i>

                <span>
                    Lecciones y actividades
                </span>

            </a>


            <p class="menu-title">
                CONTROL
            </p>


            <a href="reportes.php" class="menu-item">

                <i class="fa-solid fa-chart-column"></i>

                <span>
                    Reportes
                </span>

            </a>


            <a href="errores.php" class="menu-item">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <span>
                    Reportes y errores
                </span>

            </a>


            <a href="moderacion.php" class="menu-item">

                <i class="fa-solid fa-comments"></i>

                <span>
                    Moderación
                </span>

            </a>


            <a href="certificados.php" class="menu-item active">

                <i class="fa-solid fa-certificate"></i>

                <span>
                    Certificados
                </span>

            </a>


            <p class="menu-title">
                SISTEMA
            </p>


            <a href="configuracion.php" class="menu-item">

                <i class="fa-solid fa-gear"></i>

                <span>
                    Configuración
                </span>

            </a>

        </nav>


        <div class="sidebar-footer">

            <a
                href="../index.html"
                class="back-home"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Volver al inicio

            </a>

        </div>

    </aside>


    <!-- =========================
         CONTENIDO
    ========================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button
                    class="menu-toggle"
                    id="menuToggle"
                >

                    <i class="fa-solid fa-bars"></i>

                </button>


                <div>

                    <p class="panel-label">
                        Control y analítica
                    </p>

                    <h1>
                        Certificados
                    </h1>

                </div>

            </div>


            <div class="topbar-right">

                <button class="notification-button">

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-dot"></span>

                </button>


                <div class="admin-profile">

                    <div class="profile-avatar">
                        A
                    </div>

                    <div class="profile-info">

                        <strong>
                            Administrador
                        </strong>

                        <span>
                            Panel principal
                        </span>

                    </div>

                    <i class="fa-solid fa-chevron-down profile-arrow"></i>

                </div>

            </div>

        </header>


        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">
                        Control y analítica
                    </span>
                    <h2>
                        Certificados emitidos
                    </h2>
                    <p>
                        Los certificados se generan automáticamente cuando un estudiante
                        completa todas las lecciones publicadas de un curso.
                    </p>
                </div>
            </div>

            <div class="user-stats">

                <div class="user-stat-card">
                    <div class="user-stat-icon blue">
                        <i class="fa-solid fa-certificate"></i>
                    </div>
                    <div>
                        <span>Total emitidos</span>
                        <strong><?= $totalCertificados ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon green">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <span>Este mes</span>
                        <strong><?= $esteMes ?></strong>
                    </div>
                </div>

            </div>

            <div class="users-card">

                <div class="users-toolbar">
                    <div>
                        <h3>Historial de certificados</h3>
                        <span><?= $totalCertificados ?> certificados registrados</span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estudiante</th>
                                <th>Curso</th>
                                <th>Fecha de emisión</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (count($certificados) > 0): ?>

                                <?php foreach ($certificados as $c): ?>

                                    <tr>
                                        <td><span class="role admin-role"><?= htmlspecialchars($c["codigo"]) ?></span></td>
                                        <td>
                                            <div class="user-cell">
                                                <div class="user-avatar blue">
                                                    <?= strtoupper(substr($c["usuario_nombre"], 0, 1) . substr($c["usuario_apellido"], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <strong><?= htmlspecialchars($c["usuario_nombre"] . " " . $c["usuario_apellido"]) ?></strong>
                                                    <span><?= htmlspecialchars($c["usuario_correo"]) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($c["curso_nombre"]) ?></td>
                                        <td><?= date("d M Y", strtotime($c["fecha_emision"])) ?></td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" style="text-align:center; padding:30px;">
                                        Todavía no se ha emitido ningún certificado.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

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

