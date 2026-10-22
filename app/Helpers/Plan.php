<?php

/**
 * Plan
 *
 * Planes de BiGlobal: GRATUITO y PREMIUM (mensual o anual).
 * Tabla: suscripciones (database/planes_suscripciones.sql).
 *
 *   Gratuito: ruta de aprendizaje completa de cada idioma. Solo puede
 *             unirse a las clases en vivo marcadas "abiertas para todos".
 *   Premium : ruta de aprendizaje + todas las clases en vivo.
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
     * Catálogo de planes de pago. Precios en CENTAVOS de peso (así los pide
     * Wompi: amount_in_cents). $19.900 = 1.990.000 centavos.
     */
    public const PLANES = [
        self::MENSUAL => ["nombre" => "Premium mensual", "centavos" => 1990000,  "meses" => 1],
        self::ANUAL   => ["nombre" => "Premium anual",   "centavos" => 19900000, "meses" => 12],
    ];

    /** Estados de suscripción que dan Premium mientras "fin" no haya pasado. */
    private const ESTADOS_VIGENTES = ["activa", "cancelada", "manual"];

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
        $marcas = implode(",", array_fill(0, count(self::ESTADOS_VIGENTES), "?"));
        $stmt = $c->prepare("
            SELECT * FROM suscripciones
            WHERE usuario_id = ? AND fin > NOW() AND estado IN ($marcas)
            ORDER BY fin DESC LIMIT 1
        ");
        $stmt->execute(array_merge([$usuarioId], self::ESTADOS_VIGENTES));
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
        $marcasEst = implode(",", array_fill(0, count(self::ESTADOS_VIGENTES), "?"));
        $stmt = $c->prepare("
            SELECT usuario_id, plan, MAX(fin) AS fin FROM suscripciones
            WHERE usuario_id IN ($marcasIds) AND fin > NOW() AND estado IN ($marcasEst)
            GROUP BY usuario_id, plan
        ");
        $stmt->execute(array_merge($ids, self::ESTADOS_VIGENTES));
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
