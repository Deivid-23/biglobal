<?php

/**
 * Genera el hash de una contraseña para insertar manualmente
 * un usuario (por ejemplo, el primer administrador) en la
 * base de datos.
 *
 * Uso (línea de comandos):
 *   php tools/generar_hash.php "TuContraseñaSegura"
 *
 * A diferencia de la versión anterior de este script, la
 * contraseña ya NO queda escrita en el código: se recibe como
 * argumento y no se guarda en ningún archivo.
 */

if (php_sapi_name() !== "cli") {
    http_response_code(403);
    die("Esta herramienta solo puede ejecutarse desde la línea de comandos.");
}

$password = $argv[1] ?? null;

if (!$password) {
    echo "Uso: php tools/generar_hash.php \"TuContraseñaSegura\"\n";
    exit(1);
}

echo password_hash($password, PASSWORD_DEFAULT) . "\n";
