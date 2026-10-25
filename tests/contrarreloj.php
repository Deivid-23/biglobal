<?php
/**
 * Contrarreloj (ContrarrelojModel): de dónde salen los ejercicios, el reloj
 * del servidor, el orden, la mejor marca, el XP una vez al día y el borrado
 * al eliminar la cuenta.
 */
$en = P::idioma($c, "EN");
$es = P::idioma($c, "ES");
$u = P::usuario($c, "estudiante");

// ---------- Sin lecciones completadas no hay partida ----------
P::ver("sin lecciones completadas: 0 ejercicios", ContrarrelojModel::cuantos($c, $u, $en) === 0);
P::ver("sin lecciones completadas no se puede jugar", ContrarrelojModel::iniciar($c, $u, $en) === []);

P::completar($c, $u, P::lecciones($c, $en, "A1"));
$cuantos = ContrarrelojModel::cuantos($c, $u, $en);
P::ver("con el A1 completado hay ejercicios (al menos " . ContrarrelojModel::MINIMO . ")", $cuantos >= ContrarrelojModel::MINIMO);
P::ver("en otro idioma sigue en 0", ContrarrelojModel::cuantos($c, $u, $es) === 0);

$ejs = ContrarrelojModel::iniciar($c, $u, $en, 1000);
$completadas = array_map(fn($l) => (int) $l["id"], P::lecciones($c, $en, "A1"));
P::ver("solo trae tipos rápidos, sin video, de lecciones completadas", $ejs && !array_filter($ejs, fn($e) =>
    !in_array($e["tipo"], ContrarrelojModel::RAPIDOS, true) || (string) ($e["video_url"] ?? "") !== ""
    || !in_array((int) $e["leccion_id"], $completadas, true)));
P::ver("no trae más de " . ContrarrelojModel::MAXIMO, count($ejs) <= ContrarrelojModel::MAXIMO);
$vista = json_encode(array_map(fn($e) => RutaModel::vistaPublica($e, "EN"), $ejs), JSON_UNESCAPED_UNICODE);
P::ver("la vista pública no trae respuestas", !preg_match('/"(correcta|respuestas|aceptadas|correccion|pares)"\s*:/', $vista));

// ---------- El reloj empieza con "empezar" ----------
$primero = (int) $ejs[0]["id"];
P::ver("antes de empezar no acepta respuestas", isset(ContrarrelojModel::responder($c, $u, $en, $primero, "x", 1000)["error"]));
P::ver("empezar en otro idioma da error", isset(ContrarrelojModel::arrancar($es, 1000)["error"]));
$a = ContrarrelojModel::arrancar($en, 1000);
P::ver("empezar devuelve los segundos", ($a["segundos"] ?? 0) === ContrarrelojModel::DURACION);
ContrarrelojModel::arrancar($en, 1030);
P::ver("pulsar empezar otra vez no reinicia el reloj", $_SESSION["ruta_contrarreloj"]["inicio"] === 1000);

// ---------- Responder en orden; un fallo pasa al siguiente ----------
P::ver("exige responder en orden", isset(ContrarrelojModel::responder($c, $u, $en, (int) $ejs[1]["id"], "x", 1001)["error"]));
$r1 = ContrarrelojModel::responder($c, $u, $en, $primero, P::respuestaCorrecta($ejs[0]), 1002);
P::ver("acierto: suma y sigue el segundo", $r1["correcto"] === true && $r1["aciertos"] === 1 && (int) $r1["siguiente"] === (int) $ejs[1]["id"]);
$r2 = ContrarrelojModel::responder($c, $u, $en, (int) $ejs[1]["id"], "__mal__", 1003);
P::ver("fallo: no suma, muestra la solución y no se repite", $r2["correcto"] === false && $r2["aciertos"] === 1
    && $r2["solucion"] !== "" && (int) $r2["siguiente"] === (int) $ejs[2]["id"]);
$r3 = ContrarrelojModel::responder($c, $u, $en, (int) $ejs[2]["id"], P::respuestaCorrecta($ejs[2]), 1004);
P::ver("tercer ejercicio: 2 aciertos", $r3["aciertos"] === 2);

