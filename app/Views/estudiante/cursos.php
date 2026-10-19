<?php
/** Catálogo de cursos publicados. Variables: $cursos */
?>
<header class="encabezado-pagina">
    <h1>Explorar cursos</h1>
    <p><?= count($cursos) > 0 ? e(plural(count($cursos), "curso disponible", "cursos disponibles")) . ". Elige uno y empieza cuando quieras." : "Aún no hay cursos publicados." ?></p>
</header>

<?php if (empty($cursos)): ?>
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
