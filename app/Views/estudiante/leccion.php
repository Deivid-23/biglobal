<?php
/**
 * Una lección: contenido escrito por el docente + actividades.
 * Variables: $leccion, $actividades,
 *            $quiz       [actividad_id => preguntas, aprobada, mejor] (solo las que tienen preguntas),
 *            $pendientes cuántas de esas faltan por aprobar,
 *            $resultado  lo que acaba de enviar (o null)
 *
 * Las actividades con preguntas se resuelven aquí mismo; se califican
 * en el servidor (responder_actividad.php). Las demás solo se leen.
 */
$hecha = (int) $leccion["completado"] === 1;

$iconosTipo = [
    "quiz"      => "fa-circle-question",
    "examen"    => "fa-file-circle-check",
    "tarea"     => "fa-pen-to-square",
    "taller"    => "fa-people-group",
    "ejercicio" => "fa-dumbbell",
];

$primeraPendiente = null;
foreach ($quiz as $idQuiz => $q) {
    if (!$q["aprobada"]) { $primeraPendiente = $idQuiz; break; }
}
?>

<a class="enlace-atras" href="index.php?page=curso&amp;id=<?= (int) $leccion["curso_id"] ?>">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <?= e($leccion["curso_nombre"]) ?>
</a>

<article class="leccion">
    <header class="leccion__cabecera">
        <p class="curso__meta">Lección <?= (int) $leccion["posicion"] ?></p>
        <h1><?= e($leccion["titulo"]) ?></h1>
        <?php if (!empty($leccion["descripcion"])): ?>
            <p class="leccion__resumen"><?= e($leccion["descripcion"]) ?></p>
        <?php endif; ?>
    </header>

    <?php
    // Video de YouTube o Vimeo (la dirección la arma Video::reproductor, nunca el texto guardado)
    $video = Video::reproductor($leccion["video_url"] ?? null);
    if ($video): ?>
        <figure class="leccion__video">
            <div class="leccion__video-marco">
                <iframe src="<?= e($video["src"]) ?>"
                        title="Video de la lección: <?= e($leccion["titulo"]) ?>"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                        allowfullscreen></iframe>
            </div>
            <figcaption class="texto-suave">
                <i class="fa-solid fa-circle-play" aria-hidden="true"></i>
                Video en <?= e($video["proveedor"]) ?>. Puedes verlo en pantalla completa.
            </figcaption>
        </figure>
    <?php endif; ?>

    <div class="leccion__contenido">
        <?php if (trim((string) $leccion["contenido"]) !== ""): ?>
            <?= nl2br(e($leccion["contenido"])) ?>
        <?php else: ?>
            <p class="texto-suave">El docente aún no agregó el contenido escrito de esta lección.</p>
        <?php endif; ?>
    </div>

    <?php if (!empty($actividades)): ?>
        <section class="actividades" aria-labelledby="titulo-actividades">
            <h2 id="titulo-actividades">Actividades</h2>
            <ul class="actividades__lista">
                <?php foreach ($actividades as $a):
                    $tipo = mb_strtolower(trim((string) $a["tipo"]));
                    $idA = (int) $a["id"];
                    $q = $quiz[$idA] ?? null;
                    $res = ($resultado && (int) $resultado["actividad_id"] === $idA) ? $resultado : null;
                ?>
                    <li class="actividad<?= $q ? " actividad--quiz" : "" ?>" id="actividad-<?= $idA ?>">
                        <i class="fa-solid <?= e($iconosTipo[$tipo] ?? "fa-list-check") ?>" aria-hidden="true"></i>
                        <div>
                            <p class="actividad__titulo">
                                <?= e($a["titulo"]) ?>
                                <?php if (!empty($a["tipo"])): ?><span class="chip chip--neutro"><?= e($a["tipo"]) ?></span><?php endif; ?>
                                <?php if ($q && $q["aprobada"]): ?>
                                    <span class="chip chip--exito"><i class="fa-solid fa-check" aria-hidden="true"></i> Aprobada</span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($a["descripcion"])): ?>
                                <p class="actividad__texto"><?= nl2br(e($a["descripcion"])) ?></p>
                            <?php endif; ?>

                            <?php if ($q):
                                $totalQ = count($q["preguntas"]);
                            ?>
                                <p class="quiz__meta">
                                    <?= $totalQ ?> <?= $totalQ === 1 ? "pregunta" : "preguntas" ?> ·
                                    apruebas con <?= QuizModel::PORCENTAJE_APROBAR ?>&nbsp;%
                                    <?php if ($q["mejor"]): ?>
                                        · tu mejor resultado: <?= (int) $q["mejor"][0] ?> de <?= (int) $q["mejor"][1] ?>
                                    <?php endif; ?>
                                </p>

                                <?php if ($res): ?>
                                    <div class="quiz__resultado quiz__resultado--<?= $res["aprobado"] ? "bien" : "repasar" ?>" role="status" tabindex="-1" data-quiz-resultado>
                                        <i class="fa-solid <?= $res["aprobado"] ? "fa-circle-check" : "fa-rotate-right" ?>" aria-hidden="true"></i>
                                        <div>
                                            <p class="quiz__resultado-titulo">
                                                <?= $res["aprobado"] ? "¡Aprobaste!" : "Todavía no: repasa y vuelve a intentarlo" ?>
                                            </p>
                                            <p>
                                                Acertaste <?= (int) $res["aciertos"] ?> de <?= (int) $res["total"] ?>.
                                                <?php if ($res["xp"] > 0): ?><strong>+<?= (int) $res["xp"] ?> XP.</strong><?php endif; ?>
                                                <?php if (!empty($res["medallas"])): ?>
                                                    <?= e(trim(InsigniaModel::texto($res["medallas"]))) ?>
                                                <?php endif; ?>
                                                <?php if (!$res["aprobado"]): ?>
                                                    Necesitas el <?= QuizModel::PORCENTAJE_APROBAR ?>&nbsp;%. Cambia las respuestas marcadas en rojo.
                                                <?php elseif (!$hecha && $pendientes === 0): ?>
                                                    Ya puedes terminar la lección.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Si ya la aprobó (y no acaba de enviarla), el formulario queda plegado para practicar
                                $plegado = $q["aprobada"] && !$res;
                                if ($plegado): ?>
                                    <details class="quiz__practicar">
                                        <summary>Practicar otra vez</summary>
                                <?php endif; ?>

                                <form class="quiz" method="POST" action="responder_actividad.php">
                                    <?= campoCsrf() ?>
                                    <input type="hidden" name="actividad_id" value="<?= $idA ?>">

                                    <ol class="quiz__preguntas">
                                        <?php foreach ($q["preguntas"] as $n => $p):
                                            $det = $res["detalle"][$p["id"]] ?? null;
                                            $claseP = $det ? ($det["bien"] ? " quiz__pregunta--bien" : " quiz__pregunta--mal") : "";
                                        ?>
                                            <li>
                                                <fieldset class="quiz__pregunta<?= $claseP ?>">
                                                    <legend>
                                                        <span class="quiz__numero"><?= $n + 1 ?>.</span>
                                                        <?= nl2br(e($p["enunciado"])) ?>
                                                        <?php if ($det): ?>
                                                            <span class="sr-only"><?= $det["bien"] ? "(respondiste bien)" : "(respuesta incorrecta)" ?></span>
                                                        <?php endif; ?>
                                                    </legend>
                                                    <?php foreach ($p["opciones"] as $o):
                                                        $idO = (int) $o["id"];
                                                        $elegida = $det && $det["elegida"] === $idO;
                                                        // La respuesta correcta solo se muestra cuando ya aprobó
                                                        $mostrarCorrecta = $det && $res["aprobado"] && !$det["bien"] && $det["correcta"] === $idO;
                                                        $claseO = $elegida ? ($det["bien"] ? " quiz__opcion--bien" : " quiz__opcion--mal") : ($mostrarCorrecta ? " quiz__opcion--correcta" : "");
                                                    ?>
                                                        <label class="quiz__opcion<?= $claseO ?>">
                                                            <input type="radio" name="respuestas[<?= (int) $p["id"] ?>]" value="<?= $idO ?>"
                                                                   required <?= $elegida && ($det["bien"] || !$res["aprobado"]) ? "checked" : "" ?>>
                                                            <span><?= e($o["texto"]) ?></span>
                                                            <?php if ($elegida && $det["bien"]): ?>
                                                                <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                            <?php elseif ($elegida): ?>
                                                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                            <?php elseif ($mostrarCorrecta): ?>
                                                                <span class="quiz__etiqueta">Respuesta correcta</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </fieldset>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>

                                    <button type="submit" class="boton boton--primario">
                                        <?= ($q["mejor"] || $res) ? '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Enviar de nuevo' : "Enviar respuestas" ?>
                                    </button>
                                </form>

                                <?php if ($plegado): ?>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <footer class="leccion__pie">
        <?php if ($hecha): ?>
            <span class="chip chip--exito"><i class="fa-solid fa-check" aria-hidden="true"></i> Ya completaste esta lección</span>
            <a class="boton boton--secundario" href="index.php?page=curso&amp;id=<?= (int) $leccion["curso_id"] ?>">Volver al curso</a>
        <?php elseif ($pendientes > 0): ?>
            <p class="texto-suave">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                Para terminar la lección, aprueba
                <?= $pendientes === 1 ? "la actividad que falta" : "las {$pendientes} actividades que faltan" ?>.
            </p>
            <a class="boton boton--secundario" href="#actividad-<?= (int) $primeraPendiente ?>">Ir a la actividad</a>
        <?php else: ?>
            <p class="texto-suave">¿Terminaste de estudiarla? Márcala para guardar tu avance.</p>
            <form method="POST" action="completar_leccion.php">
                <?= campoCsrf() ?>
                <input type="hidden" name="leccion_id" value="<?= (int) $leccion["id"] ?>">
                <input type="hidden" name="curso_id" value="<?= (int) $leccion["curso_id"] ?>">
                <button type="submit" class="boton boton--exito">
                    <i class="fa-solid fa-check" aria-hidden="true"></i> Terminé esta lección
                </button>
            </form>
        <?php endif; ?>
    </footer>
</article>
