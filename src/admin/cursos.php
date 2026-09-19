<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

/*
|--------------------------------------------------------------------------
| Obtener cursos
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.nombre,
        c.descripcion,
        c.nivel,
        c.estado,
        c.fecha_creacion,
        u.nombre AS instructor_nombre,
        u.apellido AS instructor_apellido
    FROM cursos c

    LEFT JOIN usuarios u
        ON c.instructor_id = u.id

    ORDER BY c.id DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$cursos = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Estadísticas
|--------------------------------------------------------------------------
*/

$totalCursos = count($cursos);

$cursosPublicados = 0;
$cursosBorrador = 0;
$cursosInactivos = 0;

foreach ($cursos as $curso) {

    if ($curso["estado"] === "Publicado") {
        $cursosPublicados++;
    }

    if ($curso["estado"] === "Borrador") {
        $cursosBorrador++;
    }

    if ($curso["estado"] === "Inactivo") {
        $cursosInactivos++;
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

    <title>BiGlobal | Catálogo de cursos</title>

    <link rel="stylesheet" href="css/style.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

<div class="admin-container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">

        <div class="sidebar-header">

            <div class="logo">

                <span class="logo-icon">
                    <i class="fa-solid fa-language"></i>
                </span>

                <span class="logo-text">
                    BiGlobal
                </span>

            </div>

        </div>


        <nav class="sidebar-menu">

            <p class="menu-title">
                PANEL PRINCIPAL
            </p>


            <a href="index.php" class="menu-item">

                <i class="fa-solid fa-house"></i>

                <span>
                    Inicio
                </span>

            </a>


            <p class="menu-title">
                GESTIÓN
            </p>


            <a href="usuarios.php" class="menu-item">

                <i class="fa-solid fa-users"></i>

                <span>
                    Usuarios y roles
                </span>

            </a>


            <a href="cursos.php" class="menu-item active">

                <i class="fa-solid fa-book"></i>

                <span>
                    Catálogo de cursos
                </span>

            </a>


            <a href="idiomas.php" class="menu-item">

                <i class="fa-solid fa-globe"></i>

                <span>
                    Idiomas
                </span>

            </a>


            <a href="lecciones.php" class="menu-item">

                <i class="fa-solid fa-book-open"></i>

                <span>
                    Lecciones y actividades
                </span>

            </a>


            <p class="menu-title">
                CONTROL
            </p>


            <a href="reportes.php" class="menu-item">

                <i class="fa-solid fa-chart-column"></i>

                <span>
                    Reportes
                </span>

            </a>


            <a href="errores.php" class="menu-item">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <span>
                    Reportes y errores
                </span>

            </a>


            <a href="moderacion.php" class="menu-item">

                <i class="fa-solid fa-comments"></i>

                <span>
                    Moderación
                </span>

            </a>


            <a href="certificados.php" class="menu-item">

                <i class="fa-solid fa-certificate"></i>

                <span>
                    Certificados
                </span>

            </a>


            <p class="menu-title">
                SISTEMA
            </p>


            <a href="configuracion.php" class="menu-item">

                <i class="fa-solid fa-gear"></i>

                <span>
                    Configuración
                </span>

            </a>

        </nav>


        <div class="sidebar-footer">

            <a href="../index.html" class="back-home">

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
                        Gestión de plataforma
                    </p>

                    <h1>
                        Catálogo de cursos
                    </h1>

                </div>

            </div>


            <div class="topbar-right">

                <button class="notification-button">

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


                    <i class="fa-solid fa-chevron-down profile-arrow"></i>

                </div>

            </div>

        </header>



        <!-- =====================================================
             CURSOS
        ====================================================== -->

        <section class="users-page">


            <!-- ENCABEZADO -->

            <div class="users-heading">

                <div>

                    <span class="section-label">
                        Gestión académica
                    </span>

                    <h2>
                        Catálogo de cursos
                    </h2>

                    <p>
                        Crea, administra, publica y supervisa los cursos
                        disponibles en BiGlobal.
                    </p>

                </div>


                <button
                    class="add-user-btn"
                    id="openCourseModal"
                >

                    <i class="fa-solid fa-plus"></i>

                    Nuevo curso

                </button>

            </div>



            <!-- =================================================
                 ESTADÍSTICAS
            ================================================== -->

            <div class="user-stats">


                <div class="user-stat-card">

                    <div class="user-stat-icon blue">

                        <i class="fa-solid fa-book"></i>

                    </div>


                    <div>

                        <span>
                            Total cursos
                        </span>

                        <strong>
                            <?= $totalCursos ?>
                        </strong>

                    </div>

                </div>



                <div class="user-stat-card">

                    <div class="user-stat-icon green">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <div>

                        <span>
                            Publicados
                        </span>

                        <strong>
                            <?= $cursosPublicados ?>
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
                            <?= $cursosBorrador ?>
                        </strong>

                    </div>

                </div>



                <div class="user-stat-card">

                    <div class="user-stat-icon purple">

                        <i class="fa-solid fa-ban"></i>

                    </div>


                    <div>

                        <span>
                            Inactivos
                        </span>

                        <strong>
                            <?= $cursosInactivos ?>
                        </strong>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 TABLA
            ================================================== -->

            <div class="users-card">


                <!-- TOOLBAR -->

                <div class="users-toolbar">

                    <div>

                        <h3>
                            Lista de cursos
                        </h3>

                        <span>
                            <?= $totalCursos ?> cursos registrados
                        </span>

                    </div>


                    <div class="toolbar-actions">


                        <div class="search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="searchCourse"
                                placeholder="Buscar curso..."
                            >

                        </div>


                        <select id="levelFilter">

                            <option value="all">
                                Todos los niveles
                            </option>

                            <option value="Básico">
                                Básico
                            </option>

                            <option value="Intermedio">
                                Intermedio
                            </option>

                            <option value="Avanzado">
                                Avanzado
                            </option>

                        </select>


                    </div>

                </div>



                <!-- TABLA -->

                <div class="table-container">

                    <table class="users-table">

                        <thead>

                            <tr>

                                <th>
                                    Curso
                                </th>

                                <th>
                                    Nivel
                                </th>

                                <th>
                                    Instructor
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Fecha
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody id="coursesTable">


                            <?php if (count($cursos) > 0): ?>


                                <?php foreach ($cursos as $curso): ?>


                                    <?php

                                    $nivel =
                                        $curso["nivel"]
                                        ?: "Sin nivel";

                                    $estado =
                                        $curso["estado"]
                                        ?: "Borrador";

                                    $instructor =
                                        trim(
                                            ($curso["instructor_nombre"] ?? "") .
                                            " " .
                                            ($curso["instructor_apellido"] ?? "")
                                        );

                                    if ($instructor === "") {
                                        $instructor = "Sin asignar";
                                    }

                                    ?>


                                    <tr
                                        data-level="<?= htmlspecialchars($nivel) ?>"
                                    >


                                        <!-- CURSO -->

                                        <td>

                                            <div class="user-cell">

                                                <div class="user-avatar blue">

                                                    <i class="fa-solid fa-book"></i>

                                                </div>


                                                <div>

                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            $curso["nombre"]
                                                        ) ?>
                                                    </strong>

                                                    <span>

                                                        <?= htmlspecialchars(
                                                            $curso["descripcion"]
                                                            ?: "Sin descripción"
                                                        ) ?>

                                                    </span>

                                                </div>

                                            </div>

                                        </td>



                                        <!-- NIVEL -->

                                        <td>

                                            <span class="role admin-role">

                                                <?= htmlspecialchars($nivel) ?>

                                            </span>

                                        </td>



                                        <!-- INSTRUCTOR -->

                                        <td>

                                            <?= htmlspecialchars($instructor) ?>

                                        </td>



                                        <!-- ESTADO -->

                                        <td>

                                            <?php if ($estado === "Publicado"): ?>

                                                <span class="status active-status">
                                                    Publicado
                                                </span>

                                            <?php elseif ($estado === "Inactivo"): ?>

                                                <span class="status inactive-status">
                                                    Inactivo
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

                                        </td>



                                        <!-- FECHA -->

                                        <td>

                                            <?= date(
                                                "d M Y",
                                                strtotime(
                                                    $curso["fecha_creacion"]
                                                )
                                            ) ?>

                                        </td>



                                        <!-- ACCIONES -->

                                        <td>

                                            <div class="action-buttons">

                                            <a
                                                  href="editar_curso.php?id=<?= $curso['id'] ?>"
                                                  class="table-action edit"
                                                  title="Editar"
                                                  >
                                                  <i class="fa-solid fa-pen"></i>
                                            </a>



                                              <form
                                             action="eliminar_curso.php"
                                             method="POST"
                                             style="display:inline;"
                                             onsubmit="return confirmarEliminacionCurso();"
                                             >

                                             <input
                                                 type="hidden"
                                                 name="id"
                                                 value="<?= $curso['id'] ?>"
                                             >

                                             <button
                                                 class="table-action delete"
                                                 type="submit"
                                                 title="Eliminar"
                                             >
                                                 <i class="fa-solid fa-trash"></i>
                                             </button>

                                                 </form>


                                                


                                             


                                            </div>

                                        </td>

                                    </tr>


                                <?php endforeach; ?>


                            <?php else: ?>


                                <tr>

                                    <td
                                        colspan="6"
                                        style="
                                            text-align:center;
                                            padding:40px;
                                        "
                                    >

                                        <i
                                            class="fa-solid fa-book-open"
                                            style="
                                                font-size:30px;
                                                color:#98a2b3;
                                                margin-bottom:10px;
                                            "
                                        ></i>

                                        <p style="color:#667085;">
                                            Todavía no hay cursos registrados.
                                        </p>

                                    </td>

                                </tr>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>



                <!-- PAGINACIÓN -->

                <div class="pagination">

                    <span>

                        Mostrando
                        <?= $totalCursos ?>
                        cursos

                    </span>


                    <div class="pagination-buttons">

                        <button disabled>

                            <i class="fa-solid fa-chevron-left"></i>

                        </button>


                        <button class="page-active">
                            1
                        </button>


                        <button>

                            <i class="fa-solid fa-chevron-right"></i>

                        </button>

                    </div>

                </div>


            </div>

        </section>

    </main>

</div>



<!-- =====================================================
     MODAL NUEVO CURSO
====================================================== -->

<div
    class="modal-overlay"
    id="courseModal"
>


    <div class="modal">


        <div class="modal-header">

            <div>

                <span>
                    Gestión académica
                </span>

                <h3>
                    Crear nuevo curso
                </h3>

            </div>


            <button
                class="close-modal"
                id="closeCourseModal"
                type="button"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <form
            id="courseForm"
            action="guardar_curso.php"
            method="POST"
        >


            <div class="form-group">

                <label for="courseName">
                    Nombre del curso
                </label>

                <input
                    type="text"
                    id="courseName"
                    name="nombre"
                    placeholder="Ej. Inglés profesional"
                    required
                >

            </div>



            <div class="form-group">

                <label for="courseDescription">
                    Descripción
                </label>

                <textarea
                    id="courseDescription"
                    name="descripcion"
                    rows="4"
                    placeholder="Descripción del curso..."
                    style="
                        width:100%;
                        border:1px solid #d0d5dd;
                        border-radius:9px;
                        padding:12px;
                        resize:vertical;
                        font-family:inherit;
                        font-size:12px;
                        outline:none;
                    "
                ></textarea>

            </div>



            <div class="form-row">


                <div class="form-group">

                    <label for="courseLevel">
                        Nivel
                    </label>

                    <select
                        id="courseLevel"
                        name="nivel"
                        required
                    >

                        <option value="">
                            Seleccionar nivel
                        </option>

                        <option value="Básico">
                            Básico
                        </option>

                        <option value="Intermedio">
                            Intermedio
                        </option>

                        <option value="Avanzado">
                            Avanzado
                        </option>

                    </select>

                </div>



                <div class="form-group">

                    <label for="courseStatus">
                        Estado
                    </label>

                    <select
                        id="courseStatus"
                        name="estado"
                    >

                        <option value="Borrador">
                            Borrador
                        </option>

                        <option value="Publicado">
                            Publicado
                        </option>

                        <option value="Inactivo">
                            Inactivo
                        </option>

                    </select>

                </div>

            </div>



            <div class="form-group">

                <label for="instructor">
                    Instructor
                </label>

                <select
                    id="instructor"
                    name="instructor_id"
                >

                    <option value="">
                        Sin instructor
                    </option>


                    <?php

                    $stmtInstructores = $conexion->prepare("
                        SELECT
                            u.id,
                            u.nombre,
                            u.apellido
                        FROM usuarios u
                        INNER JOIN roles r
                            ON u.rol_id = r.id
                        WHERE
                            r.nombre = 'Instructor'
                            AND u.estado = 'Activo'
                        ORDER BY u.nombre ASC
                    ");

                    $stmtInstructores->execute();

                    $instructores =
                        $stmtInstructores->fetchAll();

                    ?>


                    <?php foreach ($instructores as $instructor): ?>

                        <option
                            value="<?= $instructor["id"] ?>"
                        >

                            <?= htmlspecialchars(
                                $instructor["nombre"] .
                                " " .
                                $instructor["apellido"]
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-btn"
                    id="cancelCourseModal"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="save-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Crear curso

                </button>

            </div>


        </form>

    </div>

</div>



<script>

/* =====================================================
   MENÚ
===================================================== */

const menuToggle =
    document.getElementById("menuToggle");

const sidebar =
    document.querySelector(".sidebar");


if (menuToggle) {

    menuToggle.addEventListener("click", () => {

        sidebar.classList.toggle("show");

    });

}



/* =====================================================
   MODAL
===================================================== */

const courseModal =
    document.getElementById("courseModal");

const openCourseModal =
    document.getElementById("openCourseModal");

const closeCourseModal =
    document.getElementById("closeCourseModal");

const cancelCourseModal =
    document.getElementById("cancelCourseModal");


openCourseModal.addEventListener(
    "click",
    () => {

        courseModal.classList.add("show");

    }
);


closeCourseModal.addEventListener(
    "click",
    () => {

        courseModal.classList.remove("show");

    }
);


cancelCourseModal.addEventListener(
    "click",
    () => {

        courseModal.classList.remove("show");

    }
);


courseModal.addEventListener(
    "click",
    (event) => {

        if (event.target === courseModal) {

            courseModal.classList.remove("show");

        }

    }
);



/* =====================================================
   BUSCADOR
===================================================== */

const searchCourse =
    document.getElementById("searchCourse");


searchCourse.addEventListener(
    "input",
    function () {

        const search =
            this.value.toLowerCase();

        const rows =
            document.querySelectorAll(
                "#coursesTable tr"
            );


        rows.forEach(row => {

            const text =
                row.textContent.toLowerCase();

            row.style.display =
                text.includes(search)
                    ? ""
                    : "none";

        });

    }
);



/* =====================================================
   FILTRO DE NIVEL
===================================================== */

const levelFilter =
    document.getElementById("levelFilter");


levelFilter.addEventListener(
    "change",
    function () {

        const selectedLevel =
            this.value;

        const rows =
            document.querySelectorAll(
                "#coursesTable tr"
            );


        rows.forEach(row => {

            const rowLevel =
                row.dataset.level;


            if (
                selectedLevel === "all" ||
                rowLevel === selectedLevel
            ) {

                row.style.display = "";

            } else {

                row.style.display = "none";

            }

        });

    }
);


function confirmarEliminacionCurso() {

    return confirm(
        "¿Estás seguro de que deseas eliminar este curso?"
    );

}

</script>

</script>


</body>
</html>