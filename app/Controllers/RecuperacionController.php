<?php

require_once __DIR__ . "/../Models/RecuperacionModel.php";
require_once __DIR__ . "/../Helpers/Correo.php";

/**
 * RecuperacionController
 *
 * solicitar()   -> auth/recuperar.php    : pide el correo y envía el enlace
 * restablecer() -> auth/restablecer.php  : abre el enlace y guarda la nueva clave
 *
 * Reglas de seguridad:
 *   - Siempre se responde lo mismo exista o no el correo (así nadie puede
 *     averiguar qué correos están registrados).
 *   - Máximo 3 enlaces por hora por usuario y 1 cada 30 s por navegador.
 *   - El enlace vence en 60 minutos y sirve una sola vez.
 */
class RecuperacionController
{
    private const MAX_POR_HORA = 3;

    public function solicitar(): void
    {
        $estado = "formulario";   // formulario | enviado
        $error = null;
        $correo = "";
        $enlaceLocal = null;      // solo en local, para probar sin SMTP

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $correo = trim($_POST["correo"] ?? "");

            if (!csrfValido($_POST["csrf_token"] ?? "")) {
                $error = "Tu sesión expiró. Recarga la página e inténtalo de nuevo.";
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $error = "Escribe un correo válido, por ejemplo nombre@correo.com.";
            } elseif (time() - ($_SESSION["recuperar_ultimo"] ?? 0) < 30) {
                $error = "Espera unos segundos antes de pedir otro enlace.";
            } elseif (!LimiteIntentos::permitir("recuperar:ip:" . LimiteIntentos::ipCliente(), 5, 3600)
                || !LimiteIntentos::permitir("recuperar:global", 60, 3600)) {
                // Tope en el servidor (no en la sesión): protege el cupo diario de correos
                error_log("[BiGlobal][recuperar] Tope de solicitudes alcanzado.");
                $error = "Se pidieron demasiados enlaces. Inténtalo de nuevo en un rato.";
            } else {
                $_SESSION["recuperar_ultimo"] = time();
                $enlaceLocal = $this->procesarSolicitud($correo);
                $estado = "enviado";
            }
        }

