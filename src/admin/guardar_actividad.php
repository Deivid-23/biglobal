<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();


/* =========================================================
   OBTENER LECCIÓN
========================================================= */

$leccionId = $_GET["leccion"] ?? $_POST["leccion_id"] ?? null;

if (!$leccionId || !is_numeric($leccionId)) {
    header("Location: lecciones.php");
    exit;
}

$stmtLeccion = $conexion->prepare("
    SELECT
        l.id,
        l.titulo,
        c.nombre AS curso_nombre
    FROM lecciones l
    INNER JOIN cursos c
        ON l.curso_id = c.id
    WHERE l.id = :id
    LIMIT 1
");

$stmtLeccion->execute([":id" => $leccionId]);

$leccion = $stmtLeccion->fetch();

if (!$leccion) {
    die("La lección no existe.");
}


/* =========================================================
   GUARDAR ACTIVIDAD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulo = trim($_POST["titulo"] ?? "");
    $tipo = trim($_POST["tipo"] ?? "");
    $contenido = trim($_POST["contenido"] ?? "");
    $orden = $_POST["orden"] ?? 1;
    $estado = $_POST["estado"] ?? "Borrador";

    $estadosPermitidos = ["Borrador", "Publicado", "Inactivo"];

    if (empty($titulo) || empty($tipo) || !is_numeric($orden)) {
        die("El título, el tipo y el orden son obligatorios.");
    }

    if (!in_array($estado, $estadosPermitidos, true)) {
        die("El estado seleccionado no es válido.");
    }

    try {

        $stmt = $conexion->prepare("
            INSERT INTO actividades (
                leccion_id,
                titulo,
                tipo,
                contenido,
                orden,
                estado
            )
            VALUES (
                :leccion_id,
                :titulo,
                :tipo,
                :contenido,
                :orden,
                :estado
            )
        ");

        $stmt->execute([
            ":leccion_id" => $leccionId,
            ":titulo" => $titulo,
            ":tipo" => $tipo,
            ":contenido" => $contenido,
            ":orden" => $orden,
            ":estado" => $estado
        ]);

        header("Location: actividades.php?leccion=" . urlencode($leccionId) . "&creado=1");
        exit;

    } catch (PDOException $e) {
        die("Error al crear la actividad: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Nueva actividad</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="admin-container">

    <main class="main-content" style="width: 100%; margin-left: 0;">

        <header class="topbar">
            <div class="topbar-left">
                <a href="actividades.php?leccion=<?= urlencode($leccionId) ?>"
                   class="menu-toggle"
                   style="display:flex; align-items:center; justify-content:center;">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <p class="panel-label">
                        <?= htmlspecialchars($leccion["curso_nombre"]) ?> · <?= htmlspecialchars($leccion["titulo"]) ?>
                    </p>
                    <h1>Nueva actividad</h1>
                </div>
            </div>
        </header>

        <section class="users-page">

            <div class="users-card" style="max-width:700px; margin:auto; padding:25px;">

                <form method="POST">

                    <input type="hidden" name="leccion_id" value="<?= $leccionId ?>">

                    <div class="form-group">
                        <label for="titulo">Título de la actividad</label>
                        <input type="text" id="titulo" name="titulo" placeholder="Ej. Quiz de vocabulario" required>
                    </div>

                    <div class="form-row">

                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="Quiz">Quiz</option>
                                <option value="Lectura">Lectura</option>
                                <option value="Audio">Audio</option>
                                <option value="Video">Video</option>
                                <option value="Ejercicio">Ejercicio</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="orden">Orden</label>
                            <input type="number" id="orden" name="orden" min="1" value="1" required>
                        </div>

                    </div>

                    <div class="form-group">
                        <label for="contenido">Contenido</label>
                        <textarea
                            id="contenido"
                            name="contenido"
                            rows="5"
                            placeholder="Instrucciones, pregunta, enlace del recurso, etc."
                            style="width:100%; border:1px solid #d0d5dd; border-radius:9px; padding:12px; resize:vertical; font-family:inherit; font-size:12px; outline:none;"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado">
                            <option value="Borrador">Borrador</option>
                            <option value="Publicado">Publicada</option>
                            <option value="Inactivo">Inactiva</option>
                        </select>
                    </div>

                    <div class="modal-actions" style="margin-top:25px;">
                        <a href="actividades.php?leccion=<?= urlencode($leccionId) ?>" class="cancel-btn">Cancelar</a>
                        <button type="submit" class="save-btn">
                            <i class="fa-solid fa-plus"></i>
                            Crear actividad
                        </button>
                    </div>

                </form>

            </div>

        </section>

    </main>

</div>

</body>
</html>
