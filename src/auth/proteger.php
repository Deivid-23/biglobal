<?php

require_once __DIR__ . "/sesion.php";

/* =========================================================
   ROLES DE BIGLOBAL
   - superadmin : todo lo del admin + seguridad, correo, configuración
                  global, gestión de administradores y borrado definitivo.
   - admin      : contenido, usuarios estudiante/docente, solicitudes.
   - docente    : su panel de cursos (src/profesor).
   - estudiante : su panel de aprendizaje.
========================================================= */

/* =========================================================
   VERIFICAR SI HAY SESIÓN
   Si no hay usuario logueado, lo manda al login.
========================================================= */
function protegerSesion(): void
{
    if (!isset($_SESSION["usuario_id"])) {
        // Si la sesión se cerró porque desactivaron la cuenta, se lo decimos
        $aviso = !empty($GLOBALS["biglobal_cuenta_cerrada"]) ? "?error=cuenta_inactiva" : "";
        header("Location: ../auth/login.html" . $aviso);
        exit;
    }
}

/* =========================================================
   REFRESCAR LA SESIÓN CON LA BASE DE DATOS
   El rol y el estado se guardan en la sesión al iniciar. Si después
   un super admin cambia el rol o desactiva la cuenta, la sesión
   abierta seguía con los permisos viejos. Por eso, en cada petición
   con sesión, se vuelven a leer de la BD (una consulta pequeña).
   Lo llama app/bootstrap.php.
========================================================= */
function refrescarSesionUsuario(): void
{
    static $hecho = false;
    if ($hecho || empty($_SESSION["usuario_id"]) || !class_exists("Conexion")) {
        return;
    }
    $hecho = true;

    try {
        $stmt = Conexion::conectar()->prepare("
            SELECT u.activo, u.rol_id, r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int) $_SESSION["usuario_id"]]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si la BD falla un momento, no sacamos a nadie: la página dará su propio error
        error_log("[BiGlobal][refrescarSesion] " . $e->getMessage());
        return;
    }

    // Cuenta borrada, desactivada, con eliminación pendiente (3) o eliminada (4):
    // se cierra la sesión en este mismo momento
    if (!$fila || !in_array((int) $fila["activo"], [1, 2], true)) {
        $_SESSION = [];
        session_regenerate_id(true);
        $GLOBALS["biglobal_cuenta_cerrada"] = true;
        return;
    }

    $_SESSION["rol"]    = $fila["rol"];
    $_SESSION["rol_id"] = (int) $fila["rol_id"];
}

/**
 * La contraseña (o Google) ya se verificó y la cuenta tiene eliminación
 * pendiente: se guarda en la sesión SOLO el id de la cuenta a recuperar
 * (15 minutos, sin iniciar sesión todavía). Devuelve la página a la que
 * ir, o null si ya pasó la fecha de borrado.
 */
function iniciarRecuperacionCuenta(int $usuarioId): ?string
{
    require_once __DIR__ . "/../../app/Models/CuentaEliminacionModel.php";
    if (!CuentaEliminacionModel::pendiente(Conexion::conectar(), $usuarioId)) {
        return null;
    }
    session_regenerate_id(true);
    $_SESSION = ["recuperar_cuenta_id" => $usuarioId, "recuperar_cuenta_hasta" => time() + 900];
    return "recuperar_cuenta.php";
}

/* =========================================================
   NOMBRE DEL ROL
   Se aceptan nombres antiguos ("administrador", "instructor"...)
   para que ningún archivo viejo quede bloqueado por error.
========================================================= */
function normalizarRol(string $rol): string
{
    $rol = strtolower(trim($rol));

    $alias = [
        "administrador"       => "admin",
        "instructor"          => "docente",
        "usuario"             => "estudiante",
        "super administrador" => "superadmin",
        "super_admin"         => "superadmin",
        "super-admin"         => "superadmin",
    ];

    return $alias[$rol] ?? $rol;
}

/** Rol de la sesión actual, ya normalizado ("" si no hay sesión). */
function rolActual(): string
{
    return isset($_SESSION["rol"]) ? normalizarRol($_SESSION["rol"]) : "";
}

function esSuperAdmin(): bool
{
    return rolActual() === "superadmin";
}

/**
 * Página a la que va cada rol al iniciar sesión.
 * Las rutas son relativas a la carpeta src/auth/.
 */
function panelDeRol(string $rol): string
{
    switch (normalizarRol($rol)) {
        case "superadmin": return "../superadmin/index.php";
        case "admin":      return "../admin/index.php";
        case "docente":    return "../profesor/index.php";
        case "estudiante": return "../estudiante/index.php";
        default:           return "../index.html";
    }
}

/* =========================================================
   VERIFICAR ROL
   protegerRol("estudiante")                -> solo estudiantes
   protegerRol(["admin", "superadmin"])      -> cualquiera de la lista

   El super administrador puede entrar a TODO lo que es del admin:
   protegerRol("admin") también lo deja pasar.
========================================================= */
function protegerRol($rolesPermitidos): void
{
    protegerSesion();

    if (!isset($_SESSION["rol"])) {
        session_destroy();
        header("Location: ../auth/login.html");
        exit;
    }

    $permitidos = array_map("normalizarRol", (array) $rolesPermitidos);

    if (in_array("admin", $permitidos, true)) {
        $permitidos[] = "superadmin";
    }

    if (!in_array(rolActual(), $permitidos, true)) {
        http_response_code(403);
        require __DIR__ . "/acceso_denegado.php";
        exit;
    }
}

/**
 * Para acciones que solo puede hacer el super administrador.
 * Corta con 403 aunque la persona sea admin y llegue por URL directa.
 */
function protegerSuperAdmin(): void
{
    protegerRol("superadmin");
}
