<?php
/**
 * Contenido de LSC (fase 2, database/lsc_*_juegos.sql): todo ejercicio pasa
 * las reglas del formulario del admin, lo nuevo del A1 queda en borrador
 * (el A2 entra activo porque el nivel sigue oculto) y lo que espera video
 * no se muestra.
 */
$lsc = P::idioma($c, "LSC");
$filas = $c->prepare("
    SELECT e.*, l.clave AS leccion, l.publicado AS leccion_publicada, u.publicado AS unidad_publicada, n.codigo AS nivel
    FROM ruta_ejercicios e
    INNER JOIN ruta_lecciones l ON l.id = e.leccion_id
    INNER JOIN ruta_unidades u ON u.id = l.unidad_id
    INNER JOIN ruta_niveles n ON n.id = u.nivel_id
    WHERE n.idioma_id = ?
");
$filas->execute([$lsc]);
$ejercicios = $filas->fetchAll(PDO::FETCH_ASSOC);
$juegos = array_filter($ejercicios, fn($e) => preg_match('/^lsc-a1-u[56]-|^lsc-a1-u[1-4]-l\d+-e([6-9]|\d\d)$/', $e["clave"])
    && !in_array($e["clave"], ["lsc-a1-u1-l1-e6", "lsc-a1-u1-l2-e6"], true));
P::ver("el A1 de LSC trae los juegos de la fase 2 (73 ejercicios)", count($juegos) === 73);

// Todo ejercicio de LSC se puede abrir y guardar tal cual desde Admin > Ruta
$malos = [];
foreach ($ejercicios as $e) {
    $f = RutaModel::formularioDe($e) + ["tipo" => $e["tipo"], "enunciado" => $e["enunciado"], "orden" => $e["orden"], "activo" => $e["activo"]];
    if (in_array($e["tipo"], RutaModel::NECESITAN_VIDEO, true) && (string) $e["video_url"] === "") {
        $f["video_url"] = "https://youtu.be/dQw4w9WgXcQ";
    }
    $r = RutaModel::datosEjercicio($f);
    if (is_string($r) || json_decode($r["datos"], true) != json_decode($e["datos"], true)) {
        $malos[] = $e["clave"] . (is_string($r) ? ": " . $r : ": cambia al guardar");
    }
}
P::ver("todos los ejercicios de LSC pasan las reglas del formulario" . ($malos ? " (" . implode("; ", array_slice($malos, 0, 3)) . ")" : ""), !$malos);

// Lo que se suma a lecciones publicadas entra inactivo; lo nuevo, sin publicar
$agregados = array_filter($juegos, fn($e) => preg_match('/^lsc-a1-u[1-4]-/', $e["clave"]));
P::ver("los juegos en lecciones publicadas entran inactivos", $agregados && !array_filter($agregados, fn($e) => (int) $e["activo"] === 1));
$nuevos = array_filter($juegos, fn($e) => preg_match('/^lsc-a1-u[56]-/', $e["clave"]));
P::ver("las unidades y lecciones nuevas quedan sin publicar", $nuevos && !array_filter($nuevos, fn($e) => (int) $e["leccion_publicada"] === 1 || (int) $e["unidad_publicada"] === 1));

// Seña y deletreo sin video: no se juegan
$sinVideo = array_filter($juegos, fn($e) => in_array($e["tipo"], RutaModel::NECESITAN_VIDEO, true));
P::ver("seña y deletreo van sin video", $sinVideo && !array_filter($sinVideo, fn($e) => (string) $e["video_url"] !== ""));
$jugables = $c->query("SELECT e.clave FROM ruta_ejercicios e WHERE e.clave LIKE 'lsc-a1-u5-l1-%' AND " . RutaModel::condicionJugable("e"))->fetchAll(PDO::FETCH_COLUMN);
P::ver("en una lección nueva solo se juegan los que no esperan video", count($jugables) === 4 && !in_array("lsc-a1-u5-l1-e3", $jugables, true));

// Una lección nueva, una vez publicada, se puede jugar de principio a fin
$leccionId = (int) $c->query("SELECT id FROM ruta_lecciones WHERE clave = 'lsc-a1-u6-l1'")->fetchColumn();
$ejs = RutaModel::ejercicios($c, $leccionId, true);
$bien = count($ejs) > 0;
foreach ($ejs as $e) {
    $bien = $bien && RutaModel::calificar($e, P::respuestaCorrecta($e))["correcto"] === true;
}
P::ver("los juegos de «Animales de la casa» se califican bien con la respuesta correcta", $bien);

// ---------- A2: el nivel sigue oculto, así que lo nuevo entra activo y publicado ----------
$a2 = array_filter($ejercicios, fn($e) => $e["nivel"] === "A2");
$juegosA2 = array_filter($a2, fn($e) => preg_match('/^lsc-a2-u[56]-|^lsc-a2-u[1-4]-l\d+-e([6-9]|\d\d)$/', $e["clave"]));
P::ver("el A2 de LSC trae los juegos de la fase 2 (82 ejercicios)", count($juegosA2) === 82);
$nivelA2 = $c->prepare("SELECT publicado FROM ruta_niveles WHERE idioma_id = ? AND codigo = 'A2'");
$nivelA2->execute([$lsc]);
P::ver("el nivel A2 de LSC sigue sin publicar", (string) $nivelA2->fetchColumn() === "0");
P::ver("los juegos del A2 entran activos y en lecciones publicadas", !array_filter($juegosA2, fn($e) => (int) $e["activo"] !== 1 || (int) $e["leccion_publicada"] !== 1 || (int) $e["unidad_publicada"] !== 1));
P::ver("el repaso de la unidad 4 del A2 cambia de nombre", $c->query("SELECT titulo FROM ruta_lecciones WHERE clave = 'lsc-a2-u4-l3'")->fetchColumn() === "Repaso: unidades 1 a 4");

// Cada lección del A2 tiene al menos 4 ejercicios que se juegan sin esperar video
$cortas = $c->query("
    SELECT l.clave FROM ruta_lecciones l
    WHERE l.clave LIKE 'lsc-a2-%'
      AND (SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND " . RutaModel::condicionJugable("e") . ") < 4
")->fetchAll(PDO::FETCH_COLUMN);
$totalA2 = (int) $c->query("SELECT COUNT(*) FROM ruta_lecciones WHERE clave LIKE 'lsc-a2-%'")->fetchColumn();
P::ver("las 20 lecciones del A2 tienen al menos 4 ejercicios jugables" . ($cortas ? " (" . implode(", ", $cortas) . ")" : ""), $totalA2 === 20 && !$cortas);

$leccionId = (int) $c->query("SELECT id FROM ruta_lecciones WHERE clave = 'lsc-a2-u6-l3'")->fetchColumn();
$ejs = RutaModel::ejercicios($c, $leccionId, true);
$bien = count($ejs) === 4;
foreach ($ejs as $e) {
    $bien = $bien && RutaModel::calificar($e, P::respuestaCorrecta($e))["correcto"] === true;
}
P::ver("los juegos de «Los medios de transporte» se califican bien con la respuesta correcta", $bien);