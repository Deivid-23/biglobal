<?php
/**
 * Parte superior del layout del estudiante.
 * Variables que llegan desde EstudianteController::render():
 *   $titulo, $paginaActiva, $estudiante, $flash, $avisosSinLeer, $campanaPanel, $zonaUsuario
 *
 * Igual que los otros roles: menú lateral con las secciones y una barra
 * superior con la campana, el tema y el usuario. En celular el menú
 * lateral se abre como cajón (botón ☰) y abajo quedan 4 atajos.
 */

require_once __DIR__ . "/_helpers.php";

/* "tabbar" => true: atajo de la barra inferior del celular (máximo 4) */
$menu = [
    ["page" => "inicio",       "texto" => "Inicio",          "icono" => "fa-house", "tabbar" => true],
    ["page" => "ruta",         "texto" => "Mi ruta",         "icono" => "fa-route", "corto" => "Ruta", "tabbar" => true],
    ["page" => "cursos",       "texto" => "Explorar cursos", "icono" => "fa-compass"],
    ["page" => "mis-cursos",   "texto" => "Mis cursos",      "icono" => "fa-book-open", "corto" => "Cursos", "tabbar" => true],
    ["page" => "clases",       "texto" => "Clases en vivo",  "icono" => "fa-video"],
    ["page" => "grupos",       "texto" => "Mis grupos",      "icono" => "fa-people-group", "corto" => "Grupos", "tabbar" => true],
    ["page" => "insignias",    "texto" => "Insignias",       "icono" => "fa-medal"],
    ["page" => "certificados", "texto" => "Certificados",    "icono" => "fa-award"],
];
?>
<!DOCTYPE html>
<html lang="es"<?= Panel::atributosHtml() ?> data-zona="<?= e($zonaUsuario) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <script src="../js/tema.js<?= version("js/tema.js") ?>"></script>
    <meta name="theme-color" content="#FFFFFF">
    <title><?= e($titulo) ?> | BiGlobal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">

    <link rel="stylesheet" href="../css/tokens.css<?= version("css/tokens.css") ?>">
    <link rel="stylesheet" href="../css/componentes.css<?= version("css/componentes.css") ?>">
    <link rel="stylesheet" href="css/estudiante.css<?= version("estudiante/css/estudiante.css") ?>">
</head>
<body class="est-body">
<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

<div class="est-app">

    <!-- ================= MENÚ LATERAL (en celular se abre como cajón) ================= -->
    <aside class="est-lateral" id="est-menu" aria-label="Menú del estudiante">
        <div class="est-lateral__cabecera">
            <a class="est-marca" href="index.php">
                <img src="../img/logo-Biglobal-icono.png" alt="" width="36" height="36">
                <span>BiGlobal</span>
            </a>
            <button class="est-icono-boton est-cerrar-menu" type="button" data-cerrar-menu aria-label="Cerrar menú">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="est-nav" aria-label="Principal">
            <?php foreach ($menu as $item): $activo = $paginaActiva === $item["page"]; ?>
                <a class="est-nav__enlace <?= $activo ? "es-activo" : "" ?>"
                   href="index.php?page=<?= e($item["page"]) ?>"
                   <?= $activo ? 'aria-current="page"' : "" ?>>
                    <i class="fa-solid <?= e($item["icono"]) ?>" aria-hidden="true"></i>
                    <span><?= e($item["texto"]) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="est-lateral__pie">
            <!-- Tu nombre lleva a "Mi perfil" -->
            <a class="est-perfil <?= $paginaActiva === "perfil" ? "es-activo" : "" ?>" href="index.php?page=perfil"
               <?= $paginaActiva === "perfil" ? 'aria-current="page"' : "" ?>>
                <?= FotoPerfil::avatar((int) $_SESSION["usuario_id"], $estudiante["iniciales"], "est-avatar") ?>
                <span class="est-perfil__texto">
                    <strong><?= e($estudiante["nombre"]) ?></strong>
                    <small>Mi perfil</small>
                </span>
            </a>
            <form class="est-salir-form" method="POST" action="../auth/logout.php">
                <?= campoCsrf() ?>
                <button class="est-nav__enlace est-salir" type="submit">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </aside>
    <div class="est-velo" data-cerrar-menu></div>

    <!-- ================= CONTENIDO ================= -->
    <div class="est-columna">

        <!-- Barra superior (igual que en los otros roles) -->
        <header class="est-barra">
            <button class="est-icono-boton est-abrir-menu" type="button" data-abrir-menu
                    aria-controls="est-menu" aria-expanded="false" aria-label="Abrir menú">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <a class="est-marca est-barra__marca" href="index.php">
                <img src="../img/logo-Biglobal-icono.png" alt="" width="32" height="32">
                <span>BiGlobal</span>
            </a>
            <p class="est-barra__titulo"><?= e($titulo) ?></p>

            <?php require __DIR__ . "/../panel/_campana.php"; ?>

            <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
                <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
                <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
            </button>

            <a class="est-usuario" href="index.php?page=perfil" aria-label="Mi perfil">
                <span class="est-usuario__nombre"><?= e($estudiante["nombre"]) ?></span>
                <?= FotoPerfil::avatar((int) $_SESSION["usuario_id"], $estudiante["iniciales"], "est-avatar") ?>
            </a>
        </header>

        <main id="contenido" class="est-principal" tabindex="-1">

            <?php if ($flash):
                // Ícono según el tipo de aviso (logro, éxito, info o error)
                $iconosFlash = ["logro" => "fa-trophy", "exito" => "fa-circle-check", "info" => "fa-lock", "error" => "fa-circle-exclamation"];
            ?>
                <div class="toast-biglobal toast-biglobal--<?= $flash["tipo"] === "logro" ? "logro" : e($flash["tipo"]) ?>"
                     role="status" data-toast>
                    <i class="fa-solid <?= e($iconosFlash[$flash["tipo"]] ?? "fa-circle-info") ?>" aria-hidden="true"></i>
                    <span><?= e($flash["mensaje"]) ?></span>
                </div>
            <?php endif; ?>
