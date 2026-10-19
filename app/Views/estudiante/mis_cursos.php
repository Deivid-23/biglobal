<?php
/** Cursos que el estudiante ya empezó. Variables: $cursos */
?>
<header class="encabezado-pagina">
    <h1>Mis cursos</h1>
    <p>Los cursos en los que ya completaste al menos una lección.</p>
</header>

<?php if (empty($cursos)): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
        <p class="vacio__titulo">Aún no empiezas ningún curso</p>
        <p class="vacio__texto">Elige uno del catálogo y completa tu primera lección: aparecerá aquí con tu avance.</p>
        <a class="boton boton--primario" href="index.php?page=cursos">Explorar cursos</a>
    </div>
<?php else: ?>
    <div class="rejilla-cursos">
        <?php foreach ($cursos as $curso) { require __DIR__ . "/_tarjeta_curso.php"; } ?>
    </div>
<?php endif; ?>
