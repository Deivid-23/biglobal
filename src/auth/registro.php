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
    $rolSolicitado = $_POST["rol"] ?? "estudiante";

    /* Whitelist: cualquier valor que no sea exactamente uno de
       estos dos se trata como "estudiante" (nunca se confia en
       lo que mande el formulario para decidir permisos). */
    if ($rolSolicitado !== "profesor") {
        $rolSolicitado = "estudiante";
    }


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
       ROL Y ESTADO INICIAL
    ========================= */

    if ($rolSolicitado === "profesor") {
        $rolId = 3;            // Instructor (database/schema.sql)
        $estadoInicial = "Pendiente";
    } else {
        $rolId = 2;            // Estudiante
        $estadoInicial = "Activo";
    }


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
            :rol_id,
            :estado
        )
    ");

    $stmtUsuario->execute([

        ":nombre" => $nombre,
        ":apellido" => $apellido,
        ":correo" => $correo,
        ":password" => $passwordHash,
        ":rol_id" => $rolId,
        ":estado" => $estadoInicial

    ]);


    /* =========================
       RESPUESTA
    ========================= */

    if ($estadoInicial === "Pendiente") {

        // Sin "redirect": el frontend muestra el mensaje en vez de
        // redirigir en silencio, porque todavia no puede iniciar sesion.
        echo json_encode([
            "success" => true,
            "message" => "Tu solicitud como profesor fue enviada. Un administrador debe aprobarla antes de que puedas iniciar sesión."
        ]);

        exit;
    }

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
