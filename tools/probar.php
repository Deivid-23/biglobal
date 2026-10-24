<?php
/**
 * Ejecuta las pruebas automáticas de tests/ contra la base LOCAL biglobal_prueba.
 *
 * Uso (desde la raíz del proyecto, con el MySQL de XAMPP encendido):
 *   php tools/probar.php            -> todas
 *   php tools/probar.php racha      -> solo los archivos cuyo nombre contiene "racha"
 *
 * Seguridad: fija las variables DB_ hacia el MySQL local (127.0.0.1, root) y se
 * NIEGA a correr si la base conectada no es biglobal_prueba. Nunca escribe en TiDB.
 * PRUEBAS_DB=biglobal_prueba_ci permite otra base, pero solo con ese prefijo
 * (la usa GitHub Actions: .github/workflows/pruebas.yml).
 * Cada prueba crea sus propios usuarios temporales y los borra al terminar.
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

// Siempre una base local de pruebas (aunque el .env apunte a producción).
// PRUEBAS_DB permite otra, pero solo si se llama biglobal_prueba_algo (ej. biglobal_prueba_ci).
$nombreBase = getenv("PRUEBAS_DB") ?: "biglobal_prueba";
if (!preg_match('/^biglobal_prueba(_[a-z0-9]+)?$/', $nombreBase)) {
    fwrite(STDERR, "ALTO: PRUEBAS_DB debe llamarse biglobal_prueba o biglobal_prueba_algo. No se ejecuta nada.\n");
    exit(2);
}
foreach (["DB_HOST" => "127.0.0.1", "DB_PORT" => "3306", "DB_NAME" => $nombreBase, "DB_USER" => "root", "DB_PASS" => "", "DB_SSL" => "false"] as $k => $v) {
    putenv("$k=$v");
    $_ENV[$k] = $v;
}

$raiz = dirname(__DIR__);
chdir($raiz);
require $raiz . "/app/bootstrap.php";
foreach (glob($raiz . "/app/Models/*.php") as $modelo) {
    require_once $modelo;
}
require $raiz . "/tests/_lib.php";

try {
    $c = Conexion::conectar();
    $base = (string) $c->query("SELECT DATABASE()")->fetchColumn();
} catch (Throwable $e) {
    fwrite(STDERR, "No hay conexión con el MySQL local (¿está encendido XAMPP?): " . $e->getMessage() . "\n");
    exit(2);
}
if ($base !== $nombreBase) {
    fwrite(STDERR, "ALTO: la base conectada es «{$base}», no {$nombreBase}. No se ejecuta nada.\n");
    exit(2);
}

$filtro = $argv[1] ?? "";
$archivos = array_filter(glob($raiz . "/tests/*.php"), fn($f) => basename($f)[0] !== "_" && ($filtro === "" || str_contains(basename($f), $filtro)));
if (!$archivos) {
    fwrite(STDERR, "No hay pruebas" . ($filtro !== "" ? " que contengan «{$filtro}»" : "") . ".\n");
    exit(2);
}

echo "Base: {$base}\n\n";
$inicio = microtime(true);
foreach ($archivos as $archivo) {
    P::$archivo = basename($archivo, ".php");
    $antesBien = P::$bien;
    $antesMal = P::$mal;
    $_SESSION = [];
    echo "· " . P::$archivo . "\n";
    try {
        (function () use ($archivo, $c) {
            require $archivo;
        })();
    } catch (Throwable $e) {
        P::$mal++;
        P::$fallos[] = P::$archivo . ": se detuvo con un error: " . $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")";
        echo "    ERROR " . $e->getMessage() . " (" . basename($e->getFile()) . ":" . $e->getLine() . ")\n";
    } finally {
        P::limpiar($c);
    }
    echo "    " . (P::$bien - $antesBien) . " bien" . (P::$mal - $antesMal ? ", " . (P::$mal - $antesMal) . " mal" : "") . "\n";
}

printf("\n%d bien, %d mal (%.1f s)\n", P::$bien, P::$mal, microtime(true) - $inicio);
if (P::$fallos) {
    echo "\nFallaron:\n  - " . implode("\n  - ", P::$fallos) . "\n";
    exit(1);
}
exit(0);
