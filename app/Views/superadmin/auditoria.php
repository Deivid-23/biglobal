<?php
/**
 * Registro de auditoría (solo lectura).
 * Variables: $registros, $filtro, $disponible
 */
$meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
$iconos = [
    "cuenta"        => "fa-user",
    "curso"         => "fa-book",
    "leccion"       => "fa-book-open",
    "actividad"     => "fa-list-check",
    "pregunta"      => "fa-circle-question",
    "configuracion" => "fa-sliders",
];
?>

<header class="pnl-encabezado">
    <div>
        <h1>Auditoría</h1>
        <p>Quién cambió roles o estados, borró cuentas o contenido y tocó la configuración. Solo lectura: nadie puede editar ni borrar este registro desde la app.</p>
    </div>
</header>

<?php if (!$disponible): ?>

    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>El registro aún no está activo: falta aplicar <code>database/auditoria.sql</code> en la base de datos.</span>
    </div>

<?php else: ?>

    <form class="pnl-filtro" method="GET" action="index.php">
        <input type="hidden" name="page" value="auditoria">
        <label class="campo__etiqueta" for="filtro-accion">Acción</label>
        <select class="pnl-selector" id="filtro-accion" name="accion">
            <option value="">Todas</option>
            <?php foreach (Auditoria::ACCIONES as $clave => $texto): ?>
                <option value="<?= e($clave) ?>" <?= $filtro === $clave ? "selected" : "" ?>><?= e($texto) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="boton boton--secundario boton--sm" type="submit">Filtrar</button>
    </form>

    <section class="pnl-tarjeta" aria-labelledby="tabla-auditoria">
        <div class="pnl-tarjeta__cabecera">
            <h2 id="tabla-auditoria"><?= e(plural_panel(count($registros), "registro", "registros")) ?></h2>
            <p>Los 200 más recientes.</p>
        </div>

        <?php if (empty($registros)): ?>
            <p class="texto-suave"><?= $filtro !== "" ? "No hay registros de esa acción." : "Todavía no hay nada registrado." ?></p>
        <?php else: ?>
            <div class="pnl-tabla-caja">
                <table class="pnl-tabla">
                    <thead><tr><th>Fecha</th><th>Quién</th><th>Acción</th><th>Sobre</th><th>Cambio</th></tr></thead>
                    <tbody>
                        <?php foreach ($registros as $r):
                            // Hora de Colombia, sin depender de la zona del servidor ni de la BD
                            $f = (new DateTimeImmutable("@" . (int) $r["ts"]))->setTimezone(new DateTimeZone("America/Bogota"));
                        ?>
                            <tr>
                                <td class="col-suave pnl-tabla__fecha">
                                    <time datetime="<?= e($f->format("c")) ?>">
                                        <?= $f->format("j") . " " . $meses[(int) $f->format("n") - 1] . " " . $f->format("Y") ?><br><?= $f->format("g:i a") ?>
                                    </time>
                                </td>
                                <td>
                                    <span class="col-fuerte"><?= e($r["actor_nombre"] ?: "—") ?></span>
                                    <?php if ($r["actor_rol"] !== ""): ?><br><span class="col-suave"><?= e($r["actor_rol"]) ?> · IP <?= e($r["ip"]) ?></span><?php endif; ?>
                                </td>
                                <td><span class="chip chip--neutro"><?= e(Auditoria::ACCIONES[$r["accion"]] ?? $r["accion"]) ?></span></td>
                                <td class="pnl-tabla__celda-larga" title="<?= e($r["objeto_nombre"]) ?>">
                                    <i class="fa-solid <?= e($iconos[$r["objeto_tipo"]] ?? "fa-circle") ?> col-suave" aria-hidden="true"></i>
                                    <?= e($r["objeto_nombre"] ?: "—") ?>
                                    <?php if ($r["objeto_id"] !== null): ?><span class="col-suave">#<?= (int) $r["objeto_id"] ?></span><?php endif; ?>
                                </td>
                                <td class="col-suave">
                                    <?php if ($r["antes"] !== null && $r["despues"] !== null): ?>
                                        <?= e($r["antes"]) ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span class="sr-only">a</span> <strong><?= e($r["despues"]) ?></strong>
                                    <?php else: ?>
                                        <?= e($r["despues"] ?? $r["antes"] ?? "—") ?>
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
