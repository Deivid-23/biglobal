<?php
/** Clases en vivo (todas). Variables: $disponible, $clases */
?>
<header class="pnl-encabezado">
    <div>
        <h1>Clases en vivo</h1>
        <p>Las programan los docentes. Aquí ves todas y puedes cancelar cualquiera: avisamos a los inscritos y a la lista de espera.</p>
    </div>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Las clases en vivo aún no están disponibles: falta aplicar <code>database/clases_vivo.sql</code>.</span>
    </div>
<?php else: ?>
    <section class="pnl-tarjeta" aria-labelledby="tabla-clases">
        <div class="pnl-tarjeta__cabecera"><h2 id="tabla-clases"><?= e(plural_panel(count($clases), "clase", "clases")) ?></h2></div>
        <?php if (!$clases): ?>
            <p class="texto-suave">Ningún docente ha programado clases todavía.</p>
        <?php else: ?>
            <div class="pnl-tabla-caja">
                <table class="pnl-tabla">
                    <thead><tr><th>Clase</th><th>Docente</th><th>Fecha</th><th>Cupos</th><th>Estado</th><th class="col-acciones">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($clases as $cl):
                            $terminada = (int) $cl["terminada"] === 1;
                            [$txt, $cls] = $cl["estado"] === "cancelada" ? ["Cancelada", "chip--error"] : ($terminada ? ["Terminada", "chip--neutro"] : ["Programada", "chip--exito"]);
                        ?>
                            <tr>
                                <td>
                                    <span class="col-fuerte"><?= e($cl["titulo"]) ?></span>
                                    <?php if ($cl["idioma_codigo"]): ?><span class="chip chip--neutro"><?= e($cl["idioma_codigo"]) ?></span><?php endif; ?>
                                    <?php if ((int) $cl["abierta_para_todos"] === 1): ?><span class="chip chip--logro">Abierta para todos</span><?php endif; ?>
                                </td>
                                <td class="col-suave"><?= e(trim($cl["docente"])) ?></td>
                                <td class="col-suave pnl-tabla__fecha"><?= e(ClaseModel::fechaHora($cl["inicio"])) ?></td>
                                <td><?= (int) $cl["inscritos"] ?>/<?= (int) $cl["cupo"] ?><?= (int) $cl["en_espera"] > 0 ? '<br><span class="col-suave">' . (int) $cl["en_espera"] . " en espera</span>" : "" ?></td>
                                <td><span class="chip <?= $cls ?>"><?= $txt ?></span></td>
                                <td class="col-acciones">
                                    <?php if ($cl["estado"] !== "cancelada" && !$terminada): ?>
                                        <form method="POST" action="index.php" data-confirmar="¿Cancelar la clase «<?= e($cl["titulo"]) ?>»? Avisaremos a los inscritos y a la lista de espera.">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="accion" value="cancelar_clase">
                                            <input type="hidden" name="clase_id" value="<?= (int) $cl["id"] ?>">
                                            <input type="hidden" name="motivo" value="La canceló el equipo de BiGlobal">
                                            <button class="boton boton--peligro boton--sm" type="submit">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
