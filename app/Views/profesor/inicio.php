<?php
/** Inicio del docente. Variables: $docente, $totalCursos, $totalLecciones, $totalActividades, $labelsLecciones, $valoresLecciones, $cursosDocente */
?>

<section class="pnl-bienvenida" aria-labelledby="bienvenida-titulo">
    <div>
        <h1 id="bienvenida-titulo">Hola, <?= e($docente["nombre"] ?? "docente") ?></h1>
        <p>Crea tus cursos, organiza las lecciones y agrega actividades para tus estudiantes.</p>
    </div>
    <div class="pnl-bienvenida__acciones">
        <a class="boton boton--primario" href="index.php?page=cursos"><i class="fa-solid fa-plus" aria-hidden="true"></i> Crear curso</a>
    </div>
</section>

<section class="pnl-datos" aria-label="Tu resumen">
    <a class="pnl-dato" href="index.php?page=cursos">
        <span class="pnl-dato__icono"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $totalCursos ?></span><br><span class="pnl-dato__texto">Cursos creados</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=lecciones">
        <span class="pnl-dato__icono pnl-dato__icono--verde"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $totalLecciones ?></span><br><span class="pnl-dato__texto">Lecciones</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=actividades">
        <span class="pnl-dato__icono pnl-dato__icono--naranja"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $totalActividades ?></span><br><span class="pnl-dato__texto">Actividades</span></span>
    </a>
</section>

<?php if (empty($cursosDocente)): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
        <p class="vacio__titulo">Crea tu primer curso</p>
        <p class="vacio__texto">Ponle un nombre y un nivel. Después podrás agregarle lecciones y actividades.</p>
        <a class="boton boton--primario" href="index.php?page=cursos">Crear curso</a>
    </div>
<?php else: ?>
    <?= Panel::grafico("grafico-lecciones-docente", "Lecciones por curso", "Tus cursos con más contenido",
        $labelsLecciones, $valoresLecciones, "bar") ?>
<?php endif; ?>
