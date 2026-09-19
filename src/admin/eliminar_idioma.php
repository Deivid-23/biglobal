<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: idiomas.php");
    exit;
}

$id = $_POST["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: idiomas.php");
    exit;
}

$conexion = Conexion::conectar();

try {

    $stmt = $conexion->prepare("
        DELETE FROM idiomas
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: idiomas.php?eliminado=1");
    exit;

} catch (PDOException $e) {

    die("Error al eliminar el idioma: " . $e->getMessage());

}