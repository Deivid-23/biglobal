<?php

/**
 * Prueba la conexión a la base de datos con los datos del .env
 * (o de las variables de entorno). NO muestra usuario ni contraseña.
 *
 * Uso (desde la raíz del proyecto):
 *   php tools/probar_conexion.php
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

require_once __DIR__ . "/../config/config.php";

$db = obtenerConfig()["db"];

/* Modo diagnóstico: php tools/probar_conexion.php --diagnostico
   Revisa cómo se leyó el .env SIN mostrar la contraseña. */
if (in_array("--diagnostico", $argv, true)) {
    $rutaEnv = __DIR__ . "/../.env";
    echo "== Diagnóstico del .env ==\n";
    echo "Archivo .env  : " . (is_file($rutaEnv) ? "encontrado" : "NO ENCONTRADO en la raíz del proyecto") . "\n";

    if (is_file($rutaEnv)) {
        $claves = [];
        foreach (file($rutaEnv, FILE_IGNORE_NEW_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === "" || $linea[0] === "#" || !str_contains($linea, "=")) continue;
            $clave = trim(explode("=", $linea, 2)[0]);
            $claves[$clave] = ($claves[$clave] ?? 0) + 1;
        }
        foreach (["DB_HOST", "DB_USER", "DB_PASS", "DB_SSL"] as $c) {
            $n = $claves[$c] ?? 0;
            echo str_pad($c, 14) . ": " . ($n === 0 ? "NO ESTÁ" : ($n > 1 ? "repetida {$n} veces (vale la primera)" : "1 vez")) . "\n";
        }
        $bom = substr(file_get_contents($rutaEnv), 0, 3) === "\xEF\xBB\xBF";
        if ($bom) echo "Aviso         : el archivo empieza con BOM (guárdalo como UTF-8 sin BOM)\n";
    }

    $u = $db["usuario"];
    $p = $db["clave"];
    echo "Usuario       : {$u}\n";
    echo "Usuario OK    : " . (preg_match('/^[A-Za-z0-9]+\.root$/', $u) ? "sí (formato prefijo.root)" : "revisar formato") . "\n";
    echo "Largo clave   : " . strlen($p) . " caracteres\n";
    echo "Espacios      : " . ($p !== trim($p) ? "SÍ tiene espacios al inicio o final" : "no") . "\n";
    echo "Comillas      : " . (preg_match('/["\']/', $p) ? "SÍ tiene comillas dentro" : "no") . "\n";
    echo "No ASCII      : " . (preg_match('/[^\x20-\x7E]/', $p) ? "SÍ tiene caracteres raros (ej. espacio invisible)" : "no") . "\n\n";
}

echo "Base de datos : {$db['nombre']}\n";
echo "Puerto        : {$db['port']}\n";
echo "SSL activado  : " . ($db["ssl"] ? "sí" : "NO") . "\n";

if ($db["ssl"]) {
    echo "Certificado   : " . (is_file($db["ssl_ca"]) ? "encontrado" : "NO ENCONTRADO ({$db['ssl_ca']})") . "\n";
}

$dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['nombre']};charset={$db['charset']}";
$opciones = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10];

if ($db["ssl"]) {
    $opciones[PDO::MYSQL_ATTR_SSL_CA] = $db["ssl_ca"];
    $opciones[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
}

try {
    $pdo = new PDO($dsn, $db["usuario"], $db["clave"], $opciones);
} catch (PDOException $e) {
    // Quitamos de los mensajes cualquier rastro del usuario por si acaso
    $mensaje = str_replace([$db["usuario"], $db["clave"]], "***", $e->getMessage());
    exit("\n❌ No se pudo conectar: {$mensaje}\n");
}

echo "\n✅ Conexión correcta\n";
echo "Servidor      : " . $pdo->query("SELECT VERSION()")->fetchColumn() . "\n";

$cifrado = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM)[1] ?? "";
echo "Cifrado SSL   : " . ($cifrado !== "" ? $cifrado : "ninguno (conexión sin cifrar)") . "\n";

$tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tablas        : " . count($tablas) . "\n";

$revisar = [
    "usuarios"              => "tabla base",
    "recuperacion_password" => "recuperar contraseña",
    "estudiante_stats"      => "gamificación (database/gamificacion.sql)",
    "medallas"              => "gamificación (database/gamificacion.sql)",
];

foreach ($revisar as $tabla => $para) {
    echo "  " . (in_array($tabla, $tablas, true) ? "✔" : "✘ falta") . "  {$tabla} — {$para}\n";
}

if (in_array("usuarios", $tablas, true)) {
    echo "Usuarios      : " . $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() . "\n";
}

if (in_array("configuracion", $tablas, true)) {
    $acento = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'color_acento'")->fetchColumn();
    echo "color_acento  : " . ($acento ?: "(no existe)") . ($acento === "blue" ? "" : "  ← falta database/cambios_2026-10.sql") . "\n";
}
