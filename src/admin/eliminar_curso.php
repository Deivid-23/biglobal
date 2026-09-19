<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cursos.php");
    exit;
}

$id = $_POST["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: cursos.php");
    exit;
}

$conexion = Conexion::conectar();

try {

    $stmt = $conexion->prepare("
        DELETE FROM cursos
        WHERE id = :id
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    header("Location: cursos.php?eliminado=1");
    exit;

} catch (PDOException $e) {

    die("Error al eliminar el curso: " . $e->getMessage());

}