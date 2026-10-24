<?php
/**
 * Estadísticas de la ruta: el registro al hacer una lección de verdad y los
 * cálculos con datos simulados. Los cálculos suman a TODOS los estudiantes,
 * así que corren en una transacción sin los intentos de otros y se deshacen.
 */
$en = P::idioma($c, "EN");
$u = P::usuario($c, "estudiante");

// ---------- Registro: una lección real, fallando el primer ejercicio una vez ----------
$leccion = P::lecciones($c, $en, "A1")[0];
$ejs = RutaModel::ejercicios($c, (int) $leccion["id"], true);
$reg = RutaEstadisticasModel::abrirIntento($c, $u, (int) $leccion["id"]);
RutaModel::iniciarIntento((int) $leccion["id"], $ejs, $reg);
$porId = [];
foreach ($ejs as $e) {
    $porId[(int) $e["id"]] = $e;
}
$primero = (int) $ejs[0]["id"];
$fallado = false;
$res = null;
for ($i = 0; $i < 60 && !$res; $i++) {
    $id = (int) $_SESSION["ruta_intento"]["cola"][0];
    $resp = ($id === $primero && !$fallado) ? "__mal__" : P::respuestaCorrecta($porId[$id]);
    $fallado = $fallado || $id === $primero;
    $res = RutaModel::responder($c, $u, (int) $leccion["id"], $id, $resp)["resultado"] ?? null;
}
$filas = $c->query("SELECT correcto, primera, ejercicio_id FROM ruta_respuestas WHERE intento_id = " . (int) $reg . " ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
P::ver("la lección termina normal y da XP", $res !== null && (int) $res["xp"] > 0);
P::ver("una respuesta guardada por envío", count($filas) === count($ejs) + 1);
P::ver("el primer fallo cuenta como primera respuesta", $filas && (int) $filas[0]["primera"] === 1 && (int) $filas[0]["correcto"] === 0);
$rep = array_values(array_filter($filas, fn($f) => (int) $f["ejercicio_id"] === $primero));
P::ver("el reintento no es primera respuesta", count($rep) === 2 && (int) $rep[1]["primera"] === 0 && (int) $rep[1]["correcto"] === 1);
$intento = $c->query("SELECT terminado, porcentaje FROM ruta_intentos WHERE id = " . (int) $reg)->fetch(PDO::FETCH_ASSOC);
P::ver("el intento queda terminado con su porcentaje", $intento["terminado"] !== null && (int) $intento["porcentaje"] === (int) $res["porcentaje"]);
P::ver("no se guarda lo que escribió", !in_array("respuesta", $c->query("SHOW COLUMNS FROM ruta_respuestas")->fetchAll(PDO::FETCH_COLUMN), true));

// ---------- Cálculos con datos simulados ----------
$c->beginTransaction();
$c->exec("DELETE FROM ruta_intentos");   // solo dentro de la transacción
$l2 = (int) P::lecciones($c, $en, "A1")[1]["id"];
$e2 = $c->query("SELECT id FROM ruta_ejercicios WHERE leccion_id = $l2 AND activo = 1 ORDER BY orden LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
$v = [P::usuario($c, "estudiante"), P::usuario($c, "estudiante"), P::usuario($c, "estudiante")];
$nuevo = function (int $usuario, string $hace, bool $termina) use ($c, $l2): int {
    $c->prepare("INSERT INTO ruta_intentos (usuario_id, leccion_id, iniciado, terminado, porcentaje) VALUES (?, ?, NOW() - INTERVAL $hace, " . ($termina ? "NOW(), 90" : "NULL, NULL") . ")")->execute([$usuario, $l2]);
    return (int) $c->lastInsertId();
};
$resp = fn(int $i, int $ej, int $bien) => $c->prepare("INSERT INTO ruta_respuestas (intento_id, ejercicio_id, correcto, primera) VALUES (?, ?, ?, 1)")->execute([$i, $ej, $bien]);
for ($k = 0; $k < 6; $k++) { $i = $nuevo($v[0], "2 HOUR", true); $resp($i, (int) $e2[0], $k < 2 ? 1 : 0); $resp($i, (int) $e2[1], 1); }
for ($k = 0; $k < 4; $k++) { $i = $nuevo($v[1], "2 HOUR", false); $resp($i, (int) $e2[0], 0); $resp($i, (int) $e2[1], 0); }
$i = $nuevo($v[2], "10 MINUTE", false); $resp($i, (int) $e2[0], 0);   // en curso
$nuevo($v[2], "3 HOUR", false);                                       // abrió sin responder
$i = $nuevo($v[2], "100 DAY", false); $resp($i, (int) $e2[0], 1);     // viejo

$s = RutaEstadisticasModel::resumen($c, $en, null, "30");
P::ver("empezadas: solo con respuestas y del período (11)", $s["empezadas"] === 11);
P::ver("terminadas 6; abandonadas 4 (la de hace 10 min no)", $s["terminadas"] === 6 && $s["abandonadas"] === 4);
P::ver("abandono 36 %", $s["abandono"] === 36);
P::ver("activos en 30 días: 3", $s["activos30"] === 3);
P::ver("con «Todo» entra el viejo", RutaEstadisticasModel::resumen($c, $en, null, "todo")["empezadas"] === 12);
$d = RutaEstadisticasModel::ejerciciosDificiles($c, $en, null, "30");
P::ver("difíciles: los 2 con 10+ respuestas, el peor primero (2 de 11 = 18 %)", count($d) === 2 && (int) $d[0]["id"] === (int) $e2[0] && $d[0]["acierto"] === 18);
$a = RutaEstadisticasModel::leccionesAbandono($c, $en, null, "30");
P::ver("abandono: la lección con 36 % y dónde la dejan (4 veces)", count($a) === 1 && $a[0]["abandono"] === 36 && (int) ($a[0]["donde"]["veces"] ?? 0) === 4);
$b1 = (int) $c->query("SELECT id FROM ruta_niveles WHERE idioma_id = $en AND codigo = 'B1'")->fetchColumn();
P::ver("filtro por otro nivel: vacío", RutaEstadisticasModel::resumen($c, $en, $b1, "30")["empezadas"] === 0);
$c->rollBack();
