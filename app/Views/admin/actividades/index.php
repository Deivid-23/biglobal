<?php
/** @var array $leccion */
/** @var array $actividades */
$leccionId = $leccion["id"];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Actividades</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

<div class="activities-page">

    <div class="activities-header">

        <a href="lecciones.php?curso=<?= urlencode($leccion["curso_id"]) ?>" class="activities-back">
            <i class="fa-solid fa-arrow-left"></i>
            Volver a lecciones
        </a>

        <div class="activities-heading">
            <div>
                <span class="activities-course">
                    <i class="fa-solid fa-book"></i>
                    <?= htmlspecialchars($leccion["curso_nombre"]) ?>
                </span>
                <h1><?= htmlspecialchars($leccion["titulo"]) ?></h1>
                <p><?= htmlspecialchars($leccion["descripcion"] ?: "Esta lección todavía no tiene descripción.") ?></p>
            </div>

            <a href="guardar_actividad.php?leccion=<?= $leccionId ?>" class="add-activity-btn">
                <i class="fa-solid fa-plus"></i>
                Nueva actividad
            </a>
        </div>
    </div>

    <main class="activities-content">

        <?php if (count($actividades) > 0): ?>

            <?php foreach ($actividades as $actividad): ?>

                <?php
                $tipo = strtolower(trim($actividad["tipo"] ?? ""));

                switch ($tipo) {
                    case "quiz":
                        $icono = "fa-circle-question";
                        $claseIcono = "quiz";
                        break;
                    case "audio":
                    case "escucha":
                        $icono = "fa-headphones";
                        $claseIcono = "audio";
                        break;
                    case "video":
                        $icono = "fa-video";
                        $claseIcono = "video";
                        break;
                    case "lectura":
                        $icono = "fa-book-open";
                        $claseIcono = "lectura";
                        break;
                    case "ejercicio":
                    case "escritura":
                    case "completar":
                        $icono = "fa-pen";
                        $claseIcono = "ejercicio";
                        break;
                    default:
                        $icono = "fa-list-check";
                        $claseIcono = "general";
                        break;
                }
                ?>

                <article class="activity-card">

                    <div class="activity-number">
                        <?= str_pad($actividad["orden"], 2, "0", STR_PAD_LEFT) ?>
                    </div>

                    <div class="activity-type-icon <?= $claseIcono ?>">
                        <i class="fa-solid <?= $icono ?>"></i>
                    </div>

                    <div class="activity-info">

                        <span class="activity-type"><?= htmlspecialchars($actividad["tipo"]) ?></span>

                        <h2><?= htmlspecialchars($actividad["titulo"]) ?></h2>

                        <p><?= htmlspecialchars($actividad["contenido"] ?: "Esta actividad todavía no tiene contenido.") ?></p>

                        <div class="activity-details">

                            <?php if ($actividad["estado"] === "Publicado"): ?>
                                <span class="activity-status published">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Publicada
                                </span>
                            <?php elseif ($actividad["estado"] === "Inactivo"): ?>
                                <span class="activity-status inactive">
                                    <i class="fa-solid fa-circle-pause"></i>
                                    Inactiva
                                </span>
                            <?php else: ?>
                                <span class="activity-status draft">
                                    <i class="fa-solid fa-file-pen"></i>
                                    Borrador
                                </span>
                            <?php endif; ?>

                            <span class="activity-order">
                                Actividad <?= (int) $actividad["orden"] ?>
                            </span>
                        </div>
                    </div>

                    <div class="activity-actions">

                        <a href="editar_actividad.php?id=<?= $actividad["id"] ?>" class="activity-action edit" title="Editar actividad">
                            <i class="fa-solid fa-pen"></i>
                        </a>

                        <form action="eliminar_actividad.php" method="POST" onsubmit="return confirmarEliminacionActividad();">
                            <input type="hidden" name="id" value="<?= $actividad["id"] ?>">
                            <button type="submit" class="activity-action delete" title="Eliminar actividad">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="activities-empty">
                <div class="activities-empty-icon">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <h2>Aún no hay actividades</h2>
                <p>Esta lección todavía no tiene actividades. Crea la primera para comenzar a construir el contenido.</p>
                <a href="guardar_actividad.php?leccion=<?= $leccionId ?>" class="add-activity-btn">
                    <i class="fa-solid fa-plus"></i>
                    Crear primera actividad
                </a>
            </div>

        <?php endif; ?>

    </main>

</div>

<script>
    function confirmarEliminacionActividad() {
        return confirm("¿Estás seguro de que deseas eliminar esta actividad?");
    }
</script>

</body>
</html>