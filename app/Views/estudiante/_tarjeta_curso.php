<?php
/**
 * Tarjeta de un curso. Espera la variable $curso con:
 *   id, nombre, nivel, descripcion (opcional), total_lecciones, lecciones_hechas
 */
$total  = (int) ($curso["total_lecciones"] ?? 0);
$hechas = (int) ($curso["lecciones_hechas"] ?? 0);
$pct    = porcentaje($hechas, $total);
$idioma = idiomaDeCurso($curso["nombre"] ?? "");
$terminado = $total > 0 && $hechas >= $total;
?>
<article class="curso">
    <a class="curso__enlace" href="index.php?page=curso&amp;id=<?= (int) $curso["id"] ?>">
        <span class="idioma <?= e($idioma["clase"]) ?>" aria-hidden="true"><?= e($idioma["codigo"]) ?></span>

        <span class="curso__cuerpo">
            <span class="curso__titulo"><?= e($curso["nombre"] ?? "Curso") ?></span>
            <span class="curso__meta">
                <?= e($curso["nivel"] ?: "Sin nivel") ?> · <?= e(plural($total, "lección", "lecciones")) ?>
            </span>

            <?php if (!empty($mostrarDescripcion) && !empty($curso["descripcion"])): ?>
                <span class="curso__descripcion"><?= e(mb_strimwidth($curso["descripcion"], 0, 110, "…")) ?></span>
            <?php endif; ?>
        </span>
    </a>

    <div class="curso__pie">
        <?php if ($hechas > 0): ?>
            <div class="curso__avance">
                <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                     aria-valuenow="<?= $pct ?>" aria-label="Avance en <?= e($curso["nombre"]) ?>">
                    <div class="progreso__relleno" data-valor="<?= $pct ?>" style="width: <?= $pct ?>%"></div>
                </div>
                <span class="numeros"><?= $hechas ?>/<?= $total ?></span>
            </div>
        <?php endif; ?>

        <?php if ($terminado): ?>
            <span class="chip chip--exito"><i class="fa-solid fa-check" aria-hidden="true"></i> Terminado</span>
        <?php elseif ($total === 0): ?>
            <span class="chip chip--neutro">Lecciones en preparación</span>
        <?php else: ?>
            <a class="boton <?= $hechas > 0 ? "boton--primario" : "boton--secundario" ?> boton--sm"
               href="index.php?page=curso&amp;id=<?= (int) $curso["id"] ?>">
                <?= $hechas > 0 ? "Continuar" : "Empezar" ?>
            </a>
        <?php endif; ?>
    </div>
</article>
