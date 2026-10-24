<?php
/**
 * Repaso de errores (RepasoModel): qué entra a la lista, el repaso en orden,
 * resolver o no, el XP una vez al día y el borrado al eliminar la cuenta.
 */
$en = P::idioma($c, "EN");
$es = P::idioma($c, "ES");
$u = P::usuario($c, "estudiante");

// ---------- Fallar en una lección lo agrega; acertar a la primera no ----------
$leccion = P::lecciones($c, $en, "A1")[0];
$ejs = RutaModel::ejercicios($c, (int) $leccion["id"], true);
RutaModel::iniciarIntento((int) $leccion["id"], $ejs);
$porId = [];
foreach ($ejs as $e) {
    $porId[(int) $e["id"]] = $e;
}
$fallar = [(int) $ejs[0]["id"], (int) $ejs[1]["id"]];   // los dos primeros se fallan una vez
$fallados = [];
for ($i = 0, $res = null; $i < 60 && !$res; $i++) {
    $id = (int) $_SESSION["ruta_intento"]["cola"][0];
    $mal = in_array($id, $fallar, true) && !isset($fallados[$id]);
    if ($mal) {
        $fallados[$id] = true;
    }
    $res = RutaModel::responder($c, $u, (int) $leccion["id"], $id, $mal ? "__mal__" : P::respuestaCorrecta($porId[$id]))["resultado"] ?? null;
}
$lista = $c->query("SELECT ejercicio_id FROM ruta_repaso WHERE usuario_id = $u ORDER BY ejercicio_id")->fetchAll(PDO::FETCH_COLUMN);
sort($fallar);
P::ver("los 2 ejercicios fallados entran a la lista (aunque los corrigió en la lección)", array_map("intval", $lista) === $fallar);
P::ver("pendientes en inglés: 2", RepasoModel::pendientes($c, $u, $en) === 2);
P::ver("pendientes en español: 0", RepasoModel::pendientes($c, $u, $es) === 0);

// Un ejercicio desactivado no entra al repaso
$desactivado = (int) P::lecciones($c, $en, "A1")[1]["id"];
$e3 = (int) $c->query("SELECT id FROM ruta_ejercicios WHERE leccion_id = $desactivado AND activo = 1 ORDER BY id LIMIT 1")->fetchColumn();
RepasoModel::anotarFallo($c, $u, $e3);
$c->prepare("UPDATE ruta_ejercicios SET activo = 0 WHERE id = ?")->execute([$e3]);
P::ver("un ejercicio desactivado no cuenta", RepasoModel::pendientes($c, $u, $en) === 2);
$c->prepare("UPDATE ruta_ejercicios SET activo = 1 WHERE id = ?")->execute([$e3]);
$c->prepare("DELETE FROM ruta_repaso WHERE usuario_id = ? AND ejercicio_id = ?")->execute([$u, $e3]);

// ---------- El repaso: los más viejos primero, en orden ----------
$c->prepare("UPDATE ruta_repaso SET fallado_en = NOW() - INTERVAL 2 DAY WHERE usuario_id = ? AND ejercicio_id = ?")->execute([$u, $fallar[1]]);
$repaso = RepasoModel::iniciar($c, $u, $en);
P::ver("el repaso trae los 2, el más viejo primero", count($repaso) === 2 && (int) $repaso[0]["id"] === $fallar[1]);
$r = RepasoModel::responder($c, $u, $en, $fallar[0], "x");
P::ver("exige responder en orden", isset($r["error"]) && (int) $r["siguiente"] === $fallar[1]);
P::ver("no acepta respuestas de otro idioma", isset(RepasoModel::responder($c, $u, $es, $fallar[1], "x")["error"]));

// Acierta el primero, falla el segundo
$r1 = RepasoModel::responder($c, $u, $en, $fallar[1], P::respuestaCorrecta($porId[$fallar[1]]));
P::ver("acertar: correcto y sigue el otro", $r1["correcto"] === true && (int) $r1["siguiente"] === $fallar[0]);
$xpAntes = (int) $c->query("SELECT COALESCE(SUM(puntos), 0) FROM xp_movimientos WHERE usuario_id = $u")->fetchColumn();
$r2 = RepasoModel::responder($c, $u, $en, $fallar[0], "__mal__");
P::ver("fallar: muestra la solución", $r2["correcto"] === false && $r2["solucion"] !== "");
$res = $r2["resultado"] ?? null;
P::ver("al terminar: 1 de 2 resueltos y queda 1", $res && $res["resueltos"] === 1 && $res["total"] === 2 && $res["quedan"] === 1);
P::ver("el acertado salió de la lista y el fallado sigue", RepasoModel::pendientes($c, $u, $en) === 1
    && $c->query("SELECT resuelto_en IS NULL FROM ruta_repaso WHERE usuario_id = $u AND ejercicio_id = {$fallar[0]}")->fetchColumn() == 1);
P::ver("da " . RutaModel::XP_PRACTICA . " XP la primera vez del día", $res && $res["xp"] === RutaModel::XP_PRACTICA
    && (int) $c->query("SELECT COALESCE(SUM(puntos), 0) FROM xp_movimientos WHERE usuario_id = $u")->fetchColumn() === $xpAntes + RutaModel::XP_PRACTICA);
P::ver("cuenta para la racha", $res && (int) $res["racha"] >= 1);

// Segundo repaso el mismo día: sin XP
RepasoModel::iniciar($c, $u, $en);
$res = RepasoModel::responder($c, $u, $en, $fallar[0], P::respuestaCorrecta($porId[$fallar[0]]))["resultado"] ?? null;
P::ver("segundo repaso del día: 0 XP", $res && $res["xp"] === 0 && $res["quedan"] === 0);
P::ver("sin pendientes, el repaso viene vacío", RepasoModel::iniciar($c, $u, $en) === []);

// Si lo vuelve a fallar en una lección, vuelve a la lista
RepasoModel::anotarFallo($c, $u, $fallar[0]);
P::ver("volver a fallarlo en una lección lo devuelve a la lista", RepasoModel::pendientes($c, $u, $en) === 1);

// La vista pública del repaso no lleva respuestas
$vista = json_encode(array_map(fn($e) => RutaModel::vistaPublica($e, "EN"), RepasoModel::iniciar($c, $u, $en)), JSON_UNESCAPED_UNICODE);
P::ver("la vista del repaso no trae la respuesta", !preg_match('/"(correcta|respuestas|aceptadas|correccion|pares)"\s*:/', $vista));

// Eliminar la cuenta lo borra
ob_start();
CuentaEliminacionModel::anonimizar($c, $u);
ob_end_clean();
P::ver("al eliminar la cuenta se borra su repaso", (int) $c->query("SELECT COUNT(*) FROM ruta_repaso WHERE usuario_id = $u")->fetchColumn() === 0);
