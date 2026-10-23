<?php
/**
 * Insignias del estudiante.
 * Variables: $listaInsignias (de InsigniaModel::delEstudiante),
 *            $modoInsignias = "resumen" (inicio: las últimas ganadas) | "pagina" (página Insignias, por categoría)
 *
 * Las ganadas van con su color; las que faltan en gris y con su avance.
 */
$ganadasInsignias = array_values(array_filter($listaInsignias, fn($m) => $m["fecha_ganada"] !== null));

$tarjetaInsignia = function (array $m): void {
    $ganada = $m["fecha_ganada"] !== null;
    $pct = !$ganada && (int) $m["meta"] > 0 ? (int) min(100, round(((int) ($m["avance"] ?? 0)) * 100 / max(1, (int) $m["meta"]))) : 0;
?>
    <li class="medalla <?= $ganada ? "medalla--ganada medalla--" . e($m["color"]) : "medalla--pendiente" ?>">
        <span class="medalla__icono" aria-hidden="true"><i class="fa-solid <?= e($m["icono"]) ?>"></i></span>
        <span class="medalla__nombre"><?= e($m["nombre"]) ?></span>
        <span class="medalla__texto"><?= e($m["descripcion"]) ?></span>
        <?php if ($ganada): ?>
            <span class="medalla__estado">Ganada el <?= e(fechaCorta($m["fecha_ganada"])) ?></span>
        <?php else: ?>
            <span class="medalla__estado"><i class="fa-solid fa-lock" aria-hidden="true"></i> <?= e(InsigniaModel::textoAvance($m)) ?></span>
            <?php if ($m["condicion"] !== "ruta_nivel"): ?>
                <span class="progreso medalla__progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>"
                      aria-label="Avance de «<?= e($m["nombre"]) ?>»">
                    <span class="progreso__relleno" data-valor="<?= $pct ?>" style="width: <?= $pct ?>%"></span>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </li>
<?php };
?>

<?php if ($listaInsignias && $modoInsignias === "resumen"):
    // Inicio: las últimas 6 ganadas; si aún no tiene, las 3 más cerca de ganarse
    $mostrar = $ganadasInsignias;
    usort($mostrar, fn($a, $b) => strcmp($b["fecha_ganada"], $a["fecha_ganada"]));
    $mostrar = array_slice($mostrar, 0, 6);
    if (!$mostrar) {
        $pendientes = array_values(array_filter($listaInsignias, fn($m) => $m["fecha_ganada"] === null && $m["condicion"] !== "ruta_nivel"));
        usort($pendientes, fn($a, $b) => (($b["avance"] ?? 0) / max(1, $b["meta"])) <=> (($a["avance"] ?? 0) / max(1, $a["meta"])));
        $mostrar = array_slice($pendientes, 0, 3);
    }
?>
    <section class="bloque" aria-labelledby="titulo-insignias">
        <div class="bloque__cabecera">
            <h2 id="titulo-insignias"><?= $ganadasInsignias ? "Tus últimas insignias" : "Insignias por ganar" ?></h2>
            <a href="index.php?page=insignias"><?= count($ganadasInsignias) ?> de <?= count($listaInsignias) ?> · Ver todas</a>
        </div>
        <ul class="medallas">
            <?php foreach ($mostrar as $m) { $tarjetaInsignia($m); } ?>
        </ul>
    </section>

<?php elseif ($listaInsignias):
    // Página Insignias: todas, por categoría
    $porCategoria = [];
    foreach ($listaInsignias as $m) {
        $porCategoria[$m["categoria"]][] = $m;
    }
    $orden = array_keys(InsigniaModel::CATEGORIAS);
    uksort($porCategoria, fn($a, $b) => array_search($a, $orden, true) <=> array_search($b, $orden, true));
?>
    <div class="insignias-catalogo">
        <?php foreach ($porCategoria as $categoria => $lista):
            $ganadasCat = count(array_filter($lista, fn($m) => $m["fecha_ganada"] !== null)); ?>
            <section class="insignias-categoria" aria-labelledby="cat-<?= e($categoria) ?>">
                <h2 class="insignias-categoria__titulo" id="cat-<?= e($categoria) ?>"><?= e(InsigniaModel::CATEGORIAS[$categoria] ?? ucfirst($categoria)) ?>
                    <small><?= $ganadasCat ?>/<?= count($lista) ?></small></h2>
                <ul class="medallas">
                    <?php foreach ($lista as $m) { $tarjetaInsignia($m); } ?>
                </ul>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
