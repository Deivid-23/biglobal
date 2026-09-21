<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

$sql = "
    SELECT
        u.id,
        u.nombre,
        u.apellido,
        u.correo,
        u.estado,
        u.fecha_registro,
        r.nombre AS rol
    FROM usuarios u

    LEFT JOIN roles r
        ON u.rol_id = r.id

    ORDER BY u.id DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll();

$totalUsuariosActivos = count(array_filter($usuarios, fn($u) => $u["estado"] === "Activo"));
$totalInstructores = count(array_filter($usuarios, fn($u) => $u["rol"] === "Instructor"));
$totalPendientes = count(array_filter($usuarios, fn($u) => $u["estado"] === "Pendiente"));

?>








<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Usuarios y Roles</title>

    <!-- CSS principal del administrador -->
    <link rel="stylesheet" href="css/style.css">


    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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
                <span>Inicio</span>
            </a>


            <p class="menu-title">
                GESTIÓN
            </p>


            <a href="usuarios.php" class="menu-item active">
                <i class="fa-solid fa-users"></i>
                <span>Usuarios y roles</span>
            </a>


            <a href="cursos.php" class="menu-item">
                <i class="fa-solid fa-book"></i>
                <span>Catálogo de cursos</span>
            </a>


            <a href="idiomas.php" class="menu-item">
                <i class="fa-solid fa-globe"></i>
                <span>Idiomas</span>
            </a>


            <a href="lecciones.php" class="menu-item">
                <i class="fa-solid fa-book-open"></i>
                <span>Lecciones y actividades</span>
            </a>


            <p class="menu-title">
                CONTROL
            </p>


            <a href="reportes.php" class="menu-item">
                <i class="fa-solid fa-chart-column"></i>
                <span>Reportes</span>
            </a>


            <a href="errores.php" class="menu-item">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Reportes y errores</span>
            </a>


            <a href="moderacion.php" class="menu-item">
                <i class="fa-solid fa-comments"></i>
                <span>Moderación</span>
            </a>


            <a href="certificados.php" class="menu-item">
                <i class="fa-solid fa-certificate"></i>
                <span>Certificados</span>
            </a>


            <p class="menu-title">
                SISTEMA
            </p>


            <a href="configuracion.php" class="menu-item">
                <i class="fa-solid fa-gear"></i>
                <span>Configuración</span>
            </a>

        </nav>


        <div class="sidebar-footer">

            <a href="../index.html" class="back-home">

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

                <button class="menu-toggle" id="menuToggle">

                    <i class="fa-solid fa-bars"></i>

                </button>


                <div>

                    <p class="panel-label">
                        Gestión de plataforma
                    </p>

                    <h1>
                        Usuarios y roles
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
             CONTENIDO USUARIOS
        ========================== -->

        <section class="users-page">


            <!-- ENCABEZADO -->

            <div class="users-heading">

                <div>

                    <span class="section-label">
                        Administración
                    </span>

                    <h2>
                        Usuarios y roles
                    </h2>

                    <p>
                        Administra los usuarios registrados y controla sus roles
                        dentro de la plataforma.
                    </p>

                </div>


                <button class="add-user-btn" id="openModal">

                    <i class="fa-solid fa-user-plus"></i>

                    Nuevo usuario

                </button>

            </div>



            <!-- ESTADÍSTICAS -->

            <div class="user-stats">


                <div class="user-stat-card">

                    <div class="user-stat-icon blue">

                        <i class="fa-solid fa-users"></i>

                    </div>

                    <div>

                        <span>
                            Total usuarios
                        </span>

                        <strong>
                            <?= count($usuarios) ?>
                        </strong>

                    </div>

                </div>



                <div class="user-stat-card">

                    <div class="user-stat-icon green">

                        <i class="fa-solid fa-user-check"></i>

                    </div>

                    <div>

                        <span>
                            Usuarios activos
                        </span>

                        <strong>
                            <?= $totalUsuariosActivos ?>
                        </strong>

                    </div>

                </div>



                <div class="user-stat-card">

                    <div class="user-stat-icon purple">

                        <i class="fa-solid fa-chalkboard-user"></i>

                    </div>

                    <div>

                        <span>
                            Instructores
                        </span>

                        <strong>
                            <?= $totalInstructores ?>
                        </strong>
                    </div>

                </div>



                <div class="user-stat-card">

                    <div class="user-stat-icon orange">

                        <i class="fa-solid fa-user-clock"></i>

                    </div>

                    <div>

                        <span>
                            Pendientes
                        </span>

                        <strong>
                            <?= $totalPendientes ?>
                        </strong>

                    </div>

                </div>

            </div>



            <!-- TABLA -->

            <div class="users-card">


                <!-- BARRA DE HERRAMIENTAS -->

                <div class="users-toolbar">


                    <div>

                        <h3>
                            Lista de usuarios
                        </h3>

                        <span>
                            1.248 usuarios registrados
                        </span>

                    </div>


                    <div class="toolbar-actions">


                        <div class="search-box">

                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="searchUser"
                                placeholder="Buscar usuario..."
                            >

                        </div>


                        <select id="roleFilter">

                            <option value="all">
                                Todos los roles
                            </option>

                            <option value="Administrador">
                                Administrador
                            </option>

                            <option value="Instructor">
                                Instructor
                            </option>

                            <option value="Estudiante">
                                Estudiante
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
                                    Usuario
                                </th>

                                <th>
                                    Correo
                                </th>

                                <th>
                                    Rol
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Registro
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>

                             <tbody id="usersTable">

    <?php if (count($usuarios) > 0): ?>

        <?php foreach ($usuarios as $usuario): ?>

            <tr data-role="<?= htmlspecialchars($usuario['rol'] ?? '') ?>">

                <td>

                    <div class="user-cell">

                        <div class="user-avatar blue">
                            <?= strtoupper(
                                substr($usuario['nombre'], 0, 1) .
                                substr($usuario['apellido'], 0, 1)
                            ) ?>
                        </div>

                        <div>

                            <strong>
                                <?= htmlspecialchars(
                                    $usuario['nombre'] . ' ' . $usuario['apellido']
                                ) ?>
                            </strong>

                            <span>
                                @<?= strtolower(
                                    preg_replace(
                                        '/\s+/',
                                        '',
                                        $usuario['nombre']
                                    )
                                ) ?>
                            </span>

                        </div>

                    </div>

                </td>


                <td>
                    <?= htmlspecialchars($usuario['correo']) ?>
                </td>


                <td>

                    <?php
                    $rol = $usuario['rol'] ?? 'Sin rol';

                    $claseRol = '';

                    if ($rol === 'Administrador') {
                        $claseRol = 'admin-role';
                    } elseif ($rol === 'Instructor') {
                        $claseRol = 'instructor-role';
                    } elseif ($rol === 'Estudiante') {
                        $claseRol = 'student-role';
                    }
                    ?>

                    <span class="role <?= $claseRol ?>">
                        <?= htmlspecialchars($rol) ?>
                    </span>

                </td>


                <td>

                    <?php if ($usuario['estado'] === 'Activo'): ?>

                        <span class="status active-status">
                            Activo
                        </span>

                    <?php elseif ($usuario['estado'] === 'Pendiente'): ?>

                        <span class="status pending-status">
                            Pendiente
                        </span>

                    <?php else: ?>

                        <span class="status inactive-status">
                            Inactivo
                        </span>

                    <?php endif; ?>

                </td>


                <td>
                    <?= date(
                        'd M Y',
                        strtotime($usuario['fecha_registro'])
                    ) ?>
                </td>


                <td>

                    <div class="action-buttons">

                    <a
                       href="editar_usuario.php?id=<?= $usuario['id'] ?>"
                       class="table-action edit"
                       title="Editar"
                     >
                       <i class="fa-solid fa-pen"></i>
                    </a>



          <form
            action="eliminar_usuario.php"
            method="POST"
            style="display:inline;"
            onsubmit="return confirmarEliminacion();"
            >
     
            <input
                type="hidden"
                name="id"
                value="<?= $usuario['id'] ?>"
            >
     
            <button
                class="table-action delete"
                title="Eliminar"
                type="submit"
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

            <td colspan="6" style="text-align: center; padding: 30px;">
                No hay usuarios registrados.
            </td>

        </tr>

    <?php endif; ?>

