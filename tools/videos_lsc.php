<?php
/**
 * Escribe la lista de videos del INSOR que faltan en un nivel de LSC:
 *
 *   php tools/videos_lsc.php b1     ->  database/lsc_videos_b1.csv
 *
 * Una fila por ejercicio «Ver la seña» o «Deletreo» sin video, con lo que
 * debe mostrar el video y una columna vacía para pegar el enlace.
 * Separador ";" y UTF-8 con BOM (se abre bien en Excel). Es el mismo CSV
 * que se descarga en Admin › Ruta › Videos de LSC (VideosLscModel).
 *
 * Sale SIEMPRE de la base LOCAL biglobal_prueba (nunca de TiDB): primero
 * hay que ejecutar ahí el SQL del nivel.
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

require_once __DIR__ . "/../app/Models/VideosLscModel.php";

const BASE = "biglobal_prueba";

$nivel = strtolower(trim((string) ($argv[1] ?? "")));
if (!preg_match('/^[abc][12]$/', $nivel)) {
    fwrite(STDERR, "Uso: php tools/videos_lsc.php b1\n");
    exit(1);
}

$c = new PDO("mysql:host=127.0.0.1;port=3306;dbname=" . BASE . ";charset=utf8mb4", "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$filas = VideosLscModel::pendientes($c, $nivel);
if (!$filas) {
    fwrite(STDERR, "No hay ejercicios de LSC " . strtoupper($nivel) . " esperando video en " . BASE . ".\n");
    exit(1);
}

file_put_contents(__DIR__ . "/../database/lsc_videos_" . $nivel . ".csv", VideosLscModel::csv($filas));
printf("Listo: database/lsc_videos_%s.csv (%d videos por conseguir).\n", $nivel, count($filas));
