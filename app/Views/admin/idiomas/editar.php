<?php
/** @var array $idioma */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Editar idioma</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <main class="main-content" style="width:100%; margin-left:0;">

        <header class="topbar">
            <div class="topbar-left">
                <a href="idiomas.php" class="menu-toggle" style="display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <p class="panel-label">Gestión de plataforma</p>
                    <h1>Editar idioma</h1>
                </div>
            </div>
        </header>

        <section class="users-page">
            <div class="users-card" style="max-width:600px; margin:auto; padding:25px;">

                <div style="margin-bottom:25px;">
                    <span class="section-label">Gestión académica</span>
                    <h2 style="font-size:23px;">Editar idioma</h2>
                    <p style="color:#667085; font-size:13px; margin-top:6px;">
                        Modifica la información del idioma seleccionado.
                    </p>
                </div>

                <form method="POST">

                    <div class="form-group">
                        <label for="nombre">Nombre del idioma</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($idioma["nombre"]) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="codigo">Código</label>
                        <input type="text" id="codigo" name="codigo" value="<?= htmlspecialchars($idioma["codigo"]) ?>" maxlength="10" required>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="Activo" <?= $idioma["estado"] === "Activo" ? "selected" : "" ?>>Activo</option>
                            <option value="Inactivo" <?= $idioma["estado"] === "Inactivo" ? "selected" : "" ?>>Inactivo</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <a href="idiomas.php" class="cancel-btn" style="display:flex; align-items:center;">Cancelar</a>
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

</body>
</html>