<?php
/**
 * Topbar compartido del panel admin.
 *
 * Espera $tituloPagina (ej. "Usuarios y roles") y opcionalmente
 * $panelLabel (por defecto "Gestión de plataforma") definidas
 * ANTES del require.
 */
$panelLabel = $panelLabel ?? "Gestión de plataforma";
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
