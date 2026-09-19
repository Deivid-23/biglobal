<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: usuarios.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$apellido = trim($_POST["apellido"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$password = $_POST["password"] ?? "";
$rol = $_POST["rol"] ?? "";
$estado = $_POST["estado"] ?? "Activo";


/* =========================
   VALIDACIONES
========================= */

if (
    empty($nombre) ||
    empty($apellido) ||
    empty($correo) ||
    empty($password) ||
    empty($rol)
) {
    die("Todos los campos son obligatorios.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}


/* =========================
   CONEXIÓN
========================= */

$conexion = Conexion::conectar();


try {

    /* =========================
       BUSCAR ROL
    ========================= */

    $consultaRol = $conexion->prepare("
        SELECT id
        FROM roles
        WHERE nombre = :rol
        LIMIT 1
    ");

    $consultaRol->execute([
        ":rol" => $rol
    ]);

    $rolEncontrado = $consultaRol->fetch();


    if (!$rolEncontrado) {
        die("El rol seleccionado no existe.");
    }


    /* =========================
       VERIFICAR CORREO
    ========================= */

    $consultaCorreo = $conexion->prepare("
        SELECT id
        FROM usuarios
        WHERE correo = :correo
        LIMIT 1
    ");

    $consultaCorreo->execute([
        ":correo" => $correo
    ]);


    if ($consultaCorreo->fetch()) {
        die("Ya existe un usuario con ese correo.");
    }


    /* =========================
       ENCRIPTAR CONTRASEÑA
    ========================= */

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    /* =========================
       INSERTAR USUARIO
    ========================= */

    $conexion->beginTransaction();


    $insertarUsuario = $conexion->prepare("
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

    $insertarUsuario->execute([
        ":nombre" => $nombre,
        ":apellido" => $apellido,
        ":correo" => $correo,
        ":password" => $passwordHash,
        ":rol_id" => $rolEncontrado["id"],
        ":estado" => $estado
    ]);


    $conexion->commit();


    /* =========================
       VOLVER A USUARIOS
    ========================= */

    header("Location: usuarios.php?creado=1");
    exit;


} catch (PDOException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    die("Error al crear el usuario: " . $e->getMessage());
}