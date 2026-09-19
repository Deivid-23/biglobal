<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();


/* =========================================================
   OBTENER ID DE LA LECCIÓN
========================================================= */

$leccionId = $_GET["leccion"] ?? null;

if (!$leccionId || !is_numeric($leccionId)) {
    header("Location: lecciones.php");
    exit;
}


/* =========================================================
   OBTENER LECCIÓN + CURSO
========================================================= */

$stmtLeccion = $conexion->prepare("
    SELECT
        l.id,
        l.curso_id,
        l.titulo,
        l.descripcion,
        l.estado,
        c.nombre AS curso_nombre
    FROM lecciones l

    INNER JOIN cursos c
        ON l.curso_id = c.id

    WHERE l.id = :id

    LIMIT 1
");

$stmtLeccion->execute([
    ":id" => $leccionId
]);

$leccion = $stmtLeccion->fetch();

if (!$leccion) {
    die("La lección no existe.");
}


/* =========================================================
   OBTENER ACTIVIDADES
========================================================= */

$stmtActividades = $conexion->prepare("
    SELECT
        id,
        titulo,
        tipo,
        contenido,
        orden,
        estado,
        fecha_creacion
    FROM actividades

    WHERE leccion_id = :leccion_id

    ORDER BY orden ASC, id ASC
");

$stmtActividades->execute([
    ":leccion_id" => $leccionId
]);

$actividades = $stmtActividades->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        BiGlobal | Actividades
    </title>

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


<div class="activities-page">


    <!-- =====================================================
         CABECERA
    ====================================================== -->

    <div class="activities-header">


        <!-- VOLVER -->

        <a
            href="lecciones.php?curso=<?= urlencode($leccion["curso_id"]) ?>"
            class="activities-back"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Volver a lecciones

        </a>


        <!-- INFORMACIÓN DE LA LECCIÓN -->

        <div class="activities-heading">


            <div>

                <span class="activities-course">

                    <i class="fa-solid fa-book"></i>

                    <?= htmlspecialchars(
                        $leccion["curso_nombre"]
                    ) ?>

                </span>


                <h1>

                    <?= htmlspecialchars(
                        $leccion["titulo"]
                    ) ?>

                </h1>


                <p>

                    <?= htmlspecialchars(
                        $leccion["descripcion"]
                        ?: "Esta lección todavía no tiene descripción."
                    ) ?>

                </p>

            </div>


            <a
                href="guardar_actividad.php?leccion=<?= $leccionId ?>"
                class="add-activity-btn"
            >

                <i class="fa-solid fa-plus"></i>

                Nueva actividad

            </a>

        </div>

    </div>


    <!-- =====================================================
         CONTENIDO
    ====================================================== -->

    <main class="activities-content">


        <?php if (count($actividades) > 0): ?>


            <?php foreach ($actividades as $actividad): ?>


                <?php

                $tipo = strtolower(
                    trim($actividad["tipo"] ?? "")
                );


                switch ($tipo) {

                    case "quiz":
                        $icono = "fa-circle-question";
                        $claseIcono = "quiz";
                        break;

                    case "audio":
                    case "escucha":
                        $icono = "fa-headphones";
                        $claseIcono = "audio";
                        break;

                    case "video":
                        $icono = "fa-video";
                        $claseIcono = "video";
                        break;

                    case "lectura":
                        $icono = "fa-book-open";
                        $claseIcono = "lectura";
                        break;

                    case "ejercicio":
                    case "escritura":
                    case "completar":
                        $icono = "fa-pen";
                        $claseIcono = "ejercicio";
                        break;

                    default:
                        $icono = "fa-list-check";
                        $claseIcono = "general";
                        break;
                }

                ?>


                <article class="activity-card">


                    <!-- ORDEN -->

                    <div class="activity-number">

                        <?= str_pad(
                            $actividad["orden"],
                            2,
                            "0",
                            STR_PAD_LEFT
                        ) ?>

                    </div>


                    <!-- ICONO -->

                    <div class="activity-type-icon <?= $claseIcono ?>">

                        <i class="fa-solid <?= $icono ?>"></i>

                    </div>


                    <!-- INFORMACIÓN -->

                    <div class="activity-info">


                        <span class="activity-type">

                            <?= htmlspecialchars(
                                $actividad["tipo"]
                            ) ?>

                        </span>


                        <h2>

                            <?= htmlspecialchars(
                                $actividad["titulo"]
                            ) ?>

                        </h2>


                        <p>

                            <?= htmlspecialchars(
                                $actividad["contenido"]
                                ?: "Esta actividad todavía no tiene contenido."
                            ) ?>

                        </p>


                        <div class="activity-details">


                            <?php if ($actividad["estado"] === "Publicado"): ?>

                                <span class="activity-status published">
                                    <i class="fa-solid fa-circle-check"></i>
                                    Publicada
                                </span>

                            <?php elseif ($actividad["estado"] === "Inactivo"): ?>

                                <span class="activity-status inactive">
                                    <i class="fa-solid fa-circle-pause"></i>
                                    Inactiva
                                </span>

                            <?php else: ?>

                                <span class="activity-status draft">
                                    <i class="fa-solid fa-file-pen"></i>
                                    Borrador
                                </span>

                            <?php endif; ?>


                            <span class="activity-order">

                                Actividad
                                <?= (int)$actividad["orden"] ?>

                            </span>

                        </div>

                    </div>


                    <!-- ACCIONES -->

                    <div class="activity-actions">


                        <a
                            href="editar_actividad.php?id=<?= $actividad["id"] ?>"
                            class="activity-action edit"
                            title="Editar actividad"
                        >

                            <i class="fa-solid fa-pen"></i>

                        </a>


                        <form
                            action="eliminar_actividad.php"
                            method="POST"
                            onsubmit="return confirmarEliminacionActividad();"
                        >

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $actividad["id"] ?>"
                            >


                            <button
                                type="submit"
                                class="activity-action delete"
                                title="Eliminar actividad"
                            >

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </form>

                    </div>


                </article>


            <?php endforeach; ?>


        <?php else: ?>


            <!-- =================================================
                 SIN ACTIVIDADES
            ================================================== -->

            <div class="activities-empty">


                <div class="activities-empty-icon">

                    <i class="fa-solid fa-list-check"></i>

                </div>


                <h2>
                    Aún no hay actividades
                </h2>


                <p>
                    Esta lección todavía no tiene actividades.
                    Crea la primera para comenzar a construir
                    el contenido.
                </p>


                <a
                    href="guardar_actividad.php?leccion=<?= $leccionId ?>"
                    class="add-activity-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Crear primera actividad

                </a>

            </div>


        <?php endif; ?>


    </main>


</div>


<script>

function confirmarEliminacionActividad() {

    return confirm(
        "¿Estás seguro de que deseas eliminar esta actividad?"
    );

}

</script>


</body>
</html>