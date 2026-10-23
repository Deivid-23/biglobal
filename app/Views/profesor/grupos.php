<?php
/**
 * Grupos del docente (tipo Classroom).
 * Variables: $gruposDisponibles, $grupos, $cursosDocente, $csrfToken
 */
$activos = array_values(array_filter($grupos, fn($g) => (int) $g["archivado"] === 0));
$archivados = array_values(array_filter($grupos, fn($g) => (int) $g["archivado"] === 1));

$fila = function (array $g): void { ?>
    <li class="pnl-item">
        <span class="pnl-dato__icono" aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
        <div class="pnl-item__cuerpo">
            <p class="pnl-item__titulo"><a href="index.php?page=grupo&amp;grupo=<?= (int) $g["id"] ?>"><?= e($g["nombre"]) ?></a></p>
            <p class="pnl-item__texto">
                <?= e(plural_panel((int) $g["miembros"], "estudiante", "estudiantes")) ?>
                <?php if ($g["curso"]): ?> · <?= e($g["curso"]) ?><?php endif; ?>
                <?php if ((int) $g["archivado"] === 0): ?> · Código <span class="grupo-codigo grupo-codigo--sm"><?= e($g["codigo"]) ?></span><?php endif; ?>
            </p>
        </div>
    </li>
<?php };
?>

<header class="pnl-encabezado">
    <div>
        <h1>Grupos</h1>
        <p>Crea un grupo, comparte su código y publica anuncios y material para tus estudiantes.</p>
    </div>
</header>

<?php if (!$gruposDisponibles): ?>

    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Los grupos aún no están disponibles.</span>
    </div>

<?php else: ?>

    <div class="pnl-rejilla pnl-rejilla--ancha">
        <section class="pnl-tarjeta" aria-labelledby="titulo-mis-grupos">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-mis-grupos">Tus grupos</h2></div>
            <?php if (!$activos): ?>
                <p class="texto-suave">Todavía no tienes grupos activos. Crea el primero con el formulario.</p>
            <?php else: ?>
                <ul class="pnl-lista">
                    <?php foreach ($activos as $g) { $fila($g); } ?>
                </ul>
            <?php endif; ?>

            <?php if ($archivados): ?>
                <h3 class="pnl-subtitulo">Archivados (<?= count($archivados) ?>)</h3>
                <p class="campo__ayuda">Los estudiantes no los ven. Ábrelos para restaurarlos.</p>
                <ul class="pnl-lista pnl-lista--compacta">
                    <?php foreach ($archivados as $g) { $fila($g); } ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="pnl-tarjeta" aria-labelledby="titulo-nuevo-grupo">
            <div class="pnl-tarjeta__cabecera"><h2 id="titulo-nuevo-grupo">Nuevo grupo</h2></div>
            <form class="pnl-form" method="POST" action="grupos_acciones.php">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="accion" value="crear_grupo">
                <?php $grupoForm = null; require __DIR__ . "/_form_grupo.php"; ?>
                <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Crear grupo</button></div>
            </form>
        </section>
    </div>

<?php endif; ?>
