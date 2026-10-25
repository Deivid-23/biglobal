<?php
/**
 * Videos de LSC: lo que falta por nivel, la descarga del CSV, la subida
 * del CSV lleno y la vista previa antes de guardar.
 * Variables: $disponible, $resumen, $previa (null o lo que dejó VideosLscModel::analizar)
 */
$estados = [
    "nuevo"     => ["chip--exito",  "fa-circle-check",          "Nuevo"],
    "reemplaza" => ["chip--alerta", "fa-arrows-rotate",         "Reemplaza"],
    "igual"     => ["chip--neutro", "fa-equals",                "Sin cambios"],
    "vacia"     => ["chip--neutro", "fa-minus",                 "Sin enlace"],
    "error"     => ["chip--error",  "fa-circle-xmark",          "Error"],
];
$totalFaltan = array_sum(array_column($resumen, "faltan"));
?>

<header class="pnl-encabezado">
    <div>
        <h1>Videos de LSC</h1>
        <p>Los ejercicios «Ver la seña» y «Deletreo» no se muestran hasta que tienen el video del INSOR. Descarga la lista, pega los enlaces de YouTube o Vimeo y súbela.</p>
    </div>
    <a class="boton boton--secundario" href="index.php?page=ruta"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Volver a la ruta</a>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>La ruta de aprendizaje aún no está disponible.</span>
    </div>
<?php else: ?>

<section class="pnl-tarjeta" aria-labelledby="titulo-resumen">
    <div class="pnl-tarjeta__cabecera">
        <h2 id="titulo-resumen">Videos por nivel</h2>
        <p><?= $totalFaltan > 0 ? e(plural_panel($totalFaltan, "video falta", "videos faltan")) . " en total." : "¡Todos los ejercicios de LSC tienen video!" ?></p>
    </div>
    <div class="pnl-tabla-caja">
        <table class="pnl-tabla">
            <thead><tr><th>Nivel</th><th>Estado</th><th>Con video</th><th>Faltan</th><th class="col-acciones">Lista para el revisor</th></tr></thead>
            <tbody>
                <?php foreach ($resumen as $n): ?>
                    <tr>
                        <td class="col-fuerte"><a href="index.php?page=ruta&amp;idioma=<?= (int) $n["idioma_id"] ?>&amp;nivel=<?= (int) $n["id"] ?>"><?= e($n["codigo"]) ?> · <?= e($n["nombre"]) ?></a></td>
                        <td><span class="chip <?= (int) $n["publicado"] ? "chip--exito" : "chip--neutro" ?>"><?= (int) $n["publicado"] ? "Publicado" : "Oculto" ?></span></td>
                        <td><?= (int) $n["con_video"] ?> de <?= (int) $n["total"] ?></td>
                        <td class="<?= (int) $n["faltan"] > 0 ? "col-fuerte" : "col-suave" ?>"><?= (int) $n["faltan"] ?></td>
                        <td class="col-acciones">
                            <?php if ((int) $n["faltan"] > 0): ?>
                                <a class="boton boton--secundario boton--sm" href="videos_acciones.php?descargar=1&amp;nivel=<?= e($n["codigo"]) ?>">
                                    <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> CSV <span class="sr-only">de <?= e($n["codigo"]) ?></span>
                                </a>
                            <?php else: ?>
                                <span class="col-suave">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalFaltan > 0): ?>
        <div class="pnl-form__acciones">
            <a class="boton boton--secundario" href="videos_acciones.php?descargar=1"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> Descargar todo lo que falta</a>
        </div>
    <?php endif; ?>
</section>

