<?php
/**
 * Práctica rápida contra el reloj (lo maneja js/ruta.js en modo "contrarreloj").
 * Variables: $idioma, $urlRuta, $marca (mejor marca o null), $ejercicios (sin respuestas)
 *
 * El reloj de la página es solo para mostrar: el tiempo lo lleva el servidor
 * (ContrarrelojModel), que no acepta respuestas fuera de tiempo.
 */
?>
<div class="ruta-jugar" data-ruta data-modo="contrarreloj"
     data-endpoint="contrarreloj_responder.php"
     data-idioma="<?= (int) $idioma["id"] ?>"
     data-segundos="<?= ContrarrelojModel::DURACION ?>"
     data-salir-texto="¿Salir de la práctica rápida? Esta partida no se guardará."
     data-ejercicios="<?= e(json_encode($ejercicios, JSON_UNESCAPED_UNICODE)) ?>">

    <div class="ruta-jugar__barra">
        <a class="ruta-jugar__salir" href="<?= e($urlRuta) ?>" aria-label="Salir de la práctica rápida" data-salir>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </a>
        <div class="progreso ruta-jugar__progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" aria-label="Avance" data-progreso>
            <div class="progreso__relleno" style="width: 0%" data-valor="0"></div>
        </div>
    </div>

    <div class="contrarreloj-marcador" data-marcador hidden>
        <span class="contrarreloj-marcador__reloj" aria-live="off"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i>
            <span class="numeros" data-reloj><?= ContrarrelojModel::DURACION ?></span> s</span>
        <span class="contrarreloj-marcador__aciertos"><i class="fa-solid fa-check" aria-hidden="true"></i> <span class="numeros" data-aciertos>0 aciertos</span></span>
    </div>

    <section class="ruta-intro" data-intro>
        <p class="ruta-intro__nivel"><?= e($idioma["nombre"]) ?> · Práctica rápida</p>
        <h1>Contra el reloj</h1>
        <p>Tienes <?= ContrarrelojModel::DURACION ?> segundos para responder la mayor cantidad de ejercicios de las lecciones que ya hiciste. Si fallas, pasas a la siguiente.</p>
        <p class="ruta-intro__meta">
            <?= !empty($marca["mejor"]) ? "Tu récord: " . (int) $marca["mejor"] . " aciertos · " : "" ?>+<?= RutaModel::XP_PRACTICA ?> XP (una vez al día)
        </p>
        <button class="boton boton--primario boton--bloque" type="button" data-empezar><i class="fa-solid fa-play" aria-hidden="true"></i> Empezar</button>
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
        <span class="ruta-fin__icono" aria-hidden="true"><i class="fa-solid fa-stopwatch"></i></span>
        <h2>¡Tiempo!</h2>
        <dl class="ruta-fin__datos">
            <div><dt>Aciertos</dt><dd data-fin-porcentaje>—</dd></div>
            <div><dt>Récord</dt><dd data-fin-mejor>—</dd></div>
            <div><dt>XP ganado</dt><dd data-fin-xp>—</dd></div>
            <div data-fin-racha-caja hidden><dt>Racha</dt><dd data-fin-racha>—</dd></div>
        </dl>
        <p class="ruta-fin__mensaje" data-fin-mensaje></p>
        <a class="boton boton--primario boton--bloque" href="index.php?page=contrarreloj&amp;idioma=<?= e(rawurlencode($idioma["codigo"])) ?>">Jugar otra vez</a>
        <a class="boton boton--fantasma boton--bloque" href="<?= e($urlRuta) ?>">Volver a mi ruta</a>
    </section>

    <noscript><p class="aviso aviso--alerta">Para jugar necesitas activar JavaScript.</p></noscript>
</div>

<script src="js/ruta.js<?= version("estudiante/js/ruta.js") ?>" defer></script>
