<?php
/**
 * Ruta de aprendizaje (admin).
 * Variables: $disponible, $idiomas, $idiomaId, $niveles, $nivel, $arbol, $leccion,
 *            $ejercicios, $ejercicioEditar, $pendientesVideo
 */
$csrf = generarCsrfToken();
$codigoIdioma = strtoupper((string) ($nivel["idioma_codigo"] ?? ""));
$esLsc = $codigoIdioma === "LSC";

/** Campos ocultos comunes y el botón de eliminar. */
$botonEliminar = function (string $que, int $id, string $volver, string $pregunta): void { ?>
    <form method="POST" action="ruta_acciones.php" data-confirmar="<?= e($pregunta) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(generarCsrfToken()) ?>">
        <input type="hidden" name="accion" value="eliminar">
        <input type="hidden" name="que" value="<?= e($que) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <button class="boton boton--fantasma boton--sm" type="submit"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Eliminar</button>
    </form>
<?php };

$interruptor = function (string $nombre, bool $marcado, string $titulo, string $ayuda): void { ?>
    <label class="pnl-interruptor">
        <span><strong><?= e($titulo) ?></strong><small><?= e($ayuda) ?></small></span>
        <input type="checkbox" name="<?= e($nombre) ?>" value="1" <?= $marcado ? "checked" : "" ?>>
    </label>
<?php };
?>

<header class="pnl-encabezado">
    <div>
        <h1>Ruta de aprendizaje</h1>
        <p>El camino tipo Duolingo de cada idioma: niveles, unidades, lecciones y ejercicios. Los estudiantes solo ven lo publicado.</p>
    </div>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>La ruta de aprendizaje aún no está disponible.</span>
    </div>
<?php return; endif; ?>

<nav class="pnl-pestanas" aria-label="Idioma">
    <?php foreach ($idiomas as $i): ?>
        <a class="pnl-pestana <?= (int) $i["id"] === $idiomaId ? "es-activa" : "" ?>" href="index.php?page=ruta&amp;idioma=<?= (int) $i["id"] ?>"
           <?= (int) $i["id"] === $idiomaId ? 'aria-current="page"' : "" ?>><?= e($i["nombre"]) ?></a>
    <?php endforeach; ?>
</nav>

<?php if ($niveles): ?>
    <nav class="pnl-pestanas" aria-label="Nivel">
        <?php foreach ($niveles as $n): $activo = $nivel && (int) $n["id"] === (int) $nivel["id"]; ?>
            <a class="pnl-pestana <?= $activo ? "es-activa" : "" ?>" href="index.php?page=ruta&amp;nivel=<?= (int) $n["id"] ?>" <?= $activo ? 'aria-current="page"' : "" ?>>
                <?= e($n["codigo"]) ?>
                <?php if ((int) $n["publicado"] === 0): ?><span class="chip chip--neutro">Borrador</span><?php endif; ?>
                <?php if ((int) $n["premium"] === 1): ?><i class="fa-solid fa-crown" aria-label="Premium"></i><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if (!$nivel): ?>

    <p class="texto-suave">Este idioma todavía no tiene niveles.</p>

<?php elseif ($leccion):
    /* ================= EDITOR DE UNA LECCIÓN ================= */
    $leccionId = (int) $leccion["id"];
    $urlLeccion = "index.php?page=ruta&leccion=" . $leccionId;
    $video = Video::reproductor($leccion["video_url"]);
    $valores = $_SESSION["ruta_form_ejercicio"] ?? ($ejercicioEditar ? RutaModel::formularioDe($ejercicioEditar) : ["tipo" => $esLsc ? "sena" : "opcion", "activo" => 1, "orden" => count($ejercicios) + 1]);
    unset($_SESSION["ruta_form_ejercicio"]);
    $v = fn(string $k) => e($valores[$k] ?? "");
