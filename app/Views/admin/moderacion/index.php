<?php
/** @var array $pendientes */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Moderación</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <?php
    $paginaActiva = "moderacion";
    require __DIR__ . "/../../partials/admin_sidebar.php";
    ?>

    <main class="main-content">

        <?php
        $tituloPagina = "Moderación";
        $panelLabel = "Control y analítica";
        require __DIR__ . "/../../partials/admin_topbar.php";
        ?>

        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">Control y analítica</span>
                    <h2>Cola de moderación</h2>
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
        menuToggle.addEventListener("click", () => sidebar.classList.toggle("show"));
    }
</script>

</body>
</html>