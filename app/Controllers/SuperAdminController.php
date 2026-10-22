<?php

require_once __DIR__ . "/../Models/AdminModel.php";
require_once __DIR__ . "/../Models/RevisionModel.php";
require_once __DIR__ . "/../Helpers/Correo.php";

/**
 * SuperAdminController
 *
 * Panel exclusivo del super administrador (src/superadmin/index.php).
 * Todo lo que hay aquí se valida en el servidor con protegerSuperAdmin():
 * un admin que escriba la URL a mano recibe "Acceso denegado" (403).
 *
 * Páginas: inicio (resumen del sistema), cuentas (roles, estado, borrado),
 *          auditoria (registro de cambios, solo lectura),
 *          configuracion (general, apariencia, notificaciones, seguridad,
 *          correo, sistema).
 *
 * Regla de oro: SIEMPRE debe quedar al menos un super administrador
 * activo. Por eso nadie puede quitarse a sí mismo el rol, desactivarse
 * ni borrarse, y no se puede quitar el último super administrador.
 */
class SuperAdminController
{
    private PDO $c;

    public const TABS = ["general", "apariencia", "notificaciones", "seguridad", "correo", "sistema"];

    public function __construct()
    {
        protegerSuperAdmin();
        $this->c = Conexion::conectar();
    }

    private function render(string $vista, string $activa, string $titulo, array $datos = [], bool $graficos = false): void
    {
        Panel::render(__DIR__ . "/../Views/superadmin/{$vista}.php", [
            "titulo"     => $titulo,
            "activa"     => $activa,
            "graficos"   => $graficos,
            "pendientes" => AdminModel::solicitudesPendientes($this->c),
            "revisiones" => RevisionModel::contarPendientes($this->c),
            "datos"      => $datos,
        ]);
    }

