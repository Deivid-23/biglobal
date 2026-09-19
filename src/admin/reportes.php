<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

/* =========================
   ACTUALIZAR ESTADO (accion rapida)
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion_estado"])) {

    $id = $_POST["id"] ?? null;
    $nuevoEstado = $_POST["accion_estado"];

    $estadosPermitidos = ["Pendiente", "Revisado", "Descartado"];

    if ($id && is_numeric($id) && in_array($nuevoEstado, $estadosPermitidos, true)) {

        $stmt = $conexion->prepare("
            UPDATE reportes_contenido
            SET estado = :estado
            WHERE id = :id
        ");

        $stmt->execute([
            ":estado" => $nuevoEstado,
            ":id" => $id
        ]);
    }

    header("Location: reportes.php");
    exit;
}

/* =========================
   OBTENER REPORTES
========================= */

$stmt = $conexion->prepare("
    SELECT
        rc.id,
        rc.tipo,
        rc.referencia,
        rc.motivo,
        rc.estado,
        rc.fecha_creacion,
        u.nombre AS creado_por_nombre,
        u.apellido AS creado_por_apellido
    FROM reportes_contenido rc
    LEFT JOIN usuarios u
        ON rc.creado_por = u.id
    ORDER BY rc.fecha_creacion DESC
");

$stmt->execute();

$reportes = $stmt->fetchAll();

$total = count($reportes);
$pendientes = 0;
$revisados = 0;
$descartados = 0;

foreach ($reportes as $r) {
    if ($r["estado"] === "Pendiente") $pendientes++;
    if ($r["estado"] === "Revisado") $revisados++;
    if ($r["estado"] === "Descartado") $descartados++;
}

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Reportes</title>

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


            <a href="reportes.php" class="menu-item active">

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


            <a href="certificados.php" class="menu-item">

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
                        Reportes
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
                        Reportes de la plataforma
                    </h2>

                    <p>
                        Vista general de los reportes e incidencias registrados en BiGlobal.
                        Para atender los pendientes, usa la sección de
                        <a href="moderacion.php">Moderación</a>.
                    </p>

                </div>

            </div>


            <div class="user-stats">

                <div class="user-stat-card">
                    <div class="user-stat-icon blue">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>
                    <div>
                        <span>Total reportes</span>
                        <strong><?= $total ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon orange">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>
                    <div>
                        <span>Pendientes</span>
                        <strong><?= $pendientes ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <span>Revisados</span>
                        <strong><?= $revisados ?></strong>
                    </div>
                </div>

                <div class="user-stat-card">
                    <div class="user-stat-icon purple">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <div>
                        <span>Descartados</span>
                        <strong><?= $descartados ?></strong>
                    </div>
                </div>

            </div>


            <div class="users-card">

                <div class="users-toolbar">
                    <div>
                        <h3>Historial de reportes</h3>
                        <span><?= $total ?> reportes registrados</span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Referencia</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Reportado por</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php if (count($reportes) > 0): ?>

                                <?php foreach ($reportes as $r): ?>

                                    <tr>
                                        <td><?= htmlspecialchars($r["tipo"]) ?></td>
                                        <td><?= htmlspecialchars($r["referencia"] ?? "-") ?></td>
                                        <td><?= htmlspecialchars($r["motivo"]) ?></td>
                                        <td>
                                            <?php if ($r["estado"] === "Revisado"): ?>
                                                <span class="status active-status">Revisado</span>
                                            <?php elseif ($r["estado"] === "Descartado"): ?>
                                                <span class="status inactive-status">Descartado</span>
                                            <?php else: ?>
                                                <span class="status" style="background:#fff7ed; color:#f97316;">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars(
                                                trim(($r["creado_por_nombre"] ?? "") . " " . ($r["creado_por_apellido"] ?? "")) ?: "Sistema"
                                            ) ?>
                                        </td>
                                        <td><?= date("d M Y", strtotime($r["fecha_creacion"])) ?></td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="6" style="text-align:center; padding:30px;">
                                        Todavía no hay reportes registrados.
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

