<?php
/**
 * Reproductor de una lección de la ruta (lo maneja js/ruta.js).
 * Variables: $leccion, $urlRuta, $video (o null), $ejercicios (sin respuestas), $repaso
 *
 * Los ejercicios llegan SIN la respuesta: cada respuesta se envía a
 * ruta_responder.php, que la califica y dice cuál sigue.
 */
?>
<div class="ruta-jugar" data-ruta
     data-leccion="<?= (int) $leccion["id"] ?>"
     data-url-ruta="<?= e($urlRuta) ?>"
     data-url-repetir="index.php?page=ruta-leccion&amp;id=<?= (int) $leccion["id"] ?>"
     data-ejercicios="<?= e(json_encode($ejercicios, JSON_UNESCAPED_UNICODE)) ?>">

    <div class="ruta-jugar__barra">
        <a class="ruta-jugar__salir" href="<?= e($urlRuta) ?>" aria-label="Salir de la lección" data-salir>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </a>
        <div class="progreso ruta-jugar__progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Avance de la lección" data-progreso>
            <div class="progreso__relleno" style="width: 0%" data-valor="0"></div>
        </div>
    </div>

    <section class="ruta-intro" data-intro>
        <p class="ruta-intro__nivel"><?= e($leccion["idioma"]) ?> · <?= e($leccion["nivel_codigo"]) ?> · <?= e($leccion["unidad"]) ?></p>
        <h1><?= e($leccion["titulo"]) ?></h1>
        <?php if ($leccion["descripcion"]): ?><p><?= e($leccion["descripcion"]) ?></p><?php endif; ?>
        <?php if ($video): ?>
            <div class="leccion__video-marco ruta-intro__video">
                <iframe src="<?= e($video["src"]) ?>" title="Video: <?= e($leccion["titulo"]) ?>" loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
            </div>
        <?php elseif (strtoupper($leccion["idioma_codigo"]) === "LSC"): ?>
            <p class="ruta-intro__aviso"><i class="fa-solid fa-video" aria-hidden="true"></i> El video de esta lección está en preparación.</p>
        <?php endif; ?>
        <p class="ruta-intro__meta">
            <?= e(plural(count($ejercicios), "ejercicio", "ejercicios")) ?> ·
            <?= $repaso ? "Repaso: +" . RutaModel::XP_PRACTICA . " XP (una vez al día)" : "+" . (int) $leccion["xp"] . " XP" ?>
        </p>
        <button class="boton boton--primario boton--bloque" type="button" data-empezar><?= $repaso ? "Repasar" : "Empezar" ?></button>
    </section>

    <section class="ruta-ejercicio" data-ejercicio hidden aria-live="polite"></section>

    <div class="ruta-retro" data-retro hidden role="status">
        <p class="ruta-retro__titulo" data-retro-titulo></p>
        <p class="ruta-retro__solucion" data-retro-solucion></p>
    </div>

    <div class="ruta-jugar__pie" data-pie hidden>
        <button class="boton boton--primario boton--bloque" type="button" data-comprobar disabled>Comprobar</button>
    </div>

    <section class="ruta-fin" data-fin hidden>
        <span class="ruta-fin__icono" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>
        <h2>¡Lección completada!</h2>
        <dl class="ruta-fin__datos">
            <div><dt>Aciertos a la primera</dt><dd data-fin-porcentaje>—</dd></div>
            <div><dt>XP ganado</dt><dd data-fin-xp>—</dd></div>
            <div data-fin-racha-caja hidden><dt>Racha</dt><dd data-fin-racha>—</dd></div>
        </dl>
        <p class="ruta-fin__mensaje" data-fin-mensaje></p>
        <p class="ruta-fin__insignia" data-fin-insignia hidden><i class="fa-solid fa-award" aria-hidden="true"></i> <span></span></p>
        <a class="boton boton--primario boton--bloque" href="<?= e($urlRuta) ?>">Seguir en mi ruta</a>
        <a class="boton boton--fantasma boton--bloque" href="index.php?page=ruta-leccion&amp;id=<?= (int) $leccion["id"] ?>">Repetir lección</a>
    </section>

    <noscript><p class="aviso aviso--alerta">Para hacer los ejercicios necesitas activar JavaScript.</p></noscript>
</div>

<script src="js/ruta.js<?= version("estudiante/js/ruta.js") ?>" defer></script>
