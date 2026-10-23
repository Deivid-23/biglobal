<?php
/**
 * Página Insignias: todas las insignias del estudiante, por categoría.
 * Variables: $disponible, $todas (InsigniaModel::delEstudiante), $lista (filtrada), $ver = todas | ganadas | pendientes
 */
$ganadas = count(array_filter($todas, fn($m) => $m["fecha_ganada"] !== null));
$total = count($todas);
$pct = porcentaje($ganadas, $total);
$filtros = ["todas" => "Todas", "ganadas" => "Ganadas", "pendientes" => "Por ganar"];
?>
<header class="encabezado-pagina">
    <h1>Insignias</h1>
    <p>Las ganas solo con avanzar: terminando lecciones, manteniendo tu racha, entregando tareas y participando en tus grupos.</p>
</header>

<?php if (!$disponible || !$todas): ?>
    <div class="vacio">
        <span class="vacio__icono vacio__icono--logro" aria-hidden="true"><i class="fa-solid fa-medal"></i></span>
        <p class="vacio__titulo">Las insignias están en preparación</p>
        <p class="vacio__texto">Muy pronto podrás ganarlas mientras aprendes.</p>
    </div>
<?php return; endif; ?>

<section class="insignias-resumen" aria-label="Tu avance">
    <span class="insignias-resumen__icono" aria-hidden="true"><i class="fa-solid fa-medal"></i></span>
    <div class="insignias-resumen__cuerpo">
        <p class="insignias-resumen__numero"><strong class="numeros"><?= $ganadas ?></strong> de <?= $total ?> insignias ganadas</p>
        <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>" aria-label="Insignias ganadas">
            <div class="progreso__relleno" data-valor="<?= $pct ?>" style="width: <?= $pct ?>%"></div>
        </div>
    </div>
</section>

<nav class="grupo-pestanas insignias-filtros" aria-label="Filtrar insignias">
    <?php foreach ($filtros as $clave => $texto): $activo = $ver === $clave; ?>
        <a class="grupo-pestana <?= $activo ? "es-activa" : "" ?>" href="index.php?page=insignias<?= $clave !== "todas" ? "&amp;ver=" . $clave : "" ?>"
           <?= $activo ? 'aria-current="page"' : "" ?>><?= e($texto) ?></a>
    <?php endforeach; ?>
</nav>

<?php if (!$lista): ?>
    <div class="vacio">
        <span class="vacio__icono" aria-hidden="true"><i class="fa-solid <?= $ver === "ganadas" ? "fa-seedling" : "fa-trophy" ?>"></i></span>
        <p class="vacio__titulo"><?= $ver === "ganadas" ? "Todavía no tienes insignias" : "¡Las ganaste todas!" ?></p>
        <p class="vacio__texto"><?= $ver === "ganadas"
            ? "Completa tu primera lección de la ruta o de un curso y ganarás la primera."
            : "No te queda ninguna por ganar. ¡Felicitaciones!" ?></p>
    </div>
<?php else: ?>
    <?php $listaInsignias = $lista; $modoInsignias = "pagina"; require __DIR__ . "/_insignias.php"; ?>
<?php endif; ?>