</tbody>




                    </table>

                </div>



                <!-- PAGINACIÓN -->

                <div class="pagination">

                    <span>
                        Mostrando 1 - 4 de 1.248 usuarios
                    </span>

                    <div class="pagination-buttons">

                        <button disabled>
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button class="page-active">
                            1
                        </button>

                        <button>
                            2
                        </button>

                        <button>
                            3
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



<!-- =========================
     MODAL NUEVO USUARIO
========================== -->

<div class="modal-overlay" id="userModal">

    <div class="modal">


        <div class="modal-header">

            <div>

                <span>
                    Gestión de usuarios
                </span>

                <h3>
                    Crear nuevo usuario
                </h3>

            </div>


            <button class="close-modal" id="closeModal">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <form id="userForm" action="guardar_usuario.php" method="POST">


            <div class="form-group">

                <label for="name">
                    Nombre completo
                </label>

          <input
             type="text"
             id="name"
             name="nombre"
             placeholder="Ej. Juan"
             required
                >

            </div>

            <div class="form-group">

               <label for="lastname">
                   Apellido
               </label>

               <input
                   type="text"
                   id="lastname"
                   name="apellido"
                   placeholder="Ej. Pérez"
                   required
    >

</div>





            <div class="form-group">

                <label for="email">
                    Correo electrónico
                </label>

                <input
                   type="email"
                   id="email"
                   name="correo"
                   placeholder="usuario@biglobal.com"
                   required
                        >
            </div>

            <div class="form-group">

             <label for="password">
                 Contraseña
             </label>
           
             <input
                 type="password"
                 id="password"
                 name="password"
                 placeholder="Ingrese una contraseña"
                 required
                 minlength="6"
            >

