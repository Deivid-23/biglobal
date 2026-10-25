<?php
/**
 * Tabla de posiciones: XP de la semana (PosicionesModel).
 * Variables: $disponible, $grupos (con acceso), $grupoActivo (o null = todos),
 *            $tabla (filas, yo, total), $visible (si aparece para los demás)
 */
$medallas = [1 => "🥇", 2 => "🥈", 3 => "🥉"];
?>
<header class="encabezado-pagina">
    <h1>Posiciones</h1>
    <p>El XP que cada quien ganó esta semana (de lunes a domingo). Se reinicia cada lunes: cualquiera puede llegar arriba.</p>
</header>

<?php if (!$disponible): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>
        <p class="vacio__titulo">La tabla de posiciones está en preparación</p>
    </div>
<?php return; endif; ?>

<nav class="grupo-pestanas" aria-label="Tabla">
    <a class="grupo-pestana <?= $grupoActivo === null ? "es-activa" : "" ?>" href="index.php?page=posiciones"
       <?= $grupoActivo === null ? 'aria-current="page"' : "" ?>>Todos</a>
    <?php foreach ($grupos as $g): $activo = $grupoActivo && (int) $grupoActivo["id"] === (int) $g["id"]; ?>
        <a class="grupo-pestana <?= $activo ? "es-activa" : "" ?>" href="index.php?page=posiciones&amp;grupo=<?= (int) $g["id"] ?>"
           <?= $activo ? 'aria-current="page"' : "" ?>><?= e($g["nombre"]) ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!$visible): ?>
    <p class="posiciones-nota"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
        Estás oculto: los demás no te ven en la tabla. <a href="index.php?page=perfil#privacidad">Cambiarlo en Mi perfil</a>.</p>
<?php endif; ?>

<?php if (!$tabla["filas"]): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-trophy"></i></span>
        <p class="vacio__titulo">Todavía nadie suma XP esta semana</p>
        <p class="vacio__texto">¡Haz una lección y estrena la tabla!</p>
        <a class="boton boton--primario" href="index.php?page=ruta">Ir a mi ruta</a>
    </div>
<?php else: ?>
    <ol class="posiciones" aria-label="<?= $grupoActivo ? "Posiciones del grupo " . e($grupoActivo["nombre"]) : "Posiciones de todos" ?>">
        <?php foreach ($tabla["filas"] as $f): ?>
            <li class="posicion <?= $f["soyYo"] ? "posicion--yo" : "" ?> <?= $f["puesto"] <= 3 ? "posicion--podio" : "" ?>">
                <span class="posicion__puesto numeros" aria-label="Puesto <?= (int) $f["puesto"] ?>">
                    <?= isset($medallas[$f["puesto"]]) ? $medallas[$f["puesto"]] : (int) $f["puesto"] ?>
                </span>
                <?= FotoPerfil::avatar($f["id"], $f["iniciales"], "est-avatar posicion__avatar") ?>
                <span class="posicion__nombre"><?= e($f["nombre"]) ?><?php if ($f["soyYo"]): ?> <span class="chip chip--neutro">Tú</span><?php endif; ?></span>
                <span class="posicion__xp numeros"><?= (int) $f["xp"] ?> XP</span>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if ($tabla["yo"] && $tabla["yo"]["puesto"] > PosicionesModel::LIMITE): ?>
        <p class="posiciones-yo">Tú: puesto <strong><?= (int) $tabla["yo"]["puesto"] ?></strong> de <?= (int) $tabla["total"] ?> · <?= (int) $tabla["yo"]["xp"] ?> XP</p>
    <?php elseif (!$tabla["yo"]): ?>
        <p class="posiciones-yo">Todavía no sumas XP esta semana. <a href="index.php?page=ruta">Haz una lección</a> para entrar a la tabla.</p>
    <?php endif; ?>
<?php endif; ?>