    /* =========================================================
       RESUMEN DEL SISTEMA
    ========================================================= */
    public function inicio(): void
    {
        $porRol = AdminModel::usuariosPorRol($this->c);

        $administradores = $this->c->query("
            SELECT u.id, u.nombre, u.apellido, u.correo, u.activo, r.nombre AS rol
            FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
            WHERE r.nombre IN ('admin', 'superadmin')
            ORDER BY r.nombre DESC, u.nombre
        ")->fetchAll();

        $this->render("inicio", "super-inicio", "Resumen del sistema", [
            "porRol"          => $porRol,
            "administradores" => $administradores,
            "stats"           => AdminModel::estadisticas($this->c),
            "seguridad"       => Seguridad::reglasPassword() + [
                "intentos" => Config::getInt("seg_intentos_login", 5),
                "bloqueo"  => Config::getInt("seg_bloqueo_minutos", 1),
            ],
            "correoListo"     => Correo::estaConfigurado(),
            "appUrl"          => obtenerConfig()["app"]["url"],
        ], true);
    }

    /* =========================================================
       CUENTAS Y ROLES
    ========================================================= */
    public function cuentas(): void
    {
        $filtro = $_GET["rol"] ?? "";
        $roles = $this->c->query("SELECT id, nombre FROM roles ORDER BY id")->fetchAll();

        $sql = "
            SELECT u.id, u.nombre, u.apellido, u.correo, u.activo, u.fecha_registro, u.rol_id, r.nombre AS rol
            FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id";
        $params = [];
        if (in_array($filtro, array_column($roles, "nombre"), true)) {
            $sql .= " WHERE r.nombre = ?";
            $params[] = $filtro;
        }
        $sql .= " ORDER BY FIELD(r.nombre, 'superadmin', 'admin', 'docente', 'estudiante'), u.nombre LIMIT 500";

        $stmt = $this->c->prepare($sql);
        $stmt->execute($params);
        $cuentas = $stmt->fetchAll();

        $this->render("cuentas", "super-cuentas", "Cuentas y roles", [
            "cuentas"  => $cuentas,
            // Plan de cada cuenta (null = la tabla de suscripciones aún no existe)
            "planes"   => Plan::disponible($this->c) ? Plan::planesDe($this->c, array_column($cuentas, "id")) : null,
            "roles"    => $roles,
            "filtro"   => $filtro,
            "miId"     => (int) $_SESSION["usuario_id"],
            "modal"    => [
                "roles"  => AdminModel::rolesPermitidos($this->c, true),
                "reglas" => Seguridad::reglasPassword(),
            ],
        ]);
    }

    /* =========================================================
       AUDITORÍA (solo lectura)
    ========================================================= */
    public function auditoria(): void
    {
        $filtro = (string) ($_GET["accion"] ?? "");
        if (!isset(Auditoria::ACCIONES[$filtro])) {
            $filtro = "";
        }

        $this->render("auditoria", "super-auditoria", "Auditoría", [
            "registros"  => Auditoria::recientes($this->c, $filtro ?: null),
            "filtro"     => $filtro,
            "disponible" => Auditoria::disponible($this->c),
        ]);
    }

    /** Cuántos super administradores activos hay (sin contar a $excepto). */
    private function superAdminsActivos(int $excepto = 0): int
    {
        $stmt = $this->c->prepare("
            SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
            WHERE r.nombre = 'superadmin' AND u.activo = 1 AND u.id <> ?
        ");
        $stmt->execute([$excepto]);
        return (int) $stmt->fetchColumn();
    }

    /** POST de la página de cuentas: cambiar_rol | cambiar_estado | eliminar */
    public function accionCuenta(): void
    {
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $volver = "index.php?page=cuentas" . (!empty($_POST["filtro"]) ? "&rol=" . urlencode($_POST["filtro"]) : "");
        $accion = $_POST["accion"] ?? "";
        $id     = (int) ($_POST["id"] ?? 0);
        $miId   = (int) $_SESSION["usuario_id"];

        $stmt = $this->c->prepare("
            SELECT u.id, u.nombre, u.activo, r.nombre AS rol
            FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$id]);
        $cuenta = $stmt->fetch();

        if (!$cuenta) {
            Panel::volver($volver, "error", "La cuenta no existe.");
        }

        // Nadie se cambia el rol, se desactiva ni se borra a sí mismo
        if ($id === $miId) {
            Panel::volver($volver, "error", "No puedes cambiar tu propia cuenta desde aquí. Pídeselo a otro super administrador.");
        }

        $esSuperYActivo = $cuenta["rol"] === "superadmin" && (int) $cuenta["activo"] === 1;
        $nombreCuenta = Auditoria::nombreCuenta($this->c, $id);

        try {
            switch ($accion) {
                case "cambiar_rol":
                    $rolId = (int) ($_POST["rol_id"] ?? 0);
                    $stmt = $this->c->prepare("SELECT nombre FROM roles WHERE id = ?");
                    $stmt->execute([$rolId]);
                    $nuevoRol = $stmt->fetchColumn();

                    if (!$nuevoRol) {
                        Panel::volver($volver, "error", "Ese rol no existe.");
                    }
                    if ($esSuperYActivo && $nuevoRol !== "superadmin" && $this->superAdminsActivos($id) === 0) {
                        Panel::volver($volver, "error", "No puedes quitar el último super administrador.");
                    }

                    $this->c->prepare("UPDATE usuarios SET rol_id = ? WHERE id = ?")->execute([$rolId, $id]);
                    if ($nuevoRol !== $cuenta["rol"]) {
                        Auditoria::registrar("cuenta.rol", "cuenta", $id, $nombreCuenta, $cuenta["rol"], $nuevoRol);
                    }
                    Panel::volver($volver, "ok", "Rol actualizado a «{$nuevoRol}».");

                case "cambiar_estado":
                    $activo = (int) ($_POST["activo"] ?? 1) === 1 ? 1 : 0;
                    if ($esSuperYActivo && $activo === 0 && $this->superAdminsActivos($id) === 0) {
                        Panel::volver($volver, "error", "No puedes desactivar el último super administrador.");
                    }
                    $this->c->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")->execute([$activo, $id]);
                    if ($activo !== (int) $cuenta["activo"]) {
                        Auditoria::registrar("cuenta.estado", "cuenta", $id, $nombreCuenta,
                            Auditoria::ESTADOS[(int) $cuenta["activo"]] ?? (string) $cuenta["activo"], Auditoria::ESTADOS[$activo]);
                    }
                    Panel::volver($volver, "ok", $activo ? "Cuenta activada." : "Cuenta desactivada.");

                case "eliminar":
                    if ($esSuperYActivo && $this->superAdminsActivos($id) === 0) {
                        Panel::volver($volver, "error", "No puedes eliminar el último super administrador.");
                    }
                    $this->c->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);
                    Auditoria::registrar("cuenta.eliminar", "cuenta", $id, $nombreCuenta, $cuenta["rol"], null);
                    Panel::volver($volver, "ok", "Cuenta eliminada definitivamente.");

                case "dar_premium":
                    // Premium de cortesía (beca, soporte, pruebas): sin pago y sin renovación
                    if (!Plan::disponible($this->c)) {
                        Panel::volver($volver, "error", "Los planes aún no están disponibles.");
                    }
                    $meses = (int) ($_POST["meses"] ?? 0);
                    if (!in_array($meses, [1, 3, 12], true)) {
                        Panel::volver($volver, "error", "Elige por cuánto tiempo dar Premium.");
                    }
                    $vigente = Plan::suscripcionVigente($this->c, $id);
                    $desde = $vigente ? new DateTimeImmutable($vigente["fin"]) : new DateTimeImmutable("now");
                    $fin = $desde->modify("+{$meses} months")->format("Y-m-d H:i:s");
                    Plan::otorgarManual($this->c, $id, $fin, $miId, "Cortesía de {$meses} " . ($meses === 1 ? "mes" : "meses"));
                    Auditoria::registrar("plan.cortesia", "cuenta", $id, $nombreCuenta,
                        $vigente ? Plan::nombre($vigente["plan"]) : "Gratuito", "Premium (cortesía) hasta " . substr($fin, 0, 10));
                    Panel::volver($volver, "ok", "Premium de cortesía activo hasta el " . substr($fin, 0, 10) . ".");

                case "quitar_premium":
                    if (!Plan::disponible($this->c) || Plan::quitarManual($this->c, $id) === 0) {
                        Panel::volver($volver, "error", "Esta cuenta no tiene Premium de cortesía. (Las suscripciones pagadas se cancelan desde el perfil del usuario.)");
                    }
                    Auditoria::registrar("plan.quitar", "cuenta", $id, $nombreCuenta, "Premium (cortesía)", "Gratuito");
                    Panel::volver($volver, "ok", "Se quitó el Premium de cortesía.");

                default:
                    Panel::volver($volver, "error", "Acción no reconocida.");
            }
        } catch (PDOException $e) {
            error_log("[BiGlobal][superadmin cuentas] " . $e->getMessage());
            Panel::volver($volver, "error", "No se pudo completar la acción.");
        }
    }

