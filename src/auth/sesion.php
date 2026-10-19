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
