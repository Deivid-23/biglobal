<?php
/**
 * Tarjeta "Personalización" (color de acento personal).
 * Se incluye en el perfil de TODOS los roles. El color se aplica al
 * instante (tema.js) y se guarda en la cuenta (src/auth/guardar_acento.php).
 * $claseTarjeta: clase de tarjeta del panel donde se muestra.
 */
$miColor   = Panel::acentoUsuario();          // "" si nunca eligió
$colorSitio = Panel::acentoSitio() ?: "#2563eb";
$sugeridos = Panel::coloresSugeridos();
$enLista   = $miColor !== "" && array_key_exists($miColor, $sugeridos);
?>
<section class="<?= e($claseTarjeta ?? "pnl-tarjeta") ?> personalizar" aria-labelledby="titulo-personalizar" data-personalizar>
    <div class="personalizar__cabecera">
        <h2 id="titulo-personalizar">Personalización</h2>
        <p>Elige el color de tu panel. Solo lo ves tú y se guarda en tu cuenta.</p>
    </div>

    <fieldset class="personalizar__colores">
        <legend class="sr-only">Color de acento</legend>

        <label class="color-opcion">
            <input type="radio" name="acento" value="" <?= $miColor === "" ? "checked" : "" ?>>
            <span class="color-opcion__muestra color-opcion__muestra--sitio" style="--muestra: <?= e($colorSitio) ?>"></span>
            <span class="color-opcion__texto">Del sitio</span>
        </label>

        <?php foreach ($sugeridos as $hex => $nombre): ?>
            <label class="color-opcion">
                <input type="radio" name="acento" value="<?= e($hex) ?>" <?= $miColor === $hex ? "checked" : "" ?>>
                <span class="color-opcion__muestra" style="--muestra: <?= e($hex) ?>"></span>
                <span class="color-opcion__texto"><?= e($nombre) ?></span>
            </label>
        <?php endforeach; ?>

        <label class="color-opcion color-opcion--libre">
            <input type="radio" name="acento" value="libre" <?= $miColor !== "" && !$enLista ? "checked" : "" ?>>
            <span class="color-opcion__muestra color-opcion__muestra--libre" style="--muestra: <?= e($enLista || $miColor === "" ? "#e11d48" : $miColor) ?>">
                <input type="color" class="color-opcion__selector" aria-label="Elegir cualquier color"
                       value="<?= e($enLista || $miColor === "" ? "#e11d48" : $miColor) ?>">
            </span>
            <span class="color-opcion__texto">Otro color</span>
        </label>
    </fieldset>

    <!-- Vista previa con el color elegido -->
    <div class="personalizar__vista" aria-hidden="true">
        <span class="boton boton--primario boton--sm">Botón</span>
        <span class="chip">Etiqueta</span>
        <span class="personalizar__enlace">Enlace</span>
        <span class="personalizar__barra"><span></span></span>
    </div>

    <p class="campo__ayuda" data-personalizar-nota hidden>
        Ajustamos un poco el tono para que los textos se lean bien.
    </p>
    <p class="personalizar__estado" role="status" aria-live="polite" data-personalizar-estado></p>
</section>
<script src="../js/personalizar.js" defer></script>
