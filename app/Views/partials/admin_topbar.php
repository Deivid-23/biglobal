<?php
/**
 * Topbar compartido del panel admin.
 *
 * Espera $tituloPagina (ej. "Usuarios y roles") y opcionalmente
 * $panelLabel (por defecto "Gestión de plataforma") definidas
 * ANTES del require. El nombre/inicial del admin se toman de la
 * sesión activa (misma logica que antes tenia cada pagina suelta).
 */
$panelLabel = $panelLabel ?? "Gestión de plataforma";

$nombreAdminTopbar = $_SESSION["nombre"] ?? "Administrador";
$apellidoAdminTopbar = $_SESSION["apellido"] ?? "";

$nombreCompletoAdminTopbar = trim($nombreAdminTopbar . " " . $apellidoAdminTopbar);

if (empty($nombreCompletoAdminTopbar)) {
    $nombreCompletoAdminTopbar = "Administrador";
}

$avatarAdminTopbar = strtoupper(substr($nombreAdminTopbar, 0, 1));
?>

        <header class="topbar">

            <div class="topbar-left">

                <button class="menu-toggle" id="menuToggle">

                    <i class="fa-solid fa-bars"></i>

                </button>


                <div>

                    <p class="panel-label">
                        <?= htmlspecialchars($panelLabel) ?>
                    </p>

                    <h1>
                        <?= htmlspecialchars($tituloPagina) ?>
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
                        <?= htmlspecialchars($avatarAdminTopbar) ?>
                    </div>

                    <div class="profile-info">

                        <strong>
                            <?= htmlspecialchars($nombreCompletoAdminTopbar) ?>
                        </strong>

                        <span>
                            Panel principal
                        </span>

                    </div>

                    <i class="fa-solid fa-chevron-down profile-arrow"></i>

                </div>

            </div>

        </header>