<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();


/* =========================================================
   OBTENER ID DE LA LECCIÓN
========================================================= */

$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: lecciones.php");
    exit;
}


/* =========================================================
   OBTENER LECCIÓN
========================================================= */

$stmt = $conexion->prepare("
    SELECT
        id,
        curso_id,
        titulo,
        descripcion,
        orden,
        estado
    FROM lecciones
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    ":id" => $id
]);

$leccion = $stmt->fetch();

if (!$leccion) {
    die("La lección no existe.");
}


/* =========================================================
   OBTENER CURSOS
========================================================= */

$stmtCursos = $conexion->prepare("
    SELECT
        id,
        nombre
    FROM cursos
    ORDER BY nombre ASC
");

$stmtCursos->execute();

$cursos = $stmtCursos->fetchAll();


/* =========================================================
   ACTUALIZAR LECCIÓN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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


        if (!$stmtCurso->fetch()) {
            die("El curso seleccionado no existe.");
        }


        /* =========================
           ACTUALIZAR
        ========================= */

        $stmtUpdate = $conexion->prepare("
            UPDATE lecciones

            SET
                curso_id = :curso_id,
                titulo = :titulo,
                descripcion = :descripcion,
                orden = :orden,
                estado = :estado

            WHERE id = :id
        ");


        $stmtUpdate->execute([

            ":curso_id" => $cursoId,
            ":titulo" => $titulo,
            ":descripcion" => $descripcion,
            ":orden" => $orden,
            ":estado" => $estado,
            ":id" => $id

        ]);


        /* =========================
           VOLVER
        ========================= */

        header(
            "Location: lecciones.php?curso=" .
            urlencode($cursoId) .
            "&editado=1"
        );

        exit;


    } catch (PDOException $e) {

        die(
            "Error al actualizar la lección: " .
            $e->getMessage()
        );
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>BiGlobal | Editar lección</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Manrope:wght@300;400;600;800&display=swap"
        rel="stylesheet"
    >

</head>

<body>

<div class="admin-container">


    <main
        class="main-content"
        style="width:100%; margin-left:0;"
    >


        <!-- =================================================
             TOPBAR
        ================================================== -->

        <header class="topbar">

            <div class="topbar-left">

                <a
                    href="lecciones.php"
                    class="menu-toggle"
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:center;
                    "
                >

                    <i class="fa-solid fa-arrow-left"></i>

                </a>


                <div>

                    <p class="panel-label">
                        Gestión académica
                    </p>

                    <h1>
                        Editar lección
                    </h1>

                </div>

            </div>

        </header>


        <!-- =================================================
             FORMULARIO
        ================================================== -->

        <section class="users-page">

            <div
                class="users-card"
                style="
                    max-width:700px;
                    margin:auto;
                    padding:25px;
                "
            >


                <div style="margin-bottom:25px;">

                    <span class="section-label">
                        Contenido educativo
                    </span>

                    <h2 style="font-size:23px;">
                        Editar lección
                    </h2>

                    <p
                        style="
                            color:#667085;
                            font-size:13px;
                            margin-top:6px;
                        "
                    >
                        Modifica la información de la lección seleccionada.
                    </p>

                </div>


                <form method="POST">


                    <!-- CURSO -->

                    <div class="form-group">

                        <label for="curso_id">
                            Curso
                        </label>

                        <select
                            id="curso_id"
                            name="curso_id"
                            required
                        >

                            <option value="">
                                Seleccionar curso
                            </option>


                            <?php foreach ($cursos as $curso): ?>

                                <option
                                    value="<?= $curso["id"] ?>"
                                    <?= (string)$leccion["curso_id"] === (string)$curso["id"] ? "selected" : "" ?>
                                >

                                    <?= htmlspecialchars(
                                        $curso["nombre"]
                                    ) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- TÍTULO -->

                    <div class="form-group">

                        <label for="titulo">
                            Título de la lección
                        </label>

                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            value="<?= htmlspecialchars($leccion["titulo"]) ?>"
                            placeholder="Ej. Saludos y presentaciones"
                            required
                        >

                    </div>


                    <!-- DESCRIPCIÓN -->

                    <div class="form-group">

                        <label for="descripcion">
                            Descripción
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            placeholder="Describe el contenido de la lección..."
                        ><?= htmlspecialchars($leccion["descripcion"] ?? "") ?></textarea>

                    </div>


                    <!-- ORDEN + ESTADO -->

                    <div class="form-row">


                        <div class="form-group">

                            <label for="orden">
                                Orden
                            </label>

                            <input
                                type="number"
                                id="orden"
                                name="orden"
                                min="1"
                                value="<?= (int)$leccion["orden"] ?>"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="estado">
                                Estado
                            </label>

                            <select
                                id="estado"
                                name="estado"
                            >

                                <option
                                    value="Borrador"
                                    <?= $leccion["estado"] === "Borrador" ? "selected" : "" ?>
                                >
                                    Borrador
                                </option>

                                <option
                                    value="Publicado"
                                    <?= $leccion["estado"] === "Publicado" ? "selected" : "" ?>
                                >
                                    Publicada
                                </option>

                                <option
                                    value="Inactivo"
                                    <?= $leccion["estado"] === "Inactivo" ? "selected" : "" ?>
                                >
                                    Inactiva
                                </option>

                            </select>

                        </div>

                    </div>


                    <!-- BOTONES -->

                    <div class="modal-actions">

                        <a
                            href="lecciones.php"
                            class="cancel-btn"
                        >

                            Cancelar

                        </a>


                        <button
                            type="submit"
                            class="save-btn"
                        >

                            <i class="fa-solid fa-floppy-disk"></i>

                            Guardar cambios

                        </button>

                    </div>


                </form>

            </div>

        </section>

    </main>

</div>

</body>

</html>