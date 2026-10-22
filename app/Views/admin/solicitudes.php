<?php
/** Solicitudes de docentes. Variables: $pendientes, $aprobados, $rechazados, $esSuper, $solicitudes, $documentos */
$iconosDoc = ["application/pdf" => "fa-file-pdf", "image/jpeg" => "fa-file-image", "image/png" => "fa-file-image"];

/** Formulario de una acción (aprobar, rechazar, eliminar) sobre un docente. */
function botonSolicitud(int $id, string $accion, string $texto, string $icono, string $clase, string $confirmar = ""): string
{
    ob_start(); ?>
    <form method="POST" action="solicitudes.php" <?= $confirmar ? 'data-confirmar="' . e($confirmar) . '"' : "" ?>>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="<?= e($accion) ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        <button type="submit" class="boton <?= $clase ?> boton--sm">
            <i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= e($texto) ?>
        </button>
    </form>
    <?php return ob_get_clean();
}
?>

<header class="pnl-encabezado">
    <div>
        <h1>Solicitudes de docentes</h1>
        <p>Los docentes que se registran quedan pendientes hasta que revises sus diplomas o certificados y los apruebes. Si rechazas, escribe el motivo: se lo enviamos.</p>
    </div>
</header>

<section class="pnl-datos" aria-label="Resumen de solicitudes">
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--naranja"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= count($pendientes) ?></span><br><span class="pnl-dato__texto">Pendientes</span></span>
    </div>
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--verde"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= count($aprobados) ?></span><br><span class="pnl-dato__texto">Aprobados</span></span>
    </div>
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--rojo"><i class="fa-solid fa-user-xmark" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= count($rechazados) ?></span><br><span class="pnl-dato__texto">Rechazados</span></span>
    </div>
</section>

<section class="pnl-tarjeta" aria-labelledby="titulo-pendientes">
    <div class="pnl-tarjeta__cabecera">
        <div><h2 id="titulo-pendientes">Pendientes</h2><p>Esperando tu revisión</p></div>
    </div>
    <?php if (empty($pendientes)): ?>
        <div class="vacio">
            <span class="vacio__icono"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span>
            <p class="vacio__titulo">No hay solicitudes pendientes</p>
            <p class="vacio__texto">Cuando un docente se registre, aparecerá aquí.</p>
        </div>
    <?php else: ?>
        <ul class="pnl-lista">
            <?php foreach ($pendientes as $d):
                $sol = $solicitudes[(int) $d["id"]] ?? null;
                $docs = $documentos[(int) $d["id"]] ?? [];
            ?>
                <li class="pnl-item solicitud">
                    <span class="pnl-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($d["nombre"], 0, 1))) ?></span>
                    <div class="pnl-item__cuerpo">
                        <p class="pnl-item__titulo"><?= e(nombre_panel($d)) ?></p>
                        <p class="pnl-item__texto">
                            <?= e($d["correo"]) ?> · se registró el <?= e(fecha_panel($d["fecha_registro"])) ?>
                            <?php if (!empty($sol["idioma"])): ?> · enseñará <strong><?= e($sol["idioma"]) ?></strong><?php endif; ?>
                        </p>
                        <?php if ($docs): ?>
                            <ul class="solicitud__docs" aria-label="Documentos de <?= e(nombre_panel($d)) ?>">
                                <?php foreach ($docs as $doc): ?>
                                    <li>
                                        <a href="documento.php?id=<?= (int) $doc["id"] ?>" target="_blank" rel="noopener">
                                            <i class="fa-solid <?= e($iconosDoc[$doc["tipo_mime"]] ?? "fa-file") ?>" aria-hidden="true"></i>
                                            <?= e($doc["nombre_original"]) ?>
                                        </a>
                                        <span class="col-suave"><?= e(DocenteSolicitudModel::pesoLegible((int) $doc["tamano"])) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="pnl-item__texto"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> No subió documentos.</p>
                        <?php endif; ?>
                        <details class="solicitud__rechazo">
                            <summary>Rechazar con un motivo</summary>
                            <form method="POST" action="solicitudes.php" class="pnl-form">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="accion" value="rechazar">
                                <input type="hidden" name="id" value="<?= (int) $d["id"] ?>">
                                <label class="campo__etiqueta" for="motivo-<?= (int) $d["id"] ?>">Motivo (se lo enviamos al docente)</label>
                                <textarea class="entrada" id="motivo-<?= (int) $d["id"] ?>" name="motivo" rows="2" minlength="10" maxlength="500" required
                                          placeholder="Ej. El certificado no muestra el nivel de inglés. Sube uno que acredite B2 o superior."></textarea>
                                <div class="pnl-form__acciones"><button class="boton boton--peligro boton--sm" type="submit"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Rechazar solicitud</button></div>
                            </form>
                        </details>
                    </div>
                    <div class="pnl-item__acciones">
                        <?= botonSolicitud((int) $d["id"], "aprobar", "Aprobar", "fa-check", "boton--exito") ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<div class="pnl-rejilla pnl-rejilla--2">
    <section class="pnl-tarjeta" aria-labelledby="titulo-aprobados">
        <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-aprobados">Aprobados</h2><p>Docentes activos</p></div></div>
        <?php if (empty($aprobados)): ?>
            <p class="texto-suave">Todavía no hay docentes aprobados.</p>
        <?php else: ?>
            <ul class="pnl-lista pnl-lista--compacta">
                <?php foreach ($aprobados as $d): ?>
                    <li class="pnl-item">
                        <div class="pnl-item__cuerpo">
                            <p class="pnl-item__titulo"><?= e(nombre_panel($d)) ?></p>
                            <p class="pnl-item__texto"><?= e($d["correo"]) ?></p>
                        </div>
                        <div class="pnl-item__acciones">
                            <?= botonSolicitud((int) $d["id"], "desactivar", "Desactivar", "fa-user-slash", "boton--peligro", "¿Desactivar a este docente? Podrás reactivarlo después.") ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pnl-tarjeta" aria-labelledby="titulo-rechazados">
        <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-rechazados">Rechazados o inactivos</h2><p>Puedes reactivarlos</p></div></div>
        <?php if (empty($rechazados)): ?>
            <p class="texto-suave">No hay docentes rechazados.</p>
        <?php else: ?>
            <ul class="pnl-lista pnl-lista--compacta">
                <?php foreach ($rechazados as $d): ?>
                    <li class="pnl-item">
                        <div class="pnl-item__cuerpo">
                            <p class="pnl-item__titulo"><?= e(nombre_panel($d)) ?></p>
                            <p class="pnl-item__texto"><?= e($d["correo"]) ?></p>
                            <?php if (!empty($solicitudes[(int) $d["id"]]["motivo"])): ?>
                                <p class="pnl-item__texto">Motivo: <?= e($solicitudes[(int) $d["id"]]["motivo"]) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="pnl-item__acciones">
                            <?= botonSolicitud((int) $d["id"], "aprobar", "Reactivar", "fa-rotate-left", "boton--secundario") ?>
                            <?php if ($esSuper): ?>
                                <?= botonSolicitud((int) $d["id"], "eliminar", "Eliminar", "fa-trash", "boton--peligro",
                                    "¿Eliminar definitivamente esta cuenta? No se puede deshacer. Sus cursos quedarán sin docente asignado.") ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
