<?php
/**
 * Genera tests/base_pruebas.sql: la base con la que corren las pruebas en
 * GitHub Actions (.github/workflows/pruebas.yml).
 *
 * Uso (desde la raíz, con el MySQL de XAMPP encendido):
 *   php tools/generar_base_pruebas.php
 *
 * - Sale SIEMPRE de la base LOCAL biglobal_prueba (nunca de TiDB).
 * - Estructura de todas las tablas, pero DATOS solo del contenido de la
 *   plataforma (TABLAS_CON_DATOS): roles, idiomas, la ruta e insignias.
 *   Nada de usuarios, progreso, pagos, avisos ni configuración.
 * - Antes de guardar revisa que no haya ningún correo; si hay, no guarda.
 *
 * Volver a generarla cuando cambie la estructura (un SQL nuevo en database/).
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

const BASE = "biglobal_prueba";
const TABLAS_CON_DATOS = ["roles", "idiomas", "ruta_niveles", "ruta_unidades", "ruta_lecciones", "ruta_ejercicios", "medallas"];

$raiz = dirname(__DIR__);
$salida = $raiz . "/tests/base_pruebas.sql";
$mysqldump = getenv("MYSQLDUMP") ?: (is_file("C:/xampp/mysql/bin/mysqldump.exe") ? "C:/xampp/mysql/bin/mysqldump.exe" : "mysqldump");
$comun = "--host=127.0.0.1 --port=3306 --user=root --default-character-set=utf8mb4 --skip-comments --skip-triggers --skip-lock-tables";

$correr = function (string $argumentos) use ($mysqldump, $comun): string {
    $comando = escapeshellarg($mysqldump) . " $comun $argumentos 2>&1";
    exec($comando, $lineas, $codigo);
    $texto = implode("\n", $lineas);
    if ($codigo !== 0) {
        fwrite(STDERR, "mysqldump falló (código $codigo):\n$texto\n");
        exit(2);
    }
    return $texto;
};

// 1. Estructura de todas las tablas (sin los contadores AUTO_INCREMENT)
$estructura = $correr("--no-data " . BASE);
$estructura = preg_replace('/ AUTO_INCREMENT=\d+/', "", $estructura);

// 2. Datos solo del contenido de la plataforma
$datos = $correr("--no-create-info --complete-insert " . BASE . " " . implode(" ", TABLAS_CON_DATOS));

$sql = "-- =============================================================\n"
     . "-- BiGlobal · Base para las pruebas automáticas (GitHub Actions)\n"
     . "-- =============================================================\n"
     . "-- GENERADO con: php tools/generar_base_pruebas.php (no editar a mano).\n"
     . "-- Estructura de todas las tablas + datos de: " . implode(", ", TABLAS_CON_DATOS) . ".\n"
     . "-- Sin usuarios ni datos personales.\n"
     . "-- =============================================================\n\n"
     . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n"
     . $estructura . "\n\n" . $datos . "\n\nSET FOREIGN_KEY_CHECKS = 1;\n";

// 3. Revisión: ningún correo (si aparece uno, algo se coló y no se guarda)
if (preg_match_all('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', $sql, $m)) {
    fwrite(STDERR, "ALTO: el resultado tiene " . count($m[0]) . " correo(s), por ejemplo «" . $m[0][0] . "». No se guardó nada.\n");
    exit(2);
}
foreach (["usuarios", "suscripciones", "pagos", "notificaciones", "configuracion"] as $tabla) {
    if (preg_match("/INSERT INTO `$tabla`/", $sql)) {
        fwrite(STDERR, "ALTO: se coló información de la tabla $tabla. No se guardó nada.\n");
        exit(2);
    }
}

file_put_contents($salida, $sql);
printf("Listo: %s (%.1f MB, %d tablas, datos de %d).\n", "tests/base_pruebas.sql", strlen($sql) / 1048576,
    preg_match_all("/CREATE TABLE `/", $sql), count(TABLAS_CON_DATOS));
