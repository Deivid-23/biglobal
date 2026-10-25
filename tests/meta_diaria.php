<?php
/**
 * Meta diaria de XP (GamificacionModel::meta, xpDeHoy, estadoMeta):
 * valores permitidos, el XP de hoy en la zona del estudiante y el aviso
 * cuando una lección o un repaso la cumplen.
 */
$en = P::idioma($c, "EN");
$u = P::usuario($c, "estudiante");

P::ver("la meta por defecto es " . GamificacionModel::META_DEFECTO . " XP", GamificacionModel::meta($c, $u) === GamificacionModel::META_DEFECTO);
P::ver("se puede elegir 30", GamificacionModel::guardarMeta($c, $u, 30) && GamificacionModel::meta($c, $u) === 30);
P::ver("no acepta otros valores (25)", !GamificacionModel::guardarMeta($c, $u, 25) && GamificacionModel::meta($c, $u) === 30);
P::ver("no acepta 0 ni negativos", !GamificacionModel::guardarMeta($c, $u, 0) && !GamificacionModel::guardarMeta($c, $u, -10));

// XP de hoy en la zona del estudiante (Madrid): la 1:00 a. m. de Madrid es todavía ayer en Colombia
$c->prepare("UPDATE usuarios SET zona_horaria = 'Europe/Madrid' WHERE id = ?")->execute([$u]);
$madrid = new DateTimeZone("Europe/Madrid");
$bd = new DateTimeZone("-05:00");
$hoyMadrid = (new DateTimeImmutable("now", $madrid))->setTime(12, 0);
$en_bd = fn(DateTimeImmutable $d) => $d->setTimezone($bd)->format("Y-m-d H:i:s");
$xp = fn(int $puntos, string $fecha) => $c->prepare("INSERT INTO xp_movimientos (usuario_id, puntos, motivo, fecha) VALUES (?, ?, 'prueba', ?)")->execute([$u, $puntos, $fecha]);
$xp(10, $en_bd($hoyMadrid->setTime(1, 0)));                         // hoy en Madrid (ayer en Colombia)
$xp(5, $en_bd($hoyMadrid->setTime(11, 0)));                         // hoy en Madrid
$xp(40, $en_bd($hoyMadrid->modify("-1 day")->setTime(23, 30)));     // ayer en Madrid (hoy en Colombia)
P::ver("XP de hoy en su zona: 15 (no cuenta el de ayer)", GamificacionModel::xpDeHoy($c, $u, $hoyMadrid->getTimestamp()) === 15);
$c->prepare("DELETE FROM xp_movimientos WHERE usuario_id = ?")->execute([$u]);
$c->prepare("UPDATE usuarios SET zona_horaria = 'America/Bogota' WHERE id = ?")->execute([$u]);

// estadoMeta: recién cumplida solo cuando el XP ganado cruza la meta
GamificacionModel::guardarMeta($c, $u, 20);
$xp(15, date("Y-m-d H:i:s"));
$e = GamificacionModel::estadoMeta($c, $u);
P::ver("15 de 20: no cumplida", $e["xpHoy"] === 15 && !$e["cumplida"] && !$e["recienCumplida"]);
$xp(10, date("Y-m-d H:i:s"));
P::ver("al ganar 10 y llegar a 25: recién cumplida", GamificacionModel::estadoMeta($c, $u, 10)["recienCumplida"] === true);
$xp(2, date("Y-m-d H:i:s"));
$e = GamificacionModel::estadoMeta($c, $u, 2);
P::ver("los 2 XP siguientes ya no la «cumplen» otra vez", $e["cumplida"] && !$e["recienCumplida"]);
$c->prepare("DELETE FROM xp_movimientos WHERE usuario_id = ?")->execute([$u]);

// Una lección real que cumple la meta (Relajada: 10 XP)
GamificacionModel::guardarMeta($c, $u, 10);
$leccion = P::lecciones($c, $en, "A1")[0];
$ejs = RutaModel::ejercicios($c, (int) $leccion["id"], true);
RutaModel::iniciarIntento((int) $leccion["id"], $ejs);
$porId = [];
foreach ($ejs as $ej) {
    $porId[(int) $ej["id"]] = $ej;
}
for ($i = 0, $res = null; $i < 60 && !$res; $i++) {
    $id = (int) $_SESSION["ruta_intento"]["cola"][0];
    $res = RutaModel::responder($c, $u, (int) $leccion["id"], $id, P::respuestaCorrecta($porId[$id]))["resultado"] ?? null;
}
P::ver("la lección da XP y avisa que cumplió la meta", $res && $res["xp"] >= 10 && !empty($res["meta"]["recienCumplida"]));
P::ver("la meta no cambia el XP de la lección", $res && $res["xp"] === (int) $leccion["xp"] + ($res["porcentaje"] === 100 ? RutaModel::XP_PERFECTA : 0));
P::ver("no se suma XP extra por cumplirla", GamificacionModel::xpDeHoy($c, $u) === (int) $res["xp"]);
