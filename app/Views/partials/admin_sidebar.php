<?php
/**
 * Sidebar compartido del panel admin.
 *
 * Espera una variable $paginaActiva definida ANTES del require,
 * con uno de estos valores: "inicio", "usuarios", "cursos",
 * "idiomas", "lecciones", "reportes", "errores", "moderacion",
 * "certificados", "configuracion". Marca ese item con clase
 * "active"; si no coincide ninguno, ningun item queda activo.
 */
$paginaActiva = $paginaActiva ?? "";

if (!function_exists("claseMenuAdmin")) {
    function claseMenuAdmin(string $pagina, string $paginaActiva): string
    {
        return $pagina === $paginaActiva ? "menu-item active" : "menu-item";
    }
}
?>

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

            <a href="index.php" class="<?= claseMenuAdmin("inicio", $paginaActiva) ?>">
                <i class="fa-solid fa-house"></i>
                <span>Inicio</span>
            </a>


            <p class="menu-title">
                GESTIÓN
            </p>


            <a href="usuarios.php" class="<?= claseMenuAdmin("usuarios", $paginaActiva) ?>">
                <i class="fa-solid fa-users"></i>
                <span>Usuarios y roles</span>
            </a>


            <a href="cursos.php" class="<?= claseMenuAdmin("cursos", $paginaActiva) ?>">
                <i class="fa-solid fa-book"></i>
                <span>Catálogo de cursos</span>
            </a>


            <a href="idiomas.php" class="<?= claseMenuAdmin("idiomas", $paginaActiva) ?>">
                <i class="fa-solid fa-globe"></i>
                <span>Idiomas</span>
            </a>


            <a href="lecciones.php" class="<?= claseMenuAdmin("lecciones", $paginaActiva) ?>">
                <i class="fa-solid fa-book-open"></i>
                <span>Lecciones y actividades</span>
            </a>


            <p class="menu-title">
                CONTROL
            </p>


            <a href="reportes.php" class="<?= claseMenuAdmin("reportes", $paginaActiva) ?>">
                <i class="fa-solid fa-chart-column"></i>
                <span>Reportes</span>
            </a>


            <a href="errores.php" class="<?= claseMenuAdmin("errores", $paginaActiva) ?>">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Reportes y errores</span>
            </a>


            <a href="moderacion.php" class="<?= claseMenuAdmin("moderacion", $paginaActiva) ?>">
                <i class="fa-solid fa-comments"></i>
                <span>Moderación</span>
            </a>


            <a href="certificados.php" class="<?= claseMenuAdmin("certificados", $paginaActiva) ?>">
                <i class="fa-solid fa-certificate"></i>
                <span>Certificados</span>
            </a>


            <p class="menu-title">
                SISTEMA
            </p>


            <a href="configuracion.php" class="<?= claseMenuAdmin("configuracion", $paginaActiva) ?>">
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
