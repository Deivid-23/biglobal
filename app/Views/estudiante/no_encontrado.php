<?php /** Curso o lección que no existe / no está publicado. Variables: $mensaje */ ?>
<div class="vacio">
    <span class="vacio__icono"><i class="fa-solid fa-map-signs" aria-hidden="true"></i></span>
    <p class="vacio__titulo">No encontramos esa página</p>
    <p class="vacio__texto"><?= e($mensaje) ?></p>
    <a class="boton boton--primario" href="index.php?page=cursos">Ver cursos disponibles</a>
</div>
