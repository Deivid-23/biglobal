<?php
/**
 * Estadísticas de la ruta (admin). Solo totales, nunca estudiantes por nombre.
 * Variables: $disponible, $idiomas, $idiomaId, $niveles, $nivelId, $periodo,
 *            $resumen, $dificiles, $abandono, $avance
 */
$tipos = RutaModel::TIPOS;
$porcentajeTxt = fn(?int $p) => $p === null ? "—" : $p . " %";
?>
<p><a href="index.php?page=ruta&amp;idioma=<?= (int) $idiomaId ?>">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Ruta de aprendizaje</a></p>

<header class="pnl-encabezado">
    <div>
        <h1>Estadísticas de la ruta</h1>
        <p>Qué ejercicios cuestan más y en qué lecciones se va la gente, para saber qué contenido corregir. Solo totales: aquí no aparece ningún estudiante por nombre.</p>
    </div>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Las estadísticas aún no están disponibles: falta aplicar <code>database/ruta_estadisticas.sql</code>.</span>
    </div>
<?php return; endif; ?>

<form class="pnl-tarjeta pnl-form estadisticas-filtros" method="GET" action="index.php">
    <input type="hidden" name="page" value="estadisticas">
    <div class="pnl-form__fila">
        <div class="campo">
            <label class="campo__etiqueta" for="f-idioma">Idioma</label>
            <select class="entrada" id="f-idioma" name="idioma">
                <?php foreach ($idiomas as $i): ?>
                    <option value="<?= (int) $i["id"] ?>" <?= (int) $i["id"] === $idiomaId ? "selected" : "" ?>><?= e($i["nombre"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="f-nivel">Nivel</label>
            <select class="entrada" id="f-nivel" name="nivel">
                <option value="0">Todos los niveles</option>
                <?php foreach ($niveles as $n): ?>
                    <option value="<?= (int) $n["id"] ?>" <?= (int) $n["id"] === $nivelId ? "selected" : "" ?>><?= e($n["codigo"]) ?> · <?= e($n["nombre"]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="f-periodo">Período</label>
            <select class="entrada" id="f-periodo" name="periodo">
                <?php foreach (RutaEstadisticasModel::PERIODOS as $clave => $texto): ?>
                    <option value="<?= e($clave) ?>" <?= $clave === $periodo ? "selected" : "" ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <p class="campo__ayuda">Si cambias el idioma, el nivel vuelve a «Todos». Las respuestas se guardan desde que se publicaron las estadísticas; el avance por nivel usa todo el historial.</p>
    <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit">Ver estadísticas</button></div>
</form>

<!-- ================= RESUMEN ================= -->
<section class="pnl-datos" aria-label="Resumen">
    <div class="pnl-dato">
        <span class="pnl-dato__icono"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $resumen["activos7"] ?> / <?= (int) $resumen["activos30"] ?></span><br><span class="pnl-dato__texto">Activos 7 / 30 días</span></span>
    </div>
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--celeste"><i class="fa-solid fa-play" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $resumen["empezadas"] ?></span><br><span class="pnl-dato__texto">Lecciones empezadas</span></span>
    </div>
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--verde"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= (int) $resumen["terminadas"] ?></span><br><span class="pnl-dato__texto">Terminadas</span></span>
    </div>
    <div class="pnl-dato">
        <span class="pnl-dato__icono pnl-dato__icono--naranja"><i class="fa-solid fa-door-open" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= e($porcentajeTxt($resumen["abandono"])) ?></span><br><span class="pnl-dato__texto">Abandono</span></span>
    </div>
</section>

<!-- ================= EJERCICIOS DIFÍCILES ================= -->
<section class="pnl-tarjeta" aria-labelledby="titulo-dificiles">
    <div class="pnl-tarjeta__cabecera"><div>
        <h2 id="titulo-dificiles">Ejercicios más difíciles</h2>
        <p>Los que menos gente acierta a la primera. Solo los que tienen <?= RutaEstadisticasModel::MIN_RESPUESTAS ?> respuestas o más.</p>
    </div></div>
    <?php if (!$dificiles): ?>
        <p class="texto-suave">Todavía no hay ejercicios con <?= RutaEstadisticasModel::MIN_RESPUESTAS ?> respuestas en este filtro. Vuelve cuando más estudiantes hayan practicado.</p>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>Ejercicio</th><th>Lección</th><th>Acierto a la primera</th><th class="col-acciones">Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($dificiles as $d): ?>
                        <tr>
                            <td>
                                <span class="col-fuerte"><?= e(mb_strimwidth((string) $d["enunciado"], 0, 90, "…")) ?></span>
                                <span class="chip chip--neutro"><?= e($tipos[$d["tipo"]] ?? $d["tipo"]) ?></span>
                            </td>
                            <td class="col-suave"><?= e($d["nivel"]) ?> · <?= e($d["leccion"]) ?></td>
                            <td>
                                <span class="chip <?= $d["acierto"] < 50 ? "chip--error" : ($d["acierto"] < 75 ? "chip--logro" : "chip--exito") ?>"><?= (int) $d["acierto"] ?> %</span>
                                <span class="col-suave"><?= (int) $d["aciertos"] ?> de <?= (int) $d["respuestas"] ?></span>
                            </td>
                            <td class="col-acciones">
                                <a class="boton boton--secundario boton--sm" href="index.php?page=ruta&amp;leccion=<?= (int) $d["leccion_id"] ?>&amp;ejercicio=<?= (int) $d["id"] ?>">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ================= ABANDONO ================= -->
<section class="pnl-tarjeta" aria-labelledby="titulo-abandono">
    <div class="pnl-tarjeta__cabecera"><div>
        <h2 id="titulo-abandono">Lecciones donde más abandonan</h2>
        <p>Empezadas (con al menos una respuesta) que no se terminaron. Solo lecciones con <?= RutaEstadisticasModel::MIN_EMPEZADAS ?> empezadas o más.</p>
    </div></div>
    <?php if (!$abandono): ?>
        <p class="texto-suave">Todavía no hay lecciones con suficientes datos de abandono en este filtro.</p>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>Lección</th><th>Empezadas</th><th>Abandono</th><th>Último ejercicio antes de salir</th></tr></thead>
                <tbody>
                    <?php foreach ($abandono as $l): ?>
                        <tr>
                            <td>
                                <a class="col-fuerte" href="index.php?page=ruta&amp;leccion=<?= (int) $l["id"] ?>"><?= e($l["titulo"]) ?></a>
                                <br><span class="col-suave"><?= e($l["nivel"]) ?> · <?= e($l["unidad"]) ?></span>
                            </td>
                            <td><?= (int) $l["empezadas"] ?> <span class="col-suave">(<?= (int) $l["terminadas"] ?> terminadas)</span></td>
                            <td><span class="chip <?= $l["abandono"] >= 50 ? "chip--error" : "chip--logro" ?>"><?= (int) $l["abandono"] ?> %</span></td>
                            <td>
                                <?php if ($l["donde"]): ?>
                                    <?= e(mb_strimwidth((string) $l["donde"]["enunciado"], 0, 70, "…")) ?>
                                    <br><span class="col-suave"><?= e($tipos[$l["donde"]["tipo"]] ?? $l["donde"]["tipo"]) ?> · <?= e(plural_panel((int) $l["donde"]["veces"], "vez", "veces")) ?></span>
                                <?php else: ?>
                                    <span class="col-suave">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- ================= AVANCE POR NIVEL ================= -->
<section class="pnl-tarjeta" aria-labelledby="titulo-avance">
    <div class="pnl-tarjeta__cabecera"><div>
        <h2 id="titulo-avance">Avance por nivel</h2>
        <p>Con todo el historial: estudiantes que completaron al menos una lección del nivel y los que lo terminaron completo.</p>
    </div></div>
    <?php if (!$avance): ?>
        <p class="texto-suave">Este idioma todavía no tiene niveles publicados.</p>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>Nivel</th><th>Lecciones</th><th>Empezaron</th><th>Terminaron</th></tr></thead>
                <tbody>
                    <?php foreach ($avance as $n): ?>
                        <tr>
                            <td><span class="col-fuerte"><?= e($n["codigo"]) ?></span> <span class="col-suave"><?= e($n["nombre"]) ?></span></td>
                            <td><?= (int) $n["jugables"] ?></td>
                            <td><?= (int) $n["empezaron"] ?></td>
                            <td><?= (int) $n["terminaron"] ?><?php if ($n["empezaron"] > 0): ?> <span class="col-suave">(<?= (int) round($n["terminaron"] * 100 / $n["empezaron"]) ?> %)</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
