<?php
/**
 * Campana de notificaciones de la barra superior (admin, super admin y docente).
 * Variable: $campanaPanel = ["noLeidas" => int, "lista" => [...]] (desde Panel::render)
 *
 * Sin JavaScript también funciona: cada aviso es un enlace y
 * "Marcar todas como leídas" es un formulario POST normal.
 */
$noLeidas = (int) $campanaPanel["noLeidas"];
$etiquetaCampana = $noLeidas > 0
    ? "Notificaciones: " . plural_panel($noLeidas, "sin leer", "sin leer")
    : "Notificaciones";
?>
<div class="pnl-campana" data-campana>
    <button class="pnl-icono-boton pnl-campana__boton" type="button" data-campana-boton
            aria-expanded="false" aria-controls="pnl-campana-lista" aria-label="<?= e($etiquetaCampana) ?>">
        <i class="fa-solid fa-bell" aria-hidden="true"></i>
        <span class="pnl-campana__contador numeros" data-campana-contador <?= $noLeidas > 0 ? "" : "hidden" ?>>
            <?= $noLeidas > 9 ? "9+" : $noLeidas ?>
        </span>
    </button>

    <div class="pnl-campana__panel" id="pnl-campana-lista" data-campana-panel hidden>
        <div class="pnl-campana__cabecera">
            <p class="pnl-campana__titulo">Notificaciones</p>
            <form method="POST" action="../auth/notificaciones_leer.php" data-campana-todas <?= $noLeidas > 0 ? "" : "hidden" ?>>
                <?= campoCsrf() ?>
                <input type="hidden" name="volver" value="<?= e($_SERVER["REQUEST_URI"] ?? "") ?>">
                <button class="pnl-campana__marcar" type="submit">Marcar todas como leídas</button>
            </form>
        </div>

        <?php if (empty($campanaPanel["lista"])): ?>
            <div class="pnl-campana__vacio">
                <i class="fa-regular fa-bell" aria-hidden="true"></i>
                <p>Todavía no hay avisos.</p>
                <small><?= in_array(rolActual(), ["admin", "superadmin"], true)
                    ? "Aquí verás los registros nuevos, los docentes por aprobar y los cursos creados."
                    : "Aquí verás la revisión de tus cursos y lecciones y otros avisos." ?></small>
            </div>
        <?php else: ?>
            <ul class="pnl-campana__lista">
                <?php foreach ($campanaPanel["lista"] as $aviso):
                    $sinLeer = $aviso["leida_en"] === null;
                    $icono = Notificador::ICONOS[$aviso["tipo"]] ?? "fa-bell";
                    $enlace = $aviso["enlace"] ? "../" . ltrim($aviso["enlace"], "/") : "#";
                ?>
                    <li>
                        <a class="pnl-aviso pnl-aviso--<?= e($aviso["tipo"]) ?> <?= $sinLeer ? "es-nueva" : "" ?>" href="<?= e($enlace) ?>"
                           data-aviso-id="<?= (int) $aviso["id"] ?>">
                            <span class="pnl-aviso__icono pnl-aviso__icono--<?= e($aviso["tipo"]) ?>" aria-hidden="true">
                                <i class="fa-solid <?= e($icono) ?>"></i>
                            </span>
                            <span class="pnl-aviso__texto">
                                <strong><?= e($aviso["titulo"]) ?></strong>
                                <?php if ($aviso["mensaje"] !== ""): ?>
                                    <span><?= e($aviso["mensaje"]) ?></span>
                                <?php endif; ?>
                                <time datetime="<?= e(date("c", strtotime($aviso["creada_en"]))) ?>"><?= e(hace_panel($aviso["creada_en"])) ?></time>
                            </span>
                            <?php if ($sinLeer): ?>
                                <span class="pnl-aviso__punto"><span class="sr-only">Sin leer</span></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
