<?php

/**
 * Guarda la configuración GLOBAL del sitio (solo POST).
 * Exclusivo del super administrador: un admin recibe 403 aunque
 * envíe el formulario a mano.
 *
 * El perfil y la contraseña propios se guardan en src/auth/mi_cuenta.php.
 */

require_once __DIR__ . "/../../app/bootstrap.php";

protegerSuperAdmin();

$conexion = Conexion::conectar();

/* =========================================================
   HELPERS LOCALES
========================================================= */
function redirigirCon(string $tab, string $tipo, string $mensaje): void
{
    Panel::volver("index.php?page=configuracion&tab=" . urlencode($tab), $tipo, $mensaje);
}

/**
 * Guarda las claves y deja en la auditoría CUÁLES cambiaron
 * (solo los nombres, nunca los valores: puede haber contraseñas).
 */
function guardarConfig(string $tab, array $datos): void
{
    $cambiadas = [];
    foreach ($datos as $clave => $valor) {
        if ((string) Config::get($clave, "") !== (string) $valor) {
            $cambiadas[] = $clave;
        }
    }

    Config::setMany($datos);

    if ($cambiadas) {
        Auditoria::registrar("config.cambiar", "configuracion", null, $tab, null, implode(", ", $cambiadas));
    }
}

/* =========================================================
   SOLO POST
========================================================= */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php?page=configuracion");
    exit;
}

verificarCsrfFormulario($_POST["csrf_token"] ?? "");

$accion = $_POST["accion"] ?? "";

