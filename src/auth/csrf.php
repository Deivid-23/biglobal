<?php

/**
 * Protección CSRF (que otro sitio no pueda enviar formularios
 * en nombre del usuario).
 *
 * Uso:
 *   - Formularios PHP:   <?= campoCsrf() ?> dentro del <form>
 *                        y verificarCsrfFormulario($_POST["csrf_token"] ?? "")
 *   - Login/registro (fetch): GET /auth/csrf_token.php entrega el token
 *                        y verificarCsrf(...) responde en JSON si falla.
 */

require_once __DIR__ . "/sesion.php";

function generarCsrfToken(): string
{
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }

    return $_SESSION["csrf_token"];
}

/** Devuelve el <input hidden> listo para pegar dentro de un formulario. */
function campoCsrf(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(generarCsrfToken(), ENT_QUOTES, "UTF-8") . '">';
}

/** Compara el token recibido con el de la sesión (en tiempo constante). */
function csrfValido(string $tokenRecibido): bool
{
    $tokenValido = $_SESSION["csrf_token"] ?? "";
    return $tokenValido !== "" && hash_equals($tokenValido, $tokenRecibido);
}

/**
 * Para peticiones AJAX (login y registro): si el token no coincide
 * responde JSON con error y termina.
 */
function verificarCsrf(string $tokenRecibido): void
{
    if (!csrfValido($tokenRecibido)) {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Tu sesión de formulario expiró. Recarga la página e inténtalo de nuevo."
        ]);
        exit;
    }
}

/**
 * Para formularios normales (no AJAX): corta con un mensaje simple.
 */
function verificarCsrfFormulario(string $tokenRecibido): void
{
    if (!csrfValido($tokenRecibido)) {
        http_response_code(419);
        die("Token de seguridad inválido o expirado. Vuelve atrás y recarga la página.");
    }
}
