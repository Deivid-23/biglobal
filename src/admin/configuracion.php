<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

$guardado = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombreSitio = trim($_POST["nombre_sitio"] ?? "");
    $correoContacto = trim($_POST["correo_contacto"] ?? "");
    $modoMantenimiento = isset($_POST["modo_mantenimiento"]) ? "1" : "0";

    if ($nombreSitio !== "" && filter_var($correoContacto, FILTER_VALIDATE_EMAIL)) {

        $stmt = $conexion->prepare("
            INSERT INTO configuracion (clave, valor)
            VALUES (:clave, :valor)
            ON DUPLICATE KEY UPDATE valor = :valor
        ");

        $stmt->execute([":clave" => "nombre_sitio", ":valor" => $nombreSitio]);
        $stmt->execute([":clave" => "correo_contacto", ":valor" => $correoContacto]);
        $stmt->execute([":clave" => "modo_mantenimiento", ":valor" => $modoMantenimiento]);

        $guardado = true;
    }
}

$stmt = $conexion->prepare("SELECT clave, valor FROM configuracion");
$stmt->execute();

$config = [];
foreach ($stmt->fetchAll() as $fila) {
    $config[$fila["clave"]] = $fila["valor"];
}

$nombreSitio = $config["nombre_sitio"] ?? "BiGlobal";
$correoContacto = $config["correo_contacto"] ?? "";
$modoMantenimiento = ($config["modo_mantenimiento"] ?? "0") === "1";

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Configuración</title>

    <link rel="stylesheet" href="css/style.css">

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


            <a href="configuracion.php" class="menu-item active">

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
                        Sistema
                    </p>

                    <h1>
                        Configuración
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


        <section class="users-page">

            <div class="users-heading">
                <div>
                    <span class="section-label">
                        Sistema
                    </span>
                    <h2>
                        Configuración general
                    </h2>
                    <p>
                        Ajustes básicos de la plataforma BiGlobal.
                    </p>
                </div>
            </div>

            <div class="users-card" style="max-width:600px; padding:25px;">

                <?php if ($guardado): ?>
                    <div style="background:#ecfdf3; color:#12b76a; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:13px;">
                        Configuración guardada correctamente.
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="form-group">
                        <label for="nombre_sitio">Nombre del sitio</label>
                        <input
                            type="text"
                            id="nombre_sitio"
                            name="nombre_sitio"
                            value="<?= htmlspecialchars($nombreSitio) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="correo_contacto">Correo de contacto</label>
                        <input
                            type="email"
                            id="correo_contacto"
                            name="correo_contacto"
                            value="<?= htmlspecialchars($correoContacto) ?>"
                            required
                        >
                    </div>

                    <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                        <input
                            type="checkbox"
                            id="modo_mantenimiento"
                            name="modo_mantenimiento"
                            style="width:auto;"
                            <?= $modoMantenimiento ? "checked" : "" ?>
                        >
                        <label for="modo_mantenimiento" style="margin:0;">Activar modo mantenimiento</label>
                    </div>

                    <div class="modal-actions" style="margin-top:20px;">
                        <button type="submit" class="save-btn">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar cambios
                        </button>
                    </div>

                </form>

            </div>

        </section>

    </main>

</div>


<script>

    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");

    if (menuToggle) {

        menuToggle.addEventListener("click", () => {

            sidebar.classList.toggle("show");

        });

    }

</script>

</body>

</html>

