<?php /* <head> común de las pantallas de cuenta. Variable: $tituloPagina */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <script src="../js/tema.js"></script>
    <meta name="theme-color" content="#EFF6FF">
    <meta name="robots" content="noindex">
    <title><?= e($tituloPagina) ?> | BiGlobal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha384-PPIZEGYM1v8zp5Py7UjFb79S58UeqCL9pYVnVPURKEqvioPROaVAJKKLzvH2rDnI" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="../css/tokens.css">
    <link rel="stylesheet" href="../css/componentes.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body class="auth-body">
<div class="tema-esquina">
    <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
        <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
        <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
    </button>
</div>
<main class="auth-shell" id="contenido">
    <a class="marca-logo" href="../index.html">
        <img src="../img/logo-Biglobal-icono.png" alt="" width="40" height="40"> BiGlobal
    </a>
