<?php

/**
 * Callback de Google OAuth.
 * Google nos envía aquí un "code" que intercambiamos por un access_token,
 * luego pedimos los datos del usuario (email, nombre, foto).
 */

require_once __DIR__ . "/../../app/bootstrap.php";
require_once __DIR__ . "/../../config/config.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = obtenerConfig();

$clientId     = $config["google"]["client_id"];
$clientSecret = $config["google"]["client_secret"];
$redirectUri  = $config["google"]["redirect_uri"];

/* =========================================================
   1. Verificar el state (CSRF)
========================================================= */
$stateRecibido = $_GET["state"] ?? "";
$stateGuardado = $_SESSION["google_oauth_state"] ?? "";

unset($_SESSION["google_oauth_state"]);

if (empty($stateRecibido) || empty($stateGuardado) || !hash_equals($stateGuardado, $stateRecibido)) {
    header("Location: login.html?error=state_invalido");
    exit;
}

/* =========================================================
   2. Verificar que Google no haya devuelto error
========================================================= */
if (isset($_GET["error"])) {
    header("Location: login.html?error=acceso_cancelado");
    exit;
}

$code = $_GET["code"] ?? "";

if (empty($code)) {
    header("Location: login.html?error=sin_codigo");
    exit;
}

/* =========================================================
   3. Intercambiar el code por un access_token
========================================================= */
$tokenUrl = "https://oauth2.googleapis.com/token";

$tokenData = [
    "code"          => $code,
    "client_id"     => $clientId,
    "client_secret" => $clientSecret,
    "redirect_uri"  => $redirectUri,
    "grant_type"    => "authorization_code",
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($tokenData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/x-www-form-urlencoded"],
    CURLOPT_TIMEOUT        => 15,
]);
$respuesta   = curl_exec($ch);
$codigoHttp  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$errorCurl   = curl_error($ch);
curl_close($ch);

if ($respuesta === false || $codigoHttp !== 200) {
    error_log("[BiGlobal][google_callback] Token error: " . $errorCurl . " | HTTP " . $codigoHttp . " | " . $respuesta);
    header("Location: login.html?error=token_invalido");
    exit;
}

$tokenJson = json_decode($respuesta, true);
$accessToken = $tokenJson["access_token"] ?? null;

if (!$accessToken) {
    header("Location: login.html?error=sin_access_token");
    exit;
}

/* =========================================================
   4. Pedir los datos del usuario a Google
========================================================= */
$userInfoUrl = "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . urlencode($accessToken);

$ch = curl_init($userInfoUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$respuesta  = curl_exec($ch);
$codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($respuesta === false || $codigoHttp !== 200) {
    error_log("[BiGlobal][google_callback] Userinfo error: HTTP " . $codigoHttp);
    header("Location: login.html?error=userinfo_fallido");
    exit;
}

$usuarioGoogle = json_decode($respuesta, true);

if (empty($usuarioGoogle["id"]) || empty($usuarioGoogle["email"])) {
    header("Location: login.html?error=datos_incompletos");
    exit;
}

/* Solo aceptamos correos que Google ya verificó: así nadie puede
   "adueñarse" de una cuenta existente usando un correo falso. */
if (isset($usuarioGoogle["verified_email"]) && $usuarioGoogle["verified_email"] !== true) {
    header("Location: login.html?error=datos_incompletos");
    exit;
}

/* =========================================================
   5. Buscar en la BD si ya existe (por google_id o por correo)
========================================================= */
$conexion = Conexion::conectar();

/* 5a. Buscar por google_id */
$stmt = $conexion->prepare("
    SELECT u.id, u.nombre, u.apellido, u.correo, u.rol_id, u.activo, r.nombre AS rol
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE u.google_id = :gid
    LIMIT 1
");
$stmt->execute([":gid" => $usuarioGoogle["id"]]);
$usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

/* 5b. Si no, buscar por correo (por si se registró antes con email) */
if (!$usuarioExistente) {
    $stmt = $conexion->prepare("
        SELECT u.id, u.nombre, u.apellido, u.correo, u.rol_id, u.activo, r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r ON u.rol_id = r.id
        WHERE u.correo = :correo
        LIMIT 1
    ");
    $stmt->execute([":correo" => $usuarioGoogle["email"]]);
    $usuarioExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    /* Si existe por correo, vincular la cuenta con Google */
    if ($usuarioExistente) {
        $stmt = $conexion->prepare("
            UPDATE usuarios
            SET google_id = :gid, google_avatar = :avatar
            WHERE id = :id
        ");
        $stmt->execute([
            ":gid"    => $usuarioGoogle["id"],
            ":avatar" => $usuarioGoogle["picture"] ?? null,
            ":id"     => $usuarioExistente["id"],
        ]);
    }
}

/* =========================================================
   6. Si el usuario ya existe → iniciar sesión
========================================================= */
if ($usuarioExistente) {

    $activo = (int) $usuarioExistente["activo"];

    /* Cuenta bloqueada */
    if ($activo === 0) {
        header("Location: login.html?error=cuenta_inactiva");
        exit;
    }

    session_regenerate_id(true);

    $_SESSION["usuario_id"] = $usuarioExistente["id"];
    $_SESSION["nombre"]     = $usuarioExistente["nombre"];
    $_SESSION["apellido"]   = $usuarioExistente["apellido"];
    $_SESSION["correo"]     = $usuarioExistente["correo"];
    $_SESSION["rol_id"]     = $usuarioExistente["rol_id"];
    $_SESSION["rol"]        = $usuarioExistente["rol"];

    /* Docente pendiente */
    if ($activo === 2) {
        header("Location: ../profesor/pendiente.php");
        exit;
    }

    /* Redirigir según rol */
    switch (strtolower($usuarioExistente["rol"])) {
        case "admin":
        case "administrador":
            header("Location: ../admin/index.php");
            exit;
        case "docente":
        case "instructor":
            header("Location: ../profesor/index.php");
            exit;
        case "estudiante":
        case "usuario":
            header("Location: ../estudiante/index.php");
            exit;
        default:
            header("Location: ../index.html");
            exit;
    }
}

/* =========================================================
   7. Usuario nuevo → guardar datos en sesión y pedir rol
========================================================= */
$_SESSION["google_nuevo"] = [
    "google_id" => $usuarioGoogle["id"],
    "correo"    => $usuarioGoogle["email"],
    "nombre"    => $usuarioGoogle["given_name"]  ?? $usuarioGoogle["name"] ?? "Usuario",
    "apellido"  => $usuarioGoogle["family_name"] ?? "No especificado",
    "avatar"    => $usuarioGoogle["picture"]     ?? null,
];

header("Location: elegir_rol.php");
exit;