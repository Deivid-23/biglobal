<?php

/**
 * Inicia la sesión PHP con cookies seguras.
 *
 * - httponly: JavaScript no puede leer la cookie (protege contra XSS).
 * - samesite=Lax: el navegador no la manda en formularios de otros sitios.
 * - secure: solo viaja por HTTPS (se activa sola en Render).
 *
 * Todos los archivos que necesitan sesión incluyen este archivo
 * en lugar de llamar a session_start() directamente.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {

    // Render entrega HTTPS a través de un proxy: lo detectamos por cabecera
    $esHttps = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
        || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");

    session_set_cookie_params([
        "lifetime" => 0,
        "path"     => "/",
        "secure"   => $esHttps,
        "httponly" => true,
        "samesite" => "Lax",
    ]);

    session_start();
}

/**
 * Cierra la sesión por completo: borra los datos, la cookie y la
 * sesión del servidor. La usan logout.php (POST con CSRF) y las
 * páginas que deben sacar a alguien (cuenta borrada o desactivada).
 */
if (!function_exists("cerrarSesion")) {
    function cerrarSesion(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $p = session_get_cookie_params();
            setcookie(session_name(), "", time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
