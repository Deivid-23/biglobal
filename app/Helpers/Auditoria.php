<?php

/**
 * Auditoria
 *
 * Registro de acciones sensibles (tabla de database/auditoria.sql):
 * cambios de rol y estado, borrado de cuentas y de contenido y
 * cambios en la configuración global.
 *
 * Uso:
 *   Auditoria::registrar("cuenta.rol", "cuenta", $id, "Ana (ana@x.com)", "docente", "admin");
 *
 * Nunca interrumpe la acción principal: si la tabla aún no existe o
 * falla la escritura, solo deja una línea en el log del servidor.
 * Lo lee únicamente el super administrador (SuperAdminController).
 */
class Auditoria
{
    /** Acciones que se registran, con su texto para mostrar. */
    public const ACCIONES = [
        "cuenta.crear"       => "Creó una cuenta",
        "cuenta.rol"         => "Cambió el rol",
        "cuenta.estado"      => "Cambió el estado",
        "cuenta.eliminar"    => "Eliminó una cuenta",
        "docente.aprobar"    => "Aprobó un docente",
        "docente.rechazar"   => "Rechazó un docente",
        "curso.eliminar"     => "Eliminó un curso",
        "leccion.eliminar"   => "Eliminó una lección",
        "actividad.eliminar" => "Eliminó una actividad",
        "pregunta.eliminar"  => "Eliminó una pregunta",
        "config.cambiar"     => "Cambió la configuración",
        "plan.cortesia"      => "Dio Premium de cortesía",
        "plan.quitar"        => "Quitó Premium de cortesía",
        "clase.cancelar"     => "Canceló una clase en vivo",
        "clase.abierta"      => "Cambió si una clase es gratuita",
        "plan.prueba"        => "Empezó la prueba gratis de Premium",
        "grupo.gratuito"     => "Cambió si un grupo es gratuito",
        "contenido.revision" => "Revisó contenido de un docente",
        "ruta.videos"        => "Importó videos de LSC",
        "cuenta.solicita_eliminacion" => "Pidió eliminar su cuenta",
        "cuenta.recuperada"  => "Recuperó su cuenta",
        "cuenta.borrado_definitivo" => "Se borraron los datos de una cuenta",
    ];

    /** Texto del estado de una cuenta (usuarios.activo). */
    public const ESTADOS = [0 => "inactiva", 1 => "activa", 2 => "pendiente", 3 => "eliminación pendiente", 4 => "eliminada"];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;

        if ($disponible === null) {
            try {
                $stmt = $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'auditoria'
                ");
                $disponible = (int) $stmt->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }

        return $disponible;
    }

    /**
     * Guarda una acción. Quién la hizo se toma de la sesión.
     * $objetoNombre es una "foto" en texto (sigue sirviendo aunque se borre).
     */
    public static function registrar(
        string $accion,
        string $objetoTipo,
        ?int $objetoId,
        string $objetoNombre = "",
        ?string $antes = null,
        ?string $despues = null
    ): void {
        try {
            $c = Conexion::conectar();
            if (!self::disponible($c)) {
                return;
            }

            $apellido = ($_SESSION["apellido"] ?? "") === "No especificado" ? "" : ($_SESSION["apellido"] ?? "");
            $nombreActor = trim(($_SESSION["nombre"] ?? "") . " " . $apellido);
            if (!empty($_SESSION["correo"])) {
                $nombreActor .= " (" . $_SESSION["correo"] . ")";
            }

            $c->prepare("
                INSERT INTO auditoria
                    (actor_id, actor_nombre, actor_rol, accion, objeto_tipo, objeto_id, objeto_nombre, antes, despues, ip)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                isset($_SESSION["usuario_id"]) ? (int) $_SESSION["usuario_id"] : null,
                self::corto(trim($nombreActor), 200),
                self::corto(rolActual(), 30),
                self::corto($accion, 50),
                self::corto($objetoTipo, 30),
                $objetoId,
                self::corto($objetoNombre, 255),
                $antes === null ? null : self::corto($antes, 255),
                $despues === null ? null : self::corto($despues, 255),
                self::corto(LimiteIntentos::ipCliente(), 45),
            ]);
        } catch (Throwable $e) {
            error_log("[BiGlobal][auditoria] No se pudo registrar {$accion}: " . $e->getMessage());
        }
    }

    /** "Ana Pérez (ana@x.com)" de una cuenta, para el registro. */
    public static function nombreCuenta(PDO $c, int $usuarioId): string
    {
        $stmt = $c->prepare("SELECT nombre, apellido, correo FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) {
            return "#" . $usuarioId;
        }
        $apellido = ($u["apellido"] ?? "") === "No especificado" ? "" : ($u["apellido"] ?? "");
        return trim($u["nombre"] . " " . $apellido) . " (" . $u["correo"] . ")";
    }

    /** Últimos registros (más nuevos primero), opcionalmente de una acción. */
    public static function recientes(PDO $c, ?string $accion = null, int $limite = 200): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $limite = max(1, min(500, $limite));
        if ($accion !== null && isset(self::ACCIONES[$accion])) {
            $stmt = $c->prepare("SELECT *, UNIX_TIMESTAMP(fecha) AS ts FROM auditoria WHERE accion = ? ORDER BY id DESC LIMIT {$limite}");
            $stmt->execute([$accion]);
        } else {
            $stmt = $c->query("SELECT *, UNIX_TIMESTAMP(fecha) AS ts FROM auditoria ORDER BY id DESC LIMIT {$limite}");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function corto(string $texto, int $max): string
    {
        return mb_strlen($texto) > $max ? mb_substr($texto, 0, $max - 1) . "…" : $texto;
    }
}
