<?php
/** @var array $curso */
/** @var array $instructores */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Editar curso</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="admin-container">

    <main class="main-content" style="width:100%; margin-left:0;">

        <header class="topbar">
            <div class="topbar-left">
                <a href="cursos.php" class="menu-toggle" style="display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <p class="panel-label">Gestión académica</p>
                    <h1>Editar curso</h1>
                </div>
            </div>
        </header>

        <section class="users-page">
            <div class="users-card" style="max-width:700px; margin:auto; padding:25px;">

                <div style="margin-bottom:25px;">
                    <span class="section-label">Catálogo de cursos</span>
                    <h2 style="font-size:23px;">Editar curso</h2>
                    <p style="color:#667085; font-size:13px; margin-top:6px;">
                        Modifica la información del curso seleccionado.
                    </p>
                </div>

                <form method="POST">

                    <div class="form-group">
                        <label for="nombre">Nombre del curso</label>
                        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($curso["nombre"]) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="4"
                                  style="width:100%; border:1px solid #d0d5dd; border-radius:9px; padding:12px; resize:vertical; font-family:inherit; font-size:12px; outline:none;"><?= htmlspecialchars($curso["descripcion"] ?? "") ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nivel">Nivel</label>
                            <select id="nivel" name="nivel" required>
                                <option value="Básico" <?= $curso["nivel"] === "Básico" ? "selected" : "" ?>>Básico</option>
                                <option value="Intermedio" <?= $curso["nivel"] === "Intermedio" ? "selected" : "" ?>>Intermedio</option>
                                <option value="Avanzado" <?= $curso["nivel"] === "Avanzado" ? "selected" : "" ?>>Avanzado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="Borrador" <?= $curso["estado"] === "Borrador" ? "selected" : "" ?>>Borrador</option>
                                <option value="Publicado" <?= $curso["estado"] === "Publicado" ? "selected" : "" ?>>Publicado</option>
                                <option value="Inactivo" <?= $curso["estado"] === "Inactivo" ? "selected" : "" ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="instructor_id">Instructor</label>
                        <select id="instructor_id" name="instructor_id">
                            <option value="">Sin instructor</option>
                            <?php foreach ($instructores as $instructor): ?>
                                <option value="<?= $instructor["id"] ?>"
                                    <?= (string) $curso["instructor_id"] === (string) $instructor["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($instructor["nombre"] . " " . $instructor["apellido"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <a href="cursos.php" class="cancel-btn" style="display:flex; align-items:center;">Cancelar</a>
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