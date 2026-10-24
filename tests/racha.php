<?php
/**
 * Racha: XP de práctica en la zona del estudiante y recordatorio de racha.
 * El recordatorio revisa a TODOS los estudiantes, así que se prueba dentro de
 * una transacción que se deshace al final (no toca a nadie más).
 */
$en = P::idioma($c, "EN");

// ---------- diaEn ----------
P::ver("diaEn: 20:00 de Colombia ya es mañana en Madrid", GamificacionModel::diaEn("2026-10-23 20:00:00", "Europe/Madrid") === "2026-10-24");
P::ver("diaEn: en Bogotá sigue el mismo día", GamificacionModel::diaEn("2026-10-23 20:00:00", "America/Bogota") === "2026-10-23");

// ---------- XP de práctica (2 XP una vez al día, en su zona) ----------
$u = P::usuario($c, "estudiante", "Europe/Madrid");
$leccion = P::lecciones($c, $en, "A1")[0];
$terminar = new ReflectionMethod("RutaModel", "terminar");
$terminar->setAccessible(true);
$xp = fn() => $terminar->invoke(null, $c, $u, (int) $leccion["id"], 80)["xp"];
$progreso = function (string $ultima) use ($c, $u, $leccion) {
    $c->prepare("DELETE FROM ruta_progreso WHERE usuario_id = ? AND leccion_id = ?")->execute([$u, $leccion["id"]]);
    $c->prepare("INSERT INTO ruta_progreso (usuario_id, leccion_id, mejor_porcentaje, intentos, ultima_vez, completada_en) VALUES (?, ?, 80, 2, ?, ?)")
      ->execute([$u, $leccion["id"], $ultima, $ultima]);
};
$madrid = new DateTimeImmutable("now", new DateTimeZone("Europe/Madrid"));
$progreso($madrid->setTime(0, 30)->setTimezone(new DateTimeZone("-05:00"))->format("Y-m-d H:i:s"));
P::ver("ya practicó hoy en su zona (ayer en Colombia): 0 XP", $xp() === 0);
$progreso($madrid->modify("-1 day")->setTime(23, 30)->setTimezone(new DateTimeZone("-05:00"))->format("Y-m-d H:i:s"));
P::ver("practicó ayer en su zona: " . RutaModel::XP_PRACTICA . " XP", $xp() === RutaModel::XP_PRACTICA);
P::ver("la segunda vez hoy: 0 XP", $xp() === 0);

// ---------- Recordatorio de racha ----------
$bog = new DateTimeZone("America/Bogota");
$ahora = (new DateTimeImmutable("now", $bog))->setTime(19, 30);
$t = $ahora->getTimestamp();
$hoy = $ahora->format("Y-m-d");
$ayer = $ahora->modify("-1 day")->format("Y-m-d");
$antier = $ahora->modify("-2 day")->format("Y-m-d");

$a = P::usuario($c, "estudiante");   // racha viva, no estudió hoy
$b = P::usuario($c, "estudiante");   // ya estudió hoy
$stats = function (int $id, int $racha, string $hasta, int $prot) use ($c, $hoy) {
    $c->prepare("REPLACE INTO estudiante_stats (usuario_id, xp_total, racha_actual, racha_maxima, ultima_actividad, racha_hasta, protectores, protectores_mes, recordatorio_dia)
                 VALUES (?, 0, ?, ?, ?, ?, ?, ?, NULL)")->execute([$id, $racha, $racha, $hasta, $hasta, $prot, substr($hoy, 0, 7)]);
};
$avisos = fn(int $id) => (int) $c->query("SELECT COUNT(*) FROM notificaciones WHERE tipo = 'racha_recordatorio' AND usuario_id = $id")->fetchColumn();
$texto = fn(int $id) => (string) $c->query("SELECT CONCAT(titulo, ' | ', mensaje) FROM notificaciones WHERE tipo = 'racha_recordatorio' AND usuario_id = $id ORDER BY id DESC LIMIT 1")->fetchColumn();

$c->beginTransaction();
$c->exec("UPDATE estudiante_stats SET racha_actual = 0");   // nadie más entra (se deshace al final)
P::config("racha_recordatorio", "1");
$stats($a, 5, $ayer, 2);
$stats($b, 3, $hoy, 2);
GamificacionModel::recordatoriosRacha($c, $t);
P::ver("racha viva y no estudió hoy: le llega", $avisos($a) === 1);
P::ver("el texto dice la racha y los protectores", str_contains($texto($a), "racha de 5 días termina hoy") && str_contains($texto($a), "1 de tus 2 protectores"));
P::ver("ya estudió hoy: no le llega", $avisos($b) === 0);
GamificacionModel::recordatoriosRacha($c, $t + 1800);
P::ver("una sola vez por día", $avisos($a) === 1);

$stats($a, 5, $ayer, 2);
GamificacionModel::recordatoriosRacha($c, $ahora->setTime(18, 50)->getTimestamp());
GamificacionModel::recordatoriosRacha($c, $ahora->setTime(22, 10)->getTimestamp());
P::ver("fuera de 7–10 p. m. no avisa", $avisos($a) === 1);

$c->exec("DELETE FROM notificaciones WHERE tipo = 'racha_recordatorio' AND usuario_id = $a");
$stats($a, 4, $antier, 1);
GamificacionModel::recordatoriosRacha($c, $t);
P::ver("usa el último protector por ayer y avisa que no quedan", str_contains($texto($a), "ya no te quedan protectores"));

$c->exec("DELETE FROM notificaciones WHERE tipo = 'racha_recordatorio' AND usuario_id = $a");
$stats($a, 4, $antier, 0);
GamificacionModel::recordatoriosRacha($c, $t);
P::ver("racha perdida: no avisa", $avisos($a) === 0);

$stats($a, 5, $ayer, 2);
GamificacionModel::guardarRecordatorio($c, $a, false);
GamificacionModel::recordatoriosRacha($c, $t);
P::ver("con su casilla apagada: no le llega", $avisos($a) === 0);
GamificacionModel::guardarRecordatorio($c, $a, true);

Config::set("racha_recordatorio", "0");
$r = GamificacionModel::recordatoriosRacha($c, $t);
P::ver("con el interruptor general apagado: nadie", $r["estado"] === "desactivado" && $avisos($a) === 0);
$c->rollBack();