        require __DIR__ . "/../Views/auth/recuperar.php";
    }

    /**
     * Crea el token y envía el correo. No dice nada al usuario si el correo
     * no existe. Devuelve el enlace solo en entorno local.
     */
    private function procesarSolicitud(string $correo): ?string
    {
        try {
            $conexion = Conexion::conectar();
            $usuario = RecuperacionModel::buscarUsuarioPorCorreo($conexion, $correo);

            // Activas, docentes pendientes y cuentas con eliminación pendiente (para que puedan recuperarla)
            if (!$usuario || !in_array((int) $usuario["activo"], [1, 2, 3], true)) {
                return null; // mismo mensaje en pantalla, pero no se envía nada
            }

            if (RecuperacionModel::solicitudesRecientes($conexion, (int) $usuario["id"]) >= self::MAX_POR_HORA) {
                error_log("[BiGlobal][recuperar] Límite por hora alcanzado para usuario {$usuario['id']}");
                return null;
            }

            $token = RecuperacionModel::crearToken($conexion, (int) $usuario["id"]);
            $enlace = $this->urlRestablecer($token);

            if ($enlace === null) {
                return null;
            }

            $nombre = htmlspecialchars($usuario["nombre"], ENT_QUOTES, "UTF-8");
            $enlaceHtml = htmlspecialchars($enlace, ENT_QUOTES, "UTF-8");

            $html = <<<HTML
                <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;color:#1E293B">
                  <h2 style="color:#2563EB">Crea una nueva contraseña</h2>
                  <p>Hola, {$nombre}. Recibimos una solicitud para cambiar la contraseña de tu cuenta de BiGlobal.</p>
                  <p><a href="{$enlaceHtml}" style="display:inline-block;background:#2563EB;color:#fff;padding:12px 20px;border-radius:10px;text-decoration:none;font-weight:bold">Crear nueva contraseña</a></p>
                  <p style="color:#64748B;font-size:14px">El enlace vence en 60 minutos y sirve una sola vez. Si no fuiste tú, ignora este correo: tu contraseña no cambia.</p>
                </div>
                HTML;

            $texto = "Hola, {$usuario['nombre']}.\n\nPara crear una nueva contraseña de BiGlobal abre este enlace (vence en 60 minutos):\n{$enlace}\n\nSi no fuiste tú, ignora este correo.";

            $enviado = Correo::enviar($usuario["correo"], "Recupera tu contraseña de BiGlobal", $html, $texto);

            $entorno = obtenerConfig()["app"]["entorno"];
            if (!$enviado && $entorno === "local") {
                // En tu computador, sin SMTP, el enlace se muestra en pantalla
                // para que puedas probar. En producción NUNCA se muestra.
                return $enlace;
            }

            return null;

        } catch (Throwable $e) {
            error_log("[BiGlobal][recuperar] " . $e->getMessage());
            return null;
        }
    }

    /**
     * Arma la URL completa de restablecer.php.
     * - Producción: usa APP_URL (ej. https://biglobal.onrender.com).
     * - Local: usa el host y la carpeta actual (ej. http://localhost/BiGlobal/src/auth).
     */
    private function urlRestablecer(string $token): ?string
    {
        $config = obtenerConfig()["app"];

        if ($config["url"] !== "") {
            return $config["url"] . "/auth/restablecer.php?token=" . $token;
        }

        if ($config["entorno"] !== "local") {
            // Sin APP_URL no armamos enlaces con la cabecera Host (podría ser falsa)
            error_log("[BiGlobal][recuperar] Falta APP_URL en producción: no se envió el enlace.");
            return null;
        }

        $esHttps = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
            || (($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https");
        $host = $_SERVER["HTTP_HOST"] ?? "localhost";
        $carpeta = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "/auth/recuperar.php")), "/");

        return ($esHttps ? "https" : "http") . "://" . $host . $carpeta . "/restablecer.php?token=" . $token;
    }

    public function restablecer(): void
    {
        $token = (string) ($_GET["token"] ?? $_POST["token"] ?? "");
        $error = null;
        $reglas = Seguridad::reglasPassword();

        $conexion = Conexion::conectar();
        $solicitud = RecuperacionModel::buscarTokenValido($conexion, $token);

        if (!$solicitud) {
            $estado = "invalido";
            require __DIR__ . "/../Views/auth/restablecer.php";
            return;
        }

        $estado = "formulario";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $nueva = $_POST["password"] ?? "";
            $confirmar = $_POST["confirmar"] ?? "";

            if (!csrfValido($_POST["csrf_token"] ?? "")) {
                $error = "Tu sesión expiró. Recarga la página e inténtalo de nuevo.";
            } elseif ($nueva !== $confirmar) {
                $error = "Las contraseñas no coinciden.";
            } elseif (($errorPassword = Seguridad::validarPassword($nueva)) !== null) {
                $error = $errorPassword;
            } else {
                try {
                    RecuperacionModel::cambiarPassword($conexion, (int) $solicitud["id"], (int) $solicitud["usuario_id"], $nueva);

                    // Por seguridad, cerramos cualquier sesión abierta en este navegador
                    session_regenerate_id(true);
                    unset($_SESSION["usuario_id"], $_SESSION["rol"]);

                    header("Location: login.html?ok=password_actualizada");
                    exit;
                } catch (Throwable $e) {
                    error_log("[BiGlobal][restablecer] " . $e->getMessage());
                    $error = "No pudimos guardar la contraseña. Inténtalo de nuevo.";
                }
            }
        }

        require __DIR__ . "/../Views/auth/restablecer.php";
    }
}
