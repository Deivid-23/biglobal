<?php

/**
 * Ejecuta un archivo .sql contra la base configurada en el .env
 * (sirve para TiDB cuando no tienes el cliente mysql a mano).
 *
 * Uso (desde la raíz del proyecto):
 *   php tools/ejecutar_sql.php database/archivo.sql              -> solo muestra qué haría
 *   php tools/ejecutar_sql.php database/archivo.sql --confirmar  -> lo ejecuta de verdad
 *
 * Se detiene en el primer error y te dice en qué sentencia falló.
 * Haz siempre un respaldo antes: php tools/respaldar_bd.php
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

require_once __DIR__ . "/../src/bd/conexion.php";

$archivo = $argv[1] ?? "";
$confirmar = in_array("--confirmar", $argv, true);

if ($archivo === "" || !is_file($archivo)) {
    exit("Uso: php tools/ejecutar_sql.php database/archivo.sql [--confirmar]\n");
}

/* Quitamos los comentarios (-- ...) y separamos por ";" al final de línea */
$lineas = array_filter(
    file($archivo, FILE_IGNORE_NEW_LINES),
    fn($l) => !str_starts_with(ltrim($l), "--")
);
$sentencias = array_values(array_filter(
    array_map("trim", preg_split('/;\s*$/m', implode("\n", $lineas))),
    fn($s) => $s !== ""
));

$db = obtenerConfig()["db"];
echo "Archivo   : {$archivo}\n";
echo "Base      : {$db['nombre']} (puerto {$db['port']})\n";
echo "Sentencias: " . count($sentencias) . "\n\n";

if (!$confirmar) {
    foreach ($sentencias as $i => $s) {
        echo str_pad((string) ($i + 1), 3, " ", STR_PAD_LEFT) . ". " . strtok($s, "\n") . "\n";
    }
    exit("\nNo se ejecutó nada. Para ejecutarlo agrega --confirmar\n");
}

$pdo = Conexion::conectar();

foreach ($sentencias as $i => $s) {
    try {
        $pdo->exec($s);
        echo "✔ " . str_pad((string) ($i + 1), 3, " ", STR_PAD_LEFT) . ". " . strtok($s, "\n") . "\n";
    } catch (PDOException $e) {
        echo "\n❌ Falló la sentencia " . ($i + 1) . ":\n{$s}\n\nError: " . $e->getMessage() . "\n";
        exit("Se detuvo aquí. Lo anterior sí quedó aplicado.\n");
    }
}

echo "\n✅ Listo: " . count($sentencias) . " sentencias ejecutadas.\n";
