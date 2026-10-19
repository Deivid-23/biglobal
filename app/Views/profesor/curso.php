<?php
/** Detalle de un curso del docente. Variables: $cursoActual, $leccionesCurso, $actividadesCurso */
if (!$cursoActual): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
        <p class="vacio__titulo">Elige un curso</p>
        <p class="vacio__texto">Abre uno de tus cursos para administrar sus lecciones y actividades.</p>
        <a class="boton boton--primario" href="index.php?page=cursos">Ver mis cursos</a>
    </div>
<?php return; endif; ?>

<header class="pnl-encabezado">
    <div>
        <a href="index.php?page=cursos"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Mis cursos</a>
        <h1 style="margin-top: var(--esp-2);"><?= e($cursoActual["nombre"]) ?></h1>
        <p><?= e($cursoActual["descripcion"] ?: "Sin descripción.") ?></p>
    </div>
    <?= (int) ($cursoActual["estado"] ?? 0) === 1 ? '<span class="chip chip--exito">Publicado</span>' : '<span class="chip chip--neutro">Borrador</span>' ?>
</header>

<section class="pnl-datos" aria-label="Resumen del curso">
    <div class="pnl-dato">
        <span class="pnl-dato__icono"><i class="fa-solid fa-signal" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero" style="font-size: var(--texto-lg);"><?= e($cursoActual["nivel"] ?: "—") ?></span><br><span class="pnl-dato__texto">Nivel</span></span>
    </div>
    <a class="pnl-dato" href="index.php?page=lecciones&amp;id=<?= (int) $cursoActual["id"] ?>">
        <span class="pnl-dato__icono pnl-dato__icono--verde"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= count($leccionesCurso) ?></span><br><span class="pnl-dato__texto">Lecciones · administrar</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=actividades&amp;id=<?= (int) $cursoActual["id"] ?>">
        <span class="pnl-dato__icono pnl-dato__icono--naranja"><i class="fa-solid fa-list-check" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= count($actividadesCurso) ?></span><br><span class="pnl-dato__texto">Actividades · administrar</span></span>
    </a>
</section>