try {

    switch ($accion) {

        /* =====================================================
           GENERAL
        ===================================================== */
        case "general":

            $datos = [
                "sitio_nombre"      => trim($_POST["sitio_nombre"]      ?? ""),
                "sitio_descripcion" => trim($_POST["sitio_descripcion"] ?? ""),
                "sitio_correo"      => trim($_POST["sitio_correo"]      ?? ""),
                "sitio_idioma"      => trim($_POST["sitio_idioma"]      ?? "es"),
                "sitio_zona"        => trim($_POST["sitio_zona"]        ?? "America/Bogota"),
            ];

            if ($datos["sitio_nombre"] === "") {
                redirigirCon("general", "error", "El nombre del sitio es obligatorio.");
            }

            if ($datos["sitio_correo"] !== "" && !filter_var($datos["sitio_correo"], FILTER_VALIDATE_EMAIL)) {
                redirigirCon("general", "error", "El correo de contacto no es válido.");
            }

            guardarConfig($accion, $datos);

            redirigirCon("general", "ok", "Configuración general guardada.");


        /* =====================================================
           APARIENCIA
        ===================================================== */
        case "apariencia":

            $tema   = $_POST["tema_default"]  ?? "light";
            $acento = strtolower(trim($_POST["color_acento"] ?? "blue"));

            // "libre" = el color elegido con el selector (#RRGGBB)
            if ($acento === "libre") {
                $acento = strtolower(trim($_POST["color_acento_libre"] ?? ""));
            }

            $temasValidos   = ["light", "dark"];
            $acentosValidos = ["purple", "blue", "green", "pink", "orange"];

            if (!in_array($tema, $temasValidos, true)) $tema = "light";

            // Se acepta un nombre de la lista o cualquier color #RRGGBB
            if (!in_array($acento, $acentosValidos, true) && !Panel::esColor($acento)) {
                redirigirCon("apariencia", "error", "Ese color no es válido.");
            }

            guardarConfig($accion, [
                "tema_default"  => $tema,
                "color_acento"  => $acento,
            ]);


            redirigirCon("apariencia", "ok", "Apariencia guardada.");


        /* =====================================================
           NOTIFICACIONES
        ===================================================== */
        case "notificaciones":

            /* Una clave por cada tipo de aviso que existe (ver Notificador::TIPOS).
               notif_nuevo_reporte ya no se muestra: no hay función de reportes todavía. */
            $preferencias = [];
            foreach (Notificador::TIPOS as $clave) {
                $preferencias[$clave] = isset($_POST[$clave]) ? "1" : "0";
            }
            // Aviso a estudiantes (no es del equipo): GamificacionModel::recordatoriosRacha
            $preferencias["racha_recordatorio"] = isset($_POST["racha_recordatorio"]) ? "1" : "0";
            guardarConfig($accion, $preferencias);

            redirigirCon("notificaciones", "ok", "Preferencias de notificaciones guardadas.");


        /* =====================================================
           SEGURIDAD
        ===================================================== */
        case "seguridad":

            $minPassword  = (int) ($_POST["seg_min_password"]    ?? Seguridad::MIN_PASSWORD);
            $intentos     = (int) ($_POST["seg_intentos_login"]  ?? 5);
            $bloqueoMin   = (int) ($_POST["seg_bloqueo_minutos"] ?? 1);

            $minPassword = max(Seguridad::MIN_PASSWORD, min(Seguridad::MAX_PASSWORD, $minPassword));
            if ($intentos < 1 || $intentos > 20)         $intentos = 5;
            if ($bloqueoMin < 1 || $bloqueoMin > 120)    $bloqueoMin = 1;

            guardarConfig($accion, [
                "seg_min_password"       => (string) $minPassword,
                "seg_requiere_mayuscula" => isset($_POST["seg_requiere_mayuscula"]) ? "1" : "0",
                "seg_requiere_numero"    => isset($_POST["seg_requiere_numero"])    ? "1" : "0",
                "seg_intentos_login"     => (string) $intentos,
                "seg_bloqueo_minutos"    => (string) $bloqueoMin,
            ]);

            redirigirCon("seguridad", "ok", "Configuración de seguridad guardada.");


        /* =====================================================
           CORREO / SMTP
        ===================================================== */
        case "correo":

            $datos = [
                "smtp_host"         => trim($_POST["smtp_host"]         ?? ""),
                "smtp_puerto"       => trim($_POST["smtp_puerto"]       ?? "587"),
                "smtp_usuario"      => trim($_POST["smtp_usuario"]      ?? ""),
                "smtp_encriptacion" => trim($_POST["smtp_encriptacion"] ?? "tls"),
                "smtp_remitente"    => trim($_POST["smtp_remitente"]    ?? ""),
            ];

            if ($datos["smtp_remitente"] !== "" && !filter_var($datos["smtp_remitente"], FILTER_VALIDATE_EMAIL)) {
                redirigirCon("correo", "error", "El correo remitente no es válido.");
            }

            guardarConfig($accion, $datos);

            redirigirCon("correo", "ok", "Configuración SMTP guardada.");


        /* =====================================================
           CORREO DE PRUEBA — solo a la cuenta del super admin
           (nunca a una dirección escrita en el formulario)
        ===================================================== */
        case "correo_prueba":

            $stmt = $conexion->prepare("SELECT nombre, correo FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([(int) $_SESSION["usuario_id"]]);
            $yo = $stmt->fetch();

            if (!$yo || !filter_var($yo["correo"], FILTER_VALIDATE_EMAIL)) {
                redirigirCon("correo", "error", "Tu cuenta no tiene un correo válido para la prueba.");
            }

            $sitio = Config::get("sitio_nombre", "BiGlobal");
            $nombre = htmlspecialchars($yo["nombre"], ENT_QUOTES, "UTF-8");
            $ok = Correo::enviar(
                $yo["correo"],
                "Correo de prueba de {$sitio}",
                '<div style="font-family:Arial,sans-serif;color:#1E293B"><h2 style="color:#2563EB">¡Funciona!</h2>'
                    . "<p>Hola, {$nombre}. Si lees esto, los correos de {$sitio} ya salen bien: "
                    . "recuperación de contraseña y avisos al equipo.</p></div>",
                "Hola, {$yo['nombre']}. Si lees esto, los correos de {$sitio} ya salen bien."
            );

            if ($ok) {
                redirigirCon("correo", "ok", "Correo de prueba enviado a {$yo['correo']}. Revisa también la carpeta de spam.");
            }
            redirigirCon("correo", "error", "No se pudo enviar: " . (Correo::ultimoError() ?: "error desconocido."));


        /* =====================================================
           SISTEMA — acciones especiales
        ===================================================== */
        case "sistema":

            $sub = $_POST["sub"] ?? "";

            if ($sub === "limpiar_cache") {
                Config::refresh();
                redirigirCon("sistema", "ok", "Caché de configuración limpiada.");
            }

            redirigirCon("sistema", "error", "Acción no reconocida.");


        /* =====================================================
           ACCIÓN DESCONOCIDA
        ===================================================== */
        default:
            redirigirCon("general", "error", "Acción no válida.");
    }

} catch (PDOException $e) {

    error_log("[BiGlobal][configuracion] " . $e->getMessage());
    redirigirCon(in_array($accion, ["general", "apariencia", "notificaciones", "seguridad", "correo", "sistema"], true) ? $accion : "general", "error", "Error de base de datos. Intenta de nuevo.");
}