// ---------- Fuera de tiempo: la respuesta no cuenta y la partida termina ----------
$xpAntes = (int) $c->query("SELECT COALESCE(SUM(puntos), 0) FROM xp_movimientos WHERE usuario_id = $u")->fetchColumn();
$tarde = 1000 + ContrarrelojModel::DURACION + ContrarrelojModel::GRACIA + 1;
$r4 = ContrarrelojModel::responder($c, $u, $en, (int) $ejs[3]["id"], P::respuestaCorrecta($ejs[3]), $tarde);
$res = $r4["resultado"] ?? null;
P::ver("después del tiempo: termina sin contar esa respuesta", !empty($r4["tiempo"]) && $res && $res["aciertos"] === 2 && $res["respondidas"] === 3);
P::ver("primera partida: es récord", $res && $res["record"] === true && $res["mejor"] === 2);
P::ver("da " . RutaModel::XP_PRACTICA . " XP la primera vez del día", $res && $res["xp"] === RutaModel::XP_PRACTICA
    && (int) $c->query("SELECT COALESCE(SUM(puntos), 0) FROM xp_movimientos WHERE usuario_id = $u")->fetchColumn() === $xpAntes + RutaModel::XP_PRACTICA);
P::ver("cuenta para la racha", $res && (int) $res["racha"] >= 1);
P::ver("la partida ya no existe", !isset($_SESSION["ruta_contrarreloj"]) && isset(ContrarrelojModel::terminar($c, $u)["error"]));
$m = ContrarrelojModel::marca($c, $u, $en);
P::ver("guarda la marca: mejor 2, 1 partida", $m && (int) $m["mejor"] === 2 && (int) $m["partidas"] === 1 && $m["mejor_en"] !== null);

// ---------- Segunda partida peor: sin XP y la marca no baja ----------
$ejs = ContrarrelojModel::iniciar($c, $u, $en);
ContrarrelojModel::arrancar($en, 2000);
ContrarrelojModel::responder($c, $u, $en, (int) $ejs[0]["id"], P::respuestaCorrecta($ejs[0]), 2001);
$res = ContrarrelojModel::terminar($c, $u);
P::ver("segunda partida del día: 0 XP", $res["xp"] === 0);
P::ver("1 acierto no es récord y la marca sigue en 2", $res["record"] === false && $res["mejor"] === 2
    && (int) ContrarrelojModel::marca($c, $u, $en)["mejor"] === 2 && (int) ContrarrelojModel::marca($c, $u, $en)["partidas"] === 2);

// ---------- Mejorar la marca ----------
$ejs = ContrarrelojModel::iniciar($c, $u, $en);
ContrarrelojModel::arrancar($en, 3000);
for ($i = 0; $i < 3; $i++) {
    ContrarrelojModel::responder($c, $u, $en, (int) $ejs[$i]["id"], P::respuestaCorrecta($ejs[$i]), 3001 + $i);
}
$res = ContrarrelojModel::terminar($c, $u);
P::ver("3 aciertos: nuevo récord", $res["record"] === true && $res["mejor"] === 3 && (int) ContrarrelojModel::marca($c, $u, $en)["mejor"] === 3);

// ---------- Terminar toda la cola antes del tiempo ----------
$ejs = ContrarrelojModel::iniciar($c, $u, $en);
ContrarrelojModel::arrancar($en, 4000);
$ultimo = null;
foreach ($ejs as $i => $e) {
    $ultimo = ContrarrelojModel::responder($c, $u, $en, (int) $e["id"], P::respuestaCorrecta($e), 4000);
}
P::ver("responder todos antes del tiempo cierra la partida", isset($ultimo["resultado"]) && $ultimo["siguiente"] === null
    && $ultimo["resultado"]["aciertos"] === count($ejs));

// Eliminar la cuenta lo borra
ob_start();
CuentaEliminacionModel::anonimizar($c, $u);
ob_end_clean();
P::ver("al eliminar la cuenta se borra su marca", (int) $c->query("SELECT COUNT(*) FROM ruta_contrarreloj WHERE usuario_id = $u")->fetchColumn() === 0);
