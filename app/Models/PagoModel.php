<?php

/**
 * PagoModel
 *
 * Pagos del plan Premium con Wompi (tablas pagos, wompi_eventos y
 * suscripciones). Lo usan:
 *   - EstudianteController (comprar, cancelar la renovación, historial)
 *   - src/pagos/webhook.php (Wompi avisa el resultado de cada pago)
 *   - src/tareas/renovaciones.php (cobro automático diario)
 *
 * Reglas:
 * - El Premium se activa o se extiende SOLO aquí, al procesar un webhook
 *   con firma válida y estado APPROVED. Volver de la página de pago no
 *   activa nada.
 * - Se revisa que el monto y la moneda del webhook coincidan con el pago.
 * - Cada pago se aplica una sola vez (columna "aplicado" + bloqueo de fila).
 * - Tarjeta: renovación automática con la fuente de pago tokenizada.
 *   Si el cobro falla se reintenta 3 veces (1, 3 y 5 días después del
 *   corte) y luego pasa a Gratuito. PSE, Nequi y Bancolombia son pagos
 *   únicos: se avisa unos días antes de vencer.
 */
class PagoModel
{
    /** Días después del corte para cada reintento (1.º, 2.º y 3.º). */
    public const REINTENTOS_DIAS = [1, 3, 5];

