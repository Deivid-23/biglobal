<?php

require_once "../auth/proteger.php";

protegerRol("admin");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();


/* =========================
   OBTENER IDIOMAS
========================= */

$stmt = $conexion->prepare("
    SELECT
        id,
        nombre,
        codigo,
        estado,
        fecha_creacion
    FROM idiomas
    ORDER BY id DESC
");

$stmt->execute();

$idiomas = $stmt->fetchAll();


/* =========================
   ESTADÍSTICAS
========================= */

$totalIdiomas = count($idiomas);

$idiomasActivos = 0;
$idiomasInactivos = 0;

foreach ($idiomas as $idioma) {

    if ($idioma["estado"] === "Activo") {
        $idiomasActivos++;
    } else {
        $idiomasInactivos++;
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

    <title>BiGlobal | Idiomas</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>

<body>

<div class="admin-container">


    <!-- =========================
         SIDEBAR
    ========================== -->

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


            <a href="cursos.php" class="menu-item">

                <i class="fa-solid fa-book"></i>

                <span>
                    Catálogo de cursos
                </span>

            </a>


            <a href="idiomas.php" class="menu-item active">

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

            <a
                href="../index.html"
                class="back-home"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Volver al inicio

            </a>

        </div>

    </aside>


    <!-- =========================
         CONTENIDO
    ========================== -->

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
                        Idiomas
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


        <!-- =========================
             PÁGINA IDIOMAS
        ========================== -->

        <section class="users-page">


            <!-- ENCABEZADO -->

            <div class="users-heading">

                <div>

                    <span class="section-label">
                        Gestión académica
                    </span>

                    <h2>
                        Idiomas disponibles
                    </h2>

                    <p>
                        Administra los idiomas disponibles en la plataforma BiGlobal.
                    </p>

                </div>


                <button
                    class="add-user-btn"
                    id="openLanguageModal"
                    type="button"
                >

                    <i class="fa-solid fa-plus"></i>

                    Nuevo idioma

                </button>

            </div>


            <!-- =========================
                 ESTADÍSTICAS
            ========================== -->

            <div class="user-stats">


                <div class="user-stat-card">

                    <div class="user-stat-icon blue">

                        <i class="fa-solid fa-globe"></i>

                    </div>

                    <div>

                        <span>
                            Total idiomas
                        </span>

                        <strong>
                            <?= $totalIdiomas ?>
                        </strong>

                    </div>

                </div>


                <div class="user-stat-card">

                    <div class="user-stat-icon green">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <div>

                        <span>
                            Activos
                        </span>

                        <strong>
                            <?= $idiomasActivos ?>
                        </strong>

                    </div>

                </div>


                <div class="user-stat-card">

                    <div class="user-stat-icon orange">

                        <i class="fa-solid fa-circle-pause"></i>

                    </div>

                    <div>

                        <span>
                            Inactivos
                        </span>

                        <strong>
                            <?= $idiomasInactivos ?>
                        </strong>

                    </div>

                </div>


                <div class="user-stat-card">

                    <div class="user-stat-icon purple">

                        <i class="fa-solid fa-language"></i>

                    </div>

                    <div>

                        <span>
                            Código ISO
                        </span>

                        <strong>
                            <?= $totalIdiomas ?>
                        </strong>

                    </div>

                </div>

            </div>


            <!-- =========================
                 TABLA
            ========================== -->

            <div class="users-card">


                <div class="users-toolbar">

                    <div>

                        <h3>
                            Lista de idiomas
                        </h3>

                        <span>
                            <?= $totalIdiomas ?> idiomas registrados
                        </span>

                    </div>


                    <div class="toolbar-actions">


                        <div class="search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="searchLanguage"
                                placeholder="Buscar idioma..."
                            >

                        </div>


                        <select id="statusFilter">

                            <option value="all">
                                Todos los estados
                            </option>

                            <option value="Activo">
                                Activos
                            </option>

                            <option value="Inactivo">
                                Inactivos
                            </option>

                        </select>

                    </div>

                </div>


                <div class="table-container">

                    <table class="users-table">

                        <thead>

                            <tr>

                                <th>
                                    Idioma
                                </th>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Fecha de creación
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody id="languagesTable">

                            <?php if (count($idiomas) > 0): ?>

                                <?php foreach ($idiomas as $idioma): ?>

                                    <tr
                                        data-status="<?= htmlspecialchars($idioma["estado"]) ?>"
                                    >

                                        <td>

                                            <div class="user-cell">

                                                <div class="user-avatar blue">

                                                    <i class="fa-solid fa-language"></i>

                                                </div>

                                                <div>

                                                    <strong>
                                                        <?= htmlspecialchars($idioma["nombre"]) ?>
                                                    </strong>

                                                    <span>
                                                        Idioma disponible en BiGlobal
                                                    </span>

                                                </div>

                                            </div>

                                        </td>


                                        <td>

                                            <span class="role admin-role">

                                                <?= htmlspecialchars($idioma["codigo"]) ?>

                                            </span>

                                        </td>


                                        <td>

                                            <?php if ($idioma["estado"] === "Activo"): ?>

                                                <span class="status active-status">
                                                    Activo
                                                </span>

                                            <?php else: ?>

                                                <span class="status inactive-status">
                                                    Inactivo
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <td>

                                            <?= date(
                                                "d M Y",
                                                strtotime($idioma["fecha_creacion"])
                                            ) ?>

                                        </td>


                                        <td>

                                            <div class="action-buttons">
                                                <a
                                           href="editar_idioma.php?id=<?= $idioma["id"] ?>"
                                           class="table-action edit"
                                           title="Editar"
                                           >
                                           <i class="fa-solid fa-pen"></i>
                                            </a>

                                            </div>

                                            <form
                                                      action="eliminar_idioma.php"
                                                      method="POST"
                                                      style="display:inline;"
                                                      onsubmit="return confirmarEliminacionIdioma();"
                                                   >
                                                      <input
                                                          type="hidden"
                                                          name="id"
                                                          value="<?= $idioma['id'] ?>"
                                                      >
                                                   
                                                      <button
                                                          type="submit"
                                                          class="table-action delete"
                                                          title="Eliminar"
                                                      >
                                                          <i class="fa-solid fa-trash"></i>
                                                      </button>
                                             </form>




                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="5"
                                        style="
                                            text-align:center;
                                            padding:40px;
                                        "
                                    >

                                        No hay idiomas registrados.

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>


                <div class="pagination">

                    <span>

                        Mostrando
                        <?= $totalIdiomas ?>
                        idiomas

                    </span>


                    <div class="pagination-buttons">

                        <button disabled>

                            <i class="fa-solid fa-chevron-left"></i>

                        </button>

                        <button class="page-active">
                            1
                        </button>

                        <button disabled>

                            <i class="fa-solid fa-chevron-right"></i>

                        </button>

                    </div>

                </div>

            </div>

        </section>

    </main>

</div>


<!-- =========================
     MODAL
========================== -->

<div
    class="modal-overlay"
    id="languageModal"
>

    <div class="modal">

        <div class="modal-header">

            <div>

                <span>
                    Gestión académica
                </span>

                <h3>
                    Agregar nuevo idioma
                </h3>

            </div>


            <button
                class="close-modal"
                id="closeLanguageModal"
                type="button"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <form
            action="guardar_idioma.php"
            method="POST"
        >


            <div class="form-group">

                <label for="nombre">
                    Nombre del idioma
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    placeholder="Ej. Alemán"
                    required
                >

            </div>


            <div class="form-group">

                <label for="codigo">
                    Código
                </label>

                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    placeholder="Ej. DE"
                    maxlength="10"
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

                    <option value="Activo">
                        Activo
                    </option>

                    <option value="Inactivo">
                        Inactivo
                    </option>

                </select>

            </div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-btn"
                    id="cancelLanguageModal"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="save-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Crear idioma

                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================
   MENÚ
========================= */

const menuToggle =
    document.getElementById("menuToggle");

const sidebar =
    document.querySelector(".sidebar");


if (menuToggle) {

    menuToggle.addEventListener("click", () => {

        sidebar.classList.toggle("show");

    });

}


/* =========================
   MODAL
========================= */

const languageModal =
    document.getElementById("languageModal");

const openLanguageModal =
    document.getElementById("openLanguageModal");

const closeLanguageModal =
    document.getElementById("closeLanguageModal");

const cancelLanguageModal =
    document.getElementById("cancelLanguageModal");


openLanguageModal.addEventListener("click", () => {

    languageModal.classList.add("show");

});


closeLanguageModal.addEventListener("click", () => {

    languageModal.classList.remove("show");

});


cancelLanguageModal.addEventListener("click", () => {

    languageModal.classList.remove("show");

});


languageModal.addEventListener("click", (event) => {

    if (event.target === languageModal) {

        languageModal.classList.remove("show");

    }

});


/* =========================
   BUSCADOR
========================= */

const searchLanguage =
    document.getElementById("searchLanguage");


searchLanguage.addEventListener("input", function () {

    const search =
        this.value.toLowerCase();

    const rows =
        document.querySelectorAll(
            "#languagesTable tr"
        );


    rows.forEach(row => {

        const text =
            row.textContent.toLowerCase();

        row.style.display =
            text.includes(search)
                ? ""
                : "none";

    });

});


/* =========================
   FILTRO DE ESTADO
========================= */

const statusFilter =
    document.getElementById("statusFilter");


statusFilter.addEventListener("change", function () {

    const selectedStatus =
        this.value;

    const rows =
        document.querySelectorAll(
            "#languagesTable tr"
        );


    rows.forEach(row => {

        const rowStatus =
            row.dataset.status;


        if (
            selectedStatus === "all" ||
            rowStatus === selectedStatus
        ) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

});
function confirmarEliminacionIdioma() {

    return confirm(
        "¿Estás seguro de que deseas eliminar este idioma?"
    );

}


</script>

</body>
</html>