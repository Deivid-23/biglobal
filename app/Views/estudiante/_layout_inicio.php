<?php
/**
 * Parte superior del layout del estudiante.
 * Variables que llegan desde EstudianteController::render():
 *   $titulo, $paginaActiva, $estudiante, $flash
 */

require_once __DIR__ . "/_helpers.php";

$menu = [
    ["page" => "inicio",       "texto" => "Inicio",          "icono" => "fa-house"],
    ["page" => "cursos",       "texto" => "Explorar cursos", "icono" => "fa-compass"],
    ["page" => "mis-cursos",   "texto" => "Mis cursos",      "icono" => "fa-book-open"],
    ["page" => "certificados", "texto" => "Certificados",    "icono" => "fa-award"],
];
?>
<!DOCTYPE html>
<html lang="es"<?= Panel::atributosHtml() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <script src="../js/tema.js"></script>
    <meta name="theme-color" content="#FFFFFF">
    <title><?= e($titulo) ?> | BiGlobal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="css/estudiante.css">
</head>
<body class="est-body">
<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

<div class="est-app">

    <!-- ================= BARRA LATERAL (escritorio) ================= -->
    <aside class="est-lateral" aria-label="Menú del estudiante">
        <div class="est-lateral__cabecera">
            <a class="est-marca" href="index.php">
                <img src="../img/logo-Biglobal.png" alt="" width="36" height="36">
                <span>BiGlobal</span>
            </a>
            <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
                <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
                <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
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
                <span class="est-avatar" aria-hidden="true"><?= e($estudiante["iniciales"]) ?></span>
                <span class="est-perfil__texto">
                    <strong><?= e($estudiante["nombre"]) ?></strong>
                    <small>Mi perfil</small>
                </span>
            </a>
            <a class="est-nav__enlace est-salir" href="../auth/logout.php">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                <span>Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- ================= CONTENIDO ================= -->
    <div class="est-columna">

        <!-- Encabezado de celular -->
        <header class="est-cabecera-movil">
            <a class="est-marca" href="index.php">
                <img src="../img/logo-Biglobal.png" alt="" width="32" height="32">
                <span>BiGlobal</span>
            </a>
            <div class="cabecera-acciones">
                <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
                    <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
                    <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
                </button>
            <a class="est-avatar est-avatar--boton" href="index.php?page=perfil" aria-label="Mi perfil">
                <?= e($estudiante["iniciales"]) ?>
            </a>
            </div>
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
