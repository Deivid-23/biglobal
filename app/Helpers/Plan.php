<?php

/**
 * Plan
 *
 * Planes de BiGlobal: GRATUITO y PREMIUM (mensual o anual).
 * Tabla: suscripciones (database/planes_suscripciones.sql).
 *
 *   Gratuito: ruta de aprendizaje completa de cada idioma (toda la ruta es
 *             gratis). Solo puede unirse a las clases en vivo y a los grupos
 *             que un admin marcó como gratuitos para todos.
 *   Premium : además, todas las clases en vivo y todos los grupos con docentes.
 *
 * Regla única: un usuario es Premium si tiene una suscripción cuya fecha
 * "fin" todavía no pasa. Al vencer o cancelarse vuelve solo a Gratuito y
 * no pierde nada (su progreso está en otras tablas).
 *
 * Los permisos SIEMPRE se revisan aquí, en el servidor; ocultar un botón
 * en la página no es suficiente.
 */
class Plan
{
    public const GRATUITO = "gratuito";
    public const MENSUAL  = "premium_mensual";
    public const ANUAL    = "premium_anual";
    /** Premium dado a mano por el super admin (beca, soporte). No se vende. */
    public const CORTESIA = "premium_cortesia";
    /**
     * Prueba gratis: el estudiante la pide una vez, sin tarjeta. Se guarda con
     * estado "manual" (como la cortesía), así vence, se recuerda y se puede
     * quitar igual. Se enciende en Super admin → Configuración → General.
     */
    public const PRUEBA = "premium_prueba";

    /**
     * Catálogo de planes de pago. Precios en CENTAVOS de peso (así los pide
     * Wompi: amount_in_cents). $19.900 = 1.990.000 centavos.
     */
    public const PLANES = [
        self::MENSUAL => ["nombre" => "Premium mensual", "centavos" => 1990000,  "meses" => 1],
        self::ANUAL   => ["nombre" => "Premium anual",   "centavos" => 19900000, "meses" => 12],
    ];

