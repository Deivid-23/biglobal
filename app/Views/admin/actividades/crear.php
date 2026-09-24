<?php

/** @var array $leccion */
$leccionId = $leccion["id"];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Nueva actividad</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <div class="admin-container">

        <main class="main-content" style="width: 100%; margin-left: 0;">

            <header class="topbar">
                <div class="topbar-left">
                    <a href="actividades.php?leccion=<?= urlencode($leccionId) ?>"
                        class="menu-toggle"
                        style="display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div>
                        <p class="panel-label">
                            <?= htmlspecialchars($leccion["curso_nombre"]) ?> · <?= htmlspecialchars($leccion["titulo"]) ?>
                        </p>
                        <h1>Nueva actividad</h1>
                    </div>
                </div>
            </header>

            <section class="users-page">

                <div class="users-card" style="max-width:700px; margin:auto; padding:25px;">

                    <form method="POST">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCsrfToken()) ?>">
                        <input type="hidden" name="leccion_id" value="<?= $leccionId ?>">

                        <div class="form-group">
                            <label for="titulo">Título de la actividad</label>
                            <input type="text" id="titulo" name="titulo" placeholder="Ej. Quiz de vocabulario" required>
                        </div>

                        <div class="form-row">

                            <div class="form-group">
                                <label for="tipo">Tipo</label>
                                <select id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option value="Quiz">Quiz</option>
                                    <option value="Lectura">Lectura</option>
                                    <option value="Audio">Audio</option>
                                    <option value="Video">Video</option>
                                    <option value="Ejercicio">Ejercicio</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="orden">Orden</label>
                                <input type="number" id="orden" name="orden" min="1" value="1" required>
                            </div>

                        </div>

                        <div class="form-group">
                            <label for="contenido">Contenido</label>
                            <textarea
                                id="contenido"
                                name="contenido"
                                rows="5"
                                placeholder="Instrucciones, pregunta, enlace del recurso, etc."
                                style="width:100%; border:1px solid #d0d5dd; border-radius:9px; padding:12px; resize:vertical; font-family:inherit; font-size:12px; outline:none;"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <option value="Borrador">Borrador</option>
                                <option value="Publicado">Publicada</option>
                                <option value="Inactivo">Inactiva</option>
                            </select>
                        </div>

                        <div class="modal-actions" style="margin-top:25px;">
                            <a href="actividades.php?leccion=<?= urlencode($leccionId) ?>" class="cancel-btn">Cancelar</a>
                            <button type="submit" class="save-btn">
                                <i class="fa-solid fa-plus"></i>
                                Crear actividad
                            </button>
                        </div>

                    </form>

                </div>

            </section>

        </main>

    </div>

</body>

</html>