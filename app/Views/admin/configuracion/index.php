<?php
/** @var bool $guardado */
/** @var string $nombreSitio */
/** @var string $correoContacto */
/** @var bool $modoMantenimiento */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Configuración</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <?php
    $paginaActiva = "configuracion";
    require __DIR__ . "/../../partials/admin_sidebar.php";
    ?>

    <main class="main-content">

        <?php
        $tituloPagina = "Configuración";
        $panelLabel = "Sistema";
        require __DIR__ . "/../../partials/admin_topbar.php";
        ?>

        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">Sistema</span>
                    <h2>Configuración general</h2>
                    <p>Ajustes básicos de la plataforma BiGlobal.</p>
                </div>
            </div>

            <div class="users-card" style="max-width:600px; padding:25px;">

                <?php if ($guardado): ?>
                    <div style="background:#ecfdf3; color:#12b76a; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px;">
                        Configuración guardada correctamente.
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label for="nombre_sitio">Nombre del sitio</label>
                        <input type="text" id="nombre_sitio" name="nombre_sitio" value="<?= htmlspecialchars($nombreSitio) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="correo_contacto">Correo de contacto</label>
                        <input type="email" id="correo_contacto" name="correo_contacto" value="<?= htmlspecialchars($correoContacto) ?>" required>
                    </div>

                    <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" id="modo_mantenimiento" name="modo_mantenimiento" style="width:auto;" <?= $modoMantenimiento ? "checked" : "" ?>>
                        <label for="modo_mantenimiento" style="margin:0;">Activar modo mantenimiento</label>
                    </div>

                    <div class="modal-actions" style="margin-top:20px;">
                        <button type="submit" class="save-btn">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar cambios
                        </button>
                    </div>

                </form>

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