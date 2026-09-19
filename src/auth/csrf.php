<?php

/**
 * Protección CSRF ligera para los formularios públicos de
 * autenticación (login y registro).
 *
 * Uso:
 *   - GET  /auth/csrf_token.php   -> devuelve un token nuevo
 *   - El frontend lo manda de vuelta como "csrf_token" en el
 *     POST de login/registro.
 *   - login.php / registro.php llaman a verificarCsrf(...)
 *     antes de procesar cualquier dato del formulario.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function generarCsrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

/**
 * Verifica el token recibido. Si no coincide, responde con un
 * JSON de error (mismo formato que ya usan login.php/registro.php)
 * y termina la ejecución.
 */
function verificarCsrf(string $tokenRecibido): void
{
    $tokenValido = $_SESSION["csrf_token"] ?? null;

    if (
        !$tokenValido ||
        !hash_equals($tokenValido, $tokenRecibido)
    ) {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            "success" => false,
            "message" => "Tu sesión de formulario expiró. Recarga la página e inténtalo de nuevo."
        ]);

        exit;
    }
}
