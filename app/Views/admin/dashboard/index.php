<?php
/** @var string $nombreAdmin */
/** @var int $totalUsuarios */
/** @var int $totalCursos */
/** @var int $totalInstructores */
/** @var int $usuariosActivosHoy */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Administrador</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <?php
    $paginaActiva = "inicio";
    require __DIR__ . "/../../partials/admin_sidebar.php";
    ?>

    <main class="main-content">

        <?php
        $tituloPagina = "Administrador";
        $panelLabel = "Panel administrativo";
        require __DIR__ . "/../../partials/admin_topbar.php";
        ?>

        <section class="dashboard">

            <div class="welcome-card">
                <div class="welcome-content">
                    <span class="welcome-tag">
                        <i class="fa-solid fa-shield-halved"></i>
                        Panel de administración
                    </span>
                    <h2>Hola, <?= htmlspecialchars($nombreAdmin) ?></h2>
                    <p>
                        Gestiona usuarios, cursos, idiomas y todos los
                        recursos de la plataforma BiGlobal desde un solo lugar.
                    </p>
                </div>
                <div class="welcome-icon">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>

            <div class="section-heading">
                <div>
                    <p class="section-subtitle">Resumen general</p>
                    <h2>Estadísticas de la plataforma</h2>
                </div>
                <span class="updated">
                    <i class="fa-solid fa-clock"></i>
                    Actualizado hoy
                </span>
            </div>

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="stat-data">
                        <span class="stat-title">Usuarios</span>
                        <strong><?= $totalUsuarios ?></strong>
                        <small>
                            <i class="fa-solid fa-arrow-up"></i>
                            Total registrados
                        </small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <div class="stat-data">
                        <span class="stat-title">Cursos</span>
                        <strong><?= $totalCursos ?></strong>
                        <small>
                            <i class="fa-solid fa-book"></i>
                            Cursos registrados
                        </small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fa-solid fa-chalkboard-user"></i>
                    </div>
                    <div class="stat-data">
                        <span class="stat-title">Instructores</span>
                        <strong><?= $totalInstructores ?></strong>
                        <small>
                            <i class="fa-solid fa-user-check"></i>
                            Instructores activos
                        </small>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div class="stat-data">
                        <span class="stat-title">Activos hoy</span>
                        <strong><?= $usuariosActivosHoy ?></strong>
                        <small>
                            <i class="fa-solid fa-calendar-day"></i>
                            Registros activos hoy
                        </small>
                    </div>
                </div>

            </div>

            <div class="dashboard-grid">

                <div class="dashboard-card">
                    <div class="card-header">
                        <div>
                            <span class="card-label">Requieren atención</span>
                            <h3>Solicitudes pendientes</h3>
                        </div>
                        <span class="badge">3 pendientes</span>
                    </div>

                    <div class="request-list">

                        <div class="request-item">
                            <div class="request-icon blue">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <div class="request-info">
                                <strong>Nuevo instructor</strong>
                                <span>Solicitud de registro pendiente</span>
                            </div>
                            <button class="review-btn" type="button">Revisar</button>
                        </div>

                        <div class="request-item">
                            <div class="request-icon purple">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <div class="request-info">
                                <strong>Nuevo curso</strong>
                                <span>Curso pendiente de aprobación</span>
                            </div>
                            <button class="review-btn" type="button">Revisar</button>
                        </div>

                        <div class="request-item">
                            <div class="request-icon orange">
                                <i class="fa-solid fa-flag"></i>
                            </div>
                            <div class="request-info">
                                <strong>Contenido reportado</strong>
                                <span>Requiere revisión del administrador</span>
                            </div>
                            <button class="review-btn" type="button">Revisar</button>
                        </div>

                    </div>

                    <a href="reportes.php" class="view-all">
                        Ver todas las solicitudes
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="card-header">
                        <div>
                            <span class="card-label">Administración</span>
                            <h3>Accesos rápidos</h3>
                        </div>
                        <i class="fa-solid fa-bolt quick-icon"></i>
                    </div>

                    <div class="quick-grid">

                        <a href="usuarios.php" class="quick-item">
                            <div class="quick-item-icon blue">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <span>Usuarios</span>
                        </a>

                        <a href="cursos.php" class="quick-item">
                            <div class="quick-item-icon purple">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <span>Cursos</span>
                        </a>

                        <a href="idiomas.php" class="quick-item">
                            <div class="quick-item-icon green">
                                <i class="fa-solid fa-globe"></i>
                            </div>
                            <span>Idiomas</span>
                        </a>

                        <a href="lecciones.php" class="quick-item">
                            <div class="quick-item-icon orange">
                                <i class="fa-solid fa-list-check"></i>
                            </div>
                            <span>Lecciones</span>
                        </a>

                        <a href="reportes.php" class="quick-item">
                            <div class="quick-item-icon pink">
                                <i class="fa-solid fa-chart-column"></i>
                            </div>
                            <span>Reportes</span>
                        </a>

                        <a href="certificados.php" class="quick-item">
                            <div class="quick-item-icon yellow">
                                <i class="fa-solid fa-certificate"></i>
                            </div>
                            <span>Certificados</span>
                        </a>

                    </div>
                </div>

            </div>

            <div class="dashboard-card activity-card">
                <div class="card-header">
                    <div>
                        <span class="card-label">Sistema</span>
                        <h3>Actividad reciente</h3>
                    </div>
                    <a href="reportes.php" class="view-link">Ver todo</a>
                </div>

                <div class="activity-table">

                    <div class="table-header">
                        <span>Actividad</span>
                        <span>Usuario</span>
                        <span>Fecha</span>
                        <span>Estado</span>
                    </div>

                    <div class="table-row">
                        <div class="activity-name">
                            <div class="mini-icon blue">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <span>Curso creado</span>
                        </div>
                        <span>María López</span>
                        <span>Hoy, 07:30</span>
                        <span class="status success">Completado</span>
                    </div>

                    <div class="table-row">
                        <div class="activity-name">
                            <div class="mini-icon green">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <span>Usuario registrado</span>
                        </div>
                        <span>Carlos Pérez</span>
                        <span>Hoy, 06:52</span>
                        <span class="status success">Completado</span>
                    </div>

                    <div class="table-row">
                        <div class="activity-name">
                            <div class="mini-icon orange">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            </div>
                            <span>Reporte recibido</span>
                        </div>
                        <span>Ana Torres</span>
                        <span>Ayer, 18:20</span>
                        <span class="status pending">Pendiente</span>
                    </div>

                </div>
            </div>

        </section>
    </main>
</div>

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