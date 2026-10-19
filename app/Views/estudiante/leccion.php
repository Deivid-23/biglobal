<?php
/**
 * Una lección: contenido escrito por el docente + actividades.
 * Variables: $leccion, $actividades
 *
 * Nota: por ahora las actividades solo se leen. Para que se puedan
 * resolver (quiz con respuestas) hace falta guardar preguntas y
 * opciones en la BD: está en el plan de la siguiente versión.
 */
$hecha = (int) $leccion["completado"] === 1;

$iconosTipo = [
    "quiz"      => "fa-circle-question",
    "tarea"     => "fa-pen-to-square",
    "taller"    => "fa-people-group",
    "ejercicio" => "fa-dumbbell",
];
?>

<a class="enlace-atras" href="index.php?page=curso&amp;id=<?= (int) $leccion["curso_id"] ?>">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= e($leccion["curso_nombre"]) ?>
</a>

<article class="leccion">
    <header class="leccion__cabecera">
        <p class="curso__meta">Lección <?= (int) $leccion["posicion"] ?></p>
        <h1><?= e($leccion["titulo"]) ?></h1>
        <?php if (!empty($leccion["descripcion"])): ?>
            <p class="leccion__resumen"><?= e($leccion["descripcion"]) ?></p>
        <?php endif; ?>
    </header>

    <div class="leccion__contenido">
        <?php if (trim((string) $leccion["contenido"]) !== ""): ?>
            <?= nl2br(e($leccion["contenido"])) ?>
        <?php else: ?>
            <p class="texto-suave">El docente aún no agregó el contenido escrito de esta lección.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($actividades)): ?>
        <section class="actividades" aria-labelledby="titulo-actividades">
            <h2 id="titulo-actividades">Actividades</h2>
            <ul class="actividades__lista">
                <?php foreach ($actividades as $a):
                    $tipo = mb_strtolower(trim((string) $a["tipo"]));
                ?>
                    <li class="actividad">
                        <i class="fa-solid <?= e($iconosTipo[$tipo] ?? "fa-list-check") ?>" aria-hidden="true"></i>
                        <div>
                            <p class="actividad__titulo">
                                <?= e($a["titulo"]) ?>
                                <?php if (!empty($a["tipo"])): ?><span class="chip chip--neutro"><?= e($a["tipo"]) ?></span><?php endif; ?>
                            </p>
                            <?php if (!empty($a["descripcion"])): ?>
                                <p class="actividad__texto"><?= nl2br(e($a["descripcion"])) ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <footer class="leccion__pie">
        <?php if ($hecha): ?>
            <span class="chip chip--exito"><i class="fa-solid fa-check" aria-hidden="true"></i> Ya completaste esta lección</span>
            <a class="boton boton--secundario" href="index.php?page=curso&amp;id=<?= (int) $leccion["curso_id"] ?>">Volver al curso</a>
        <?php else: ?>
            <p class="texto-suave">¿Terminaste de estudiarla? Márcala para guardar tu avance.</p>
            <form method="POST" action="completar_leccion.php">
                <?= campoCsrf() ?>
                <input type="hidden" name="leccion_id" value="<?= (int) $leccion["id"] ?>">
                <input type="hidden" name="curso_id" value="<?= (int) $leccion["curso_id"] ?>">
                <button type="submit" class="boton boton--exito">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Terminé esta lección
                </button>
            </form>
        <?php endif; ?>
    </footer>
</article>
