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

$stmtBuscar = $conexion->prepare("SELECT leccion_id FROM actividades WHERE id = :id");
$stmtBuscar->execute([":id" => $id]);
$actividad = $stmtBuscar->fetch();

if (!$actividad) {
    header("Location: lecciones.php");
    exit;
}

try {

    $stmt = $conexion->prepare("DELETE FROM actividades WHERE id = :id");
    $stmt->execute([":id" => $id]);

    header("Location: actividades.php?leccion=" . urlencode($actividad["leccion_id"]) . "&eliminado=1");
    exit;

} catch (PDOException $e) {
    die("Error al eliminar la actividad: " . $e->getMessage());
}
