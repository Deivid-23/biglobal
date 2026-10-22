<?php
/**
 * Notificaciones del estudiante (la campana).
 * Variables: $avisos (las últimas 50; las que tienen leida_en = null eran nuevas)
 * Al abrir esta página quedan todas como leídas.
 */
?>
<header class="encabezado-pagina">
    <h1>Notificaciones</h1>
    <p>Pagos, clases en vivo, invitaciones y otros avisos de tu cuenta.
        <a href="index.php?page=perfil#titulo-correos">Elige qué te llega por correo</a>.</p>
</header>

<?php if (!$avisos): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-regular fa-bell"></i></span>
        <p>Todavía no tienes notificaciones.</p>
    </div>
<?php else: ?>
    <ul class="avisos">
        <?php foreach ($avisos as $a):
            $nuevo = $a["leida_en"] === null;
            $icono = Notificador::ICONOS[$a["tipo"]] ?? "fa-bell";
            $enlace = $a["enlace"] ? "../" . ltrim($a["enlace"], "/") : null;
        ?>
            <li class="aviso-item<?= $nuevo ? " aviso-item--nuevo" : "" ?>">
                <span class="aviso-item__icono" aria-hidden="true"><i class="fa-solid <?= e($icono) ?>"></i></span>
                <div class="aviso-item__cuerpo">
                    <p class="aviso-item__titulo">
                        <?php if ($enlace): ?><a href="<?= e($enlace) ?>"><?= e($a["titulo"]) ?></a><?php else: ?><?= e($a["titulo"]) ?><?php endif; ?>
                        <?php if ($nuevo): ?><span class="chip chip--exito">Nuevo</span><?php endif; ?>
                    </p>
                    <?php if ($a["mensaje"] !== ""): ?><p class="aviso-item__texto"><?= e($a["mensaje"]) ?></p><?php endif; ?>
                    <time class="aviso-item__fecha" datetime="<?= e(date("c", strtotime($a["creada_en"]))) ?>"><?= e(hace_panel($a["creada_en"])) ?></time>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
