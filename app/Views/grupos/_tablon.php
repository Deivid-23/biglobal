<?php
/**
 * Tablón de un grupo (lo usan el docente y el estudiante).
 * Variables: $anuncios (con "comentarios"), $accionUrl (a dónde van los POST),
 *            $grupoId, $usuarioId, $esDocente, $archivado
 *
 * Solo el docente borra anuncios; cada quien borra sus comentarios y el
 * docente puede borrar cualquiera. El servidor vuelve a revisar todo.
 */
$inicialesGrupo = function (array $fila): string {
    $nombre = nombre_panel($fila);
    $partes = preg_split('/\s+/', $nombre) ?: [$nombre];
    return mb_strtoupper(mb_substr($partes[0], 0, 1) . (isset($partes[1]) ? mb_substr($partes[1], 0, 1) : "")) ?: "?";
};
// Fotos de todos los que aparecen, en una sola consulta
$autoresTablon = array_column($anuncios, "autor_id");
foreach ($anuncios as $a) {
    $autoresTablon = array_merge($autoresTablon, array_column($a["comentarios"], "autor_id"));
}
FotoPerfil::versiones($autoresTablon);
?>
<?php if (!$anuncios): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-bullhorn"></i></span>
        <p class="vacio__titulo">Todavía no hay anuncios</p>
        <p class="vacio__texto"><?= $esDocente ? "Publica el primero: bienvenida, fechas importantes o lo que deben preparar." : "Aquí verás los anuncios de tu docente." ?></p>
    </div>
<?php else: ?>
    <ol class="grupo-tablon">
        <?php foreach ($anuncios as $a): ?>
            <li class="grupo-anuncio" id="anuncio-<?= (int) $a["id"] ?>">
                <div class="grupo-anuncio__cabecera">
                    <?= FotoPerfil::avatar((int) $a["autor_id"], $inicialesGrupo($a), "grupo-avatar") ?>
                    <div class="grupo-anuncio__autor">
                        <strong><?= e(nombre_panel($a)) ?></strong>
                        <small><time datetime="<?= e(date("c", strtotime($a["creado"]))) ?>"><?= e(hace_panel($a["creado"])) ?></time></small>
                    </div>
                    <?php if ($esDocente): ?>
                        <form method="POST" action="<?= e($accionUrl) ?>" data-confirmar="¿Eliminar este anuncio y sus comentarios?">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="eliminar_anuncio">
                            <input type="hidden" name="grupo_id" value="<?= (int) $grupoId ?>">
                            <input type="hidden" name="anuncio_id" value="<?= (int) $a["id"] ?>">
                            <button class="boton boton--fantasma boton--sm" type="submit" aria-label="Eliminar anuncio">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <p class="grupo-anuncio__texto"><?= nl2br(e($a["texto"])) ?></p>

                <div class="grupo-comentarios">
                    <?php if ($a["comentarios"]): ?>
                        <p class="grupo-comentarios__total"><?= e(count($a["comentarios"]) === 1 ? "1 comentario" : count($a["comentarios"]) . " comentarios") ?></p>
                        <ul class="grupo-comentarios__lista">
                            <?php foreach ($a["comentarios"] as $k): $mio = (int) $k["autor_id"] === (int) $usuarioId; ?>
                                <li class="grupo-comentario">
                                    <?= FotoPerfil::avatar((int) $k["autor_id"], $inicialesGrupo($k), "grupo-avatar grupo-avatar--sm") ?>
                                    <div class="grupo-comentario__cuerpo">
                                        <p class="grupo-comentario__autor">
                                            <strong><?= e(nombre_panel($k)) ?></strong>
                                            <small><?= e(hace_panel($k["creado"])) ?></small>
                                        </p>
                                        <p class="grupo-comentario__texto"><?= nl2br(e($k["texto"])) ?></p>
                                    </div>
                                    <?php if ($esDocente || $mio): ?>
                                        <form method="POST" action="<?= e($accionUrl) ?>" data-confirmar="¿Borrar este comentario?">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="accion" value="borrar_comentario">
                                            <input type="hidden" name="grupo_id" value="<?= (int) $grupoId ?>">
                                            <input type="hidden" name="anuncio_id" value="<?= (int) $a["id"] ?>">
                                            <input type="hidden" name="comentario_id" value="<?= (int) $k["id"] ?>">
                                            <button class="boton boton--fantasma boton--sm" type="submit" aria-label="Borrar comentario">
                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!$archivado): ?>
                        <form class="grupo-comentar" method="POST" action="<?= e($accionUrl) ?>">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="comentar">
                            <input type="hidden" name="grupo_id" value="<?= (int) $grupoId ?>">
                            <input type="hidden" name="anuncio_id" value="<?= (int) $a["id"] ?>">
                            <label class="sr-only" for="comentario-<?= (int) $a["id"] ?>">Escribe un comentario</label>
                            <input class="entrada" id="comentario-<?= (int) $a["id"] ?>" name="texto" maxlength="1000" required
                                   placeholder="Escribe un comentario…" autocomplete="off">
                            <button class="boton boton--secundario boton--sm" type="submit">Comentar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
