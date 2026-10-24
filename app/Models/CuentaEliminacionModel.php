<?php

/**
 * CuentaEliminacionModel
 *
 * Eliminación de cuenta con 30 días de gracia (database/eliminacion_cuenta.sql).
 *
 *   1. solicitar(): la cuenta pasa a activo = 3 (eliminación pendiente),
 *      se cancela la renovación de Premium, sale de sus clases en vivo
 *      (y, si es docente, se cancelan sus clases futuras) y se le manda
 *      el correo con la fecha de borrado.
 *   2. Si inicia sesión antes de la fecha, puede recuperar(la).
 *   3. borrarVencidas() (tarea diaria): pasados los 30 días ANONIMIZA la
 *      cuenta (activo = 4). Se conservan los pagos sin datos personales.
 */
require_once __DIR__ . "/PagoModel.php";
require_once __DIR__ . "/ClaseModel.php";

class CuentaEliminacionModel
{
    public const DIAS_GRACIA = 30;
    public const PENDIENTE = 3;
    public const ELIMINADA = 4;

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND table_name = 'usuarios'
                      AND column_name IN ('eliminacion_solicitada_en', 'eliminacion_programada_para', 'activo_previo')
                ")->fetchColumn() === 3;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /** ¿Es el último super administrador activo? (no puede irse) */
    public static function esUltimoSuperAdmin(PDO $c, int $usuarioId): bool
    {
        $stmt = $c->prepare("
            SELECT
              (SELECT r.nombre FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE u.id = ?) AS rol,
              (SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
               WHERE r.nombre = 'superadmin' AND u.activo = 1 AND u.id <> ?) AS otros
        ");
        $stmt->execute([$usuarioId, $usuarioId]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($f["rol"] ?? "") === "superadmin" && (int) $f["otros"] === 0;
    }

    /**
     * Programa la eliminación. Devuelve la fecha programada (Y-m-d H:i:s) o un
     * texto de error que empieza con "error:".
     */
    public static function solicitar(PDO $c, int $usuarioId): string
    {
        if (!self::disponible($c)) {
            return "error:La eliminación de cuentas todavía no está disponible.";
        }
        if (self::esUltimoSuperAdmin($c, $usuarioId)) {
            return "error:Eres el único super administrador activo. Nombra a otro antes de eliminar tu cuenta.";
        }

        $stmt = $c->prepare("SELECT u.id, u.activo, u.nombre, u.correo, r.nombre AS rol FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id WHERE u.id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u || !in_array((int) $u["activo"], [1, 2], true)) {
            return "error:Esta cuenta no se puede eliminar en este momento.";
        }

        $c->prepare("
            UPDATE usuarios
            SET activo_previo = activo, activo = ?, eliminacion_solicitada_en = NOW(),
                eliminacion_programada_para = NOW() + INTERVAL " . self::DIAS_GRACIA . " DAY
            WHERE id = ?
        ")->execute([self::PENDIENTE, $usuarioId]);

        $stmt = $c->prepare("SELECT eliminacion_programada_para FROM usuarios WHERE id = ?");
        $stmt->execute([$usuarioId]);
        $fecha = (string) $stmt->fetchColumn();

        // Lo que ya no tiene sentido mantener mientras tanto
        if (PagoModel::disponible($c)) {
            PagoModel::cancelarRenovacion($c, $usuarioId);
        }
        if (ClaseModel::disponible($c)) {
            self::salirDeClases($c, $usuarioId, $u["rol"] === "docente");
        }

        Auditoria::registrar("cuenta.solicita_eliminacion", "cuenta", $usuarioId,
            Auditoria::nombreCuenta($c, $usuarioId), null, "se elimina el " . substr($fecha, 0, 10));

        Notificador::aUsuario($usuarioId, "cuenta_eliminacion", "Tu cuenta se eliminará el " . self::fecha($fecha),
            "Recibimos tu solicitud para eliminar tu cuenta de BiGlobal. El " . self::fecha($fecha)
            . " borraremos definitivamente tus datos personales: nombre, correo, progreso, XP, medallas y certificados. "
            . "Si cambias de opinión, inicia sesión antes de esa fecha y elige «Recuperar mi cuenta». "
            . "Ya cancelamos la renovación automática de Premium (si la tenías) y te retiramos de tus clases en vivo.");

        return $fecha;
    }

    /** Sale de sus clases (libera cupos). Si es docente, cancela sus clases futuras. */
    private static function salirDeClases(PDO $c, int $usuarioId, bool $esDocente): void
    {
        $stmt = $c->prepare("
            SELECT ci.clase_id FROM clase_inscripciones ci INNER JOIN clases_vivo cv ON cv.id = ci.clase_id
            WHERE ci.usuario_id = ? AND ci.estado IN ('inscrito', 'espera') AND cv.estado = 'programada'
              AND cv.inicio + INTERVAL cv.duracion_min MINUTE > NOW()
        ");
        $stmt->execute([$usuarioId]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $claseId) {
            ClaseModel::retirar($c, (int) $claseId, $usuarioId);
        }

        if ($esDocente) {
            $stmt = $c->prepare("SELECT id FROM clases_vivo WHERE docente_id = ? AND estado = 'programada' AND inicio > NOW()");
            $stmt->execute([$usuarioId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $claseId) {
                ClaseModel::cancelar($c, (int) $claseId, "El docente cerró su cuenta.");
            }
        }
    }

    /** Datos para la pantalla de recuperación (o null si no está pendiente o ya pasó la fecha). */
    public static function pendiente(PDO $c, int $usuarioId): ?array
    {
        if (!self::disponible($c)) {
            return null;
        }
        $stmt = $c->prepare("
            SELECT u.id, u.nombre, u.apellido, u.correo, u.rol_id, r.nombre AS rol, u.eliminacion_programada_para
            FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = ? AND u.activo = ? AND u.eliminacion_programada_para > NOW() LIMIT 1
        ");
        $stmt->execute([$usuarioId, self::PENDIENTE]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Devuelve la cuenta a su estado anterior. true si se recuperó. */
    public static function recuperar(PDO $c, int $usuarioId): bool
    {
        $stmt = $c->prepare("
            UPDATE usuarios
            SET activo = COALESCE(activo_previo, 1), activo_previo = NULL,
                eliminacion_solicitada_en = NULL, eliminacion_programada_para = NULL
            WHERE id = ? AND activo = ? AND eliminacion_programada_para > NOW()
        ");
        $stmt->execute([$usuarioId, self::PENDIENTE]);
        if ($stmt->rowCount() !== 1) {
            return false;
        }
        Auditoria::registrar("cuenta.recuperada", "cuenta", $usuarioId, Auditoria::nombreCuenta($c, $usuarioId), "eliminación pendiente", "activa");
        Notificador::aUsuario($usuarioId, "cuenta_eliminacion", "Recuperaste tu cuenta",
            "Cancelamos la eliminación de tu cuenta. Todo sigue como estaba. Si tenías Premium con renovación automática, puedes reactivarla en Mi plan.");
        return true;
    }

    /* =========================================================
       TAREA DIARIA: borrar lo que cumplió los 30 días
    ========================================================= */

    public static function borrarVencidas(PDO $c): int
    {
        if (!self::disponible($c)) {
            return 0;
        }
        $stmt = $c->prepare("SELECT id FROM usuarios WHERE activo = ? AND eliminacion_programada_para <= NOW() LIMIT 100");
        $stmt->execute([self::PENDIENTE]);
        $n = 0;
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            self::anonimizar($c, (int) $id);
            $n++;
        }
        return $n;
    }

    /**
     * Borra los datos personales y deja la fila anónima (activo = 4).
     * Se conservan: los pagos (montos y fechas, sin token ni IP ni tarjeta)
     * y los cursos y clases pasadas de un docente.
     */
    public static function anonimizar(PDO $c, int $usuarioId): void
    {
        $nombreAnterior = Auditoria::nombreCuenta($c, $usuarioId);

        $c->beginTransaction();
        try {
            // Datos de aprendizaje y avisos (cada tabla, solo si existe)
            foreach (["progreso", "certificados", "recuperacion_password", "estudiante_stats", "xp_movimientos",
                      "usuario_medallas", "notificaciones", "actividad_intentos", "clase_inscripciones", "clase_invitaciones",
                      "ruta_progreso", "racha_dias", "grupo_miembros", "usuario_fotos", "ruta_ubicacion", "ruta_intentos", "ruta_repaso",
                      // Docente: su solicitud y los documentos que subió (pueden ser de identidad)
                      "docente_documentos", "docente_solicitudes"] as $tabla) {
                if (self::existeTabla($c, $tabla)) {
                    $c->prepare("DELETE FROM {$tabla} WHERE usuario_id = ?")->execute([$usuarioId]);
                }
            }
            // Lo que escribió o subió en los grupos (comentarios, entregas y sus archivos)
            if (self::existeTabla($c, "anuncio_comentarios")) {
                $c->prepare("DELETE FROM anuncio_comentarios WHERE autor_id = ?")->execute([$usuarioId]);
            }
            if (self::existeTabla($c, "archivos")) {
                $c->prepare("DELETE FROM archivos WHERE dueno_id = ? AND contexto = 'entrega'")->execute([$usuarioId]);
            }
            if (self::existeTabla($c, "tarea_entregas")) {
                $c->prepare("DELETE FROM tarea_entregas WHERE estudiante_id = ?")->execute([$usuarioId]);
            }

            // Pagos y suscripciones: se quedan por contabilidad, sin datos personales
            if (self::existeTabla($c, "pagos")) {
                if (self::existeTabla($c, "wompi_eventos")) {
                    $c->prepare("UPDATE wompi_eventos SET cuerpo = NULL WHERE referencia IN (SELECT referencia FROM pagos WHERE usuario_id = ?)")
                      ->execute([$usuarioId]);
                }
                $c->prepare("
                    UPDATE pagos SET token_aceptacion = NULL, token_datos_personales = NULL, aceptado_ip = NULL,
                                     fuente_pago_id = NULL, tarjeta_marca = NULL, tarjeta_ultimos4 = NULL
                    WHERE usuario_id = ?
                ")->execute([$usuarioId]);
            }
            if (self::existeTabla($c, "suscripciones")) {
                $c->prepare("
                    UPDATE suscripciones SET renovacion_automatica = 0, fuente_pago_id = NULL, tarjeta_marca = NULL, tarjeta_ultimos4 = NULL,
                                             estado = CASE WHEN estado IN ('activa', 'en_reintento', 'manual', 'cancelada') THEN 'vencida' ELSE estado END,
                                             fin = LEAST(fin, NOW())
                    WHERE usuario_id = ?
                ")->execute([$usuarioId]);
            }

            // Registro de auditoría: se quita el nombre y el correo
            if (self::existeTabla($c, "auditoria")) {
                $c->prepare("UPDATE auditoria SET actor_nombre = 'Cuenta eliminada', ip = '' WHERE actor_id = ?")->execute([$usuarioId]);
                $c->prepare("UPDATE auditoria SET objeto_nombre = CONCAT('Cuenta eliminada #', ?) WHERE objeto_tipo = 'cuenta' AND objeto_id = ?")
                  ->execute([$usuarioId, $usuarioId]);
            }

            // La cuenta queda anónima y sin forma de entrar
            $c->prepare("
                UPDATE usuarios
                SET nombre = 'Cuenta', apellido = 'eliminada', correo = CONCAT('eliminada-', id, '@biglobal.invalid'),
                    password = NULL, google_id = NULL, google_avatar = NULL, color_acento = NULL,
                    activo = ?, activo_previo = NULL
                WHERE id = ?
            ")->execute([self::ELIMINADA, $usuarioId]);

            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            error_log("[BiGlobal][eliminacion] No se pudo anonimizar la cuenta {$usuarioId}: " . $e->getMessage());
            throw $e;
        }

        Auditoria::registrar("cuenta.borrado_definitivo", "cuenta", $usuarioId, "Cuenta eliminada #{$usuarioId}", "eliminación pendiente", "datos borrados");
        error_log("[BiGlobal][eliminacion] Cuenta {$usuarioId} anonimizada (antes: " . mb_substr($nombreAnterior, 0, 3) . "…)");
    }

    private static function existeTabla(PDO $c, string $tabla): bool
    {
        static $cache = [];
        if (!isset($cache[$tabla])) {
            $stmt = $c->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmt->execute([$tabla]);
            $cache[$tabla] = (int) $stmt->fetchColumn() === 1;
        }
        return $cache[$tabla];
    }

    /** "2026-11-22 10:00:00" -> "22 nov 2026" */
    public static function fecha(string $fecha): string
    {
        $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
        $t = strtotime($fecha);
        return date("j", $t) . " " . $meses[(int) date("n", $t) - 1] . " " . date("Y", $t);
    }
}
