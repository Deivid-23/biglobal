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
$meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
$textoRecarga = !empty($g["recarga"]) ? "Se recargan el 1 de " . $meses[(int) substr($g["recarga"], 5, 2) - 1] : "";
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

            <?php if (!empty($g["conProtectores"])): ?>
                <!-- Protectores: cuidan la racha los días que no estudias -->
                <p class="racha-protectores" title="Si un día no estudias, se usa uno solo y tu racha sigue">
                    <?php for ($i = 1; $i <= GamificacionModel::PROTECTORES_MES; $i++): ?>
                        <i class="fa-solid fa-shield-halved <?= $i <= $g["protectores"] ? "es-lleno" : "" ?>" aria-hidden="true"></i>
                    <?php endfor; ?>
                    <span><?= e(plural($g["protectores"], "protector", "protectores")) ?> · <?= e($textoRecarga) ?></span>
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($g["semana"])): ?>
            <ol class="racha-semana" aria-label="Tus últimos 7 días">
                <?php foreach ($g["semana"] as $d):
                    $estado = $d["tipo"] === "estudio" ? "estudiaste" : ($d["tipo"] === "protector" ? "se usó un protector" : ($d["hoy"] ? "todavía no estudias" : "no estudiaste"));
                ?>
                    <li class="racha-dia racha-dia--<?= e($d["tipo"] ?? "nada") ?><?= $d["hoy"] ? " es-hoy" : "" ?>"
                        aria-label="<?= e(($d["hoy"] ? "Hoy" : ucfirst($d["nombre"])) . ": " . $estado) ?>">
                        <span class="racha-dia__icono" aria-hidden="true">
                            <i class="fa-solid <?= $d["tipo"] === "estudio" ? "fa-fire" : ($d["tipo"] === "protector" ? "fa-shield-halved" : "fa-circle") ?>"></i>
                        </span>
                        <span class="racha-dia__letra" aria-hidden="true"><?= e($d["letra"]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
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

