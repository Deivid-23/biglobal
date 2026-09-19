<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once "../bd/conexion.php";
require_once "csrf.php";

verificarCsrf($_POST["csrf_token"] ?? "");

/* =========================
   LIMITE DE INTENTOS
========================= */

$maxIntentos = 5;
$tiempoBloqueoSegundos = 60;

if (!isset($_SESSION["login_intentos"])) {
    $_SESSION["login_intentos"] = 0;
}

if (
    isset($_SESSION["login_bloqueado_hasta"]) &&
    time() < $_SESSION["login_bloqueado_hasta"]
) {

    $segundosRestantes = $_SESSION["login_bloqueado_hasta"] - time();

    echo json_encode([
        "success" => false,
        "message" => "Demasiados intentos. Inténtalo de nuevo en {$segundosRestantes} segundos."
    ]);

    exit;
}

try {

    $conexion = Conexion::conectar();

    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";


    /* =========================
       VALIDACIONES
    ========================= */

    if (empty($correo) || empty($password)) {

        echo json_encode([
            "success" => false,
            "message" => "Correo y contraseña son obligatorios."
        ]);

        exit;
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        echo json_encode([
            "success" => false,
            "message" => "El correo electrónico no es válido."
        ]);

        exit;
    }


    /* =========================
       BUSCAR USUARIO
    ========================= */

    $sql = "
        SELECT
            u.id,
            u.nombre,
            u.apellido,
            u.correo,
            u.password,
            u.rol_id,
            u.estado,
            r.nombre AS rol
        FROM usuarios u
        INNER JOIN roles r
            ON u.rol_id = r.id
        WHERE u.correo = :correo
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":correo" => $correo
    ]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


    /* =========================
       VERIFICAR USUARIO
    ========================= */

    if (!$usuario) {

        echo json_encode([
            "success" => false,
            "message" => "El correo o la contraseña son incorrectos."
        ]);

        exit;
    }


    /* =========================
       VERIFICAR ESTADO
    ========================= */

    if (($usuario["estado"] ?? "") !== "Activo") {

        echo json_encode([
            "success" => false,
            "message" => "Tu cuenta está inactiva. Contacta al administrador."
        ]);

        exit;
    }


    /* =========================
       VERIFICAR CONTRASEÑA
    ========================= */

    if (!password_verify($password, $usuario["password"])) {

        $_SESSION["login_intentos"]++;

        if ($_SESSION["login_intentos"] >= $maxIntentos) {
            $_SESSION["login_bloqueado_hasta"] = time() + $tiempoBloqueoSegundos;
            $_SESSION["login_intentos"] = 0;
        }

        echo json_encode([
            "success" => false,
            "message" => "El correo o la contraseña son incorrectos."
        ]);

        exit;
    }


    /* =========================
       GUARDAR SESIÓN
    ========================= */

    $_SESSION["login_intentos"] = 0;
    unset($_SESSION["login_bloqueado_hasta"]);

    session_regenerate_id(true);

    $_SESSION["usuario_id"] = $usuario["id"];
    $_SESSION["nombre"] = $usuario["nombre"];
    $_SESSION["apellido"] = $usuario["apellido"];
    $_SESSION["correo"] = $usuario["correo"];
    $_SESSION["rol_id"] = $usuario["rol_id"];
    $_SESSION["rol"] = $usuario["rol"];


    /* =========================
       DEFINIR REDIRECCIÓN
    ========================= */

    $redireccion = "../index.html";

    switch (strtolower($usuario["rol"])) {

        case "admin":
        case "administrador":
            $redireccion = "../admin/index.php";
            break;

        case "instructor":
            $redireccion = "../profesor/index.php";
            break;

        case "estudiante":
        case "usuario":
            $redireccion = "../estudiante/index.php";
            break;

        default:
            $redireccion = "../index.html";
            break;
    }


    /* =========================
       RESPUESTA
    ========================= */

    echo json_encode([
        "success" => true,
        "message" => "Inicio de sesión correcto.",
        "redirect" => $redireccion
    ]);

    exit;


} catch (PDOException $e) {

    echo json_encode([
        "success" => false,
        "message" => "Error de conexión con la base de datos.",
        "error" => $e->getMessage()
    ]);

    exit;
}