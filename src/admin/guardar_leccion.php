<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: lecciones.php");
    exit;
}


/* =========================
   RECIBIR DATOS
========================= */

$cursoId = $_POST["curso_id"] ?? "";
$titulo = trim($_POST["titulo"] ?? "");
$descripcion = trim($_POST["descripcion"] ?? "");
$orden = $_POST["orden"] ?? 1;
$estado = $_POST["estado"] ?? "Borrador";


/* =========================
   VALIDACIONES
========================= */

if (
    empty($cursoId) ||
    empty($titulo) ||
    empty($orden)
) {
    die("El curso, el título y el orden son obligatorios.");
}

if (
    !is_numeric($cursoId) ||
    !is_numeric($orden)
) {
    die("El curso o el orden no son válidos.");
}


$estadosPermitidos = [
    "Borrador",
    "Publicado",
    "Inactivo"
];

if (!in_array($estado, $estadosPermitidos, true)) {

    die("El estado seleccionado no es válido.");
}


/* =========================
   CONEXIÓN
========================= */

$conexion = Conexion::conectar();


try {

    /* =========================
       COMPROBAR CURSO
    ========================= */

    $stmtCurso = $conexion->prepare("
        SELECT id
        FROM cursos
        WHERE id = :id
        LIMIT 1
    ");

    $stmtCurso->execute([
        ":id" => $cursoId
    ]);

    $curso = $stmtCurso->fetch();


    if (!$curso) {

        die("El curso seleccionado no existe.");

    }


    /* =========================
       INSERTAR LECCIÓN
    ========================= */

    $stmt = $conexion->prepare("
        INSERT INTO lecciones (
            curso_id,
            titulo,
            descripcion,
            orden,
            estado
        )
        VALUES (
            :curso_id,
            :titulo,
            :descripcion,
            :orden,
            :estado
        )
    ");


    $stmt->execute([

        ":curso_id" => $cursoId,
        ":titulo" => $titulo,
        ":descripcion" => $descripcion,
        ":orden" => $orden,
        ":estado" => $estado

    ]);


    /* =========================
       VOLVER A LECCIONES
    ========================= */

    header(
        "Location: lecciones.php?curso=" .
        urlencode($cursoId) .
        "&creado=1"
    );

    exit;


} catch (PDOException $e) {

    die(
        "Error al crear la lección: " .
        $e->getMessage()
    );

}