    /** Días antes del vencimiento para recordar renovar (pagos únicos). */
    public const DIAS_RECORDATORIO = 3;

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = Plan::disponible($c) && (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name IN ('pagos', 'wompi_eventos')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       CREAR UN PAGO
    ========================================================= */

    public static function nuevaReferencia(int $usuarioId): string
    {
        return "BG-" . $usuarioId . "-" . date("YmdHis") . "-" . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Guarda el intento de pago (estado PENDING) y devuelve la fila.
     * $terminos: lo que devolvió Wompi::terminos() (tokens de aceptación) o null.
     */
    public static function crear(PDO $c, int $usuarioId, string $plan, string $tipo, string $canal, ?int $suscripcionId, ?array $terminos, ?string $fuentePagoId = null): array
    {
        $centavos = (int) Plan::PLANES[$plan]["centavos"];
        $referencia = self::nuevaReferencia($usuarioId);

        $c->prepare("
            INSERT INTO pagos (usuario_id, suscripcion_id, plan, tipo, canal, referencia, centavos, moneda, estado,
                               fuente_pago_id, token_aceptacion, token_datos_personales, aceptado_en, aceptado_ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'COP', 'PENDING', ?, ?, ?, ?, ?)
        ")->execute([
            $usuarioId, $suscripcionId, $plan, $tipo, $canal, $referencia, $centavos,
            $fuentePagoId,
            $terminos["aceptacion"]["token"] ?? null,
            $terminos["datos"]["token"] ?? null,
            $terminos ? date("Y-m-d H:i:s") : null,
            $terminos ? LimiteIntentos::ipCliente() : null,
        ]);

        return self::porReferencia($c, $referencia);
    }

    public static function porReferencia(PDO $c, string $referencia): ?array
    {
        $stmt = $c->prepare("SELECT * FROM pagos WHERE referencia = ? LIMIT 1");
        $stmt->execute([$referencia]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function guardarTransaccion(PDO $c, int $pagoId, ?array $transaccion, ?string $error = null): void
    {
        if ($transaccion === null) {
            $c->prepare("UPDATE pagos SET estado = 'ERROR', mensaje = ? WHERE id = ? AND estado = 'PENDING'")
              ->execute([mb_substr($error ?? "No se pudo contactar a Wompi.", 0, 255), $pagoId]);
            return;
        }
        $c->prepare("UPDATE pagos SET wompi_transaccion_id = ? WHERE id = ?")
          ->execute([(string) ($transaccion["id"] ?? ""), $pagoId]);
    }

    /** Pagos del usuario, el más nuevo primero. */
    public static function historial(PDO $c, int $usuarioId, int $limite = 50): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $stmt = $c->prepare("
            SELECT referencia, plan, tipo, canal, metodo, centavos, estado, tarjeta_marca, tarjeta_ultimos4, mensaje, creado_en
            FROM pagos WHERE usuario_id = ? ORDER BY id DESC LIMIT " . max(1, min(200, $limite))
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** ¿Tiene una suscripción con renovación automática en curso? (no se compra dos veces) */
    public static function suscripcionConRenovacion(PDO $c, int $usuarioId): ?array
    {
        $stmt = $c->prepare("
            SELECT * FROM suscripciones
            WHERE usuario_id = ? AND renovacion_automatica = 1 AND estado IN ('activa', 'en_reintento')
            ORDER BY fin DESC LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* =========================================================
       WEBHOOK: resultado de una transacción
    ========================================================= */

    /**
     * Guarda el evento (una sola vez por checksum).
     * Devuelve false si ya se había recibido (Wompi a veces reenvía).
     */
    public static function registrarEvento(PDO $c, array $evento, string $cuerpo, bool $valido): bool
    {
        $checksum = strtolower((string) ($evento["signature"]["checksum"] ?? ""));
        if (!preg_match('/^[0-9a-f]{64}$/', $checksum)) {
            $checksum = hash("sha256", $cuerpo);   // evento raro sin firma: igual se guarda
        }
        $t = $evento["data"]["transaction"] ?? [];
        $stmt = $c->prepare("
            INSERT IGNORE INTO wompi_eventos (evento, checksum, referencia, estado, valido, cuerpo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            mb_substr((string) ($evento["event"] ?? ""), 0, 60),
            $checksum,
            isset($t["reference"]) ? mb_substr((string) $t["reference"], 0, 60) : null,
            isset($t["status"]) ? mb_substr((string) $t["status"], 0, 20) : null,
            $valido ? 1 : 0,
            mb_substr($cuerpo, 0, 60000),
        ]);
        return $stmt->rowCount() === 1;
    }

    public static function resultadoEvento(PDO $c, array $evento, string $resultado): void
    {
        $checksum = strtolower((string) ($evento["signature"]["checksum"] ?? ""));
        $c->prepare("UPDATE wompi_eventos SET resultado = ? WHERE checksum = ?")
          ->execute([mb_substr($resultado, 0, 255), $checksum]);
    }

    /**
     * Aplica el resultado de una transacción (data.transaction del webhook).
     * Devuelve un texto corto de lo que se hizo (queda en wompi_eventos).
     */
    public static function procesarTransaccion(PDO $c, array $t): string
    {
        $referencia = (string) ($t["reference"] ?? "");
        $estado     = strtoupper((string) ($t["status"] ?? ""));
        if ($referencia === "" || !in_array($estado, ["APPROVED", "DECLINED", "VOIDED", "ERROR", "PENDING"], true)) {
            return "ignorado: sin referencia o estado desconocido";
        }

        $c->beginTransaction();
        try {
            // FOR UPDATE: si llegan dos avisos juntos, el segundo espera al primero
            $stmt = $c->prepare("SELECT * FROM pagos WHERE referencia = ? LIMIT 1 FOR UPDATE");
            $stmt->execute([$referencia]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pago) {
                $c->rollBack();
                return "ignorado: referencia desconocida";
            }

            // El monto y la moneda tienen que ser los que cobramos
            if ((int) ($t["amount_in_cents"] ?? -1) !== (int) $pago["centavos"] || strtoupper((string) ($t["currency"] ?? "")) !== $pago["moneda"]) {
                $c->prepare("UPDATE pagos SET mensaje = 'El monto del aviso de Wompi no coincide' WHERE id = ?")->execute([$pago["id"]]);
                $c->commit();
                error_log("[BiGlobal][pagos] Monto no coincide en {$referencia}");
                return "rechazado: el monto no coincide";
            }

            if ((int) $pago["aplicado"] === 1) {
                $c->commit();
                return "ya aplicado";
            }

            $extra  = $t["payment_method"]["extra"] ?? [];
            $marca  = isset($extra["brand"]) ? mb_substr((string) $extra["brand"], 0, 30) : $pago["tarjeta_marca"];
            $ultimos = isset($extra["last_four"]) && preg_match('/^\d{4}$/', (string) $extra["last_four"]) ? (string) $extra["last_four"] : $pago["tarjeta_ultimos4"];

            $c->prepare("
                UPDATE pagos SET estado = ?, metodo = ?, wompi_transaccion_id = COALESCE(?, wompi_transaccion_id),
                                 tarjeta_marca = ?, tarjeta_ultimos4 = ?, mensaje = ?
                WHERE id = ?
            ")->execute([
                $estado,
                isset($t["payment_method_type"]) ? mb_substr((string) $t["payment_method_type"], 0, 30) : $pago["metodo"],
                isset($t["id"]) ? (string) $t["id"] : null,
                $marca, $ultimos,
                isset($t["status_message"]) ? mb_substr((string) $t["status_message"], 0, 255) : null,
                $pago["id"],
            ]);

            $avisos = [];
            if ($estado === "APPROVED") {
                $avisos = self::aplicarAprobado($c, $pago, $marca, $ultimos);
                $resultado = "aprobado: Premium activado o extendido";
            } elseif ($estado === "PENDING") {
                $resultado = "pendiente";
            } else {
                $avisos = self::aplicarFallido($c, $pago, (string) ($t["status_message"] ?? ""));
                $resultado = "fallido: " . strtolower($estado);
            }

            $c->commit();
        } catch (Throwable $e) {
            if ($c->inTransaction()) {
                $c->rollBack();
            }
            throw $e;
        }

        // Los avisos van después del commit: un correo lento no deja la fila bloqueada
        foreach ($avisos as $a) {
            Notificador::aUsuario(...$a);
        }
        return $resultado;
    }

    /** Activa (primer pago) o extiende (renovación) el Premium. Devuelve los avisos a mandar. */
    private static function aplicarAprobado(PDO $c, array $pago, ?string $marca, ?string $ultimos): array
    {
        $meses = (int) Plan::PLANES[$pago["plan"]]["meses"];
        $usuarioId = (int) $pago["usuario_id"];

        if ($pago["tipo"] === "renovacion" && $pago["suscripcion_id"]) {
            $stmt = $c->prepare("SELECT * FROM suscripciones WHERE id = ? FOR UPDATE");
            $stmt->execute([$pago["suscripcion_id"]]);
            $s = $stmt->fetch(PDO::FETCH_ASSOC);
            // El nuevo periodo sigue desde el corte anterior (no se pierden ni se regalan días)
            $nuevoFin = (new DateTimeImmutable($s["fin"]))->modify("+{$meses} months")->format("Y-m-d H:i:s");
            $c->prepare("
                UPDATE suscripciones SET fin = ?, estado = 'activa', intentos_fallidos = 0, proximo_intento = NULL,
                                         tarjeta_marca = COALESCE(?, tarjeta_marca), tarjeta_ultimos4 = COALESCE(?, tarjeta_ultimos4)
                WHERE id = ?
            ")->execute([$nuevoFin, $marca, $ultimos, $s["id"]]);
            $suscripcionId = (int) $s["id"];
        } else {
            // Primer pago: si ya era Premium (p. ej. pago único), el periodo nuevo empieza cuando termina el actual
            $vigente = Plan::suscripcionVigente($c, $usuarioId);
            $desde = $vigente && $vigente["fin"] > date("Y-m-d H:i:s") ? new DateTimeImmutable($vigente["fin"]) : new DateTimeImmutable("now");
            $nuevoFin = $desde->modify("+{$meses} months")->format("Y-m-d H:i:s");
            $conTarjeta = $pago["canal"] === "tarjeta" && $pago["fuente_pago_id"];

            $c->prepare("
                INSERT INTO suscripciones (usuario_id, plan, estado, inicio, fin, renovacion_automatica, fuente_pago_id, tarjeta_marca, tarjeta_ultimos4)
                VALUES (?, ?, 'activa', NOW(), ?, ?, ?, ?, ?)
            ")->execute([$usuarioId, $pago["plan"], $nuevoFin, $conTarjeta ? 1 : 0,
                         $conTarjeta ? $pago["fuente_pago_id"] : null, $conTarjeta ? $marca : null, $conTarjeta ? $ultimos : null]);
            $suscripcionId = (int) $c->lastInsertId();
        }

        $c->prepare("UPDATE pagos SET aplicado = 1, suscripcion_id = ? WHERE id = ?")->execute([$suscripcionId, $pago["id"]]);

        $fecha = self::fecha($nuevoFin);
        $monto = Plan::pesos((int) $pago["centavos"]);
        return [[
            $usuarioId, "pago_aprobado",
            $pago["tipo"] === "renovacion" ? "Renovamos tu Premium" : "¡Ya eres Premium!",
            "Recibimos tu pago de {$monto} (" . Plan::nombre($pago["plan"]) . "). Tu Premium está activo hasta el {$fecha}.",
            "estudiante/index.php?page=plan",
        ]];
    }

    /** Pago rechazado. En una renovación programa el reintento o pasa a Gratuito. */
    private static function aplicarFallido(PDO $c, array $pago, string $motivo): array
    {
        $usuarioId = (int) $pago["usuario_id"];
        $motivoTexto = $motivo !== "" ? " Motivo: {$motivo}." : "";

        if ($pago["tipo"] !== "renovacion" || !$pago["suscripcion_id"]) {
            return [[$usuarioId, "pago_rechazado", "Tu pago no se aprobó",
                "No pudimos cobrar " . Plan::pesos((int) $pago["centavos"]) . ".{$motivoTexto} No se te cobró nada; puedes intentarlo otra vez.",
                "estudiante/index.php?page=plan"]];
        }

        $c->prepare("UPDATE pagos SET aplicado = 1 WHERE id = ?")->execute([$pago["id"]]);   // el fallo ya se contó
        $stmt = $c->prepare("SELECT * FROM suscripciones WHERE id = ? FOR UPDATE");
        $stmt->execute([$pago["suscripcion_id"]]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);
        $intentos = (int) $s["intentos_fallidos"] + 1;

        return self::registrarFalloRenovacion($c, $s, $intentos, $motivoTexto);
    }

    /**
     * Cuenta un cobro fallido de renovación. Con más de 3 reintentos fallidos
     * la suscripción vence y el usuario pasa a Gratuito (sin perder progreso).
     */
    private static function registrarFalloRenovacion(PDO $c, array $s, int $intentos, string $motivoTexto): array
    {
        $usuarioId = (int) $s["usuario_id"];
        $tarjeta = trim(($s["tarjeta_marca"] ?? "tarjeta") . " " . ($s["tarjeta_ultimos4"] ? "terminada en " . $s["tarjeta_ultimos4"] : ""));

        if ($intentos > count(self::REINTENTOS_DIAS)) {
            $c->prepare("UPDATE suscripciones SET estado = 'vencida', renovacion_automatica = 0, intentos_fallidos = ?, proximo_intento = NULL WHERE id = ?")
              ->execute([$intentos, $s["id"]]);
            return [[$usuarioId, "premium_terminado", "Tu Premium terminó",
                "No pudimos cobrar la renovación con tu {$tarjeta} después de varios intentos.{$motivoTexto} Pasaste al plan Gratuito: tu progreso, XP y medallas siguen intactos. Puedes volver a Premium cuando quieras.",
                "estudiante/index.php?page=plan"]];
        }

        $proximo = (new DateTimeImmutable($s["fin"]))->modify("+" . self::REINTENTOS_DIAS[$intentos - 1] . " days")->format("Y-m-d H:i:s");
        $c->prepare("UPDATE suscripciones SET estado = 'en_reintento', intentos_fallidos = ?, proximo_intento = ? WHERE id = ?")
          ->execute([$intentos, $proximo, $s["id"]]);
        return [[$usuarioId, "pago_rechazado", "No pudimos renovar tu Premium",
            "El cobro con tu {$tarjeta} no se aprobó.{$motivoTexto} Lo intentaremos de nuevo el " . self::fecha($proximo)
            . " (intento {$intentos} de " . count(self::REINTENTOS_DIAS) . "). Mientras tanto sigues siendo Premium. Revisa tu tarjeta o cancela la renovación si lo prefieres.",
            "estudiante/index.php?page=plan"]];
    }

    /* =========================================================
       RENOVACIÓN: cancelar y reactivar (desde el perfil)
    ========================================================= */

    public static function cancelarRenovacion(PDO $c, int $usuarioId): int
    {
        // Si estaba en reintento (la tarjeta ya falló), se corta ya: vuelve a Gratuito
        $stmt = $c->prepare("
            UPDATE suscripciones
            SET renovacion_automatica = 0, proximo_intento = NULL,
                estado = CASE WHEN estado = 'en_reintento' THEN 'vencida' ELSE 'cancelada' END
            WHERE usuario_id = ? AND renovacion_automatica = 1 AND estado IN ('activa', 'en_reintento')
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->rowCount();
    }

    public static function reactivarRenovacion(PDO $c, int $usuarioId): int
    {
        $stmt = $c->prepare("
            UPDATE suscripciones SET renovacion_automatica = 1, estado = 'activa'
            WHERE usuario_id = ? AND estado = 'cancelada' AND fin > NOW() AND fuente_pago_id IS NOT NULL
            ORDER BY fin DESC LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->rowCount();
    }

    /* =========================================================
       TAREA DIARIA (src/tareas/renovaciones.php)
    ========================================================= */

    /** Cobra las renovaciones del día, vence lo que terminó y manda recordatorios. */
    public static function tareaDiaria(PDO $c): array
    {
        $r = ["cobros" => 0, "sin_respuesta" => 0, "vencidas" => 0, "recordatorios" => 0];

        // 1) Cobros: corte de hoy (activa) o reintento programado (en_reintento).
        //    No se cobra dos veces si ya hay un cobro pendiente de las últimas 24 h.
        $pendientes = $c->query("
            SELECT s.*, u.correo FROM suscripciones s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.renovacion_automatica = 1 AND s.fuente_pago_id IS NOT NULL AND u.activo = 1
              AND ((s.estado = 'activa' AND s.fin <= NOW()) OR (s.estado = 'en_reintento' AND s.proximo_intento <= NOW()))
              AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.suscripcion_id = s.id AND p.tipo = 'renovacion'
                              AND p.estado = 'PENDING' AND p.creado_en > NOW() - INTERVAL 1 DAY)
            LIMIT 200
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pendientes as $s) {
            $pago = self::crear($c, (int) $s["usuario_id"], $s["plan"], "renovacion", "tarjeta", (int) $s["id"], null, $s["fuente_pago_id"]);
            $transaccion = Wompi::cobrarFuente($s["fuente_pago_id"], (int) $pago["centavos"], $pago["referencia"], $s["correo"]);
            self::guardarTransaccion($c, (int) $pago["id"], $transaccion);

            if ($transaccion !== null) {
                $r["cobros"]++;   // el resultado llega por el webhook
                continue;
            }

            // Wompi no respondió: se cuenta como intento fallido
            $r["sin_respuesta"]++;
            $c->beginTransaction();
            $c->prepare("UPDATE pagos SET aplicado = 1 WHERE id = ?")->execute([$pago["id"]]);
            $avisos = self::registrarFalloRenovacion($c, $s, (int) $s["intentos_fallidos"] + 1, " Motivo: no pudimos comunicarnos con el banco.");
            $c->commit();
            foreach ($avisos as $a) {
                Notificador::aUsuario(...$a);
            }
        }

        // 2) Lo que terminó sin renovación automática pasa a "vencida" (y se avisa)
        $terminadas = $c->query("
            SELECT id, usuario_id, plan FROM suscripciones
            WHERE estado IN ('activa', 'cancelada', 'manual') AND fin <= NOW()
              AND NOT (estado = 'activa' AND renovacion_automatica = 1 AND fuente_pago_id IS NOT NULL)
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($terminadas as $s) {
            $c->prepare("UPDATE suscripciones SET estado = 'vencida' WHERE id = ?")->execute([$s["id"]]);
            if (!Plan::esPremium($c, (int) $s["usuario_id"])) {   // si tiene otra vigente, no se avisa
                Notificador::aUsuario((int) $s["usuario_id"], "premium_terminado", "Tu Premium terminó",
                    "Pasaste al plan Gratuito. Tu progreso, XP y medallas siguen intactos y puedes volver a Premium cuando quieras.",
                    "estudiante/index.php?page=plan");
            }
            $r["vencidas"]++;
        }

        // 3) Recordatorio de los pagos únicos (y cortesías) que vencen pronto
        $porVencer = $c->query("
            SELECT id, usuario_id, fin FROM suscripciones
            WHERE estado IN ('activa', 'manual') AND renovacion_automatica = 0 AND recordatorio_enviado_en IS NULL
              AND fin > NOW() AND fin <= NOW() + INTERVAL " . self::DIAS_RECORDATORIO . " DAY
        ")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($porVencer as $s) {
            $c->prepare("UPDATE suscripciones SET recordatorio_enviado_en = NOW() WHERE id = ?")->execute([$s["id"]]);
            Notificador::aUsuario((int) $s["usuario_id"], "premium_por_vencer", "Tu Premium vence pronto",
                "Tu Premium termina el " . self::fecha($s["fin"]) . ". Si quieres seguir en las clases en vivo y los grupos con docentes, renuévalo desde tu plan.",
                "estudiante/index.php?page=plan");
            $r["recordatorios"]++;
        }

        return $r;
    }

    /** "2026-11-22 10:00:00" -> "22 nov 2026" */
    private static function fecha(string $fecha): string
    {
        $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
        $t = strtotime($fecha);
        return date("j", $t) . " " . $meses[(int) date("n", $t) - 1] . " " . date("Y", $t);
    }
}
