<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: idiomas.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$codigo = strtoupper(trim($_POST["codigo"] ?? ""));
$estado = $_POST["estado"] ?? "Activo";


/* =========================
   VALIDACIONES
========================= */

if (empty($nombre) || empty($codigo)) {
    die("El nombre y el código son obligatorios.");
}

if (!in_array($estado, ["Activo", "Inactivo"], true)) {
    die("El estado seleccionado no es válido.");
}


/* =========================
   CONEXIÓN
========================= */

$conexion = Conexion::conectar();


try {

    /* =========================
       VERIFICAR IDIOMA
    ========================== */

    $stmt = $conexion->prepare("
        SELECT id
        FROM idiomas
        WHERE nombre = :nombre
           OR codigo = :codigo
        LIMIT 1
    ");

    $stmt->execute([
        ":nombre" => $nombre,
        ":codigo" => $codigo
    ]);

    if ($stmt->fetch()) {
        die("Ya existe un idioma con ese nombre o código.");
    }


    /* =========================
       INSERTAR
    ========================== */

    $stmt = $conexion->prepare("
        INSERT INTO idiomas (
            nombre,
            codigo,
            estado
        )
        VALUES (
            :nombre,
            :codigo,
            :estado
        )
    ");

    $stmt->execute([
        ":nombre" => $nombre,
        ":codigo" => $codigo,
        ":estado" => $estado
    ]);


    /* =========================
       VOLVER
    ========================== */

    header("Location: idiomas.php?creado=1");
    exit;


} catch (PDOException $e) {

    die("Error al crear el idioma: " . $e->getMessage());

}