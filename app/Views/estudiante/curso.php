<?php
/**
 * Detalle de un curso con su lista de lecciones.
 * Variables: $curso, $lecciones, $totalLecciones, $leccionesHechas,
 *            $porcentaje, $siguiente, $certificado
 */
$idioma = idiomaDeCurso($curso["nombre"], $curso["idioma_codigo"] ?? null);
$idSiguiente = $siguiente ? (int) $siguiente["id"] : 0;
?>

<a class="enlace-atras" href="index.php?page=cursos">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Cursos
</a>

<section class="curso-cabecera">
    <span class="idioma idioma--grande <?= e($idioma["clase"]) ?>" aria-hidden="true"><?= e($idioma["codigo"]) ?></span>
    <div class="curso-cabecera__texto">
        <h1><?= e($curso["nombre"]) ?></h1>
        <p class="curso__meta"><?= e($curso["nivel"] ?: "Sin nivel") ?> · <?= e(plural($totalLecciones, "lección", "lecciones")) ?></p>
        <?php if (!empty($curso["descripcion"])): ?>
            <p class="curso-cabecera__descripcion"><?= e($curso["descripcion"]) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($totalLecciones > 0): ?>
        <div class="curso-cabecera__avance">
            <div class="curso__avance">
                <div class="progreso" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                     aria-valuenow="<?= $porcentaje ?>" aria-label="Avance del curso">
                    <div class="progreso__relleno" data-valor="<?= $porcentaje ?>" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <span class="numeros"><?= $leccionesHechas ?>/<?= $totalLecciones ?></span>
            </div>
            <?php if ($siguiente): ?>
                <a class="boton boton--primario" href="index.php?page=leccion&amp;id=<?= $idSiguiente ?>">
                    <?= $leccionesHechas > 0 ? "Continuar" : "Empezar curso" ?>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($certificado): ?>
    <div class="aviso aviso--exito logro" role="status">
        <i class="fa-solid fa-trophy" aria-hidden="true"></i>
        <span>¡Completaste este curso! Tu certificado <strong><?= e($certificado["codigo"]) ?></strong> está en
            <a href="index.php?page=certificados">Certificados</a>.</span>
    </div>
<?php endif; ?>

<section class="bloque" aria-labelledby="titulo-lecciones">
    <div class="bloque__cabecera">
        <h2 id="titulo-lecciones">Lecciones</h2>
    </div>

    <?php if (empty($lecciones)): ?>
        <div class="vacio">
            <span class="vacio__icono"><i class="fa-solid fa-pen-ruler" aria-hidden="true"></i></span>
            <p class="vacio__titulo">Lecciones en preparación</p>
            <p class="vacio__texto">El docente todavía no publica lecciones en este curso.</p>
        </div>
    <?php else: ?>
        <ol class="ruta">
            <?php foreach ($lecciones as $i => $l):
                $hecha = (int) $l["completado"] === 1;
                $esSiguiente = (int) $l["id"] === $idSiguiente;
                $bloqueada = !empty($l["bloqueada"]);
                $estado = $hecha ? "hecha" : ($esSiguiente ? "siguiente" : "bloqueada");
                $textoActividades = (int) $l["total_actividades"] > 0
                    ? " · " . plural((int) $l["total_actividades"], "actividad", "actividades") : "";
            ?>
                <li class="ruta__paso ruta__paso--<?= $estado ?>">
                    <span class="ruta__marca" aria-hidden="true">
                        <?php if ($hecha): ?><i class="fa-solid fa-check"></i>
                        <?php elseif ($bloqueada): ?><i class="fa-solid fa-lock"></i>
                        <?php else: ?><?= $i + 1 ?><?php endif; ?>
                    </span>

                    <?php if ($bloqueada): ?>
                        <!-- Bloqueada: no es enlace, se abre al completar la anterior -->
                        <div class="ruta__enlace" aria-disabled="true">
                            <span class="ruta__titulo"><?= e($l["titulo"]) ?></span>
                            <span class="ruta__meta">Bloqueada · completa la lección anterior<?= e($textoActividades) ?></span>
                        </div>
                    <?php else: ?>
                        <a class="ruta__enlace" href="index.php?page=leccion&amp;id=<?= (int) $l["id"] ?>">
                            <span class="ruta__titulo"><?= e($l["titulo"]) ?></span>
                            <span class="ruta__meta"><?= $hecha ? "Completada" : "Tu siguiente lección" ?><?= e($textoActividades) ?></span>
                        </a>
                        <i class="fa-solid fa-chevron-right ruta__flecha" aria-hidden="true"></i>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
