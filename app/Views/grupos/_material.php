<?php
/**
 * Material de un grupo (lo usan el docente y el estudiante).
 * Variables: $material, $accionUrl, $grupoId, $esDocente
 *
 * Los archivos se abren por ../grupos/archivo.php, que revisa permisos.
 * Los videos se muestran con Video::reproductor (solo YouTube y Vimeo).
 */
$iconosMaterial = ["archivo" => "fa-file", "enlace" => "fa-link", "video" => "fa-circle-play"];
?>
<?php if (!$material): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-folder-open"></i></span>
        <p class="vacio__titulo">Todavía no hay material</p>
        <p class="vacio__texto"><?= $esDocente ? "Sube guías en PDF, imágenes, audios o enlaces para tus estudiantes." : "Aquí aparecerán las guías, audios y enlaces que comparta tu docente." ?></p>
    </div>
<?php else: ?>
    <ul class="grupo-material">
        <?php foreach ($material as $m):
            $icono = $m["tipo"] === "archivo" && $m["archivo_mime"] ? Archivo::icono($m["archivo_mime"]) : $iconosMaterial[$m["tipo"]] ?? "fa-file";
            $urlArchivo = $m["archivo_id"] ? "../grupos/archivo.php?id=" . (int) $m["archivo_id"] : null;
            $video = $m["tipo"] === "video" ? Video::reproductor($m["url"]) : null;
        ?>
            <li class="grupo-material__item" id="material-<?= (int) $m["id"] ?>">
                <div class="grupo-material__cabecera">
                    <span class="grupo-material__icono" aria-hidden="true"><i class="fa-solid <?= e($icono) ?>"></i></span>
                    <div class="grupo-material__cuerpo">
                        <p class="grupo-material__titulo"><?= e($m["titulo"]) ?></p>
                        <p class="grupo-material__meta">
                            <?= e(fecha_panel($m["creado"])) ?>
                            <?php if ($m["tipo"] === "archivo" && $m["archivo_id"]): ?>
                                · <?= e(strtoupper(Archivo::extension($m["archivo_mime"]))) ?> · <?= e(Archivo::peso((int) $m["archivo_tamano"])) ?>
                            <?php elseif ($m["tipo"] === "enlace"): ?>
                                · <?= e((string) parse_url((string) $m["url"], PHP_URL_HOST)) ?>
                            <?php elseif ($video): ?>
                                · <?= e($video["proveedor"]) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if ($esDocente): ?>
                        <form method="POST" action="<?= e($accionUrl) ?>" data-confirmar="¿Eliminar «<?= e($m["titulo"]) ?>»?">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="eliminar_material">
                            <input type="hidden" name="grupo_id" value="<?= (int) $grupoId ?>">
                            <input type="hidden" name="material_id" value="<?= (int) $m["id"] ?>">
                            <button class="boton boton--fantasma boton--sm" type="submit" aria-label="Eliminar «<?= e($m["titulo"]) ?>»">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php if (trim((string) $m["descripcion"]) !== ""): ?>
                    <p class="grupo-material__texto"><?= nl2br(e($m["descripcion"])) ?></p>
                <?php endif; ?>

                <?php if ($m["tipo"] === "archivo" && $urlArchivo): ?>
                    <?php if (str_starts_with((string) $m["archivo_mime"], "audio/")): ?>
                        <audio class="grupo-material__audio" controls preload="none" src="<?= e($urlArchivo) ?>"></audio>
                    <?php elseif (str_starts_with((string) $m["archivo_mime"], "image/")): ?>
                        <a href="<?= e($urlArchivo) ?>" target="_blank" rel="noopener">
                            <img class="grupo-material__imagen" src="<?= e($urlArchivo) ?>" alt="<?= e($m["titulo"]) ?>" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <a class="boton boton--secundario boton--sm" href="<?= e($urlArchivo) ?>" target="_blank" rel="noopener">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Abrir <?= e($m["archivo_nombre"]) ?>
                    </a>
                <?php elseif ($m["tipo"] === "archivo"): ?>
                    <p class="campo__ayuda">El archivo ya no está disponible.</p>
                <?php elseif ($m["tipo"] === "enlace"): ?>
                    <a class="boton boton--secundario boton--sm" href="<?= e($m["url"]) ?>" target="_blank" rel="noopener noreferrer nofollow">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Abrir enlace
                    </a>
                <?php elseif ($video): ?>
                    <div class="grupo-material__video">
                        <iframe src="<?= e($video["src"]) ?>" title="Video: <?= e($m["titulo"]) ?>" loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
