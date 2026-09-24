<?php

/** @var array $actividad */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Editar actividad</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <div class="admin-container">

        <main class="main-content" style="width: 100%; margin-left: 0;">

            <header class="topbar">
                <div class="topbar-left">
                    <a href="actividades.php?leccion=<?= urlencode($actividad["leccion_id"]) ?>"
                        class="menu-toggle"
                        style="display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <p class="panel-label">
                            <?= htmlspecialchars($actividad["curso_nombre"]) ?> · <?= htmlspecialchars($actividad["leccion_titulo"]) ?>
                        </p>
                        <h1>Editar actividad</h1>
                    </div>
                </div>
            </header>

            <section class="users-page">

                <div class="users-card" style="max-width:700px; margin:auto; padding:25px;">

                    <form method="POST">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCsrfToken()) ?>">

                        <div class="form-group">
                            <label for="titulo">Título de la actividad</label>
                            <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($actividad["titulo"]) ?>" required>
                        </div>

                        <div class="form-row">

                            <div class="form-group">
                                <label for="tipo">Tipo</label>
                                <select id="tipo" name="tipo" required>
                                    <?php foreach (["Quiz", "Lectura", "Audio", "Video", "Ejercicio"] as $opcion): ?>
                                        <option value="<?= $opcion ?>" <?= $actividad["tipo"] === $opcion ? "selected" : "" ?>>
                                            <?= $opcion ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="orden">Orden</label>
                                <input type="number" id="orden" name="orden" min="1" value="<?= (int) $actividad["orden"] ?>" required>
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="contenido">Contenido</label>
                            <textarea
                                id="contenido"
                                name="contenido"
                                rows="5"
                                style="width:100%; border:1px solid #d0d5dd; border-radius:9px; padding:12px; resize:vertical; font-family:inherit; font-size:12px; outline:none;"><?= htmlspecialchars($actividad["contenido"] ?? "") ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="Borrador" <?= $actividad["estado"] === "Borrador" ? "selected" : "" ?>>Borrador</option>
                                <option value="Publicado" <?= $actividad["estado"] === "Publicado" ? "selected" : "" ?>>Publicada</option>
                                <option value="Inactivo" <?= $actividad["estado"] === "Inactivo" ? "selected" : "" ?>>Inactiva</option>
                            </select>
                        </div>

                        <div class="modal-actions" style="margin-top:25px;">
                            <a href="actividades.php?leccion=<?= urlencode($actividad["leccion_id"]) ?>" class="cancel-btn">Cancelar</a>
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