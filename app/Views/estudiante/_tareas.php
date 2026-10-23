<?php
/**
 * Pestaña Tareas del grupo (estudiante).
 * Variables: $grupo, $grupoId, $tareas, $tarea, $entrega, $adjuntosTarea, $adjuntosEntrega
 */
$fechaTarea = fn(?string $f): string => $f ? "Entrega: " . ClaseModel::fechaHora($f) : "Sin fecha límite";

/** [texto, clase del chip] según el estado de la entrega. */
$estadoTarea = function (array $t): array {
    if ($t["entrega_id"] === null) {
        return (int) $t["vencida"] === 1 ? ["Vencida · sin entregar", "chip--error"] : ["Pendiente", "chip--logro"];
    }
    if ($t["puntaje"] !== null) {
        return ["Calificada: " . TareaModel::numero($t["puntaje"]) . "/" . (int) $t["puntaje_max"], "chip--exito"];
    }
    return [(int) $t["tardia"] === 1 ? "Entregada tarde" : "Entregada", "chip--neutro"];
};
$urlGrupo = "index.php?page=grupo&amp;id=" . (int) $grupoId;
?>

<?php if (!$tarea): ?>

    <?php if (!$tareas): ?>
        <div class="vacio">
            <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
            <p class="vacio__titulo">No hay tareas por ahora</p>
            <p class="vacio__texto">Cuando tu docente publique una tarea, te avisaremos y aparecerá aquí.</p>
        </div>
    <?php else: ?>
        <ul class="grupos-lista">
            <?php foreach ($tareas as $t): [$txtEstado, $clsEstado] = $estadoTarea($t); ?>
                <li>
                    <a class="grupo-tarjeta" href="<?= $urlGrupo ?>&amp;tab=tareas&amp;tarea=<?= (int) $t["id"] ?>">
                        <span class="grupo-tarjeta__icono" aria-hidden="true"><i class="fa-solid fa-clipboard-list"></i></span>
                        <span class="grupo-tarjeta__cuerpo">
                            <strong><?= e($t["titulo"]) ?></strong>
                            <small><?= e($fechaTarea($t["fecha_entrega"])) ?> · <?= (int) $t["puntaje_max"] ?> puntos</small>
                            <span><span class="chip <?= $clsEstado ?>"><?= e($txtEstado) ?></span></span>
                        </span>
                        <i class="fa-solid fa-chevron-right grupo-tarjeta__flecha" aria-hidden="true"></i>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php else:
    $calificada = $entrega && $entrega["puntaje"] !== null;
    $vencida = (int) $tarea["vencida"] === 1;
?>

    <a class="enlace-atras" href="<?= $urlGrupo ?>&amp;tab=tareas"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Todas las tareas</a>

    <article class="grupo-tarea">
        <h2 class="grupo-tarea__titulo"><?= e($tarea["titulo"]) ?></h2>
        <p class="grupo-tarea__meta">
            <?php if ($vencida): ?><span class="chip chip--neutro">Venció</span><?php endif; ?>
            <?= e($fechaTarea($tarea["fecha_entrega"])) ?> · <?= (int) $tarea["puntaje_max"] ?> puntos
        </p>
        <?php if ($tarea["instrucciones"]): ?><p class="grupo-tarea__texto"><?= nl2br(e($tarea["instrucciones"])) ?></p><?php endif; ?>
        <?php $listaAdjuntos = $adjuntosTarea; require __DIR__ . "/../grupos/_adjuntos.php"; ?>
    </article>

    <?php if ($calificada): ?>
        <section class="grupo-nota" aria-labelledby="titulo-nota">
            <h3 id="titulo-nota">Tu calificación</h3>
            <p class="grupo-nota__valor"><strong><?= e(TareaModel::numero($entrega["puntaje"])) ?></strong> / <?= (int) $tarea["puntaje_max"] ?></p>
            <?php if ($entrega["comentario_docente"]): ?>
                <p class="grupo-nota__comentario"><i class="fa-solid fa-comment" aria-hidden="true"></i> <?= nl2br(e($entrega["comentario_docente"])) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($entrega): ?>
        <section class="grupo-mi-entrega" aria-labelledby="titulo-mi-entrega">
            <h3 id="titulo-mi-entrega">Tu entrega</h3>
            <p class="grupo-tarea__meta">
                Enviada el <?= e(ClaseModel::fechaHora($entrega["enviada_en"])) ?>
                <?php if ((int) $entrega["tardia"] === 1): ?><span class="chip chip--error">Tardía</span><?php endif; ?>
            </p>
            <?php if ($entrega["texto"]): ?><p class="grupo-tarea__texto"><?= nl2br(e($entrega["texto"])) ?></p><?php endif; ?>
            <?php $listaAdjuntos = $adjuntosEntrega; require __DIR__ . "/../grupos/_adjuntos.php"; ?>
        </section>
    <?php endif; ?>

    <?php if (!$calificada): ?>
        <section class="grupo-mi-entrega" aria-labelledby="titulo-entregar">
            <h3 id="titulo-entregar"><?= $entrega ? "Reenviar" : "Entregar" ?></h3>
            <?php if ($vencida): ?>
                <div class="aviso aviso--alerta">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    <span>La fecha ya pasó: todavía puedes entregar, pero quedará marcada como tardía.</span>
                </div>
            <?php endif; ?>
            <form class="grupo-entregar" method="POST" action="grupo.php" enctype="multipart/form-data"
                  <?= $entrega ? 'data-confirmar="Al reenviar se reemplazan tu texto y tus archivos anteriores. ¿Continuar?"' : "" ?>>
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="entregar">
                <input type="hidden" name="grupo_id" value="<?= (int) $grupoId ?>">
                <input type="hidden" name="tarea_id" value="<?= (int) $tarea["id"] ?>">
                <div class="campo">
                    <label class="campo__etiqueta" for="entrega-texto">Tu respuesta</label>
                    <textarea class="entrada" id="entrega-texto" name="texto" rows="5" maxlength="<?= TareaModel::MAX_TEXTO ?>"
                              placeholder="Escribe aquí (opcional si adjuntas archivos)"><?= e($entrega["texto"] ?? "") ?></textarea>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="entrega-archivos">Archivos <span class="campo__ayuda">(opcional)</span></label>
                    <input class="entrada" type="file" id="entrega-archivos" name="archivos[]" multiple
                           accept="<?= e(Archivo::accept(GrupoModel::GRUPOS_ARCHIVO)) ?>" aria-describedby="entrega-archivos-ayuda">
                    <p class="campo__ayuda" id="entrega-archivos-ayuda">
                        <?= e(Archivo::textoTipos(GrupoModel::GRUPOS_ARCHIVO)) ?>. Hasta <?= TareaModel::MAX_ARCHIVOS ?> archivos de 5 MB.
                        <?= $entrega ? "Si reenvías, vuelve a adjuntar todo lo que quieras entregar." : "" ?>
                    </p>
                </div>
                <button class="boton boton--primario" type="submit"><?= $entrega ? "Reenviar" : "Entregar" ?></button>
            </form>
        </section>
    <?php endif; ?>

<?php endif; ?>
