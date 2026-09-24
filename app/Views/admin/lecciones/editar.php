<?php

/** @var array $leccion */
/** @var array $cursos */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Editar lección</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <div class="admin-container">

        <main class="main-content" style="width:100%; margin-left:0;">

            <header class="topbar">
                <div class="topbar-left">
                    <a href="lecciones.php" class="menu-toggle" style="display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <p class="panel-label">Gestión académica</p>
                        <h1>Editar lección</h1>
                    </div>
                </div>
            </header>

            <section class="users-page">
                <div class="users-card" style="max-width:700px; margin:auto; padding:25px;">

                    <div style="margin-bottom:25px;">
                        <span class="section-label">Contenido educativo</span>
                        <h2 style="font-size:23px;">Editar lección</h2>
                        <p style="color:#667085; font-size:13px; margin-top:6px;">
                            Modifica la información de la lección seleccionada.
                        </p>
                    </div>

                    <form method="POST">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCsrfToken()) ?>">

                        <div class="form-group">
                            <label for="curso_id">Curso</label>
                            <select id="curso_id" name="curso_id" required>
                                <option value="">Seleccionar curso</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?= $curso["id"] ?>" <?= (string) $leccion["curso_id"] === (string) $curso["id"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars($curso["nombre"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="titulo">Título de la lección</label>
                            <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($leccion["titulo"]) ?>" placeholder="Ej. Saludos y presentaciones" required>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="5" placeholder="Describe el contenido de la lección..."><?= htmlspecialchars($leccion["descripcion"] ?? "") ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="orden">Orden</label>
                                <input type="number" id="orden" name="orden" min="1" value="<?= (int) $leccion["orden"] ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado">
                                    <option value="Borrador" <?= $leccion["estado"] === "Borrador" ? "selected" : "" ?>>Borrador</option>
                                    <option value="Publicado" <?= $leccion["estado"] === "Publicado" ? "selected" : "" ?>>Publicada</option>
                                    <option value="Inactivo" <?= $leccion["estado"] === "Inactivo" ? "selected" : "" ?>>Inactiva</option>
                                </select>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <a href="lecciones.php" class="cancel-btn">Cancelar</a>
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