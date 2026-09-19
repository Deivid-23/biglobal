<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

/* =========================
   ACCIONES DE MODERACION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"], $_POST["id"])) {

    $id = $_POST["id"];
    $accion = $_POST["accion"];

    $nuevoEstado = null;

    if ($accion === "aprobar") {
        $nuevoEstado = "Revisado";
    } elseif ($accion === "descartar") {
        $nuevoEstado = "Descartado";
    }

    if ($nuevoEstado && is_numeric($id)) {

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

    header("Location: moderacion.php");
    exit;
}

/* =========================
   REPORTES PENDIENTES
========================= */

$stmt = $conexion->prepare("
    SELECT
        rc.id,
        rc.tipo,
        rc.referencia,
        rc.motivo,
        rc.fecha_creacion,
        u.nombre AS creado_por_nombre,
        u.apellido AS creado_por_apellido
    FROM reportes_contenido rc
    LEFT JOIN usuarios u
        ON rc.creado_por = u.id
    WHERE rc.estado = 'Pendiente'
    ORDER BY rc.fecha_creacion ASC
");

$stmt->execute();

$pendientes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Moderación</title>

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


            <a href="moderacion.php" class="menu-item active">

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
                        Moderación
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
                        Cola de moderación
                    </h2>
                    <p>
                        Revisa el contenido reportado por la comunidad y decide si se
                        mantiene o se descarta. El historial completo queda en
                        <a href="reportes.php">Reportes</a>.
                    </p>
                </div>
            </div>

            <div class="users-card">

                <div class="users-toolbar">
                    <div>
                        <h3>Pendientes por revisar</h3>
                        <span><?= count($pendientes) ?> elementos en cola</span>
                    </div>
                </div>

                <?php if (count($pendientes) > 0): ?>

                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Referencia</th>
                                    <th>Motivo reportado</th>
                                    <th>Reportado por</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php foreach ($pendientes as $r): ?>

                                    <tr>
                                        <td><?= htmlspecialchars($r["tipo"]) ?></td>
                                        <td><?= htmlspecialchars($r["referencia"] ?? "-") ?></td>
                                        <td><?= htmlspecialchars($r["motivo"]) ?></td>
                                        <td>
                                            <?= htmlspecialchars(
                                                trim(($r["creado_por_nombre"] ?? "") . " " . ($r["creado_por_apellido"] ?? "")) ?: "Sistema"
                                            ) ?>
                                        </td>
                                        <td><?= date("d M Y", strtotime($r["fecha_creacion"])) ?></td>
                                        <td>
                                            <div class="action-buttons">

                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= $r["id"] ?>">
                                                    <input type="hidden" name="accion" value="aprobar">
                                                    <button class="table-action edit" type="submit" title="Marcar como revisado">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>

                                                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Descartar este reporte?');">
                                                    <input type="hidden" name="id" value="<?= $r["id"] ?>">
                                                    <input type="hidden" name="accion" value="descartar">
                                                    <button class="table-action delete" type="submit" title="Descartar">
                                                        <i class="fa-solid fa-xmark"></i>
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                <?php else: ?>

                    <div style="text-align:center; padding:40px;">
                        <i class="fa-solid fa-circle-check" style="font-size:30px; color:#12b76a; margin-bottom:10px;"></i>
                        <p style="color:#667085;">No hay nada pendiente por moderar. ¡Todo al día!</p>
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

