<?php
/**
 * Un grupo del docente con pestañas: tablón, tareas, material y estudiantes.
 * Variables: $grupoActual, $tab, $anuncios, $material, $progreso, $tareasDisponibles,
 *            $tareas, $tareaActual, $adjuntosTarea, $entregas, $adjuntosEntregas, $resumenTareas,
 *            $cursosDocente, $acceptArchivo, $tiposArchivo, $csrfToken
 */
$grupoId = (int) $grupoActual["id"];
$archivado = (int) $grupoActual["archivado"] === 1;
$base = "index.php?page=grupo&amp;grupo=" . $grupoId;
$pestanas = [
    "tablon"      => ["Tablón", "fa-bullhorn"],
    "tareas"      => ["Tareas", "fa-clipboard-list"],
    "material"    => ["Material", "fa-folder-open"],
    "estudiantes" => ["Estudiantes (" . (int) $grupoActual["miembros"] . ")", "fa-user-group"],
];
if (!$tareasDisponibles) {
    unset($pestanas["tareas"]);
}

// Para las piezas compartidas
$accionUrl = "grupos_acciones.php";
$usuarioId = (int) $_SESSION["usuario_id"];
$esDocente = true;
?>

<header class="pnl-encabezado">
    <div>
        <a href="index.php?page=grupos"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Grupos</a>
        <h1 style="margin-top: var(--esp-2);"><?= e($grupoActual["nombre"]) ?></h1>
        <p>
            <?php if ($archivado): ?><span class="chip chip--neutro">Archivado</span><?php endif; ?>
            <?php if ($grupoActual["curso"]): ?><span class="chip"><i class="fa-solid fa-book" aria-hidden="true"></i> <?= e($grupoActual["curso"]) ?></span><?php endif; ?>
            <?= $grupoActual["descripcion"] ? e($grupoActual["descripcion"]) : "" ?>
        </p>
    </div>
    <?php if (!$archivado): ?>
        <div class="grupo-codigo-caja">
            <span class="campo__ayuda">Código para entrar</span>
            <span class="grupo-codigo" id="codigo-grupo"><?= e($grupoActual["codigo"]) ?></span>
            <button class="boton boton--secundario boton--sm" type="button" data-copiar="<?= e($grupoActual["codigo"]) ?>">
                <i class="fa-regular fa-copy" aria-hidden="true"></i> Copiar
            </button>
        </div>
    <?php endif; ?>
</header>

<?php if ($archivado): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
        <span>Este grupo está archivado: los estudiantes no lo ven y nadie puede entrar. Puedes restaurarlo en la pestaña Estudiantes.</span>
    </div>
<?php endif; ?>

<nav class="pnl-pestanas" aria-label="Secciones del grupo">
    <?php foreach ($pestanas as $clave => [$texto, $icono]): ?>
        <a class="pnl-pestana <?= $tab === $clave ? "es-activa" : "" ?>" href="<?= $base ?>&amp;tab=<?= $clave ?>"
           <?= $tab === $clave ? 'aria-current="page"' : "" ?>>
            <i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= e($texto) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($tab === "tablon"): ?>

    <?php if (!$archivado): ?>
        <section class="pnl-tarjeta" aria-labelledby="titulo-nuevo-anuncio">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nuevo-anuncio">Nuevo anuncio</h2></div>
            <form class="pnl-form" method="POST" action="grupos_acciones.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="accion" value="publicar_anuncio">
                <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                <div class="campo">
                    <label class="sr-only" for="a-texto">Texto del anuncio</label>
                    <textarea class="entrada" id="a-texto" name="texto" rows="3" maxlength="5000" required
                              placeholder="Comparte algo con tu grupo…" aria-describedby="a-texto-ayuda"></textarea>
                    <p class="campo__ayuda" id="a-texto-ayuda">Les llega a la campana de los <?= e(plural_panel((int) $grupoActual["miembros"], "estudiante", "estudiantes")) ?> (y por correo si lo tienen activado).</p>
                </div>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Publicar</button></div>
            </form>
        </section>
    <?php endif; ?>

    <section class="grupo-seccion" aria-label="Anuncios">
        <?php require __DIR__ . "/../grupos/_tablon.php"; ?>
    </section>

<?php elseif ($tab === "tareas"): ?>

    <?php require __DIR__ . "/_tareas.php"; ?>

<?php elseif ($tab === "material"): ?>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section aria-label="Material del grupo">
            <?php require __DIR__ . "/../grupos/_material.php"; ?>
        </section>

        <?php if (!$archivado): ?>
            <section class="pnl-tarjeta" aria-labelledby="titulo-nuevo-material">
                <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nuevo-material">Agregar material</h2></div>
                <form class="pnl-form" method="POST" action="grupos_acciones.php" enctype="multipart/form-data" data-material>
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="accion" value="agregar_material">
                    <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                    <fieldset class="campo grupo-tipos">
                        <legend class="campo__etiqueta">Tipo</legend>
                        <?php foreach (["archivo" => ["Archivo", "fa-file-arrow-up"], "enlace" => ["Enlace", "fa-link"], "video" => ["Video", "fa-circle-play"]] as $valor => [$texto, $icono]): ?>
                            <label class="grupo-tipo">
                                <input type="radio" name="tipo" value="<?= $valor ?>" <?= $valor === "archivo" ? "checked" : "" ?> data-material-tipo>
                                <span><i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= $texto ?></span>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                    <div class="campo">
                        <label class="campo__etiqueta" for="m-titulo">Título</label>
                        <input class="entrada" id="m-titulo" name="titulo" maxlength="150" required placeholder="Ej. Guía de la unidad 1">
                    </div>
                    <div class="campo" data-para-tipo="archivo">
                        <label class="campo__etiqueta" for="m-archivo">Archivo</label>
                        <input class="entrada" type="file" id="m-archivo" name="archivo" accept="<?= e($acceptArchivo) ?>" aria-describedby="m-archivo-ayuda">
                        <p class="campo__ayuda" id="m-archivo-ayuda"><?= e($tiposArchivo) ?>. Máximo 5 MB.</p>
                    </div>
                    <div class="campo" data-para-tipo="enlace video">
                        <label class="campo__etiqueta" for="m-url">Enlace</label>
                        <input class="entrada" type="url" id="m-url" name="url" maxlength="500" inputmode="url"
                               placeholder="https://…" aria-describedby="m-url-ayuda">
                        <p class="campo__ayuda" id="m-url-ayuda">Para video: YouTube o Vimeo. Para enlace: cualquier página que empiece por https://</p>
                    </div>
                    <div class="campo">
                        <label class="campo__etiqueta" for="m-desc">Descripción <span class="campo__ayuda">(opcional)</span></label>
                        <textarea class="entrada" id="m-desc" name="descripcion" rows="2" maxlength="1000"></textarea>
                    </div>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Agregar</button></div>
                </form>
            </section>
        <?php endif; ?>
    </div>