    /* =========================================================
       CONFIGURACIÓN GLOBAL (pestañas)
       Los formularios se guardan en src/superadmin/configuracion.php
    ========================================================= */
    public function configuracion(): void
    {
        $tab = $_GET["tab"] ?? "general";
        if (!in_array($tab, self::TABS, true)) {
            $tab = "general";
        }

        $sistema = [];
        if ($tab === "sistema") {
            $sistema = [
                "Versión de PHP"     => PHP_VERSION,
                "Servidor web"       => $_SERVER["SERVER_SOFTWARE"] ?? "—",
                "Base de datos"      => (string) $this->c->query("SELECT DATABASE()")->fetchColumn(),
                "Versión del motor"  => (string) $this->c->query("SELECT VERSION()")->fetchColumn(),
                "Zona horaria (PHP)" => date_default_timezone_get(),
                "Entorno"            => obtenerConfig()["app"]["entorno"],
            ];
        }

        $this->render("configuracion", "super-config", "Configuración", [
            "tab"         => $tab,
            "sistema"     => $sistema,
            "correoEnv"   => obtenerConfig()["smtp"]["host"] !== null,
            "proveedor"   => Correo::proveedor(),
            "remitente"   => Correo::remitente(),
            "miCorreo"    => (string) ($_SESSION["correo"] ?? ""),
        ]);
    }
}