<section class="pnl-tarjeta" aria-labelledby="titulo-subir">
    <div class="pnl-tarjeta__cabecera">
        <h2 id="titulo-subir">Subir el CSV con los enlaces</h2>
        <p>Usa el mismo archivo que descargaste, con la columna «enlace del video (INSOR)» llena. Primero verás una vista previa: nada se guarda hasta que confirmes.</p>
    </div>
    <form class="pnl-form" method="POST" action="videos_acciones.php" enctype="multipart/form-data">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="analizar">
        <div class="campo">
            <label class="campo__etiqueta" for="videos-archivo">Archivo CSV</label>
            <input class="entrada" id="videos-archivo" type="file" name="archivo" accept=".csv,text/csv" required>
            <p class="campo__ayuda">Máximo 1 MB. En Excel: Archivo › Guardar como › «CSV UTF-8». Solo enlaces de YouTube o Vimeo. Las filas sin enlace se ignoran.</p>
        </div>
        <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Revisar archivo</button></div>
    </form>
</section>

<?php if ($previa): ?>
    <section class="pnl-tarjeta" id="vista-previa" aria-labelledby="titulo-previa">
        <div class="pnl-tarjeta__cabecera">
            <h2 id="titulo-previa">Vista previa: <?= e($previa["archivo"]) ?></h2>
            <p>
                <?php foreach ($estados as $clave => [$chip, $icono, $texto]): ?>
                    <?php if ((int) $previa["cuenta"][$clave] > 0): ?>
                        <span class="chip <?= $chip ?>"><i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= e($texto) ?>: <?= (int) $previa["cuenta"][$clave] ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </p>
        </div>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>Línea</th><th>Ejercicio</th><th>El video debe mostrar</th><th>Resultado</th><th>Enlace</th></tr></thead>
                <tbody>
                    <?php foreach ($previa["filas"] as $f): [$chip, $icono, $texto] = $estados[$f["estado"]]; ?>
                        <tr>
                            <td class="col-id"><?= (int) $f["linea"] ?></td>
                            <td>
                                <span class="col-fuerte"><?= e($f["clave"] !== "" ? $f["clave"] : "—") ?></span>
                                <?php if ($f["leccion"] !== ""): ?><br><small class="col-suave"><?= e($f["nivel"]) ?> · <?= e($f["leccion"]) ?></small><?php endif; ?>
                            </td>
                            <td class="col-suave"><?= e($f["muestra"] !== "" ? $f["muestra"] : "—") ?></td>
                            <td>
                                <span class="chip <?= $chip ?>"><i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= e($texto) ?></span>
                                <?php if ($f["mensaje"] !== ""): ?><br><small class="col-suave"><?= e($f["mensaje"]) ?></small><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($f["url"]): ?>
                                    <a href="<?= e($f["url"]) ?>" target="_blank" rel="noopener noreferrer">Ver video <span class="sr-only">de <?= e($f["clave"]) ?></span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
                                <?php elseif ($f["enlace"] !== ""): ?>
                                    <small class="col-suave pnl-tabla__celda-larga" title="<?= e($f["enlace"]) ?>"><?= e(mb_strimwidth($f["enlace"], 0, 60, "…")) ?></small>
                                <?php else: ?>
                                    <span class="col-suave">—</span>
                                <?php endif; ?>
                                <?php if ($f["estado"] === "reemplaza"): ?>
                                    <br><small class="col-suave">Antes: <a href="<?= e($f["anterior"]) ?>" target="_blank" rel="noopener noreferrer">video anterior</a></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pnl-form__acciones">
            <?php $guardar = count($previa["cambios"]); ?>
            <?php if ($guardar > 0): ?>
                <form method="POST" action="videos_acciones.php" data-confirmar="<?= e("¿Guardar " . plural_panel($guardar, "video", "videos") . "? Los ejercicios activos se empiezan a mostrar a los estudiantes de inmediato si su lección y su nivel están publicados.") ?>">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="aplicar">
                    <input type="hidden" name="token" value="<?= e($previa["token"]) ?>">
                    <button class="boton boton--primario" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Guardar <?= e(plural_panel($guardar, "video", "videos")) ?></button>
                </form>
            <?php else: ?>
                <p class="texto-suave">No hay videos nuevos para guardar en este archivo.</p>
            <?php endif; ?>
            <form method="POST" action="videos_acciones.php">
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="cancelar">
                <button class="boton boton--fantasma" type="submit">Descartar</button>
            </form>
        </div>
    </section>
<?php endif; ?>

<?php endif; ?>
