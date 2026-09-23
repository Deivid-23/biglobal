<?php
/** @var array $certificados */
/** @var int $totalCertificados */
/** @var int $esteMes */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Certificados</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <?php
    $paginaActiva = "certificados";
    require __DIR__ . "/../../partials/admin_sidebar.php";
    ?>

    <main class="main-content">

        <?php
        $tituloPagina = "Certificados";
        $panelLabel = "Control y analítica";
        require __DIR__ . "/../../partials/admin_topbar.php";
        ?>

        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">Control y analítica</span>
                    <h2>Certificados emitidos</h2>
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
        menuToggle.addEventListener("click", () => sidebar.classList.toggle("show"));
    }
</script>

</body>
</html>