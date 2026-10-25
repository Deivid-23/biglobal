<?php
/**
 * Escribe la lista de videos del INSOR que faltan en un nivel de LSC:
 *
 *   php tools/videos_lsc.php b1     ->  database/lsc_videos_b1.csv
 *
 * Una fila por ejercicio «Ver la seña» o «Deletreo» sin video, con lo que
 * debe mostrar el video y una columna vacía para pegar el enlace.
 * Separador ";" y UTF-8 con BOM (se abre bien en Excel).
 *
 * Sale SIEMPRE de la base LOCAL biglobal_prueba (nunca de TiDB): primero
 * hay que ejecutar ahí el SQL del nivel.
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

const BASE = "biglobal_prueba";

$nivel = strtolower(trim((string) ($argv[1] ?? "")));
if (!preg_match('/^[abc][12]$/', $nivel)) {
    fwrite(STDERR, "Uso: php tools/videos_lsc.php b1\n");
    exit(1);
}

$c = new PDO("mysql:host=127.0.0.1;port=3306;dbname=" . BASE . ";charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $c->prepare("
    SELECT e.clave, e.tipo, e.datos, e.activo, l.titulo AS leccion, l.publicado AS leccion_publicada,
           u.titulo AS unidad, u.publicado AS unidad_publicada, n.publicado AS nivel_publicado
    FROM ruta_ejercicios e
    INNER JOIN ruta_lecciones l ON l.id = e.leccion_id
    INNER JOIN ruta_unidades u ON u.id = l.unidad_id
    INNER JOIN ruta_niveles n ON n.id = u.nivel_id
    INNER JOIN idiomas i ON i.id = n.idioma_id
    WHERE i.codigo = 'LSC' AND n.codigo = ? AND e.tipo IN ('sena', 'deletreo') AND COALESCE(e.video_url, '') = ''
    ORDER BY u.orden, l.orden, e.orden
");
$stmt->execute([strtoupper($nivel)]);
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$filas) {
    fwrite(STDERR, "No hay ejercicios de LSC " . strtoupper($nivel) . " esperando video en " . BASE . ".\n");
    exit(1);
}

$estado = function (array $r): string {
    if (!(int) $r["nivel_publicado"]) {
        return "nivel sin publicar";
    }
    if (!(int) $r["leccion_publicada"] || !(int) $r["unidad_publicada"]) {
        return "lección nueva (sin publicar)";
    }
    return (int) $r["activo"] ? "lección publicada" : "lección publicada, ejercicio nuevo (inactivo)";
};

$salida = __DIR__ . "/../database/lsc_videos_" . $nivel . ".csv";
$f = fopen($salida, "w");
fwrite($f, "\xEF\xBB\xBF");
fputcsv($f, ["clave", "unidad", "leccion", "tipo", "el video debe mostrar", "opciones que ve el estudiante", "estado", "enlace del video (INSOR)"], ";");
foreach ($filas as $r) {
    $d = json_decode($r["datos"], true);
    if ($r["tipo"] === "sena") {
        $muestra = "La seña de: " . $d["opciones"][(int) $d["correcta"]];
        $opciones = implode(" | ", $d["opciones"]);
        $tipo = "Ver la seña";
    } else {
        $muestra = "Deletreo con el alfabeto manual de: " . mb_strtoupper($d["respuestas"][0]);
        $opciones = "(el estudiante escribe la palabra)";
        $tipo = "Deletreo";
    }
    fputcsv($f, [$r["clave"], $r["unidad"], $r["leccion"], $tipo, $muestra, $opciones, $estado($r), ""], ";");
}
fclose($f);
printf("Listo: database/lsc_videos_%s.csv (%d videos por conseguir).\n", $nivel, count($filas));
