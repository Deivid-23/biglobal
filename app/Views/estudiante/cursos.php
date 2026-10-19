<?php
/** Catálogo de cursos publicados. Variables: $cursos, $idiomas (para filtrar), $filtro (código o null) */
$idiomas = $idiomas ?? [];
$filtro  = $filtro ?? null;
?>
<header class="encabezado-pagina">
    <h1>Explorar cursos</h1>
    <p><?= count($cursos) > 0 ? e(plural(count($cursos), "curso disponible", "cursos disponibles")) . ". Elige uno y empieza cuando quieras." : "Aún no hay cursos publicados." ?></p>
</header>

<?php if (count($idiomas) > 1 || $filtro !== null): ?>
    <nav class="filtros-idioma" aria-label="Filtrar por idioma">
        <a class="filtro-idioma" href="index.php?page=cursos" <?= $filtro === null ? 'aria-current="page"' : "" ?>>Todos</a>
        <?php foreach ($idiomas as $idi): $clase = idiomaDeCurso("", $idi["codigo"])["clase"]; ?>
            <a class="filtro-idioma" href="index.php?page=cursos&amp;idioma=<?= e(urlencode($idi["codigo"])) ?>"
               <?= $filtro === $idi["codigo"] ? 'aria-current="page"' : "" ?>>
                <span class="idioma idioma--mini <?= e($clase) ?>" aria-hidden="true"><?= e($idi["codigo"]) ?></span>
                <?= e($idi["nombre"]) ?> <span class="filtro-idioma__total numeros">(<?= (int) $idi["total"] ?>)</span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<?php if (empty($cursos) && $filtro !== null): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-filter" aria-hidden="true"></i></span>
        <p class="vacio__titulo">No hay cursos de ese idioma</p>
        <p class="vacio__texto"><a href="index.php?page=cursos">Ver todos los cursos</a></p>
    </div>
<?php elseif (empty($cursos)): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
        <p class="vacio__titulo">Todavía no hay cursos</p>
        <p class="vacio__texto">Cuando un docente publique un curso aparecerá aquí.</p>
    </div>
<?php else: ?>
    <div class="rejilla-cursos">
        <?php $mostrarDescripcion = true; foreach ($cursos as $curso) { require __DIR__ . "/_tarjeta_curso.php"; } ?>
    </div>
<?php endif; ?>