    /**
     * Condición SQL de "esta suscripción da Premium ahora" (alias s.):
     * - vigente y sin vencer, o
     * - en reintento de cobro (la tarjeta falló; se reintenta 3 veces), o
     * - renovación automática en curso: hasta 2 días después del corte,
     *   mientras llega la respuesta (webhook) de Wompi.
     */
    private const SQL_VIGENTE = "(
            (s.fin > NOW() AND s.estado IN ('activa', 'cancelada', 'manual'))
            OR s.estado = 'en_reintento'
            OR (s.estado = 'activa' AND s.renovacion_automatica = 1 AND s.fuente_pago_id IS NOT NULL AND s.fin > NOW() - INTERVAL 2 DAY)
        )";

    /* =========================================================
       ¿Existe la tabla? (antes del SQL todos son Gratuito)
    ========================================================= */
    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'suscripciones'
                ")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       PLAN DEL USUARIO
    ========================================================= */

    /** Suscripción vigente (la que termina más tarde), o null si es Gratuito. */
    public static function suscripcionVigente(PDO $c, int $usuarioId): ?array
    {
        if (!self::disponible($c)) {
            return null;
        }
        $stmt = $c->prepare("
            SELECT s.* FROM suscripciones s
            WHERE s.usuario_id = ? AND " . self::SQL_VIGENTE . "
            ORDER BY s.fin DESC LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function esPremium(PDO $c, int $usuarioId): bool
    {
        return self::suscripcionVigente($c, $usuarioId) !== null;
    }

    /** "gratuito" | "premium_mensual" | "premium_anual" */
    public static function codigoActual(PDO $c, int $usuarioId): string
    {
        $s = self::suscripcionVigente($c, $usuarioId);
        return $s ? (string) $s["plan"] : self::GRATUITO;
    }

    /** Planes de varios usuarios a la vez (para listados): [usuario_id => "premium_anual"]. */
    public static function planesDe(PDO $c, array $usuarioIds): array
    {
        $ids = array_values(array_filter(array_map("intval", $usuarioIds)));
        if (!$ids || !self::disponible($c)) {
            return [];
        }
        $marcasIds = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT s.usuario_id, s.plan, MAX(s.fin) AS fin FROM suscripciones s
            WHERE s.usuario_id IN ($marcasIds) AND " . self::SQL_VIGENTE . "
            GROUP BY s.usuario_id, s.plan
        ");
        $stmt->execute($ids);
        $planes = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $uid = (int) $f["usuario_id"];
            // Si tiene dos (pagada y de cortesía), se muestra la que termina más tarde
            if (!isset($planes[$uid]) || $f["fin"] > $planes[$uid]["fin"]) {
                $planes[$uid] = ["plan" => $f["plan"], "fin" => $f["fin"]];
            }
        }
        return $planes;
    }

    /* =========================================================
       PERMISOS
    ========================================================= */

    /**
     * ¿Puede unirse a una clase en vivo? (requerimiento 3)
     * Premium: a todas. Gratuito: solo a las "abiertas para todos".
     */
    public static function puedeUnirseAClase(PDO $c, int $usuarioId, bool $abiertaParaTodos): bool
    {
        return $abiertaParaTodos || self::esPremium($c, $usuarioId);
    }

    /**
     * ¿Puede entrar a un grupo de Classroom? Premium: a todos.
     * Gratuito: solo a los que un admin marcó como gratuitos (grupos.gratuito).
     */
    public static function puedeEntrarAGrupo(PDO $c, int $usuarioId, bool $gratuito): bool
    {
        return $gratuito || self::esPremium($c, $usuarioId);
    }

    /* =========================================================
       PRUEBA GRATIS (una vez por cuenta, sin tarjeta)
    ========================================================= */

    public static function pruebaActiva(): bool
    {
        return Config::getBool("premium_prueba_activa", false);
    }

    /** Días de la prueba (1–30; 7 por defecto). */
    public static function diasPrueba(): int
    {
        return max(1, min(30, Config::getInt("premium_prueba_dias", 7)));
    }

    /**
     * ¿Puede empezar la prueba? Prueba encendida, es estudiante activo y
     * nunca ha tenido Premium (ni pagado, ni de cortesía, ni otra prueba).
     */
    public static function puedeProbar(PDO $c, int $usuarioId): bool
    {
        if (!self::disponible($c) || !self::pruebaActiva()) {
            return false;
        }
        $stmt = $c->prepare("
            SELECT (r.nombre = 'estudiante' AND u.activo = 1)
                   AND NOT EXISTS (SELECT 1 FROM suscripciones s WHERE s.usuario_id = u.id)
            FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.id = ? LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Empieza la prueba. Bloquea la fila del usuario para que dos clics
     * seguidos no creen dos pruebas. Devuelve la fecha de fin, o null si no puede.
     */
    public static function empezarPrueba(PDO $c, int $usuarioId): ?string
    {
        $c->beginTransaction();
        try {
            $c->prepare("SELECT id FROM usuarios WHERE id = ? FOR UPDATE")->execute([$usuarioId]);
            if (!self::puedeProbar($c, $usuarioId)) {
                $c->rollBack();
                return null;
            }
            $c->prepare("
                INSERT INTO suscripciones (usuario_id, plan, estado, inicio, fin, renovacion_automatica, nota)
                VALUES (?, ?, 'manual', NOW(), NOW() + INTERVAL " . self::diasPrueba() . " DAY, 0, 'Prueba gratis')
            ")->execute([$usuarioId, self::PRUEBA]);
            $id = (int) $c->lastInsertId();
            $c->commit();
        } catch (Throwable $e) {
            if ($c->inTransaction()) {
                $c->rollBack();
            }
            throw $e;
        }
        $stmt = $c->prepare("SELECT fin FROM suscripciones WHERE id = ?");
        $stmt->execute([$id]);
        return (string) $stmt->fetchColumn();
    }

    /* =========================================================
       PREMIUM MANUAL (super admin: becas, soporte, pruebas)
    ========================================================= */

    /**
     * Da Premium sin pago hasta $fin. Si ya tenía una manual vigente, la extiende.
     * Devuelve el id de la suscripción.
     */
    public static function otorgarManual(PDO $c, int $usuarioId, string $fin, int $adminId, string $nota): int
    {
        $c->prepare("
            INSERT INTO suscripciones (usuario_id, plan, estado, inicio, fin, renovacion_automatica, otorgada_por, nota)
            VALUES (?, ?, 'manual', NOW(), ?, 0, ?, ?)
        ")->execute([$usuarioId, self::CORTESIA, $fin, $adminId, mb_substr($nota, 0, 255)]);
        return (int) $c->lastInsertId();
    }

    /** Quita el Premium manual vigente (las pagadas no se tocan aquí). */
    public static function quitarManual(PDO $c, int $usuarioId): int
    {
        $stmt = $c->prepare("UPDATE suscripciones SET fin = NOW(), estado = 'vencida' WHERE usuario_id = ? AND estado = 'manual' AND fin > NOW()");
        $stmt->execute([$usuarioId]);
        return $stmt->rowCount();
    }

    /* =========================================================
       TEXTOS Y FORMATO
    ========================================================= */

    public static function nombre(string $codigo): string
    {
        if ($codigo === self::CORTESIA) {
            return "Premium (cortesía)";
        }
        if ($codigo === self::PRUEBA) {
            return "Premium (prueba gratis)";
        }
        return self::PLANES[$codigo]["nombre"] ?? "Gratuito";
    }

    /** 1990000 centavos -> "$19.900 COP" (formato colombiano: punto de miles). */
    public static function pesos(int $centavos, bool $conMoneda = true): string
    {
        $texto = "$" . number_format(intdiv($centavos, 100), 0, ",", ".");
        return $conMoneda ? $texto . " COP" : $texto;
    }

    /** Lo que cuesta el anual por mes, para mostrar el ahorro: "$16.583". */
    public static function anualPorMes(): string
    {
        return self::pesos(intdiv(self::PLANES[self::ANUAL]["centavos"], 12), false);
    }
}
