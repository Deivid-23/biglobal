<?php
/**
 * Verificación pública de certificados.
 *
 *   /certificado.php                 → formulario para escribir el código
 *   /certificado.php?codigo=BG-XXXX  → muestra el certificado si es válido
 *
 * Cualquiera puede usarla (una empresa, un colegio). Muestra el nombre
 * completo del estudiante, el curso y la fecha. Tiene un límite de
 * consultas por IP para que nadie pruebe códigos al azar.
 * El botón "Imprimir" deja solo el certificado (ver css/certificado.css).
 */
require_once __DIR__ . "/../app/bootstrap.php";
require_once __DIR__ . "/../app/Models/CertificadoModel.php";

$codigo = CertificadoModel::normalizar((string) ($_GET["codigo"] ?? ""));
$estado = "formulario";   // formulario | valido | no_existe | formato | limite
$cert = null;

if ($codigo !== "") {
    if (!CertificadoModel::codigoValido($codigo)) {
        $estado = "formato";
    } elseif (!LimiteIntentos::permitir("certificado:ip:" . LimiteIntentos::ipCliente(), 40, 3600)) {
        $estado = "limite";
    } else {
        try {
            $cert = CertificadoModel::buscarPorCodigo(Conexion::conectar(), $codigo);
            $estado = $cert ? "valido" : "no_existe";
        } catch (PDOException $e) {
            error_log("[BiGlobal][certificado] " . $e->getMessage());
            $estado = "no_existe";
        }
    }
}

/** 19 de octubre de 2026 */
function fechaLarga(?string $fecha): string
{
    $t = $fecha ? strtotime($fecha) : false;
    if (!$t) return "";
    $meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio",
              "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
    return date("j", $t) . " de " . $meses[(int) date("n", $t) - 1] . " de " . date("Y", $t);
}

$sitio = Config::get("sitio_nombre", "BiGlobal");
$nombreCompleto = $cert
    ? trim($cert["nombre"] . " " . ($cert["apellido"] === "No especificado" ? "" : $cert["apellido"]))
    : "";
$base = obtenerConfig()["app"]["url"];
$enlace = ($base !== "" ? $base : "") . "/certificado.php?codigo=" . rawurlencode($codigo);

header("X-Robots-Tag: noindex");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <script src="js/tema.js"></script>
    <title><?= $estado === "valido" ? "Certificado de " . e($nombreCompleto) : "Verificar certificado" ?> | <?= e($sitio) ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/tokens.css">
    <link rel="stylesheet" href="css/componentes.css">
    <link rel="stylesheet" href="css/paginas.css">
    <link rel="stylesheet" href="css/certificado.css">
</head>
<body class="pagina-body">
<a class="saltar-contenido" href="#contenido">Saltar al contenido</a>

<header class="pagina-cabecera">
    <div class="pagina-cabecera__barra">
        <a class="pagina-logo" href="index.html"><img src="img/logo-Biglobal.png" alt="" width="34" height="34"> <?= e($sitio) ?></a>
        <div class="cabecera-acciones">
            <a class="boton boton--secundario boton--sm" href="index.html">Ir al inicio</a>
            <button class="boton-tema" type="button" data-alternar-tema aria-label="Cambiar a modo oscuro">
                <i class="fa-solid fa-sun icono-sol" aria-hidden="true"></i>
                <i class="fa-solid fa-moon icono-luna" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <div class="pagina-titulo">
        <h1>Verificar certificado</h1>
        <p>Escribe el código que aparece en el certificado (por ejemplo, BG-1A2B3C4D) para comprobar que es auténtico.</p>
    </div>
</header>

<main id="contenido" class="pagina-contenido">

    <form class="verificar" method="GET" action="certificado.php" role="search">
        <label class="campo__etiqueta" for="codigo">Código del certificado</label>
        <div class="verificar__fila">
            <input class="entrada verificar__entrada" id="codigo" name="codigo" value="<?= e($codigo) ?>"
                   placeholder="BG-1A2B3C4D" maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
            <button class="boton boton--primario" type="submit">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Verificar
            </button>
        </div>
    </form>

    <?php if ($estado === "formato"): ?>
        <div class="aviso aviso--error verificar__aviso" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
            <span>Ese código no tiene el formato correcto. Debe verse así: <strong>BG-</strong> y 8 letras o números (ej. BG-1A2B3C4D).</span>
        </div>

    <?php elseif ($estado === "limite"): ?>
        <div class="aviso aviso--alerta verificar__aviso" role="alert">
            <i class="fa-solid fa-hourglass-half" aria-hidden="true"></i>
            <span>Se hicieron muchas consultas desde esta conexión. Inténtalo de nuevo en un rato.</span>
        </div>

    <?php elseif ($estado === "no_existe"): ?>
        <div class="aviso aviso--error verificar__aviso" role="alert">
            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
            <span>No encontramos un certificado con el código <strong><?= e($codigo) ?></strong>. Revisa que esté bien escrito.</span>
        </div>

    <?php elseif ($estado === "valido"): ?>
        <div class="aviso aviso--exito verificar__aviso" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <span><strong>Certificado válido.</strong> Fue emitido por <?= e($sitio) ?> a nombre de <?= e($nombreCompleto) ?>.</span>
        </div>

        <!-- El certificado: es lo único que sale al imprimir -->
        <article class="diploma" aria-label="Certificado de <?= e($nombreCompleto) ?>">
            <div class="diploma__marco">
                <header class="diploma__cabecera">
                    <span class="diploma__marca">
                        <img src="img/logo-Biglobal.png" alt="" width="36" height="36"> <?= e($sitio) ?>
                    </span>
                    <span class="diploma__sello" aria-hidden="true"><i class="fa-solid fa-award"></i></span>
                </header>

                <p class="diploma__tipo">Certificado de finalización</p>
                <p class="diploma__texto">Se certifica que</p>
                <h2 class="diploma__nombre"><?= e($nombreCompleto) ?></h2>
                <p class="diploma__texto">completó todas las lecciones del curso</p>
                <p class="diploma__curso"><?= e($cert["curso"]) ?></p>
                <p class="diploma__detalle">
                    <?= e(implode(" · ", array_filter([$cert["idioma"] ?? "", $cert["nivel"] ?? ""]))) ?>
                </p>

                <footer class="diploma__pie">
                    <div>
                        <span class="diploma__etiqueta">Fecha de emisión</span>
                        <span class="diploma__valor"><?= e(fechaLarga($cert["fecha_emision"])) ?></span>
                    </div>
                    <div>
                        <span class="diploma__etiqueta">Código de verificación</span>
                        <span class="diploma__valor diploma__codigo"><?= e($cert["codigo"]) ?></span>
                    </div>
                    <div class="diploma__verificar">
                        <span class="diploma__etiqueta">Verifícalo en</span>
                        <span class="diploma__valor diploma__enlace"><?= e($enlace) ?></span>
                    </div>
                </footer>
            </div>
        </article>

        <div class="verificar__acciones">
            <button class="boton boton--primario" type="button" onclick="window.print()">
                <i class="fa-solid fa-print" aria-hidden="true"></i> Imprimir o guardar en PDF
            </button>
        </div>
    <?php endif; ?>
</main>

<footer class="pagina-pie">
    © 2026 <?= e($sitio) ?> ·
    <a href="privacidad.html">Privacidad</a>
    <a href="terminos.html">Términos</a>
</footer>
</body>
</html>
