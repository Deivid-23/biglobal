<?php

header('Content-Type: application/json; charset=utf-8');

require_once "../bd/conexion.php";
require_once "csrf.php";

verificarCsrf($_POST["csrf_token"] ?? "");

try {

    $conexion = Conexion::conectar();

    /* =========================
       DATOS
    ========================= */

    $nombreCompleto = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";


    /* =========================
       VALIDACIONES
    ========================= */

    if (
        empty($nombreCompleto) ||
        empty($correo) ||
        empty($password) ||
        empty($confirmPassword)
    ) {

        echo json_encode([
            "success" => false,
            "message" => "Todos los campos son obligatorios."
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


    if ($password !== $confirmPassword) {

        echo json_encode([
            "success" => false,
            "message" => "Las contraseñas no coinciden."
        ]);

        exit;
    }


    if (strlen($password) < 6) {

        echo json_encode([
            "success" => false,
            "message" => "La contraseña debe tener al menos 6 caracteres."
        ]);

        exit;
    }


    /* =========================
       SEPARAR NOMBRE Y APELLIDO
    ========================= */

    $partesNombre = preg_split(
        '/\s+/',
        $nombreCompleto,
        2
    );

    $nombre = $partesNombre[0];

    $apellido = $partesNombre[1] ?? "No especificado";


    /* =========================
       VERIFICAR CORREO
    ========================= */

    $stmt = $conexion->prepare("
        SELECT id
        FROM usuarios
        WHERE correo = :correo
        LIMIT 1
    ");

    $stmt->execute([
        ":correo" => $correo
    ]);

    if ($stmt->fetch()) {

        echo json_encode([
            "success" => false,
            "message" => "Ya existe una cuenta con ese correo."
        ]);

        exit;
    }


    /* =========================
       ENCRIPTAR CONTRASEÑA
    ========================= */

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /* =========================
       CREAR USUARIO
    ========================= */

    $stmtUsuario = $conexion->prepare("
        INSERT INTO usuarios (
            nombre,
            apellido,
            correo,
            password,
            rol_id,
            estado
        )
        VALUES (
            :nombre,
            :apellido,
            :correo,
            :password,
            2,
            'Activo'
        )
    ");

    $stmtUsuario->execute([

        ":nombre" => $nombre,
        ":apellido" => $apellido,
        ":correo" => $correo,
        ":password" => $passwordHash

    ]);


    /* =========================
       RESPUESTA
    ========================= */

    echo json_encode([
        "success" => true,
        "message" => "Cuenta creada correctamente.",
        "redirect" => "login.html"
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