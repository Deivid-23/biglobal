<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: usuarios.php");
    exit;
}

$id = $_POST["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: usuarios.php");
    exit;
}

$conexion = Conexion::conectar();

try {

    $conexion->beginTransaction();

    $stmtUsuario = $conexion->prepare("
        DELETE FROM usuarios
        WHERE id = :id
    ");

    $stmtUsuario->execute([
        ":id" => $id
    ]);


    $conexion->commit();

    header("Location: usuarios.php?eliminado=1");
    exit;


} catch (PDOException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    die("Error al eliminar usuario: " . $e->getMessage());
}