<?php

require_once "../auth/proteger.php";
require_once "../bd/conexion.php";

/* =========================================================
   PROTEGER PANEL ADMINISTRATIVO
========================================================= */

protegerRol("admin");


/* =========================================================
   CONEXIÓN
========================================================= */

$conexion = Conexion::conectar();


/* =========================================================
   TOTAL DE USUARIOS
========================================================= */

$stmtUsuarios = $conexion->query("
    SELECT COUNT(*) AS total
    FROM usuarios
");

$totalUsuarios = (int) $stmtUsuarios->fetch()["total"];


/* =========================================================
   TOTAL DE CURSOS
========================================================= */

$stmtCursos = $conexion->query("
    SELECT COUNT(*) AS total
    FROM cursos
");

$totalCursos = (int) $stmtCursos->fetch()["total"];


/* =========================================================
   TOTAL DE INSTRUCTORES
========================================================= */

$stmtInstructores = $conexion->query("
    SELECT COUNT(*) AS total
    FROM usuarios u
    INNER JOIN roles r
        ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'instructor'
      AND u.estado = 'Activo'
");

$totalInstructores = (int) $stmtInstructores->fetch()["total"];


/* =========================================================
   USUARIOS REGISTRADOS HOY
========================================================= */

$stmtActivos = $conexion->query("
    SELECT COUNT(*) AS total
    FROM usuarios
    WHERE estado = 'Activo'
      AND DATE(fecha_registro) = CURDATE()
");

$usuariosActivosHoy = (int) $stmtActivos->fetch()["total"];


/* =========================================================
   DATOS DEL ADMINISTRADOR
========================================================= */

$nombreAdmin = $_SESSION["nombre"] ?? "Administrador";
$apellidoAdmin = $_SESSION["apellido"] ?? "";

$nombreCompletoAdmin = trim(
    $nombreAdmin . " " . $apellidoAdmin
);

if (empty($nombreCompletoAdmin)) {
    $nombreCompletoAdmin = "Administrador";
}


/* Primera letra para el avatar */

$avatar = strtoupper(
    substr($nombreAdmin, 0, 1)
);

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Administrador</title>


    <!-- CSS del administrador -->

    <link
        rel="stylesheet"
        href="css/style.css">


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Bootstrap Icons (usados en el dashboard) -->

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


    <!-- Google Fonts -->

    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Sora:wght@400;600;700;800&display=swap"
        rel="stylesheet">

</head>


<body>


    <div class="admin-container">


        <!-- =====================================================
         BARRA LATERAL
    ====================================================== -->

        <aside class="sidebar">


            <div class="sidebar-header">

                <div class="logo">

                    <a
                        href="index.php"
                        class="logo-admin">

                        <img
                            src="../img/logo-Biglobal.png"
                            alt="BiGlobal">

                        <span>BiGlobal</span>

                    </a>

                </div>

            </div>


            <nav class="sidebar-menu">


                <p class="menu-title">
                    PANEL PRINCIPAL
                </p>


                <a
                    href="index.php"
                    class="menu-item active">

                    <i class="bi bi-house"></i>

                    <span>Inicio</span>

                </a>


                <p class="menu-title">
                    GESTIÓN
                </p>


                <a
                    href="usuarios.php"
                    class="menu-item">

                    <i class="bi bi-people"></i>

                    <span>Usuarios y roles</span>

                </a>


                <a
                    href="cursos.php"
                    class="menu-item">

                    <i class="bi bi-book"></i>

                    <span>Catálogo de cursos</span>

                </a>


                <a
                    href="idiomas.php"
                    class="menu-item">

                    <i class="bi bi-globe2"></i>

                    <span>Idiomas</span>

                </a>


                <a
                    href="lecciones.php"
                    class="menu-item">

                    <i class="bi bi-journal-bookmark"></i>

                    <span>Lecciones y actividades</span>

                </a>


                <p class="menu-title">
                    CONTROL
                </p>


                <a
                    href="reportes.php"
                    class="menu-item">

                    <i class="bi bi-bar-chart"></i>

                    <span>Reportes</span>

                </a>


                <a
                    href="errores.php"
                    class="menu-item">

                    <i class="bi bi-exclamation-triangle"></i>

                    <span>Reportes y errores</span>

                </a>


                <a
                    href="moderacion.php"
                    class="menu-item">

                    <i class="bi bi-chat-dots"></i>

                    <span>Moderación</span>

                </a>


                <a
                    href="certificados.php"
                    class="menu-item">

                    <i class="bi bi-award"></i>

                    <span>Certificados</span>

                </a>


                <p class="menu-title">
                    SISTEMA
                </p>


                <a
                    href="configuracion.php"
                    class="menu-item">

                    <i class="bi bi-gear"></i>

                    <span>Configuración</span>

                </a>


            </nav>


            <div class="sidebar-footer">

                <a
                    href="../index.html"
                    class="back-home">

                    <i class="bi bi-arrow-left"></i>

                    Volver al inicio

                </a>

            </div>


        </aside>



        <!-- =====================================================
         CONTENIDO PRINCIPAL
    ====================================================== -->

        <main class="main-content">


            <!-- HEADER -->

            <header class="topbar">


                <div class="topbar-left">


                    <button
                        class="menu-toggle"
                        id="menuToggle"
                        type="button">

                        <i class="bi bi-list"></i>

                    </button>


                    <div>

                        <p class="panel-label">
                            Panel administrativo
                        </p>

                        <h1>
                            Administrador
                        </h1>

                    </div>


                </div>


                <div class="topbar-right">


                    <button
                        class="notification-button"
                        type="button"
                        aria-label="Notificaciones">

                        <i class="bi bi-bell"></i>

                        <span class="notification-dot"></span>

                    </button>


                    <div class="admin-profile">


                        <div class="profile-avatar">

                            <?= htmlspecialchars($avatar) ?>

                        </div>


                        <div class="profile-info">

                            <strong>
                                <?= htmlspecialchars($nombreCompletoAdmin) ?>
                            </strong>

                            <span>
                                Panel principal
                            </span>

                        </div>


                        <i class="bi bi-chevron-down profile-arrow"></i>


                    </div>


                </div>


            </header>



            <!-- =====================================================
             DASHBOARD
        ====================================================== -->

            <section class="dashboard">


                <!-- BIENVENIDA -->

                <div class="welcome-card">


                    <div class="welcome-content">


                        <span class="welcome-tag">

                            <i class="bi bi-shield-check"></i>

                            Panel de administración

                        </span>


                        <h2>
                            Hola, <?= htmlspecialchars($nombreAdmin) ?>
                        </h2>


                        <p>

                            Gestiona usuarios, cursos, idiomas y todos los
                            recursos de la plataforma BiGlobal desde un solo lugar.

                        </p>


                    </div>


                    <div class="welcome-icon">

                        <i class="bi bi-graph-up-arrow"></i>

                    </div>


                </div>



                <!-- =================================================
                 ESTADÍSTICAS
            ================================================== -->

                <div class="section-heading">


                    <div>

                        <p class="section-subtitle">
                            Resumen general
                        </p>

                        <h2>
                            Estadísticas de la plataforma
                        </h2>

                    </div>


                    <span class="updated">

                        <i class="bi bi-clock"></i>

                        Actualizado hoy

                    </span>


                </div>



                <div class="stats-grid">


                    <!-- USUARIOS -->

                    <div class="stat-card">


                        <div class="stat-icon blue">

                            <i class="bi bi-people"></i>

                        </div>


                        <div class="stat-data">


                            <span class="stat-title">
                                Usuarios
                            </span>


                            <strong>
                                <?= $totalUsuarios ?>
                            </strong>


                            <small>

                                <i class="bi bi-arrow-up"></i>

                                Total registrados

                            </small>


                        </div>


                    </div>



                    <!-- CURSOS -->

                    <div class="stat-card">


                        <div class="stat-icon purple">

                            <i class="bi bi-book"></i>

                        </div>


                        <div class="stat-data">


                            <span class="stat-title">
                                Cursos
                            </span>


                            <strong>
                                <?= $totalCursos ?>
                            </strong>


                            <small>

                                <i class="bi bi-book"></i>

                                Cursos registrados

                            </small>


                        </div>


                    </div>



                    <!-- INSTRUCTORES -->

                    <div class="stat-card">


                        <div class="stat-icon green">

                            <i class="bi bi-person-video3"></i>

                        </div>


                        <div class="stat-data">


                            <span class="stat-title">
                                Instructores
                            </span>


                            <strong>
                                <?= $totalInstructores ?>
                            </strong>


                            <small>

                                <i class="bi bi-person-check"></i>

                                Instructores activos

                            </small>


                        </div>


                    </div>



                    <!-- ACTIVOS HOY -->

                    <div class="stat-card">


                        <div class="stat-icon orange">

                            <i class="bi bi-person-check"></i>

                        </div>


                        <div class="stat-data">


                            <span class="stat-title">
                                Activos hoy
                            </span>


                            <strong>
                                <?= $usuariosActivosHoy ?>
                            </strong>


                            <small>

                                <i class="bi bi-calendar-day"></i>

                                Registros activos hoy

                            </small>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                 DOS COLUMNAS
            ================================================== -->

                <div class="dashboard-grid">


                    <!-- SOLICITUDES -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <div>

                                <span class="card-label">
                                    Requieren atención
                                </span>

                                <h3>
                                    Solicitudes pendientes
                                </h3>

                            </div>


                            <span class="badge">
                                3 pendientes
                            </span>


                        </div>



                        <div class="request-list">


                            <!-- SOLICITUD 1 -->

                            <div class="request-item">


                                <div class="request-icon blue">

                                    <i class="bi bi-person-plus"></i>

                                </div>


                                <div class="request-info">

                                    <strong>
                                        Nuevo instructor
                                    </strong>

                                    <span>
                                        Solicitud de registro pendiente
                                    </span>

                                </div>


                                <button
                                    class="review-btn"
                                    type="button">
                                    Revisar
                                </button>


                            </div>



                            <!-- SOLICITUD 2 -->

                            <div class="request-item">


                                <div class="request-icon purple">

                                    <i class="bi bi-book"></i>

                                </div>


                                <div class="request-info">

                                    <strong>
                                        Nuevo curso
                                    </strong>

                                    <span>
                                        Curso pendiente de aprobación
                                    </span>

                                </div>


                                <button
                                    class="review-btn"
                                    type="button">
                                    Revisar
                                </button>


                            </div>



                            <!-- SOLICITUD 3 -->

                            <div class="request-item">


                                <div class="request-icon orange">

                                    <i class="bi bi-flag"></i>

                                </div>


                                <div class="request-info">

                                    <strong>
                                        Contenido reportado
                                    </strong>

                                    <span>
                                        Requiere revisión del administrador
                                    </span>

                                </div>


                                <button
                                    class="review-btn"
                                    type="button">
                                    Revisar
                                </button>


                            </div>


                        </div>


                        <a
                            href="reportes.php"
                            class="view-all">

                            Ver todas las solicitudes

                            <i class="bi bi-arrow-right"></i>

                        </a>


                    </div>



                    <!-- ACCESOS RÁPIDOS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <div>

                                <span class="card-label">
                                    Administración
                                </span>

                                <h3>
                                    Accesos rápidos
                                </h3>

                            </div>


                            <i class="bi bi-lightning-charge quick-icon"></i>


                        </div>



                        <div class="quick-grid">


                            <a
                                href="usuarios.php"
                                class="quick-item">

                                <div class="quick-item-icon blue">

                                    <i class="bi bi-people"></i>

                                </div>

                                <span>
                                    Usuarios
                                </span>

                            </a>


                            <a
                                href="cursos.php"
                                class="quick-item">

                                <div class="quick-item-icon purple">

                                    <i class="bi bi-book"></i>

                                </div>

                                <span>
                                    Cursos
                                </span>

                            </a>


                            <a
                                href="idiomas.php"
                                class="quick-item">

                                <div class="quick-item-icon green">

                                    <i class="bi bi-globe2"></i>

                                </div>

                                <span>
                                    Idiomas
                                </span>

                            </a>


                            <a
                                href="lecciones.php"
                                class="quick-item">

                                <div class="quick-item-icon orange">

                                    <i class="bi bi-list-check"></i>

                                </div>

                                <span>
                                    Lecciones
                                </span>

                            </a>


                            <a
                                href="reportes.php"
                                class="quick-item">

                                <div class="quick-item-icon pink">

                                    <i class="bi bi-bar-chart"></i>

                                </div>

                                <span>
                                    Reportes
                                </span>

                            </a>


                            <a
                                href="certificados.php"
                                class="quick-item">

                                <div class="quick-item-icon yellow">

                                    <i class="bi bi-award"></i>

                                </div>

                                <span>
                                    Certificados
                                </span>

                            </a>


                        </div>


                    </div>


                </div>



                <!-- =================================================
                 ACTIVIDAD RECIENTE
            ================================================== -->

                <div class="dashboard-card activity-card">


                    <div class="card-header">


                        <div>

                            <span class="card-label">
                                Sistema
                            </span>

                            <h3>
                                Actividad reciente
                            </h3>

                        </div>


                        <a
                            href="reportes.php"
                            class="view-link">
                            Ver todo
                        </a>


                    </div>



                    <div class="activity-table">


                        <div class="table-header">

                            <span>
                                Actividad
                            </span>

                            <span>
                                Usuario
                            </span>

                            <span>
                                Fecha
                            </span>

                            <span>
                                Estado
                            </span>

                        </div>



                        <div class="table-row">


                            <div class="activity-name">

                                <div class="mini-icon blue">

                                    <i class="bi bi-book"></i>

                                </div>

                                <span>
                                    Curso creado
                                </span>

                            </div>


                            <span>
                                María López
                            </span>


                            <span>
                                Hoy, 07:30
                            </span>


                            <span class="status success">
                                Completado
                            </span>


                        </div>



                        <div class="table-row">


                            <div class="activity-name">

                                <div class="mini-icon green">

                                    <i class="bi bi-person-plus"></i>

                                </div>

                                <span>
                                    Usuario registrado
                                </span>

                            </div>


                            <span>
                                Carlos Pérez
                            </span>


                            <span>
                                Hoy, 06:52
                            </span>


                            <span class="status success">
                                Completado
                            </span>


                        </div>



                        <div class="table-row">


                            <div class="activity-name">

                                <div class="mini-icon orange">

                                    <i class="bi bi-exclamation-triangle"></i>

                                </div>

                                <span>
                                    Reporte recibido
                                </span>

                            </div>


                            <span>
                                Ana Torres
                            </span>


                            <span>
                                Ayer, 18:20
                            </span>


                            <span class="status pending">
                                Pendiente
                            </span>


                        </div>


                    </div>


                </div>


            </section>


        </main>


    </div>



    <!-- =========================================================
    JAVASCRIPT DEL MENÚ
========================================================= -->

    <script>
        const menuToggle = document.getElementById("menuToggle");

        const sidebar = document.querySelector(".sidebar");


        if (menuToggle && sidebar) {

            menuToggle.addEventListener("click", () => {

                sidebar.classList.toggle("show");

            });

        }
    </script>


</body>

</html>