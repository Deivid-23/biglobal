<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();


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
   CURSO SELECCIONADO
========================================================= */

$cursoSeleccionado = $_GET["curso"] ?? "";


/* =========================================================
   OBTENER LECCIONES
========================================================= */

if ($cursoSeleccionado !== "" && is_numeric($cursoSeleccionado)) {

    $stmtLecciones = $conexion->prepare("
        SELECT
            l.id,
            l.curso_id,
            l.titulo,
            l.descripcion,
            l.orden,
            l.estado,
            l.fecha_creacion,
            c.nombre AS curso_nombre,

            (
                SELECT COUNT(*)
                FROM actividades a
                WHERE a.leccion_id = l.id
            ) AS total_actividades

        FROM lecciones l

        INNER JOIN cursos c
            ON l.curso_id = c.id

        WHERE l.curso_id = :curso_id

        ORDER BY l.orden ASC, l.id ASC
    ");

    $stmtLecciones->execute([
        ":curso_id" => $cursoSeleccionado
    ]);

} else {

    $stmtLecciones = $conexion->prepare("
        SELECT
            l.id,
            l.curso_id,
            l.titulo,
            l.descripcion,
            l.orden,
            l.estado,
            l.fecha_creacion,
            c.nombre AS curso_nombre,

            (
                SELECT COUNT(*)
                FROM actividades a
                WHERE a.leccion_id = l.id
            ) AS total_actividades

        FROM lecciones l

        INNER JOIN cursos c
            ON l.curso_id = c.id

        ORDER BY c.nombre ASC, l.orden ASC, l.id ASC
    ");

    $stmtLecciones->execute();
}

$lecciones = $stmtLecciones->fetchAll();


/* =========================================================
   ESTADÍSTICAS
========================================================= */

$totalLecciones = count($lecciones);

$publicadas = 0;
$borradores = 0;
$inactivas = 0;
$totalActividades = 0;


foreach ($lecciones as $leccion) {

    if ($leccion["estado"] === "Publicado") {
        $publicadas++;
    }

    if ($leccion["estado"] === "Borrador") {
        $borradores++;
    }

    if ($leccion["estado"] === "Inactivo") {
        $inactivas++;
    }

    $totalActividades += (int) $leccion["total_actividades"];
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

    <title>BiGlobal | Lecciones y actividades</title>

   
    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

    <!-- Fuentes -->
    <link
        href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=Sora:wght@400;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- CSS Administrador -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="admin-container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <a
                href="index.php"
                class="logo-admin"
            >

                <img
                    src="../img/logo-Biglobal.png"
                    alt="BiGlobal"
                >

                <span>
                    BiGlobal
                </span>

            </a>

        </div>


        <nav class="sidebar-menu">

            <p class="menu-title">
                PANEL PRINCIPAL
            </p>

            <a
                href="index.php"
                class="menu-item"
            >

                <i class="fa-solid fa-house"></i>

                <span>
                    Inicio
                </span>

            </a>


            <p class="menu-title">
                GESTIÓN
            </p>


            <a
                href="usuarios.php"
                class="menu-item"
            >

                <i class="fa-solid fa-users"></i>

                <span>
                    Usuarios y roles
                </span>

            </a>


            <a
                href="cursos.php"
                class="menu-item"
            >

                <i class="fa-solid fa-book"></i>

                <span>
                    Catálogo de cursos
                </span>

            </a>


            <a
                href="idiomas.php"
                class="menu-item"
            >

                <i class="fa-solid fa-globe"></i>

                <span>
                    Idiomas
                </span>

            </a>


            <a
                href="lecciones.php"
                class="menu-item active"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>
                    Lecciones y actividades
                </span>

            </a>


            <p class="menu-title">
                CONTROL
            </p>


            <a
                href="reportes.php"
                class="menu-item"
            >

                <i class="fa-solid fa-chart-column"></i>

                <span>
                    Reportes
                </span>

            </a>


            <a
                href="errores.php"
                class="menu-item"
            >

                <i class="fa-solid fa-triangle-exclamation"></i>

                <span>
                    Reportes y errores
                </span>

            </a>


            <a
                href="moderacion.php"
                class="menu-item"
            >

                <i class="fa-solid fa-comments"></i>

                <span>
                    Moderación
                </span>

            </a>


            <a
                href="certificados.php"
                class="menu-item"
            >

                <i class="fa-solid fa-certificate"></i>

                <span>
                    Certificados
                </span>

            </a>


            <p class="menu-title">
                SISTEMA
            </p>


            <a
                href="configuracion.php"
                class="menu-item"
            >

                <i class="fa-solid fa-gear"></i>

                <span>
                    Configuración
                </span>

            </a>

        </nav>


        <div class="sidebar-footer">

            <a
                href="../index.html"
                class="back-home"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Volver al inicio

            </a>

        </div>

    </aside>


    <!-- =====================================================
         CONTENIDO PRINCIPAL
    ====================================================== -->

    <main class="main-content">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button
                    class="menu-toggle"
                    id="menuToggle"
                >

                    <i class="fa-solid fa-bars"></i>

                </button>


                <div>

                    <p class="panel-label">
                        Gestión académica
                    </p>

                    <h1>
                        Lecciones y actividades
                    </h1>

                </div>

            </div>


            <div class="topbar-right">

                <button
                    class="notification-button"
                    type="button"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-dot"></span>

                </button>


                <div class="admin-profile">

                    <div class="profile-avatar">
                        A
                    </div>


                    <div class="profile-info">

                        <strong>
                            Administrador
                        </strong>

                        <span>
                            Panel principal
                        </span>

                    </div>


                    <i
                        class="fa-solid fa-chevron-down profile-arrow"
                    ></i>

                </div>

            </div>

        </header>


        <!-- =====================================================
             CONTENIDO
        ====================================================== -->

        <section class="users-page">


            <!-- ENCABEZADO -->

            <div class="users-heading">

                <div>

                    <span class="section-label">
                        Contenido educativo
                    </span>

                    <h2>
                        Lecciones y actividades
                    </h2>

                    <p>
                        Organiza las lecciones de cada curso y supervisa
                        sus actividades.
                    </p>

                </div>


                <button
                    type="button"
                    class="add-user-btn"
                    id="openLessonModal"
                >

                    <i class="fa-solid fa-plus"></i>

                    Nueva lección

                </button>

            </div>


            <!-- =================================================
                 ESTADÍSTICAS
            ================================================== -->

            <div class="user-stats">


                <div class="user-stat-card">

                    <div class="user-stat-icon blue">

                        <i class="fa-solid fa-book-open"></i>

                    </div>

                    <div>

                        <span>
                            Total lecciones
                        </span>

                        <strong>
                            <?= $totalLecciones ?>
                        </strong>

                    </div>

                </div>


                <div class="user-stat-card">

                    <div class="user-stat-icon green">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>
                            Publicadas
                        </span>

                        <strong>
                            <?= $publicadas ?>
                        </strong>

                    </div>

                </div>


                <div class="user-stat-card">

                    <div class="user-stat-icon orange">

                        <i class="fa-solid fa-file-pen"></i>

                    </div>

                    <div>

                        <span>
                            Borradores
                        </span>

                        <strong>
                            <?= $borradores ?>
                        </strong>

                    </div>

                </div>


                <div class="user-stat-card">

                    <div class="user-stat-icon purple">

                        <i class="fa-solid fa-list-check"></i>

                    </div>

                    <div>

                        <span>
                            Actividades
                        </span>

                        <strong>
                            <?= $totalActividades ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CONTROLES
            ================================================== -->

            <div class="users-card lesson-filter-card">

                <div class="users-toolbar">

                    <div>

                        <h3>
                            Contenido del curso
                        </h3>

                        <span>
                            Selecciona un curso para ver sus lecciones
                        </span>

                    </div>


                    <form
                        method="GET"
                        class="course-selector"
                    >

                        <select
                            name="curso"
                            id="courseFilter"
                            onchange="this.form.submit()"
                        >

                            <option value="">
                                Todos los cursos
                            </option>


                            <?php foreach ($cursos as $curso): ?>

                                <option
                                    value="<?= $curso["id"] ?>"
                                    <?= (string)$cursoSeleccionado === (string)$curso["id"] ? "selected" : "" ?>
                                >

                                    <?= htmlspecialchars($curso["nombre"]) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </form>

                </div>

            </div>


            <!-- =================================================
                 LISTA DE LECCIONES
            ================================================== -->

            <div class="lesson-list">


                <?php if (count($lecciones) > 0): ?>


                    <?php foreach ($lecciones as $leccion): ?>


                        <div class="lesson-card">


                            <!-- NÚMERO -->

                            <div class="lesson-number">

                                <?= str_pad(
                                    $leccion["orden"],
                                    2,
                                    "0",
                                    STR_PAD_LEFT
                                ) ?>

                            </div>


                            <!-- INFORMACIÓN -->

                            <div class="lesson-info">

                                <div class="lesson-course">

                                    <i class="fa-solid fa-book"></i>

                                    <?= htmlspecialchars(
                                        $leccion["curso_nombre"]
                                    ) ?>

                                </div>


                                <h3>

                                    <?= htmlspecialchars(
                                        $leccion["titulo"]
                                    ) ?>

                                </h3>


                                <p>

                                    <?= htmlspecialchars(
                                        $leccion["descripcion"]
                                        ?: "Sin descripción disponible."
                                    ) ?>

                                </p>


                                <div class="lesson-meta">

                                    <span>

                                        <i class="fa-solid fa-list-check"></i>

                                        <?= (int)$leccion["total_actividades"] ?>

                                        actividades

                                    </span>


                                    <?php if ($leccion["estado"] === "Publicado"): ?>

                                        <span class="status active-status">
                                            Publicada
                                        </span>

                                    <?php elseif ($leccion["estado"] === "Inactivo"): ?>

                                        <span class="status inactive-status">
                                            Inactiva
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="status"
                                            style="
                                                background:#fff7ed;
                                                color:#f97316;
                                            "
                                        >
                                            Borrador
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <!-- ACCIONES -->

                            <div class="lesson-actions">


                                <a
                                    href="actividades.php?leccion=<?= $leccion["id"] ?>"
                                    class="lesson-action-btn"
                                    title="Ver actividades"
                                >

                                    <i class="fa-solid fa-list-check"></i>

                                </a>


                                <a
                                    href="editar_leccion.php?id=<?= $leccion["id"] ?>"
                                    class="lesson-action-btn edit"
                                    title="Editar"
                                >

                                    <i class="fa-solid fa-pen"></i>

                                </a>


                                <form
                                    action="eliminar_leccion.php"
                                    method="POST"
                                    onsubmit="return confirmarEliminacionLeccion();"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $leccion["id"] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="lesson-action-btn delete"
                                        title="Eliminar"
                                    >

                                        <i class="fa-solid fa-trash"></i>

                                    </button>

                                </form>

                            </div>

                        </div>


                    <?php endforeach; ?>


                <?php else: ?>


                    <div class="empty-lessons">

                        <div class="empty-lessons-icon">

                            <i class="fa-solid fa-book-open"></i>

                        </div>


                        <h3>
                            No hay lecciones todavía
                        </h3>


                        <p>
                            Crea la primera lección para comenzar
                            a construir el contenido del curso.
                        </p>


                        <button
                            type="button"
                            class="add-user-btn"
                            id="openLessonModalEmpty"
                        >

                            <i class="fa-solid fa-plus"></i>

                            Crear primera lección

                        </button>

                    </div>


                <?php endif; ?>


            </div>

        </section>

    </main>

</div>


<!-- =========================================================
     MODAL NUEVA LECCIÓN
========================================================= -->

<div
    class="modal-overlay"
    id="lessonModal"
>

    <div class="modal">


        <div class="modal-header">

            <div>

                <span>
                    Contenido educativo
                </span>

                <h3>
                    Crear nueva lección
                </h3>

            </div>


            <button
                type="button"
                class="close-modal"
                id="closeLessonModal"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form
            action="guardar_leccion.php"
            method="POST"
        >


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
                            <?= (string)$cursoSeleccionado === (string)$curso["id"] ? "selected" : "" ?>
                        >

                            <?= htmlspecialchars(
                                $curso["nombre"]
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="titulo">
                    Título de la lección
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    placeholder="Ej. Saludos y presentaciones"
                    required
                >

            </div>


            <div class="form-group">

                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="4"
                    placeholder="Describe brevemente el contenido de la lección..."
                ></textarea>

            </div>


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
                        value="1"
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

                        <option value="Borrador">
                            Borrador
                        </option>

                        <option value="Publicado">
                            Publicada
                        </option>

                        <option value="Inactivo">
                            Inactiva
                        </option>

                    </select>

                </div>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-btn"
                    id="cancelLessonModal"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="save-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Crear lección

                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================================================
   MENÚ RESPONSIVE
========================================================= */

const menuToggle =
    document.getElementById("menuToggle");

const sidebar =
    document.querySelector(".sidebar");


if (menuToggle) {

    menuToggle.addEventListener("click", () => {

        sidebar.classList.toggle("show");

    });

}


/* =========================================================
   MODAL
========================================================= */

const lessonModal =
    document.getElementById("lessonModal");

const openLessonModal =
    document.getElementById("openLessonModal");

const openLessonModalEmpty =
    document.getElementById("openLessonModalEmpty");

const closeLessonModal =
    document.getElementById("closeLessonModal");

const cancelLessonModal =
    document.getElementById("cancelLessonModal");


function abrirModalLeccion() {

    if (lessonModal) {

        lessonModal.classList.add("show");

    }
}


function cerrarModalLeccion() {

    if (lessonModal) {

        lessonModal.classList.remove("show");

    }
}


if (openLessonModal) {

    openLessonModal.addEventListener(
        "click",
        abrirModalLeccion
    );

}


if (openLessonModalEmpty) {

    openLessonModalEmpty.addEventListener(
        "click",
        abrirModalLeccion
    );

}


if (closeLessonModal) {

    closeLessonModal.addEventListener(
        "click",
        cerrarModalLeccion
    );

}


if (cancelLessonModal) {

    cancelLessonModal.addEventListener(
        "click",
        cerrarModalLeccion
    );

}


if (lessonModal) {

    lessonModal.addEventListener(
        "click",
        (event) => {

            if (event.target === lessonModal) {

                cerrarModalLeccion();

            }

        }
    );

}


/* =========================================================
   CONFIRMAR ELIMINACIÓN
========================================================= */

function confirmarEliminacionLeccion() {

    return confirm(
        "¿Estás seguro de que deseas eliminar esta lección?"
    );

}

</script>

</body>

</html>