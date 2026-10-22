<?php
/**
 * Clases en vivo del docente.
 * Variables: $clasesDisponibles, $clases, $claseActual (o null), $participantes,
 *            $idiomasActivos, $csrfToken
 */
$estadoClase = function (array $cl): array {
    if ($cl["estado"] === "cancelada") return ["Cancelada", "chip--error"];
    if ((int) ($cl["terminada"] ?? 0) === 1) return ["Terminada", "chip--neutro"];
    return ["Programada", "chip--exito"];
};

/** Campos del formulario (crear o editar). */
$formulario = function (?array $cl) use ($idiomasActivos): void { ?>
    <div class="campo">
        <label class="campo__etiqueta" for="c-titulo">Título</label>
        <input class="entrada" id="c-titulo" name="titulo" maxlength="150" required
               value="<?= e($cl["titulo"] ?? "") ?>" placeholder="Ej. Conversación: saludos y presentaciones">
    </div>
    <div class="pnl-form__fila">
        <div class="campo">
            <label class="campo__etiqueta" for="c-inicio">Fecha y hora (Colombia)</label>
            <input class="entrada" type="datetime-local" id="c-inicio" name="inicio" required
                   value="<?= $cl ? e(date("Y-m-d\TH:i", strtotime($cl["inicio"]))) : "" ?>">
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="c-duracion">Duración</label>
            <select class="entrada" id="c-duracion" name="duracion_min">
                <?php foreach (ClaseModel::DURACIONES as $min): ?>
                    <option value="<?= $min ?>" <?= (int) ($cl["duracion_min"] ?? 60) === $min ? "selected" : "" ?>><?= $min ?> minutos</option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="pnl-form__fila">
        <div class="campo">
            <label class="campo__etiqueta" for="c-idioma">Idioma</label>
            <select class="entrada" id="c-idioma" name="idioma_id">
                <option value="">Sin idioma</option>
                <?php foreach ($idiomasActivos as $i): ?>
                    <option value="<?= (int) $i["id"] ?>" <?= (int) ($cl["idioma_id"] ?? 0) === (int) $i["id"] ? "selected" : "" ?>><?= e($i["nombre"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="c-cupo">Cupo (máximo <?= ClaseModel::CUPO_MAX ?>)</label>
            <input class="entrada" type="number" id="c-cupo" name="cupo" min="1" max="<?= ClaseModel::CUPO_MAX ?>" required
                   value="<?= (int) ($cl["cupo"] ?? ClaseModel::CUPO_MAX) ?>">
        </div>
    </div>
    <div class="campo">
        <label class="campo__etiqueta" for="c-enlace">Enlace de la clase</label>
        <input class="entrada" type="url" id="c-enlace" name="enlace" maxlength="500" required inputmode="url"
               value="<?= e($cl["enlace"] ?? "") ?>" placeholder="https://meet.google.com/..." aria-describedby="c-enlace-ayuda">
        <p class="campo__ayuda" id="c-enlace-ayuda">Google Meet, Zoom, Teams… Solo lo ven los inscritos, <?= ClaseModel::MINUTOS_ANTES ?> minutos antes de empezar.</p>
    </div>
    <div class="campo">
        <label class="campo__etiqueta" for="c-desc">Descripción <span class="campo__ayuda">(opcional)</span></label>
        <textarea class="entrada" id="c-desc" name="descripcion" rows="3" placeholder="Qué van a practicar y qué deben preparar"><?= e($cl["descripcion"] ?? "") ?></textarea>
    </div>
<?php };
?>

<?php if (!$clasesDisponibles): ?>

    <header class="pnl-encabezado"><div><h1>Clases en vivo</h1></div></header>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Las clases en vivo aún no están disponibles.</span>
    </div>

<?php elseif ($claseActual):
    [$txtEstado, $clsEstado] = $estadoClase($claseActual);
    $inscritos = array_values(array_filter($participantes, fn($p) => $p["estado"] === "inscrito"));
    $espera = array_values(array_filter($participantes, fn($p) => $p["estado"] === "espera"));
?>

    <header class="pnl-encabezado">
        <div>
            <a href="index.php?page=clases"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Clases en vivo</a>
            <h1 style="margin-top: var(--esp-2);"><?= e($claseActual["titulo"]) ?></h1>
            <p>
                <span class="chip <?= $clsEstado ?>"><?= $txtEstado ?></span>
                <?php if ((int) $claseActual["abierta_para_todos"] === 1): ?><span class="chip chip--logro">Abierta para todos</span><?php endif; ?>
                <?= e(ClaseModel::fechaHora($claseActual["inicio"])) ?> · <?= (int) $claseActual["duracion_min"] ?> min
            </p>
        </div>
    </header>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-participantes">
            <div class="pnl-tarjeta__cabecera">
                <h2 id="titulo-participantes">Inscritos (<?= count($inscritos) ?> de <?= (int) $claseActual["cupo"] ?>)</h2>
            </div>
            <?php if (!$inscritos): ?>
                <p class="texto-suave">Todavía no hay estudiantes inscritos.</p>
            <?php else: ?>
                <ol class="pnl-lista pnl-lista--compacta">
                    <?php foreach ($inscritos as $p): ?>
                        <li class="pnl-item"><div class="pnl-item__cuerpo"><p class="pnl-item__titulo"><?= e(nombre_panel($p)) ?></p><p class="pnl-item__texto"><?= e($p["correo"]) ?></p></div></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>

            <?php if ($espera): ?>
                <h3 class="pnl-subtitulo">Lista de espera (<?= count($espera) ?>)</h3>
                <p class="campo__ayuda">Si alguien se retira, entra automáticamente el primero de la lista y se le avisa.</p>
                <ol class="pnl-lista pnl-lista--compacta">
                    <?php foreach ($espera as $p): ?>
                        <li class="pnl-item"><div class="pnl-item__cuerpo"><p class="pnl-item__titulo"><?= e(nombre_panel($p)) ?></p><p class="pnl-item__texto"><?= e($p["correo"]) ?></p></div></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <section class="pnl-tarjeta" aria-labelledby="titulo-editar-clase">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-editar-clase">Editar clase</h2></div>
            <?php if ($claseActual["estado"] === "cancelada"): ?>
                <p class="texto-suave">Esta clase se canceló<?= $claseActual["motivo_cancelacion"] ? ": " . e($claseActual["motivo_cancelacion"]) : "" ?>.</p>
            <?php else: ?>
                <form class="pnl-form" method="POST" action="acciones.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="accion" value="editar_clase">
                    <input type="hidden" name="clase_id" value="<?= (int) $claseActual["id"] ?>">
                    <?php $formulario($claseActual); ?>
                    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Guardar cambios</button></div>
                </form>

                <form class="pnl-form clase-cancelar" method="POST" action="acciones.php"
                      data-confirmar="¿Cancelar la clase «<?= e($claseActual["titulo"]) ?>»? Avisaremos a los <?= count($participantes) ?> estudiantes inscritos o en espera.">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="accion" value="cancelar_clase">
                    <input type="hidden" name="clase_id" value="<?= (int) $claseActual["id"] ?>">
                    <div class="campo">
                        <label class="campo__etiqueta" for="c-motivo">Motivo de la cancelación <span class="campo__ayuda">(se lo contamos a los estudiantes)</span></label>
                        <input class="entrada" id="c-motivo" name="motivo" maxlength="255">
                    </div>
                    <div class="pnl-form__acciones"><button class="boton boton--peligro" type="submit">Cancelar clase</button></div>
                </form>
            <?php endif; ?>
        </section>
    </div>

<?php else: ?>

    <header class="pnl-encabezado">
        <div>
            <h1>Clases en vivo</h1>
            <p>Clases por Meet, Zoom o Teams con cupo de hasta <?= ClaseModel::CUPO_MAX ?> estudiantes. Si se llena, se abre una lista de espera.</p>
        </div>
    </header>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-mis-clases">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-mis-clases">Tus clases</h2></div>
            <?php if (!$clases): ?>
                <p class="texto-suave">Todavía no has programado clases. Crea la primera con el formulario.</p>
            <?php else: ?>
                <ul class="pnl-lista">
                    <?php foreach ($clases as $cl): [$txtEstado, $clsEstado] = $estadoClase($cl); ?>
                        <li class="pnl-item">
                            <span class="pnl-dato__icono" aria-hidden="true"><i class="fa-solid fa-video"></i></span>
                            <div class="pnl-item__cuerpo">
                                <p class="pnl-item__titulo"><a href="index.php?page=clases&amp;clase=<?= (int) $cl["id"] ?>"><?= e($cl["titulo"]) ?></a></p>
                                <p class="pnl-item__texto">
                                    <span class="chip <?= $clsEstado ?>"><?= $txtEstado ?></span>
                                    <?= e(ClaseModel::fechaHora($cl["inicio"])) ?> ·
                                    <?= (int) $cl["inscritos"] ?>/<?= (int) $cl["cupo"] ?> inscritos<?= (int) $cl["en_espera"] > 0 ? " · " . (int) $cl["en_espera"] . " en espera" : "" ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="pnl-tarjeta" aria-labelledby="titulo-nueva-clase">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nueva-clase">Nueva clase</h2></div>
            <form class="pnl-form" method="POST" action="acciones.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="accion" value="crear_clase">
                <?php $formulario(null); ?>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Programar clase</button></div>
            </form>
        </section>
    </div>

<?php endif; ?>
