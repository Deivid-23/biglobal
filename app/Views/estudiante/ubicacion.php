<?php
/**
 * Examen de ubicación (lo maneja js/ruta.js en modo "ubicacion").
 * Variables: $idioma, $urlRuta, $niveles (códigos del examen), $ejercicios (sin respuestas)
 *
 * Llegan los ejercicios de todos los niveles, sin la respuesta. Cada
 * respuesta va a ubicacion_responder.php, que la califica y decide si
 * sigue en el nivel, pasa al siguiente o termina.
 */
?>
<div class="ruta-jugar" data-ruta data-modo="ubicacion"
     data-endpoint="ubicacion_responder.php"
     data-idioma="<?= (int) $idioma["id"] ?>"
     data-salir-texto="¿Salir del examen? Hoy ya no podrás repetirlo."
     data-ejercicios="<?= e(json_encode($ejercicios, JSON_UNESCAPED_UNICODE)) ?>">

    <div class="ruta-jugar__barra">
        <a class="ruta-jugar__salir" href="<?= e($urlRuta) ?>" aria-label="Salir del examen" data-salir>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </a>
        <div class="progreso ruta-jugar__progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Avance del examen" data-progreso>
            <div class="progreso__relleno" style="width: 0%" data-valor="0"></div>
        </div>
    </div>

    <section class="ruta-intro" data-intro>
        <p class="ruta-intro__nivel"><?= e($idioma["nombre"]) ?> · <?= e(implode(" → ", $niveles)) ?></p>
        <h1>Examen de ubicación</h1>
        <p>Si ya sabes algo de <?= e(mb_strtolower($idioma["nombre"])) ?>, empieza en tu nivel en vez de empezar desde el principio.</p>
        <ul class="ubicacion-reglas">
            <li><i class="fa-solid fa-layer-group" aria-hidden="true"></i> <?= UbicacionModel::POR_NIVEL ?> ejercicios por nivel, desde el <?= e($niveles[0]) ?>.</li>
            <li><i class="fa-solid fa-check" aria-hidden="true"></i> Con <?= UbicacionModel::APROBAR ?> aciertos o más pasas al siguiente nivel.</li>
            <li><i class="fa-solid fa-unlock" aria-hidden="true"></i> Se abren tu nivel y los anteriores, para repasar cuando quieras.</li>
            <li><i class="fa-regular fa-clock" aria-hidden="true"></i> Unos 10 minutos. Una vez al día; se guarda tu mejor resultado.</li>
        </ul>
        <p class="ruta-intro__meta">No da XP: los XP los ganas en las lecciones.</p>
        <button class="boton boton--primario boton--bloque" type="button" data-empezar>Empezar el examen</button>
        <a class="boton boton--fantasma boton--bloque" href="<?= e($urlRuta) ?>">Ahora no</a>
    </section>

    <section class="ruta-ejercicio" data-ejercicio hidden></section>

    <div class="ruta-retro" data-retro hidden>
        <p class="ruta-retro__titulo" data-retro-titulo></p>
        <p class="ruta-retro__solucion" data-retro-solucion></p>
    </div>

    <div class="ruta-jugar__pie" data-pie hidden>
        <button class="boton boton--primario boton--bloque" type="button" data-comprobar disabled>Comprobar</button>
    </div>

    <section class="ruta-fin" data-fin hidden>
        <span class="ruta-fin__icono" aria-hidden="true"><i class="fa-solid fa-flag-checkered"></i></span>
        <h2 data-fin-titulo>Examen terminado</h2>
        <p class="ruta-fin__mensaje" data-fin-mensaje></p>
        <p class="ruta-fin__detalle" data-fin-detalle></p>
        <a class="boton boton--primario boton--bloque" href="<?= e($urlRuta) ?>" data-fin-ir>Ir a mi ruta</a>
    </section>

    <noscript><p class="aviso aviso--alerta">Para hacer el examen necesitas activar JavaScript.</p></noscript>
</div>

<script src="js/ruta.js<?= version("estudiante/js/ruta.js") ?>" defer></script>
