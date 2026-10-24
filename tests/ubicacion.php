<?php
/**
 * Examen de ubicación (UbicacionModel): armado, orden, una vez al día,
 * resultado, que solo sube y que no da XP, insignias ni lecciones completadas.
 */
$en = P::idioma($c, "EN");

/** Hace el examen: aprueba los niveles de $aprobar y falla el siguiente. */
$examen = function (int $u, array $aprobar) use ($c, $en): ?array {
    $_SESSION = [];
    $porId = [];
    foreach (UbicacionModel::iniciar($c, $u, $en) as $e) {
        $porId[(int) $e["id"]] = $e;
    }
    $id = (int) $_SESSION["ruta_ubicacion"]["bloques"][0]["ids"][0];
    for ($i = 0; $i < 40; $i++) {
        $x = $_SESSION["ruta_ubicacion"];
        $bien = in_array($x["bloques"][$x["bloque"]]["codigo"], $aprobar, true);
        $r = UbicacionModel::responder($c, $u, $en, $id, $bien ? P::respuestaCorrecta($porId[$id]) : "__mal__");
        if (isset($r["error"])) {
            return null;
        }
        if (isset($r["resultado"])) {
            return $r["resultado"];
        }
        $id = (int) $r["siguiente"];
    }
    return null;
};

P::ver("inglés tiene examen y LSC no", UbicacionModel::tieneExamen(["codigo" => "EN"]) && !UbicacionModel::tieneExamen(["codigo" => "LSC"]));

$u = P::usuario($c, "estudiante");
$lista = UbicacionModel::iniciar($c, $u, $en);
$bloques = $_SESSION["ruta_ubicacion"]["bloques"];
P::ver("5 niveles de " . UbicacionModel::POR_NIVEL . " ejercicios", count($bloques) === 5 && !array_filter($bloques, fn($b) => count($b["ids"]) !== UbicacionModel::POR_NIVEL));
P::ver("sin escuchar, dictado ni seña", !array_filter($lista, fn($e) => in_array($e["tipo"], ["escuchar", "dictado", "sena"], true)));
$r = UbicacionModel::responder($c, $u, $en, (int) $bloques[0]["ids"][1], "x");
P::ver("exige responder en orden", isset($r["error"]) && (int) $r["siguiente"] === (int) $bloques[0]["ids"][0]);
P::ver("empezarlo ya cuenta: hoy no lo repite", !UbicacionModel::puedeHoy($c, $u, $en));
$r = UbicacionModel::responder($c, $u, P::idioma($c, "ES"), (int) $bloques[0]["ids"][0], "x");
P::ver("no acepta respuestas de otro idioma", isset($r["error"]));

// Aprueba A1 y A2: queda en B1, sin XP ni lecciones completadas
$u = P::usuario($c, "estudiante");
$res = $examen($u, ["A1", "A2"]);
P::ver("aprueba A1 y A2: queda en B1", $res && $res["nivel"] === "B1" && $res["aprobados"] === 2 && $res["mejoro"]);
P::ver("el detalle cuenta lo respondido", $res && str_starts_with($res["detalle"], "A1 6/6 · A2 6/6 · B1 0/"));
P::ver("no marca lecciones completadas", (int) $c->query("SELECT COUNT(*) FROM ruta_progreso WHERE usuario_id = $u")->fetchColumn() === 0);
P::ver("no da XP", (int) $c->query("SELECT COUNT(*) FROM xp_movimientos WHERE usuario_id = $u")->fetchColumn() === 0);
P::ver("no da insignias", !InsigniaModel::revisar($c, $u, "ruta"));

// Otro día, peor resultado: no baja
$c->prepare("UPDATE ruta_ubicacion SET ultimo_intento = NOW() - INTERVAL 2 DAY WHERE usuario_id = ?")->execute([$u]);
P::ver("al día siguiente puede repetir", UbicacionModel::puedeHoy($c, $u, $en));
$res = $examen($u, []);
P::ver("peor resultado: sigue en B1", $res && $res["nivel"] === "B1" && !$res["mejoro"] && UbicacionModel::nivelDe($c, $u, $en) === "B1");

// Falla el A1 / aprueba todo
$u = P::usuario($c, "estudiante");
$res = $examen($u, []);
P::ver("falla el A1: queda en A1", $res && $res["nivel"] === "A1" && $res["aprobados"] === 0);
$u = P::usuario($c, "estudiante");
$res = $examen($u, ["A1", "A2", "B1", "B2", "C1"]);
P::ver("aprueba todo: queda en C1", $res && $res["nivel"] === "C1" && $res["aprobados"] === 5);
