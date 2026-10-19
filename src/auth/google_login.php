<?php

/**
 * Inicia el flujo OAuth con Google.
 * Redirige al usuario a la pantalla de consentimiento de Google.
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../config/config.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = obtenerConfig();

$clientId    = $config["google"]["client_id"];
$redirectUri = $config["google"]["redirect_uri"];

if (empty($clientId)) {
    // Falta GOOGLE_CLIENT_ID en el .env o en Render: avisamos sin mostrar detalles
    error_log("[BiGlobal][google_login] Falta GOOGLE_CLIENT_ID");
    header("Location: login.html?error=google_no_config");
    exit;
}

/* =========================================================
   Generar state (protección CSRF)
========================================================= */
$state = bin2hex(random_bytes(16));
$_SESSION["google_oauth_state"] = $state;

/* =========================================================
   Construir URL de autorización de Google
========================================================= */
$params = [
    "client_id"     => $clientId,
    "redirect_uri"  => $redirectUri,
    "response_type" => "code",
    "scope"         => "openid email profile",
    "state"         => $state,
    "access_type"   => "online",
    "prompt"        => "select_account",
];

$urlGoogle = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);

header("Location: " . $urlGoogle);
exit;