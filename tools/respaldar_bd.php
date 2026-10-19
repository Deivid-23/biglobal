<?php

/**
 * Hace un respaldo completo (estructura + datos) de la base configurada
 * en el .env y lo guarda en respaldos/ (esa carpeta no se sube a git).
 *
 * Sirve también con TiDB, donde no siempre tenemos mysqldump a mano.
 *
 * Uso (desde la raíz del proyecto):
 *   php tools/respaldar_bd.php
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

require_once __DIR__ . "/../src/bd/conexion.php";

$pdo = Conexion::conectar();
$nombreBd = obtenerConfig()["db"]["nombre"];

$carpeta = __DIR__ . "/../respaldos";
if (!is_dir($carpeta)) {
    mkdir($carpeta, 0700, true);
}

$archivo = $carpeta . "/respaldo_" . $nombreBd . "_" . date("Y-m-d_His") . ".sql";
$salida = fopen($archivo, "w");

fwrite($salida, "-- Respaldo de {$nombreBd} hecho el " . date("Y-m-d H:i:s") . "\n");
fwrite($salida, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

$tablas = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
$totalFilas = 0;

foreach ($tablas as $tabla) {
    $crear = $pdo->query("SHOW CREATE TABLE `{$tabla}`")->fetch(PDO::FETCH_NUM)[1];
    fwrite($salida, "DROP TABLE IF EXISTS `{$tabla}`;\n{$crear};\n\n");

    $filas = $pdo->query("SELECT * FROM `{$tabla}`");
    $n = 0;

    while ($fila = $filas->fetch(PDO::FETCH_ASSOC)) {
        $columnas = "`" . implode("`, `", array_keys($fila)) . "`";
        $valores = array_map(fn($v) => $v === null ? "NULL" : $pdo->quote((string) $v), array_values($fila));
        fwrite($salida, "INSERT INTO `{$tabla}` ({$columnas}) VALUES (" . implode(", ", $valores) . ");\n");
        $n++;
    }

    fwrite($salida, "\n");
    echo str_pad($tabla, 24) . " {$n} filas\n";
    $totalFilas += $n;
}

fwrite($salida, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($salida);

echo "\n✅ Respaldo listo: " . count($tablas) . " tablas, {$totalFilas} filas\n";
echo "Archivo: respaldos/" . basename($archivo) . "\n";
