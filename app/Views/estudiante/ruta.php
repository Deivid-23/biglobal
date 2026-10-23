<?php
/**
 * Mi ruta: el camino de lecciones de un idioma.
 * Variables: $disponible, $idiomas, $idioma, $mapa (niveles con unidades y lecciones), $nivelActivo
 */
$iconoEstado = ["completada" => "fa-check", "disponible" => "fa-star", "bloqueada" => "fa-lock", "preparacion" => "fa-screwdriver-wrench"];
$textoEstado = ["completada" => "Completada", "disponible" => "Siguiente lección", "bloqueada" => "Bloqueada", "preparacion" => "En preparación"];
$codigoIdioma = $idioma ? strtoupper($idioma["codigo"]) : "";
?>
<header class="encabezado-pagina">
    <h1>Mi ruta</h1>
    <p>Lecciones cortas, una tras otra. Cada una abre la siguiente.</p>
</header>

<?php if (!$disponible || !$idiomas): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-route"></i></span>
        <p class="vacio__titulo">La ruta está en preparación</p>
        <p class="vacio__texto">Muy pronto tendrás aquí un camino de lecciones para cada idioma.</p>
    </div>
<?php return; endif; ?>

<nav class="grupo-pestanas" aria-label="Idioma">
    <?php foreach ($idiomas as $i): $activo = (int) $i["id"] === (int) $idioma["id"]; ?>
        <a class="grupo-pestana <?= $activo ? "es-activa" : "" ?>" href="index.php?page=ruta&amp;idioma=<?= e(rawurlencode($i["codigo"])) ?>"
           <?= $activo ? 'aria-current="page"' : "" ?>><?= e($i["nombre"]) ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!$mapa): ?>
    <p class="texto-suave ruta-vacia">Este idioma todavía no tiene niveles publicados.</p>
<?php return; endif; ?>

<nav class="ruta-niveles" aria-label="Niveles">
    <?php foreach ($mapa as $n):
        $activo = $n["codigo"] === $nivelActivo;
        $icono = $n["estado"] === "premium" ? "fa-crown" : ($n["estado"] === "bloqueado" ? "fa-lock" : ($n["jugables"] > 0 && $n["hechas"] === $n["jugables"] ? "fa-check" : ""));
    ?>
        <a class="ruta-nivel <?= $activo ? "es-activo" : "" ?> ruta-nivel--<?= e($n["estado"]) ?>"
           href="index.php?page=ruta&amp;idioma=<?= e(rawurlencode($idioma["codigo"])) ?>&amp;nivel=<?= e($n["codigo"]) ?>"
           <?= $activo ? 'aria-current="page"' : "" ?>>
            <strong><?= e($n["codigo"]) ?><?php if ($icono): ?> <i class="fa-solid <?= $icono ?>" aria-hidden="true"></i><?php endif; ?></strong>
            <small><?= $n["jugables"] > 0 ? (int) $n["hechas"] . "/" . (int) $n["jugables"] : "Pronto" ?></small>
        </a>
    <?php endforeach; ?>
</nav>

<?php foreach ($mapa as $n): if ($n["codigo"] !== $nivelActivo) continue; ?>

    <div class="ruta-nivel-info">
        <h2><?= e(RutaModel::nombreNivel($n, $codigoIdioma)) ?></h2>
        <?php if ($n["descripcion"]): ?><p><?= e($n["descripcion"]) ?></p><?php endif; ?>
        <?php if ($n["jugables"] > 0): $pct = porcentaje((int) $n["hechas"], (int) $n["jugables"]); ?>
            <div class="curso__avance">
                <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>" aria-label="Avance del nivel">
                    <div class="progreso__relleno" data-valor="<?= $pct ?>" style="width: <?= $pct ?>%"></div>
                </div>
                <span class="numeros"><?= (int) $n["hechas"] ?>/<?= (int) $n["jugables"] ?></span>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($n["estado"] === "premium"): ?>
        <div class="vacio">
            <span class="vacio__icono vacio__icono--logro" aria-hidden="true"><i class="fa-solid fa-crown"></i></span>
            <p class="vacio__titulo">El nivel <?= e($n["codigo"]) ?> es parte de Premium</p>
            <p class="vacio__texto">Con Premium sigues tu ruta hasta C1 y entras a todas las clases en vivo.</p>
            <a class="boton boton--primario" href="index.php?page=plan">Ver Premium</a>
        </div>
    <?php elseif ($n["estado"] === "bloqueado"): ?>
        <div class="vacio">
            <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
            <p class="vacio__titulo">Termina el nivel anterior</p>
            <p class="vacio__texto">Cuando completes todas las lecciones del nivel anterior, este se abre solo.</p>
        </div>
    <?php elseif (!$n["unidades"]): ?>
        <p class="texto-suave ruta-vacia">Este nivel todavía no tiene lecciones.</p>
    <?php endif; ?>

    <?php if ($n["estado"] !== "premium"): foreach ($n["unidades"] as $numero => $u): ?>
        <section class="ruta-unidad" aria-labelledby="unidad-<?= (int) $u["id"] ?>">
            <div class="ruta-unidad__cabecera">
                <span class="ruta-unidad__icono" aria-hidden="true"><i class="fa-solid <?= e($u["icono"]) ?>"></i></span>
                <div>
                    <p class="ruta-unidad__numero">Unidad <?= $numero + 1 ?></p>
                    <h3 id="unidad-<?= (int) $u["id"] ?>"><?= e($u["titulo"]) ?></h3>
                    <?php if ($u["descripcion"]): ?><p><?= e($u["descripcion"]) ?></p><?php endif; ?>
                </div>
            </div>
            <ol class="ruta-camino">
                <?php foreach ($u["lecciones"] as $l):
                    $estado = $l["estado"];
                    $jugable = in_array($estado, ["disponible", "completada"], true);
                ?>
                    <li class="ruta-nodo ruta-nodo--<?= e($estado) ?>">
                        <?php if ($jugable): ?>
                            <a class="ruta-nodo__boton" href="index.php?page=ruta-leccion&amp;id=<?= (int) $l["id"] ?>"
                               aria-label="<?= e($l["titulo"]) ?> · <?= e($textoEstado[$estado]) ?>">
                                <i class="fa-solid <?= $iconoEstado[$estado] ?>" aria-hidden="true"></i>
                            </a>
                        <?php else: ?>
                            <span class="ruta-nodo__boton" role="img" aria-label="<?= e($l["titulo"]) ?> · <?= e($textoEstado[$estado]) ?>">
                                <i class="fa-solid <?= $iconoEstado[$estado] ?>" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                        <span class="ruta-nodo__titulo">
                            <?= e($l["titulo"]) ?>
                            <?php if ($estado === "disponible"): ?><small>¡Empieza aquí!</small><?php endif; ?>
                            <?php if ($estado === "completada" && $l["mejor_porcentaje"] !== null): ?><small><?= (int) $l["mejor_porcentaje"] ?>% · repasar</small><?php endif; ?>
                            <?php if ($estado === "preparacion"): ?><small>En preparación</small><?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endforeach; endif; ?>

<?php endforeach; ?>
