<?php
/**
 * Repaso de errores (lo maneja js/ruta.js en modo "repaso").
 * Variables: $idioma, $urlRuta, $pendientes (total por repasar), $ejercicios (sin respuestas)
 *
 * Los ejercicios llegan SIN la respuesta: cada respuesta va a
 * repaso_responder.php, que la califica y la saca de la lista si acierta.
 */
?>
<div class="ruta-jugar" data-ruta data-modo="repaso"
     data-endpoint="repaso_responder.php"
     data-idioma="<?= (int) $idioma["id"] ?>"
     data-salir-texto="¿Salir del repaso? Los ejercicios que no respondiste siguen en tu lista."
     data-ejercicios="<?= e(json_encode($ejercicios, JSON_UNESCAPED_UNICODE)) ?>">

    <div class="ruta-jugar__barra">
        <a class="ruta-jugar__salir" href="<?= e($urlRuta) ?>" aria-label="Salir del repaso" data-salir>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </a>
        <div class="progreso ruta-jugar__progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Avance del repaso" data-progreso>
            <div class="progreso__relleno" style="width: 0%" data-valor="0"></div>
        </div>
    </div>

    <section class="ruta-intro" data-intro>
        <p class="ruta-intro__nivel"><?= e($idioma["nombre"]) ?> · <?= e(plural((int) $pendientes, "ejercicio por repasar", "ejercicios por repasar")) ?></p>
        <h1>Repaso de errores</h1>
        <p>Ejercicios que fallaste en tus lecciones, empezando por los más viejos. Si aciertas, salen de tu lista; si fallas, ves la respuesta y quedan para otro día.</p>
        <p class="ruta-intro__meta">
            <?= e(plural(count($ejercicios), "ejercicio", "ejercicios")) ?> ·
            +<?= RutaModel::XP_PRACTICA ?> XP (una vez al día) · cuenta para tu racha
        </p>
        <button class="boton boton--primario boton--bloque" type="button" data-empezar>Empezar el repaso</button>
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
        <span class="ruta-fin__icono" aria-hidden="true"><i class="fa-solid fa-rotate-left"></i></span>
        <h2>¡Repaso terminado!</h2>
        <dl class="ruta-fin__datos">
            <div><dt>Resueltos</dt><dd data-fin-porcentaje>—</dd></div>
            <div><dt>XP ganado</dt><dd data-fin-xp>—</dd></div>
            <div data-fin-racha-caja hidden><dt>Racha</dt><dd data-fin-racha>—</dd></div>
        </dl>
        <p class="ruta-fin__mensaje" data-fin-mensaje></p>
        <a class="boton boton--primario boton--bloque" href="<?= e($urlRuta) ?>">Seguir en mi ruta</a>
        <a class="boton boton--fantasma boton--bloque" href="index.php?page=repaso&amp;idioma=<?= e(rawurlencode($idioma["codigo"])) ?>" data-fin-otro>Otro repaso</a>
    </section>

    <noscript><p class="aviso aviso--alerta">Para hacer el repaso necesitas activar JavaScript.</p></noscript>
</div>

<script src="js/ruta.js<?= version("estudiante/js/ruta.js") ?>" defer></script>
