<?php
/**
 * Contenido de LSC (fase 2, database/lsc_*_juegos.sql): todo ejercicio pasa
 * las reglas del formulario del admin, lo nuevo queda en borrador y lo que
 * espera video no se muestra.
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
