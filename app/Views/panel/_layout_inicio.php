<?php
/**
 * Parte superior del layout de los paneles (admin, super admin, profesor).
 * Variables (desde Panel::render): $tituloPagina, $paginaActiva, $menuPanel,
 * $usuarioPanel, $perfilUrl, $flashPanel, $conGraficos, $campanaPanel (null si no es admin)
 */
?>
<!DOCTYPE html>
<html lang="es"<?= Panel::atributosHtml() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#EFF6FF">
    <meta name="robots" content="noindex">
    <!-- Tema claro/oscuro: va primero para que no haya parpadeo -->
    <script src="../js/tema.js<?= version("js/tema.js") ?>"></script>
    <title><?= e($tituloPagina) ?> | BiGlobal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">

    <link rel="stylesheet" href="../css/tokens.css<?= version("css/tokens.css") ?>">
    <link rel="stylesheet" href="../css/componentes.css<?= version("css/componentes.css") ?>">
    <link rel="stylesheet" href="../css/panel.css<?= version("css/panel.css") ?>">

    <?php if ($conGraficos): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <?php endif; ?>
    <script src="../js/panel.js<?= version("js/panel.js") ?>" defer></script>
</head>
<body class="pnl-body">
<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

<div class="pnl-app">

    <!-- ================= BARRA LATERAL ================= -->
    <aside class="pnl-lateral" id="pnl-menu" aria-label="Menú del panel">
        <a class="pnl-marca" href="<?= e($menuPanel[array_key_first($menuPanel)][0][3]) ?>">
            <img src="../img/logo-Biglobal.png" alt="" width="36" height="36">
            <span>BiGlobal</span>
        </a>

        <span class="pnl-rol <?= $usuarioPanel["rol"] === "superadmin" ? "pnl-rol--super" : "" ?>">
            <i class="fa-solid <?= $usuarioPanel["rol"] === "superadmin" ? "fa-crown" : ($usuarioPanel["rol"] === "docente" ? "fa-chalkboard-user" : "fa-shield-halved") ?>" aria-hidden="true"></i>
            <?= e($usuarioPanel["etiqueta"]) ?>
        </span>

        <nav class="pnl-nav" aria-label="Secciones">
            <?php foreach ($menuPanel as $grupo => $opciones): ?>
                <div class="pnl-nav__grupo">
                    <p class="pnl-nav__titulo"><?= e($grupo) ?></p>
                    <?php foreach ($opciones as $op):
                        [$clave, $texto, $icono, $url] = $op;
                        $insignia = (int) ($op[4] ?? 0);
                        $activo = $paginaActiva === $clave;
                    ?>
                        <a class="pnl-nav__enlace <?= $activo ? "es-activo" : "" ?>" href="<?= e($url) ?>"
                           <?= $activo ? 'aria-current="page"' : "" ?>>
                            <i class="fa-solid <?= e($icono) ?>" aria-hidden="true"></i>
                            <span><?= e($texto) ?></span>
                            <?php if ($insignia > 0): ?>
                                <span class="pnl-insignia" aria-label="<?= $insignia ?> pendientes"><?= $insignia ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="pnl-lateral__pie">
            <a class="pnl-nav__enlace <?= $paginaActiva === "perfil" ? "es-activo" : "" ?>" href="<?= e($perfilUrl) ?>">
                <i class="fa-solid fa-user" aria-hidden="true"></i><span>Mi perfil</span>
            </a>
            <form class="pnl-salir" method="POST" action="../auth/logout.php">
                <?= campoCsrf() ?>
                <button class="pnl-nav__enlace" type="submit">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="pnl-velo" data-cerrar-menu></div>

    <!-- ================= CONTENIDO ================= -->
    <div class="pnl-columna">
        <header class="pnl-barra">
            <button class="pnl-icono-boton pnl-abrir-menu" type="button" data-abrir-menu
                    aria-controls="pnl-menu" aria-expanded="false" aria-label="Abrir menú">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <p class="pnl-barra__titulo"><?= e($tituloPagina) ?></p>

            <?php if ($campanaPanel !== null) require __DIR__ . "/_campana.php"; ?>

            <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
                <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
                <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
            </button>

            <a class="pnl-usuario" href="<?= e($perfilUrl) ?>" aria-label="Mi perfil">
                <span class="pnl-usuario__nombre"><?= e($usuarioPanel["nombre"]) ?></span>
                <span class="pnl-avatar" aria-hidden="true"><?= e($usuarioPanel["iniciales"]) ?></span>
            </a>
        </header>

        <main id="contenido" class="pnl-principal" tabindex="-1">

            <?php if ($flashPanel): ?>
                <div class="toast-biglobal toast-biglobal--<?= $flashPanel["tipo"] === "ok" ? "exito" : "error" ?>"
                     role="status" data-toast>
                    <i class="fa-solid <?= $flashPanel["tipo"] === "ok" ? "fa-circle-check" : "fa-circle-exclamation" ?>" aria-hidden="true"></i>
                    <span><?= e($flashPanel["mensaje"]) ?></span>
                </div>
            <?php endif; ?>
