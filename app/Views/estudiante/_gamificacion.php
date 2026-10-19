<?php
/**
 * Racha diaria y nivel/XP del estudiante (se usa en el inicio).
 * Variable: $gamificacion (de GamificacionModel::obtenerResumen)
 *
 * Colores: naranja = racha y logros, verde = progreso, azul = base.
 */
$g = $gamificacion;

$pctNivel = porcentaje($g["xpEnNivel"], $g["xpPorNivel"]);
$faltanXp = $g["xpPorNivel"] - $g["xpEnNivel"];

if ($g["estudioHoy"]) {
    $textoRacha = "¡Ya sumaste hoy! Vuelve mañana para seguir.";
} elseif ($g["racha"] > 0) {
    $textoRacha = "Completa una lección hoy para no perderla.";
} else {
    $textoRacha = "Completa una lección para empezar tu racha.";
}
?>

<section class="juego" aria-label="Tu progreso">

    <!-- Racha diaria -->
    <div class="juego__tarjeta juego__racha <?= $g["racha"] > 0 ? "es-activa" : "" ?>">
        <span class="juego__icono" aria-hidden="true"><i class="fa-solid fa-fire"></i></span>
        <div>
            <p class="juego__numero">
                <strong class="numeros"><?= (int) $g["racha"] ?></strong>
                <?= $g["racha"] === 1 ? "día de racha" : "días de racha" ?>
            </p>
            <p class="juego__texto"><?= e($textoRacha) ?></p>
            <?php if ($g["rachaMaxima"] > 1): ?>
                <p class="juego__texto">Tu mejor racha: <?= e(plural($g["rachaMaxima"], "día", "días")) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nivel y XP -->
    <div class="juego__tarjeta juego__nivel">
        <span class="juego__icono" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
        <div class="juego__nivel-cuerpo">
            <p class="juego__numero">
                <span>Nivel <strong class="numeros"><?= (int) $g["nivel"] ?></strong></span>
                <span class="juego__xp numeros"><?= (int) $g["xp"] ?> XP</span>
            </p>
            <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="<?= (int) $g["xpPorNivel"] ?>"
                 aria-valuenow="<?= (int) $g["xpEnNivel"] ?>" aria-label="XP para el siguiente nivel">
                <div class="progreso__relleno" data-valor="<?= $pctNivel ?>" style="width: <?= $pctNivel ?>%"></div>
            </div>
            <p class="juego__texto">
                Te faltan <span class="numeros"><?= (int) $faltanXp ?></span> XP para el nivel <?= (int) $g["nivel"] + 1 ?>
            </p>
        </div>
    </div>
</section>