</div>



            <div class="form-row">


                <div class="form-group">

                    <label for="role">
                        Rol
                    </label>



                    <select id="role" name="rol" required>

                       <option value="">
                           Seleccionar rol
                       </option>

                       <option value="Administrador">
                           Administrador
                       </option>

                       <option value="Instructor">
                           Instructor
                       </option>

                       <option value="Estudiante">
                           Estudiante
                       </option>

                    </select>

                 

                </div>



                <div class="form-group">

                    <label for="status">
                        Estado
                    </label>

                    <select id="status" name="estado">
                    
                      <option value="Activo">
                          Activo
                      </option>
                    
                      <option value="Inactivo">
                          Inactivo
                      </option>
                    
                    </select>




                </div>

            </div>



            <div class="modal-actions">

                <button
                    type="button"
                    class="cancel-btn"
                    id="cancelModal"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="save-btn"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Crear usuario
                </button>

            </div>

        </form>

    </div>

</div>



<script>

    /* =========================
       MENÚ RESPONSIVE
    ========================== */

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");

    if (menuToggle) {

        menuToggle.addEventListener("click", () => {

            sidebar.classList.toggle("show");

        });

    }



    /* =========================
       MODAL
    ========================== */

    const modal = document.getElementById("userModal");

    const openModal = document.getElementById("openModal");

    const closeModal = document.getElementById("closeModal");

    const cancelModal = document.getElementById("cancelModal");


    openModal.addEventListener("click", () => {

        modal.classList.add("show");

    });


    closeModal.addEventListener("click", () => {

        modal.classList.remove("show");

    });


    cancelModal.addEventListener("click", () => {

        modal.classList.remove("show");

    });


    modal.addEventListener("click", (event) => {

        if (event.target === modal) {

            modal.classList.remove("show");

        }

    });



    /* =========================
       BUSCADOR
    ========================== */

    const searchInput = document.getElementById("searchUser");

    searchInput.addEventListener("input", function () {

        const search = this.value.toLowerCase();

        const rows = document.querySelectorAll("#usersTable tr");


        rows.forEach(row => {

            const text = row.textContent.toLowerCase();

            row.style.display =
                text.includes(search) ? "" : "none";

        });

    });



    /* =========================
       FILTRO DE ROLES
    ========================== */

    const roleFilter = document.getElementById("roleFilter");


    roleFilter.addEventListener("change", function () {

        const selectedRole = this.value;

        const rows = document.querySelectorAll("#usersTable tr");


        rows.forEach(row => {

            const rowRole = row.dataset.role;


            if (
                selectedRole === "all" ||
                rowRole === selectedRole
            ) {

                row.style.display = "";

            } else {

                row.style.display = "none";

            }

        });

    });



   


    /* =========================
       BOTONES ELIMINAR
    ========================== */
function confirmarEliminacion() {

    return confirm(
        "¿Estás seguro de que deseas eliminar este usuario?"
    );

}


</script>




</body>

</html>