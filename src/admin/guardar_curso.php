<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cursos.php");
    exit;
}

$nombre = trim($_POST["nombre"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");
$nivel = trim($_POST["nivel"] ?? "");
$estado = $_POST["estado"] ?? "Borrador";
$instructor_id = $_POST["instructor_id"] ?? "";


/* =========================
   VALIDACIONES
========================= */

if (empty($nombre) || empty($nivel)) {
    die("El nombre y el nivel del curso son obligatorios.");
}

$nivelesPermitidos = [
    "Básico",
    "Intermedio",
    "Avanzado"
];

$estadosPermitidos = [
    "Borrador",
    "Publicado",
    "Inactivo"
];

if (!in_array($nivel, $nivelesPermitidos, true)) {
    die("El nivel seleccionado no es válido.");
}

if (!in_array($estado, $estadosPermitidos, true)) {
    die("El estado seleccionado no es válido.");
}


/* =========================
   CONEXIÓN
========================= */

$conexion = Conexion::conectar();


try {

    /* =========================
       VALIDAR INSTRUCTOR
    ========================= */

    if ($instructor_id !== "") {

        if (!is_numeric($instructor_id)) {
            die("El instructor seleccionado no es válido.");
        }

        $stmtInstructor = $conexion->prepare("
            SELECT u.id
            FROM usuarios u
            INNER JOIN roles r
                ON u.rol_id = r.id
            WHERE
                u.id = :id
                AND r.nombre = 'Instructor'
                AND u.estado = 'Activo'
            LIMIT 1
        ");

        $stmtInstructor->execute([
            ":id" => $instructor_id
        ]);

        if (!$stmtInstructor->fetch()) {
            die("El instructor seleccionado no existe o no está activo.");
        }

    } else {

        $instructor_id = null;

    }


    /* =========================
       GUARDAR CURSO
    ========================= */

    $stmt = $conexion->prepare("
        INSERT INTO cursos (
            nombre,
            descripcion,
            nivel,
            estado,
            instructor_id
        )
        VALUES (
            :nombre,
            :descripcion,
            :nivel,
            :estado,
            :instructor_id
        )
    ");

    $stmt->execute([
        ":nombre" => $nombre,
        ":descripcion" => $descripcion,
        ":nivel" => $nivel,
        ":estado" => $estado,
        ":instructor_id" => $instructor_id
    ]);


    header("Location: cursos.php?creado=1");
    exit;


} catch (PDOException $e) {

    die("Error al crear el curso: " . $e->getMessage());

}