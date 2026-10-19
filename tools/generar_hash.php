<?php

/**
 * Genera el hash de una contraseña para crear un admin a mano en la BD.
 * Solo se usa desde la terminal (no está en la carpeta pública).
 *
 * Uso:
 *   php tools/generar_hash.php "TuContraseñaSegura"
 */

if (PHP_SAPI !== "cli") {
    exit("Este script solo funciona desde la terminal.\n");
}

$password = $argv[1] ?? "";

if ($password === "") {
    exit("Uso: php tools/generar_hash.php \"TuContraseñaSegura\"\n");
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
