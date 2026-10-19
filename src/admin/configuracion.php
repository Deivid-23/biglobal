<?php

require_once __DIR__ . "/../../app/bootstrap.php";

protegerRol("admin");

$conexion = Conexion::conectar();




/* =========================================================
   HELPERS LOCALES
========================================================= */
function redirigirCon(string $tab, string $tipo, string $mensaje): void
{
    $_SESSION["flash_config"] = [
        "tipo"    => $tipo,        // "ok" | "error"
        "mensaje" => $mensaje,
        "tab"     => $tab,
    ];
    header("Location: index.php?page=configuracion&tab=" . urlencode($tab));
    exit;
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
           PERFIL — datos personales
        ===================================================== */
        case "perfil":

            $nombre   = trim($_POST["nombre"]   ?? "");
            $apellido = trim($_POST["apellido"] ?? "");
            $correo   = trim($_POST["correo"]   ?? "");

            if ($nombre === "" || $correo === "") {
                redirigirCon("perfil", "error", "Nombre y correo son obligatorios.");
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                redirigirCon("perfil", "error", "El correo electrónico no es válido.");
            }

            /* Verificar que el correo no lo tenga otro usuario */
            $stmt = $conexion->prepare("
                SELECT id FROM usuarios
                WHERE correo = :correo AND id <> :id
                LIMIT 1
            ");
            $stmt->execute([
                ":correo" => $correo,
                ":id"     => (int) $_SESSION["usuario_id"],
            ]);

            if ($stmt->fetch()) {
                redirigirCon("perfil", "error", "Ese correo ya está en uso por otra cuenta.");
            }

            $stmt = $conexion->prepare("
                UPDATE usuarios
                SET nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo
                WHERE id = :id
            ");
            $stmt->execute([
                ":nombre"   => $nombre,
                ":apellido" => $apellido !== "" ? $apellido : "No especificado",
                ":correo"   => $correo,
                ":id"       => (int) $_SESSION["usuario_id"],
            ]);

            /* Refrescar sesión */
            $_SESSION["nombre"]   = $nombre;
            $_SESSION["apellido"] = $apellido !== "" ? $apellido : "No especificado";
            $_SESSION["correo"]   = $correo;

            redirigirCon("perfil", "ok", "Perfil actualizado correctamente.");


        /* =====================================================
           PERFIL — cambiar contraseña
        ===================================================== */
        case "password":

            $actual    = $_POST["password_actual"]    ?? "";
            $nueva     = $_POST["password_nueva"]     ?? "";
            $confirmar = $_POST["password_confirmar"] ?? "";

            if ($actual === "" || $nueva === "" || $confirmar === "") {
                redirigirCon("perfil", "error", "Todos los campos de contraseña son obligatorios.");
            }

            if ($nueva !== $confirmar) {
                redirigirCon("perfil", "error", "La nueva contraseña y su confirmación no coinciden.");
            }

            /* Mismas reglas que el registro (Configuración > Seguridad) */
            if (($errorPassword = Seguridad::validarPassword($nueva)) !== null) {
                redirigirCon("perfil", "error", $errorPassword);
            }

            /* Verificar contraseña actual */
            $stmt = $conexion->prepare("SELECT password FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([(int) $_SESSION["usuario_id"]]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($actual, $hash)) {
                redirigirCon("perfil", "error", "La contraseña actual no es correcta.");
            }

            $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);

            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$nuevoHash, (int) $_SESSION["usuario_id"]]);

            redirigirCon("perfil", "ok", "Contraseña actualizada correctamente.");


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

            Config::setMany($datos);

            redirigirCon("general", "ok", "Configuración general guardada.");


        /* =====================================================
           APARIENCIA
        ===================================================== */
        case "apariencia":

            $tema   = $_POST["tema_default"]  ?? "light";
            $acento = $_POST["color_acento"]  ?? "blue";

            $temasValidos   = ["light", "dark"];
            $acentosValidos = ["purple", "blue", "green", "pink", "orange"];

            if (!in_array($tema, $temasValidos, true))       $tema   = "light";
            if (!in_array($acento, $acentosValidos, true))   $acento = "blue";

            Config::setMany([
                "tema_default"  => $tema,
                "color_acento"  => $acento,
            ]);


            redirigirCon("apariencia", "ok", "Apariencia guardada.");


        /* =====================================================
           NOTIFICACIONES
        ===================================================== */
        case "notificaciones":

            Config::setMany([
                "notif_nuevo_usuario"   => isset($_POST["notif_nuevo_usuario"])   ? "1" : "0",
                "notif_nuevo_curso"     => isset($_POST["notif_nuevo_curso"])     ? "1" : "0",
                "notif_nuevo_reporte"   => isset($_POST["notif_nuevo_reporte"])   ? "1" : "0",
                "notif_resumen_semanal" => isset($_POST["notif_resumen_semanal"]) ? "1" : "0",
            ]);

            redirigirCon("notificaciones", "ok", "Preferencias de notificaciones guardadas.");


        /* =====================================================
           SEGURIDAD
        ===================================================== */
        case "seguridad":

            $minPassword  = (int) ($_POST["seg_min_password"]    ?? 6);
            $intentos     = (int) ($_POST["seg_intentos_login"]  ?? 5);
            $bloqueoMin   = (int) ($_POST["seg_bloqueo_minutos"] ?? 1);

            if ($minPassword < 4 || $minPassword > 32)   $minPassword = 6;
            if ($intentos < 1 || $intentos > 20)         $intentos = 5;
            if ($bloqueoMin < 1 || $bloqueoMin > 120)    $bloqueoMin = 1;

            Config::setMany([
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
                "smtp_password"     => $_POST["smtp_password"]          ?? "",
                "smtp_encriptacion" => trim($_POST["smtp_encriptacion"] ?? "tls"),
                "smtp_remitente"    => trim($_POST["smtp_remitente"]    ?? ""),
            ];

            if ($datos["smtp_remitente"] !== "" && !filter_var($datos["smtp_remitente"], FILTER_VALIDATE_EMAIL)) {
                redirigirCon("correo", "error", "El correo remitente no es válido.");
            }

            /* Si la contraseña llega vacía, conservar la anterior */
            if ($datos["smtp_password"] === "") {
                $datos["smtp_password"] = Config::get("smtp_password", "");
            }

            Config::setMany($datos);

            redirigirCon("correo", "ok", "Configuración SMTP guardada.");


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
            redirigirCon("perfil", "error", "Acción no válida.");
    }

} catch (PDOException $e) {

    error_log("[BiGlobal][configuracion] " . $e->getMessage());
    redirigirCon($accion ?: "perfil", "error", "Error de base de datos. Intenta de nuevo.");
}
