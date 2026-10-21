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
    <?php if ($estado === "valido"): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
                integrity="sha384-3zSEDfvllQohrq0PHL1fOXJuC/jSOO34H46t6UQfobFOmxE5BpjjaIJY5F2/bMnU"
                crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
    <?php endif; ?>
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
        <?php
        // Docente del curso (firma). Sin docente asignado, firma la plataforma.
        $docente = trim(($cert["docente_nombre"] ?? "") . " "
            . (($cert["docente_apellido"] ?? "") === "No especificado" ? "" : ($cert["docente_apellido"] ?? "")));
        $codigoIdioma = $cert["idioma_codigo"] ?? "";

        // Sello: estrella de 32 puntas dibujada como polígono (sin imágenes externas)
        $puntas = [];
        for ($k = 0; $k < 64; $k++) {
            $radio = $k % 2 === 0 ? 60 : 53;
            $angulo = M_PI * $k / 32 - M_PI / 2;
            $puntas[] = round(60 + $radio * cos($angulo), 2) . "," . round(60 + $radio * sin($angulo), 2);
        }
        ?>
        <article class="diploma" aria-label="Certificado de <?= e($nombreCompleto) ?>">
            <div class="diploma__hoja">

                <!-- Franja lateral: marca, idioma y verificación -->
                <aside class="diploma__franja">
                    <span class="diploma__marca">
                        <img src="img/logo-Biglobal.png" alt="" width="40" height="40">
                        <span><?= e($sitio) ?></span>
                    </span>

                    <?php if ($codigoIdioma !== ""): ?>
                        <div class="diploma__idioma">
                            <span class="diploma__idioma-codigo"><?= e($codigoIdioma) ?></span>
                            <span class="diploma__idioma-nombre"><?= e($cert["idioma"]) ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="diploma__verificacion">
                        <div class="diploma__qr" data-qr="<?= e($enlace) ?>" role="img" aria-label="Código QR para verificar este certificado"></div>
                        <span class="diploma__etiqueta diploma__etiqueta--clara">Código de verificación</span>
                        <span class="diploma__codigo"><?= e($cert["codigo"]) ?></span>
                    </div>
                </aside>

                <!-- Cuerpo del certificado -->
                <div class="diploma__cuerpo">
                    <header class="diploma__titulo">
                        <h2>Certificado</h2>
                        <p>de finalización de curso</p>
                    </header>

                    <p class="diploma__otorgado">Se otorga a</p>
                    <p class="diploma__nombre"><?= e($nombreCompleto) ?></p>

                    <p class="diploma__motivo">
                        por completar todas las lecciones del curso
                        <strong class="diploma__curso"><?= e($cert["curso"]) ?></strong>
                        <?php $detalle = implode(" · ", array_filter([$cert["idioma"] ?? "", $cert["nivel"] ?? ""])); ?>
                        <?php if ($detalle !== ""): ?><span class="diploma__detalle">(<?= e($detalle) ?>)</span><?php endif; ?>
                        en <?= e($sitio) ?>.
                    </p>

                    <footer class="diploma__pie">
                        <div class="diploma__firma">
                            <span class="diploma__firma-nombre"><?= e($docente !== "" ? $docente : $sitio) ?></span>
                            <span class="diploma__linea" aria-hidden="true"></span>
                            <span class="diploma__etiqueta"><?= $docente !== "" ? "Docente del curso" : "Plataforma de aprendizaje" ?></span>
                        </div>

                        <svg class="diploma__sello" viewBox="0 0 120 150" aria-hidden="true">
                            <defs>
                                <path id="sello-circulo" d="M60,60 m-38,0 a38,38 0 1,1 76,0 a38,38 0 1,1 -76,0"></path>
                            </defs>
                            <path class="diploma__cinta" d="M38 92 L28 146 L42 136 L50 148 L58 98 Z"></path>
                            <path class="diploma__cinta" d="M82 92 L92 146 L78 136 L70 148 L62 98 Z"></path>
                            <polygon class="diploma__estrella" points="<?= implode(" ", $puntas) ?>"></polygon>
                            <circle class="diploma__aro" cx="60" cy="60" r="46"></circle>
                            <circle class="diploma__centro" cx="60" cy="60" r="30"></circle>
                            <text class="diploma__sello-texto"><textPath href="#sello-circulo" startOffset="0">LOGRO VERIFICADO · <?= e(mb_strtoupper($sitio)) ?> ·</textPath></text>
                            <path class="diploma__check" d="M48 60 l8 8 l16 -17"></path>
                        </svg>

                        <div class="diploma__firma">
                            <span class="diploma__firma-nombre"><?= e(fechaLarga($cert["fecha_emision"])) ?></span>
                            <span class="diploma__linea" aria-hidden="true"></span>
                            <span class="diploma__etiqueta">Fecha de emisión</span>
                        </div>
                    </footer>

                    <p class="diploma__enlace">Verifica su autenticidad en <?= e($enlace) ?></p>
                </div>
            </div>
        </article>

        <div class="verificar__acciones">
            <button class="boton boton--primario" type="button" onclick="window.print()">
                <i class="fa-solid fa-print" aria-hidden="true"></i> Imprimir o guardar en PDF
            </button>
        </div>
    <?php endif; ?>
</main>

<script>
/* Código QR del certificado: lleva a esta misma página de verificación.
   Si la librería no carga, el cuadro queda vacío y el código sigue visible. */
window.addEventListener("load", function () {
    var caja = document.querySelector("[data-qr]");
    if (!caja || typeof QRCode === "undefined") { if (caja) caja.hidden = true; return; }
    var url = caja.dataset.qr;
    if (url.charAt(0) === "/") url = location.origin + url;
    new QRCode(caja, { text: url, width: 112, height: 112, colorDark: "#1E3A8A", colorLight: "#FFFFFF", correctLevel: QRCode.CorrectLevel.M });
    caja.removeAttribute("title");
});
</script>

<footer class="pagina-pie">
    © 2026 <?= e($sitio) ?> ·
    <a href="privacidad.html">Privacidad</a>
    <a href="terminos.html">Términos</a>
</footer>
</body>
</html>