?>

    <p><a href="index.php?page=ruta&amp;nivel=<?= (int) $nivel["id"] ?>#unidad-<?= (int) $leccion["unidad_id"] ?>">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= e($leccion["nivel_codigo"]) ?> · <?= e($leccion["unidad"]) ?></a></p>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-ejercicios">
            <div class="pnl-tarjeta__cabecera">
                <h2 id="titulo-ejercicios">Ejercicios (<?= count($ejercicios) ?>)</h2>
                <p>Se juegan en este orden. Los «Ver la seña» sin video no se muestran.</p>
            </div>
            <?php if (!$ejercicios): ?>
                <p class="texto-suave">Todavía no tiene ejercicios. Mientras no tenga ninguno que se pueda jugar, los estudiantes la ven «en preparación».</p>
            <?php else: ?>
                <ol class="pnl-lista pnl-lista--compacta">
                    <?php foreach ($ejercicios as $e): $sinVideo = $e["tipo"] === "sena" && trim((string) $e["video_url"]) === ""; ?>
                        <li class="pnl-item" id="ejercicio-<?= (int) $e["id"] ?>">
                            <div class="pnl-item__cuerpo">
                                <p class="pnl-item__titulo"><?= (int) $e["orden"] ?>. <?= e($e["enunciado"]) ?></p>
                                <p class="pnl-item__texto">
                                    <span class="chip"><?= e(RutaModel::TIPOS[$e["tipo"]] ?? $e["tipo"]) ?></span>
                                    <?php if ((int) $e["activo"] === 0): ?><span class="chip chip--neutro">Inactivo</span><?php endif; ?>
                                    <?php if ($sinVideo): ?><span class="chip chip--alerta">Falta el video</span><?php endif; ?>
                                    <?php if (trim((string) $e["video_url"]) !== "" && $e["tipo"] !== "sena"): ?><span class="chip chip--neutro"><i class="fa-solid fa-video" aria-hidden="true"></i> Con video</span><?php endif; ?>
                                </p>
                            </div>
                            <div class="pnl-item__acciones">
                                <a class="boton boton--secundario boton--sm" href="<?= e($urlLeccion) ?>&amp;ejercicio=<?= (int) $e["id"] ?>#form-ejercicio">Editar</a>
                                <?php $botonEliminar("ejercicio", (int) $e["id"], $urlLeccion, "¿Eliminar este ejercicio?"); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <section class="pnl-tarjeta" aria-labelledby="titulo-datos-leccion">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-datos-leccion">Lección</h2></div>
            <form class="pnl-form" method="POST" action="ruta_acciones.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="accion" value="guardar_leccion">
                <input type="hidden" name="leccion_id" value="<?= $leccionId ?>">
                <div class="campo">
                    <label class="campo__etiqueta" for="l-titulo">Título</label>
                    <input class="entrada" id="l-titulo" name="titulo" maxlength="120" required value="<?= e($leccion["titulo"]) ?>">
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="l-desc">Descripción <span class="campo__ayuda">(opcional)</span></label>
                    <input class="entrada" id="l-desc" name="descripcion" maxlength="255" value="<?= e($leccion["descripcion"] ?? "") ?>">
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="l-video">Video de la lección <span class="campo__ayuda">(YouTube o Vimeo<?= $esLsc ? "; en LSC es la explicación de las señas" : ", opcional" ?>)</span></label>
                    <input class="entrada" id="l-video" name="video_url" maxlength="255" inputmode="url" value="<?= e($leccion["video_url"] ?? "") ?>" placeholder="https://youtu.be/...">
                </div>
                <?php if ($video): ?>
                    <div class="grupo-material__video"><iframe src="<?= e($video["src"]) ?>" title="Video de la lección" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe></div>
                <?php endif; ?>
                <div class="pnl-form__fila">
                    <div class="campo">
                        <label class="campo__etiqueta" for="l-orden">Orden</label>
                        <input class="entrada" type="number" id="l-orden" name="orden" min="1" max="999" value="<?= (int) $leccion["orden"] ?>">
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="l-xp">XP</label>
                        <input class="entrada" type="number" id="l-xp" name="xp" min="1" max="100" value="<?= (int) $leccion["xp"] ?>">
                    </div>
                </div>
                <?php $interruptor("publicado", (int) $leccion["publicado"] === 1, "Publicada", "Los estudiantes la ven en su ruta."); ?>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar lección</button></div>
            </form>
            <?php $botonEliminar("leccion", $leccionId, "index.php?page=ruta&nivel=" . (int) $nivel["id"], "¿Eliminar «" . $leccion["titulo"] . "» con sus ejercicios y el progreso de los estudiantes?"); ?>
        </section>
    </div>

    <section class="pnl-tarjeta" id="form-ejercicio" aria-labelledby="titulo-form-ejercicio">
        <div class="pnl-tarjeta__cabecera">
            <h2 id="titulo-form-ejercicio"><?= $ejercicioEditar ? "Editar ejercicio" : "Nuevo ejercicio" ?></h2>
            <?php if ($ejercicioEditar): ?><a href="<?= e($urlLeccion) ?>#form-ejercicio">Cancelar</a><?php endif; ?>
        </div>
        <form class="pnl-form" method="POST" action="ruta_acciones.php" data-tipos>
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="accion" value="guardar_ejercicio">
            <input type="hidden" name="leccion_id" value="<?= $leccionId ?>">
            <input type="hidden" name="ejercicio_id" value="<?= (int) ($ejercicioEditar["id"] ?? 0) ?>">
            <div class="pnl-form__fila">
                <div class="campo">
                    <label class="campo__etiqueta" for="e-tipo">Tipo</label>
                    <select class="entrada" id="e-tipo" name="tipo" data-tipo-selector>
                        <?php foreach (RutaModel::TIPOS as $clave => $texto): ?>
                            <option value="<?= $clave ?>" <?= ($valores["tipo"] ?? "") === $clave ? "selected" : "" ?>><?= e($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="e-orden">Orden</label>
                    <input class="entrada" type="number" id="e-orden" name="orden" min="1" max="999" value="<?= (int) ($valores["orden"] ?? 1) ?>">
                </div>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="e-enunciado">Enunciado</label>
                <input class="entrada" id="e-enunciado" name="enunciado" maxlength="500" required value="<?= $v("enunciado") ?>"
                       placeholder="Ej. ¿Cómo se dice «gato» en inglés?">
            </div>

            <div class="campo" data-para-tipo="escuchar dictado">
                <label class="campo__etiqueta" for="e-audio">Lo que va a sonar</label>
                <input class="entrada" id="e-audio" name="texto_audio" maxlength="200" value="<?= $v("texto_audio") ?>" aria-describedby="e-audio-ayuda">
                <p class="campo__ayuda" id="e-audio-ayuda">Lo lee la voz del navegador (<?= e(RutaModel::VOCES[$codigoIdioma] ?? "es-CO") ?>).</p>
            </div>
            <div class="campo" data-para-tipo="opcion sena escuchar">
                <label class="campo__etiqueta" for="e-opciones">Opciones (una por línea, de 2 a 4)</label>
                <textarea class="entrada" id="e-opciones" name="opciones" rows="4"><?= $v("opciones") ?></textarea>
            </div>
            <div class="campo" data-para-tipo="opcion sena escuchar">
                <label class="campo__etiqueta" for="e-correcta">Número de la opción correcta</label>
                <input class="entrada" type="number" id="e-correcta" name="correcta" min="1" max="4" value="<?= $v("correcta") ?>" aria-describedby="e-correcta-ayuda">
                <p class="campo__ayuda" id="e-correcta-ayuda">Al estudiante le aparecen mezcladas.</p>
            </div>
            <div class="campo" data-para-tipo="traducir">
                <label class="campo__etiqueta" for="e-original">Frase para traducir</label>
                <input class="entrada" id="e-original" name="original" maxlength="200" value="<?= $v("original") ?>" placeholder="Ej. Tengo dos hermanos.">
            </div>
            <div class="campo" data-para-tipo="huecos">
                <label class="campo__etiqueta" for="e-texto-huecos">Texto con espacios</label>
                <textarea class="entrada" id="e-texto-huecos" name="texto_huecos" rows="3" maxlength="400" aria-describedby="e-huecos-ayuda"><?= $v("texto_huecos") ?></textarea>
                <p class="campo__ayuda" id="e-huecos-ayuda">Marca de 2 a 4 espacios con ___ (ej. «Yesterday I ___ to the park and ___ a friend»).</p>
            </div>
            <div class="campo" data-para-tipo="completar ordenar error">
                <label class="campo__etiqueta" for="e-frase">Frase</label>
                <input class="entrada" id="e-frase" name="frase" maxlength="300" value="<?= $v("frase") ?>" aria-describedby="e-frase-ayuda">
                <p class="campo__ayuda" id="e-frase-ayuda">Completar: marca el espacio con ___ (ej. «I ___ a student»). Ordenar: escribe la frase correcta completa. Encontrar el error: marca la palabra equivocada entre asteriscos (ej. «She *go* to school»).</p>
            </div>
            <div class="campo" data-para-tipo="error">
                <label class="campo__etiqueta" for="e-correccion">Palabra correcta</label>
                <input class="entrada" id="e-correccion" name="correccion" maxlength="60" value="<?= $v("correccion") ?>" placeholder="Ej. goes">
            </div>
            <div class="campo" data-para-tipo="completar traducir huecos">
                <label class="campo__etiqueta" for="e-respuestas">Respuestas aceptadas (una por línea)</label>
                <textarea class="entrada" id="e-respuestas" name="respuestas" rows="3" aria-describedby="e-respuestas-ayuda"><?= $v("respuestas") ?></textarea>
                <p class="campo__ayuda" id="e-respuestas-ayuda">No importan mayúsculas, tildes ni signos. Traducir: escribe todas las traducciones válidas. Varios espacios: una respuesta por espacio, en orden.</p>
            </div>
            <div class="campo" data-para-tipo="ordenar huecos">
                <label class="campo__etiqueta" for="e-extra">Palabras de más <span class="campo__ayuda">(opcional, separadas por espacio)</span></label>
                <input class="entrada" id="e-extra" name="extra" maxlength="200" value="<?= $v("extra") ?>">
            </div>
            <div class="campo" data-para-tipo="ordenar dictado">
                <label class="campo__etiqueta" for="e-aceptadas">Otras respuestas válidas <span class="campo__ayuda">(opcional, una por línea)</span></label>
                <textarea class="entrada" id="e-aceptadas" name="aceptadas" rows="2"><?= $v("aceptadas") ?></textarea>
            </div>
            <div class="campo" data-para-tipo="clasificar">
                <label class="campo__etiqueta" for="e-grupos">Grupos (2 o 3, uno por línea)</label>
                <textarea class="entrada" id="e-grupos" name="grupos" rows="3" placeholder="Pasado&#10;Presente"><?= $v("grupos") ?></textarea>
            </div>
            <div class="campo" data-para-tipo="clasificar">
                <label class="campo__etiqueta" for="e-items">Palabras (de 4 a 8, una por línea: palabra = grupo)</label>
                <textarea class="entrada" id="e-items" name="items" rows="6" placeholder="went = Pasado&#10;go = Presente"><?= $v("items") ?></textarea>
            </div>
            <div class="campo" data-para-tipo="emparejar">
                <label class="campo__etiqueta" for="e-pares">Parejas (una por línea: palabra = traducción)</label>
                <textarea class="entrada" id="e-pares" name="pares" rows="5" placeholder="cat = gato"><?= $v("pares") ?></textarea>
            </div>
            <div class="campo" data-para-tipo="vf">
                <label class="campo__etiqueta" for="e-vf">La afirmación del enunciado es…</label>
                <select class="entrada" id="e-vf" name="correcta_vf">
                    <option value="1" <?= ($valores["correcta_vf"] ?? "1") === "1" ? "selected" : "" ?>>Verdadera</option>
                    <option value="0" <?= ($valores["correcta_vf"] ?? "") === "0" ? "selected" : "" ?>>Falsa</option>
                </select>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="e-video">Video <span class="campo__ayuda" data-para-tipo="sena">(obligatorio para que se muestre: la seña que el estudiante debe reconocer)</span></label>
                <input class="entrada" id="e-video" name="video_url" maxlength="255" inputmode="url" value="<?= $v("video_url") ?>" placeholder="https://youtu.be/... (opcional)">
            </div>
            <?php $interruptor("activo", (int) ($valores["activo"] ?? 1) === 1, "Activo", "Si lo apagas, no aparece en la lección."); ?>
            <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit"><?= $ejercicioEditar ? "Guardar ejercicio" : "Agregar ejercicio" ?></button></div>
        </form>
    </section>

<?php else:
    /* ================= NIVEL: UNIDADES Y LECCIONES ================= */
    $nivelId = (int) $nivel["id"];
    $urlNivel = "index.php?page=ruta&nivel=" . $nivelId;
?>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <div>
            <?php if (!$arbol): ?>
                <section class="pnl-tarjeta"><p class="texto-suave">Este nivel todavía no tiene unidades. Crea la primera con el formulario.</p></section>
            <?php endif; ?>

            <?php foreach ($arbol as $u): $uid = (int) $u["id"]; ?>
                <section class="pnl-tarjeta ruta-unidad" id="unidad-<?= $uid ?>" aria-labelledby="titulo-unidad-<?= $uid ?>">
                    <div class="pnl-tarjeta__cabecera">
                        <div>
                            <h2 id="titulo-unidad-<?= $uid ?>"><i class="fa-solid <?= e($u["icono"]) ?>" aria-hidden="true"></i> <?= (int) $u["orden"] ?>. <?= e($u["titulo"]) ?></h2>
                            <?php if ($u["descripcion"]): ?><p><?= e($u["descripcion"]) ?></p><?php endif; ?>
                        </div>
                        <?php if ((int) $u["publicado"] === 0): ?><span class="chip chip--neutro">Borrador</span><?php endif; ?>
                    </div>

                    <?php if (!$u["lecciones"]): ?>
                        <p class="texto-suave">Sin lecciones.</p>
                    <?php else: ?>
                        <ol class="pnl-lista pnl-lista--compacta">
                            <?php foreach ($u["lecciones"] as $l): ?>
                                <li class="pnl-item">
                                    <div class="pnl-item__cuerpo">
                                        <p class="pnl-item__titulo"><a href="index.php?page=ruta&amp;leccion=<?= (int) $l["id"] ?>"><?= (int) $l["orden"] ?>. <?= e($l["titulo"]) ?></a></p>
                                        <p class="pnl-item__texto">
                                            <?= e(plural_panel((int) $l["ejercicios"], "ejercicio", "ejercicios")) ?> · <?= (int) $l["xp"] ?> XP
                                            <?php if ((int) $l["publicado"] === 0): ?><span class="chip chip--neutro">Borrador</span><?php endif; ?>
                                            <?php if ((int) $l["jugables"] === 0): ?><span class="chip chip--alerta">En preparación</span><?php endif; ?>
                                            <?php if ((int) $l["senas_sin_video"] > 0): ?><span class="chip chip--alerta"><?= (int) $l["senas_sin_video"] ?> sin video</span><?php endif; ?>
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>

                    <details class="ruta-detalles">
                        <summary>Nueva lección</summary>
                        <form class="pnl-form" method="POST" action="ruta_acciones.php">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="accion" value="guardar_leccion">
                            <input type="hidden" name="unidad_id" value="<?= $uid ?>">
                            <div class="campo">
                                <label class="campo__etiqueta" for="nl-titulo-<?= $uid ?>">Título</label>
                                <input class="entrada" id="nl-titulo-<?= $uid ?>" name="titulo" maxlength="120" required>
                            </div>
                            <input type="hidden" name="orden" value="<?= count($u["lecciones"]) + 1 ?>">
                            <input type="hidden" name="xp" value="10">
                            <div class="pnl-form__acciones"><button class="boton boton--primario boton--sm" type="submit">Crear lección</button></div>
                        </form>
                    </details>

                    <details class="ruta-detalles">
                        <summary>Editar unidad</summary>
                        <form class="pnl-form" method="POST" action="ruta_acciones.php">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="accion" value="guardar_unidad">
                            <input type="hidden" name="nivel_id" value="<?= $nivelId ?>">
                            <input type="hidden" name="unidad_id" value="<?= $uid ?>">
                            <?php $unidadForm = $u; require __DIR__ . "/_ruta_unidad.php"; ?>
                            <div class="pnl-form__acciones"><button class="boton boton--primario boton--sm" type="submit">Guardar unidad</button></div>
                        </form>
                        <?php $botonEliminar("unidad", $uid, $urlNivel, "¿Eliminar la unidad «" . $u["titulo"] . "» con todas sus lecciones y ejercicios?"); ?>
                    </details>
                </section>
            <?php endforeach; ?>
        </div>

        <div>
            <section class="pnl-tarjeta" aria-labelledby="titulo-nivel">
                <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nivel"><?= e(RutaModel::nombreNivel($nivel, $codigoIdioma)) ?></h2></div>
                <form class="pnl-form" method="POST" action="ruta_acciones.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="accion" value="guardar_nivel">
                    <input type="hidden" name="nivel_id" value="<?= $nivelId ?>">
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-nombre">Nombre</label>
                        <input class="entrada" id="n-nombre" name="nombre" maxlength="60" required value="<?= e($nivel["nombre"]) ?>">
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="n-desc">Descripción</label>
                        <textarea class="entrada" id="n-desc" name="descripcion" rows="2" maxlength="255"><?= e($nivel["descripcion"] ?? "") ?></textarea>
                    </div>
                    <?php $interruptor("publicado", (int) $nivel["publicado"] === 1, "Publicado", "Los estudiantes ven este nivel en su ruta."); ?>
                    <?php $interruptor("premium", (int) $nivel["premium"] === 1, "Solo Premium", "El plan Gratuito lo ve con candado."); ?>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar nivel</button></div>
                </form>
            </section>

            <?php if ($esLsc): ?>
                <section class="pnl-tarjeta" aria-labelledby="titulo-videos">
                    <div class="pnl-tarjeta__cabecera">
                        <h2 id="titulo-videos">Videos pendientes</h2>
                        <p>Lecciones de LSC sin video propio o con señas sin video.</p>
                    </div>
                    <?php if (!$pendientesVideo): ?>
                        <p class="texto-suave">¡Todo tiene video!</p>
                    <?php else: ?>
                        <ul class="pnl-lista pnl-lista--compacta">
                            <?php foreach ($pendientesVideo as $p): ?>
                                <li class="pnl-item"><div class="pnl-item__cuerpo">
                                    <p class="pnl-item__titulo"><a href="index.php?page=ruta&amp;leccion=<?= (int) $p["id"] ?>"><?= e($p["nivel"]) ?> · <?= e($p["titulo"]) ?></a></p>
                                    <p class="pnl-item__texto">
                                        <?= (int) $p["sin_video_leccion"] === 1 ? "Sin video de la lección" : "" ?>
                                        <?= (int) $p["sin_video_leccion"] === 1 && (int) $p["senas_sin_video"] > 0 ? " · " : "" ?>
                                        <?= (int) $p["senas_sin_video"] > 0 ? e(plural_panel((int) $p["senas_sin_video"], "seña sin video", "señas sin video")) : "" ?>
                                    </p>
                                </div></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="pnl-tarjeta" aria-labelledby="titulo-nueva-unidad">
                <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nueva-unidad">Nueva unidad</h2></div>
                <form class="pnl-form" method="POST" action="ruta_acciones.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="accion" value="guardar_unidad">
                    <input type="hidden" name="nivel_id" value="<?= $nivelId ?>">
                    <?php $unidadForm = ["orden" => count($arbol) + 1, "publicado" => 0]; require __DIR__ . "/_ruta_unidad.php"; ?>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Crear unidad</button></div>
                </form>
            </section>
        </div>
    </div>

<?php endif; ?>