<?php else:
    $total = $progreso["total"];
?>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-estudiantes">
            <div class="pnl-tarjeta__cabecera">
                <h2 id="titulo-estudiantes">Estudiantes</h2>
                <?php if ($total !== null): ?><p>Avance en <?= e($grupoActual["curso"]) ?> (<?= e(plural_panel($total, "lección", "lecciones")) ?>)</p><?php endif; ?>
            </div>
            <?php if (!$progreso["miembros"]): ?>
                <p class="texto-suave">Todavía no hay estudiantes. Compárteles el código <strong><?= e($grupoActual["codigo"]) ?></strong>: lo escriben en «Mis grupos».</p>
            <?php else: ?>
                <ul class="pnl-lista pnl-lista--compacta">
                    <?php foreach ($progreso["miembros"] as $m):
                        $pct = $total ? (int) min(100, round(((int) $m["hechas"] / $total) * 100)) : 0;
                    ?>
                        <li class="pnl-item">
                            <div class="pnl-item__cuerpo">
                                <p class="pnl-item__titulo"><?= e(nombre_panel($m)) ?></p>
                                <p class="pnl-item__texto"><?= e($m["correo"]) ?> · desde <?= e(fecha_panel($m["unido_en"])) ?></p>
                                <?php if ($resumenTareas && $resumenTareas["total"] > 0):
                                    $rt = $resumenTareas["porEstudiante"][(int) $m["id"]] ?? null; ?>
                                    <p class="pnl-item__texto">
                                        Tareas: <?= (int) ($rt["entregadas"] ?? 0) ?> de <?= (int) $resumenTareas["total"] ?> entregadas
                                        <?php if ($rt && $rt["promedio"] !== null): ?> · promedio <?= e(TareaModel::numero($rt["promedio"])) ?>%<?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($total !== null): ?>
                                    <div class="grupo-progreso">
                                        <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>"
                                             aria-label="Avance de <?= e(nombre_panel($m)) ?>">
                                            <div class="progreso__relleno" data-valor="<?= $pct ?>" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                        <span><?= (int) $m["hechas"] ?>/<?= (int) $total ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="grupos_acciones.php" data-confirmar="¿Quitar a <?= e(nombre_panel($m)) ?> del grupo?">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="accion" value="quitar_miembro">
                                <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                                <input type="hidden" name="usuario_id" value="<?= (int) $m["id"] ?>">
                                <button class="boton boton--fantasma boton--sm" type="submit">Quitar</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <div>
            <section class="pnl-tarjeta" aria-labelledby="titulo-datos-grupo">
                <div class="pnl-tarjeta__cabecera"><h2 id="titulo-datos-grupo">Datos del grupo</h2></div>
                <form class="pnl-form" method="POST" action="grupos_acciones.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="accion" value="editar_grupo">
                    <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                    <?php $grupoForm = $grupoActual; require __DIR__ . "/_form_grupo.php"; ?>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar cambios</button></div>
                </form>
            </section>

            <section class="pnl-tarjeta" aria-labelledby="titulo-acceso-grupo">
                <div class="pnl-tarjeta__cabecera"><h2 id="titulo-acceso-grupo">Acceso</h2></div>
                <?php if (!$archivado): ?>
                    <form class="pnl-form" method="POST" action="grupos_acciones.php"
                          data-confirmar="¿Cambiar el código? El código <?= e($grupoActual["codigo"]) ?> dejará de servir (quienes ya están en el grupo siguen).">
                        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                        <input type="hidden" name="accion" value="regenerar_codigo">
                        <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                        <p class="campo__ayuda">Si el código se compartió donde no debía, genera uno nuevo.</p>
                        <div class="pnl-form__acciones"><button class="boton boton--secundario" type="submit">Generar código nuevo</button></div>
                    </form>
                <?php endif; ?>
                <form class="pnl-form" method="POST" action="grupos_acciones.php"
                      <?= $archivado ? "" : 'data-confirmar="¿Archivar el grupo? Los estudiantes dejarán de verlo y nadie podrá entrar. Puedes restaurarlo cuando quieras."' ?>>
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="accion" value="archivar_grupo">
                    <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
                    <input type="hidden" name="archivar" value="<?= $archivado ? "0" : "1" ?>">
                    <div class="pnl-form__acciones">
                        <button class="boton <?= $archivado ? "boton--primario" : "boton--peligro" ?>" type="submit">
                            <?= $archivado ? "Restaurar grupo" : "Archivar grupo" ?>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

<?php endif; ?>
