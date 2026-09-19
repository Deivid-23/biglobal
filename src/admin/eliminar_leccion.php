<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: lecciones.php");
    exit;
}

$id = $_POST["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: lecciones.php");
    exit;
}

$conexion = Conexion::conectar();

try {

    $stmt = $conexion->prepare("
        DELETE FROM lecciones
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: lecciones.php?eliminado=1");
    exit;

} catch (PDOException $e) {

    die(
        "Error al eliminar la lección: " .
        $e->getMessage()
    );
}