<?php

/**
 * Bootstrap de la aplicación.
 *
 * Carga lo que cualquier controlador necesita antes de atender una
 * petición: configuración, sesión segura, protección de rutas,
 * conexión a la base de datos, CSRF y el helper Config.
 *
 * OJO: en Linux (Docker/Render) las rutas distinguen mayúsculas.
 * La carpeta se llama app/Helpers y el archivo Config.php.
 */

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../src/auth/sesion.php";
require_once __DIR__ . "/../src/auth/proteger.php";
require_once __DIR__ . "/../src/bd/conexion.php";
require_once __DIR__ . "/../src/auth/csrf.php";
require_once __DIR__ . "/Helpers/Config.php";
require_once __DIR__ . "/Helpers/Seguridad.php";
require_once __DIR__ . "/Helpers/Panel.php";
require_once __DIR__ . "/Helpers/Correo.php";
require_once __DIR__ . "/Helpers/Notificador.php";
require_once __DIR__ . "/Helpers/LimiteIntentos.php";
require_once __DIR__ . "/Helpers/Auditoria.php";
require_once __DIR__ . "/Helpers/Video.php";
require_once __DIR__ . "/Helpers/Plan.php";

// Rol y estado de la cuenta siempre al día (ver src/auth/proteger.php)
refrescarSesionUsuario();

/**
 * Loguea el error real en el servidor (nunca al navegador)
 * y corta la ejecución con un mensaje genérico para el usuario.
 */
function manejarErrorBD(Throwable $e, string $mensaje): void
{
    error_log("[BiGlobal] " . $e->getMessage());
    die(htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8"));
}

/** Atajo para imprimir texto seguro en HTML. */
if (!function_exists("e")) {
    function e($valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
    }
}
