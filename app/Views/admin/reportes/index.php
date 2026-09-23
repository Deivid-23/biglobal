<?php
/** @var array $reportes */
/** @var int $total */
/** @var int $pendientes */
/** @var int $revisados */
/** @var int $descartados */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Reportes</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <?php
    $paginaActiva = "reportes";
    require __DIR__ . "/../../partials/admin_sidebar.php";
    ?>

    <main class="main-content">

        <?php
        $tituloPagina = "Reportes";
        $panelLabel = "Control y analítica";
        require __DIR__ . "/../../partials/admin_topbar.php";
        ?>

        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">Control y analítica</span>
                    <h2>Reportes de la plataforma</h2>
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
        menuToggle.addEventListener("click", () => sidebar.classList.toggle("show"));
    }
</script>

</body>
</html>