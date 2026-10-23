<?php
/**
 * Inicio del estudiante.
 * Variables: $estudiante, $leccionesCompletadas, $totalCertificados,
 *            $cursosEnProgreso, $misCursos, $continuar, $sugeridos,
 *            $gamificacion (racha, XP, nivel y medallas; null si no hay tablas),
 *            $ruta (idioma y siguiente lección de la ruta; null si no hay ruta)
 */
$esNuevo = $leccionesCompletadas === 0;
?>

<section class="saludo" aria-labelledby="titulo-saludo">
    <div class="saludo__texto">
        <h1 id="titulo-saludo">
            <?= $esNuevo ? "¡Bienvenido, " . e($estudiante["primerNombre"]) . "!" : "¡Hola de nuevo, " . e($estudiante["primerNombre"]) . "!" ?>
        </h1>
        <p>
            <?php if ($esNuevo): ?>
                Elige un curso y completa tu primera lección. Toma solo unos minutos.
            <?php elseif ($continuar): ?>
                Llevas <?= e(plural($leccionesCompletadas, "lección completada", "lecciones completadas")) ?>. ¡Sigue así!
            <?php else: ?>
                Terminaste todo lo que empezaste. ¿Qué tal un curso nuevo?
            <?php endif; ?>
        </p>
    </div>

    <?php if ($continuar): ?>
        <?php
            $pctContinuar = porcentaje((int) $continuar["lecciones_hechas"], (int) $continuar["total_lecciones"]);
            $idioma = idiomaDeCurso($continuar["nombre"], $continuar["idioma_codigo"] ?? null);
        ?>
        <div class="continuar">
            <span class="idioma idioma--grande <?= e($idioma["clase"]) ?>" aria-hidden="true"><?= e($idioma["codigo"]) ?></span>
            <div class="continuar__cuerpo">
                <p class="continuar__curso"><?= e($continuar["nombre"]) ?></p>
                <p class="continuar__leccion">
                    <?= $continuar["siguiente"] ? "Sigue: " . e($continuar["siguiente"]["titulo"]) : "Revisa tus lecciones" ?>
                </p>
                <div class="curso__avance">
                    <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                         aria-valuenow="<?= $pctContinuar ?>" aria-label="Avance del curso">
                        <div class="progreso__relleno" style="width: <?= $pctContinuar ?>%"></div>
                    </div>
                    <span class="numeros"><?= $pctContinuar ?>%</span>
                </div>
            </div>
            <a class="boton boton--primario"
               href="<?= $continuar["siguiente"]
                    ? "index.php?page=leccion&amp;id=" . (int) $continuar["siguiente"]["id"]
                    : "index.php?page=curso&amp;id=" . (int) $continuar["id"] ?>">
                Continuar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>
    <?php else: ?>
        <a class="boton boton--primario" href="index.php?page=cursos">
            <?= $esNuevo ? "Elegir mi primer curso" : "Explorar cursos" ?>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
</section>

<?php if ($ruta):
    $idiomaRuta = idiomaDeCurso($ruta["idioma"]["nombre"], $ruta["idioma"]["codigo"]);
    $urlRuta = "index.php?page=ruta&amp;idioma=" . e(rawurlencode($ruta["idioma"]["codigo"]));
?>
    <section class="ruta-inicio" aria-labelledby="titulo-ruta-inicio">
        <span class="idioma idioma--grande <?= e($idiomaRuta["clase"]) ?>" aria-hidden="true"><?= e($idiomaRuta["codigo"]) ?></span>
        <div class="ruta-inicio__cuerpo">
            <h2 id="titulo-ruta-inicio"><?= $ruta["empezo"] ? "Continúa tu ruta" : "Empieza tu ruta" ?> · <?= e($ruta["idioma"]["nombre"]) ?></h2>
            <p><?= $ruta["siguiente"]
                ? e($ruta["siguiente"]["nivel"]) . " · " . e($ruta["siguiente"]["unidad"]) . ": " . e($ruta["siguiente"]["titulo"])
                : "Lecciones cortas con ejercicios para avanzar paso a paso." ?></p>
        </div>
        <a class="boton boton--primario" href="<?= $ruta["siguiente"] ? "index.php?page=ruta-leccion&amp;id=" . (int) $ruta["siguiente"]["id"] : $urlRuta ?>">
            <?= $ruta["siguiente"] ? "Seguir" : "Ver la ruta" ?> <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </section>
<?php endif; ?>

<?php if ($gamificacion) { require __DIR__ . "/_gamificacion.php"; } ?>

<?php if (!$esNuevo): ?>
    <section class="resumen" aria-label="Tu resumen">
        <div class="resumen__dato">
            <i class="fa-solid fa-circle-check resumen__icono resumen__icono--verde" aria-hidden="true"></i>
            <span><strong class="numeros"><?= $leccionesCompletadas ?></strong> <?= $leccionesCompletadas === 1 ? "lección completada" : "lecciones completadas" ?></span>
        </div>
        <div class="resumen__dato">
            <i class="fa-solid fa-book-open resumen__icono" aria-hidden="true"></i>
            <span><strong class="numeros"><?= $cursosEnProgreso ?></strong> <?= $cursosEnProgreso === 1 ? "curso en progreso" : "cursos en progreso" ?></span>
        </div>
        <div class="resumen__dato">
            <i class="fa-solid fa-award resumen__icono resumen__icono--naranja" aria-hidden="true"></i>
            <span><strong class="numeros"><?= $totalCertificados ?></strong> <?= $totalCertificados === 1 ? "certificado" : "certificados" ?></span>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($misCursos)): ?>
    <section class="bloque" aria-labelledby="titulo-mis-cursos">
        <div class="bloque__cabecera">
            <h2 id="titulo-mis-cursos">Tus cursos</h2>
            <a class="enlace-ver" href="index.php?page=mis-cursos">Ver todos</a>
        </div>
        <div class="rejilla-cursos">
            <?php foreach ($misCursos as $curso) { require __DIR__ . "/_tarjeta_curso.php"; } ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($sugeridos)): ?>
    <section class="bloque" aria-labelledby="titulo-sugeridos">
        <div class="bloque__cabecera">
            <h2 id="titulo-sugeridos">Cursos para empezar</h2>
            <a class="enlace-ver" href="index.php?page=cursos">Ver catálogo</a>
        </div>
        <div class="rejilla-cursos">
            <?php $mostrarDescripcion = true; foreach ($sugeridos as $curso) { require __DIR__ . "/_tarjeta_curso.php"; } ?>
        </div>
    </section>
<?php elseif ($esNuevo): ?>
    <div class="vacio">
        <span class="vacio__icono"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
        <p class="vacio__titulo">Muy pronto habrá cursos aquí</p>
        <p class="vacio__texto">Nuestros docentes están preparando las lecciones. Vuelve en unos días.</p>
    </div>
<?php endif; ?>

<?php if ($gamificacion) { require __DIR__ . "/_medallas.php"; } ?>
