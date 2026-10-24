<?php

/**
 * GamificacionModel
 *
 * Racha diaria, protectores de racha, XP y nivel del estudiante.
 * Las insignias están en InsigniaModel.
 * Usa las tablas de database/gamificacion.sql (estudiante_stats, xp_movimientos)
 * y de database/racha_protectores.sql (columnas nuevas y racha_dias).
 *
 * Racha (requerimiento 12):
 * - Los días se cuentan en la zona horaria del estudiante (usuarios.zona_horaria).
 *   Se calculan en PHP: la base local no tiene las tablas de zonas que pide CONVERT_TZ.
 * - racha_hasta = último día cubierto (estudió o se usó un protector).
 * - Protectores: 2 por mes. El día 1 vuelven a ser 2 (no se acumulan).
 *   Cada día sin estudiar gasta uno solo; si faltan más días que
 *   protectores, la racha se pierde (y entonces no se gasta ninguno).
 *   Se revisa al volver (actualizarRacha),
 *   día por día, así el resultado es el mismo que revisar a medianoche.
 *
 * Si las tablas todavía no existen, disponible() devuelve false y el panel
 * sigue funcionando sin gamificación en vez de mostrar un error.
 */
class GamificacionModel
{
    /** Puntos que se ganan */
    public const XP_LECCION = 10;
    public const XP_CURSO   = 50;

    /** XP que hace falta para subir cada nivel */
    public const XP_POR_NIVEL = 100;

    /** Protectores de racha que se reciben cada mes (no se acumulan) */
    public const PROTECTORES_MES = 2;

    /** Zona si el estudiante todavía no tiene una (o no existe la columna) */
    public const ZONA_DEFECTO = "America/Bogota";

    /* =========================================================
       ¿Existen las tablas? (se pregunta una sola vez por visita)
    ========================================================= */
    public static function disponible(PDO $conexion): bool
    {
        static $disponible = null;

        if ($disponible === null) {
            $stmt = $conexion->query("
                SELECT COUNT(*) FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name IN ('estudiante_stats', 'xp_movimientos', 'medallas', 'usuario_medallas')
            ");
            $disponible = (int) $stmt->fetchColumn() === 4;
        }

        return $disponible;
    }

    /** ¿Ya se ejecutó database/racha_protectores.sql? */
    public static function conProtectores(PDO $conexion): bool
    {
        static $hay = null;
        if ($hay === null) {
            try {
                $hay = self::disponible($conexion) && (int) $conexion->query("
                    SELECT (SELECT COUNT(*) FROM information_schema.columns
                            WHERE table_schema = DATABASE() AND table_name = 'estudiante_stats' AND column_name = 'racha_hasta')
                         + (SELECT COUNT(*) FROM information_schema.tables
                            WHERE table_schema = DATABASE() AND table_name = 'racha_dias')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $hay = false;
            }
        }
        return $hay;
    }

    /* =========================================================
       ZONA HORARIA Y FECHAS
    ========================================================= */

    /** Zona horaria del estudiante (Colombia si no tiene). */
    public static function zonaDe(PDO $conexion, int $usuarioId): string
    {
        static $cache = [];
        if (!isset($cache[$usuarioId])) {
            $zona = self::ZONA_DEFECTO;
            try {
                $stmt = $conexion->prepare("SELECT zona_horaria FROM usuarios WHERE id = ? LIMIT 1");
                $stmt->execute([$usuarioId]);
                $valor = (string) $stmt->fetchColumn();
                if (self::zonaValida($valor)) {
                    $zona = $valor;
                }
            } catch (PDOException $e) {
                // la columna todavía no existe: Colombia
            }
            $cache[$usuarioId] = $zona;
        }
        return $cache[$usuarioId];
    }

    public static function zonaValida(string $zona): bool
    {
        return $zona !== "" && in_array($zona, DateTimeZone::listIdentifiers(), true);
    }

    /** Fecha de hoy (AAAA-MM-DD) en una zona horaria. */
    public static function hoy(string $zona = self::ZONA_DEFECTO): string
    {
        return (new DateTimeImmutable("now", new DateTimeZone($zona)))->format("Y-m-d");
    }

    /** Día (AAAA-MM-DD) en una zona horaria de una fecha guardada por la BD (hora de Colombia, -05:00). */
    public static function diaEn(string $fechaBd, string $zona = self::ZONA_DEFECTO): string
    {
        return (new DateTimeImmutable($fechaBd, new DateTimeZone("-05:00")))
            ->setTimezone(new DateTimeZone($zona))
            ->format("Y-m-d");
    }

    /**
     * Cambia la zona del estudiante. Solo una vez al día, para que nadie
     * cambie de zona a propósito para salvar su racha.
     * @return string "ok" | "igual" | "hoy-no" | "invalida"
     */
    public static function cambiarZona(PDO $conexion, int $usuarioId, string $zona): string
    {
        if (!self::zonaValida($zona)) {
            return "invalida";
        }
        if (self::zonaDe($conexion, $usuarioId) === $zona) {
            return "igual";
        }
        $stmt = $conexion->prepare("
            UPDATE usuarios SET zona_horaria = ?, zona_cambiada = CURDATE()
            WHERE id = ? AND (zona_cambiada IS NULL OR zona_cambiada < CURDATE())
        ");
        $stmt->execute([$zona, $usuarioId]);
        return $stmt->rowCount() > 0 ? "ok" : "hoy-no";
    }

    /** Zonas para el selector: las más usadas primero y luego todas. */
    public static function zonasParaElegir(): array
    {
        $comunes = [
            "America/Bogota"      => "Colombia (Bogotá)",
            "America/Mexico_City" => "México (Ciudad de México)",
            "America/Lima"        => "Perú (Lima)",
            "America/Guayaquil"   => "Ecuador (Guayaquil)",
            "America/Caracas"     => "Venezuela (Caracas)",
            "America/Santiago"    => "Chile (Santiago)",
            "America/Argentina/Buenos_Aires" => "Argentina (Buenos Aires)",
            "America/New_York"    => "Estados Unidos (Nueva York)",
            "America/Los_Angeles" => "Estados Unidos (Los Ángeles)",
            "Europe/Madrid"       => "España (Madrid)",
        ];
        $todas = [];
        foreach (DateTimeZone::listIdentifiers() as $z) {
            if (!isset($comunes[$z])) {
                $todas[$z] = str_replace(["_", "/"], [" ", " / "], $z);
            }
        }
        return ["comunes" => $comunes, "todas" => $todas];
    }

    /* =========================================================
       PROTECTORES: se gastan solos al volver
    ========================================================= */

    /**
     * Revisa los días que pasaron sin estudiar: gasta protectores o pierde la
     * racha, y recarga los protectores el día 1. Avisa por la campana.
     * Se llama al abrir cualquier página del estudiante y antes de sumar
     * una actividad. $hoy solo se pasa en las pruebas.
     */
    public static function actualizarRacha(PDO $conexion, int $usuarioId, ?string $hoy = null): void
    {
        if (!self::conProtectores($conexion)) {
            return;
        }
        $hoy = $hoy ?? self::hoy(self::zonaDe($conexion, $usuarioId));
        $propia = !$conexion->inTransaction();
        if ($propia) {
            $conexion->beginTransaction();
        }
        $avisos = [];
        try {
            $stmt = $conexion->prepare("
                SELECT racha_actual, ultima_actividad, racha_hasta, protectores, protectores_mes
                FROM estudiante_stats WHERE usuario_id = ? FOR UPDATE
            ");
            $stmt->execute([$usuarioId]);
            $f = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$f) {
                if ($propia) {
                    $conexion->commit();
                }
                return;
            }

            $racha = (int) $f["racha_actual"];
            $hasta = $f["racha_hasta"] ?? $f["ultima_actividad"];
            $protectores = (int) $f["protectores"];
            $mes = $f["protectores_mes"];
            $usados = [];
            $perdida = false;

            if ($racha > 0 && $hasta !== null) {
                $dia = new DateTimeImmutable($hasta);
                $limite = new DateTimeImmutable($hoy);
                $vueltas = 0;
                // Cada día entre el último cubierto y hoy (sin contar hoy)
                while (($dia = $dia->modify("+1 day")) < $limite && $vueltas++ < 400) {
                    if ($dia->format("Y-m") !== $mes) {         // día 1 de otro mes: se recargan (no se acumulan)
                        $mes = $dia->format("Y-m");
                        $protectores = self::PROTECTORES_MES;
                    }
                    if ($protectores > 0) {
                        $protectores--;
                        $hasta = $dia->format("Y-m-d");
                        $usados[] = $hasta;
                    } else {
                        $perdida = true;
                        break;
                    }
                }
                // Si la racha se pierde igual, no se gasta ningún protector
                if ($perdida) {
                    $usados = [];
                    $protectores = (int) $f["protectores"];
                    $mes = $f["protectores_mes"];
                    $hasta = $f["racha_hasta"] ?? $f["ultima_actividad"];
                }
            }

            $mesHoy = substr($hoy, 0, 7);
            if ($mes !== $mesHoy) {
                $mes = $mesHoy;
                $protectores = self::PROTECTORES_MES;
            }

            $cambio = $usados || $perdida || $mes !== $f["protectores_mes"] || $protectores !== (int) $f["protectores"]
                || $hasta !== ($f["racha_hasta"] ?? $f["ultima_actividad"]);
            if ($cambio) {
                $conexion->prepare("
                    UPDATE estudiante_stats
                    SET racha_actual = ?, racha_hasta = ?, protectores = ?, protectores_mes = ?
                    WHERE usuario_id = ?
                ")->execute([$perdida ? 0 : $racha, $hasta, $protectores, $mes, $usuarioId]);

                $dias = $conexion->prepare("INSERT IGNORE INTO racha_dias (usuario_id, fecha, tipo) VALUES (?, ?, 'protector')");
                foreach ($usados as $d) {
                    $dias->execute([$usuarioId, $d]);
                }
            }

            if ($usados) {
                $n = count($usados);
                $avisos[] = ["racha_protector",
                    "Usamos " . ($n === 1 ? "1 protector" : "{$n} protectores") . ": tu racha de {$racha} " . ($racha === 1 ? "día" : "días") . " sigue viva",
                    $protectores === 0 ? "Ya no te quedan protectores este mes. ¡Estudia hoy!"
                        : "Te " . ($protectores === 1 ? "queda 1 protector" : "quedan {$protectores} protectores") . " este mes."];
            }
            if ($perdida) {
                $avisos[] = ["racha_perdida", "Tu racha de {$racha} " . ($racha === 1 ? "día" : "días") . " terminó",
                    "No pasa nada: completa una lección hoy y empieza una nueva."];
            }

            if ($propia) {
                $conexion->commit();
            }
        } catch (Throwable $e) {
            if ($propia && $conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("[BiGlobal][racha] " . $e->getMessage());
            return;
        }

        foreach ($avisos as [$tipo, $titulo, $mensaje]) {
            Notificador::aUsuario($usuarioId, $tipo, $titulo, $mensaje, "estudiante/index.php");
        }
    }

    /* =========================================================
       LECTURA (para el inicio)
    ========================================================= */

    /**
     * XP, nivel, racha, protectores y la semana, listos para mostrar.
     * La racha "vigente" es 0 si el último día cubierto no es ni hoy ni ayer.
     */
    public static function obtenerResumen(PDO $conexion, int $usuarioId): array
    {
        $conProtectores = self::conProtectores($conexion);
        $stmt = $conexion->prepare("
            SELECT xp_total, racha_actual, racha_maxima, ultima_actividad"
            . ($conProtectores ? ", racha_hasta, protectores" : ", ultima_actividad AS racha_hasta, " . self::PROTECTORES_MES . " AS protectores") . "
            FROM estudiante_stats
            WHERE usuario_id = :usuario_id
            LIMIT 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            "xp_total" => 0, "racha_actual" => 0, "racha_maxima" => 0, "ultima_actividad" => null,
            "racha_hasta" => null, "protectores" => self::PROTECTORES_MES,
        ];

        $zona = self::zonaDe($conexion, $usuarioId);
        $hoy  = self::hoy($zona);
        $ayer = self::diaAnterior($hoy);
        $hasta = $fila["racha_hasta"] ?? $fila["ultima_actividad"];

        $xp = (int) $fila["xp_total"];

        return [
            "xp"            => $xp,
            "nivel"         => intdiv($xp, self::XP_POR_NIVEL) + 1,
            "xpEnNivel"     => $xp % self::XP_POR_NIVEL,
            "xpPorNivel"    => self::XP_POR_NIVEL,
            "racha"         => ($hasta === $hoy || $hasta === $ayer) ? (int) $fila["racha_actual"] : 0,
            "rachaMaxima"   => (int) $fila["racha_maxima"],
            "estudioHoy"    => $fila["ultima_actividad"] === $hoy,
            "conProtectores"=> $conProtectores,
            "protectores"   => (int) $fila["protectores"],
            "recarga"       => (new DateTimeImmutable($hoy))->modify("first day of next month")->format("Y-m-d"),
            "semana"        => $conProtectores ? self::semana($conexion, $usuarioId, $hoy) : [],
        ];
    }

    /** Los últimos 7 días (de hace 6 días a hoy) con 'estudio', 'protector' o null. */
    private static function semana(PDO $conexion, int $usuarioId, string $hoy): array
    {
        $inicio = (new DateTimeImmutable($hoy))->modify("-6 days");
        $stmt = $conexion->prepare("SELECT fecha, tipo FROM racha_dias WHERE usuario_id = ? AND fecha BETWEEN ? AND ?");
        $stmt->execute([$usuarioId, $inicio->format("Y-m-d"), $hoy]);
        $tipos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $f) {
            $tipos[$f["fecha"]] = $f["tipo"];
        }
        $letras = ["D", "L", "M", "M", "J", "V", "S"];
        $nombres = ["domingo", "lunes", "martes", "miércoles", "jueves", "viernes", "sábado"];
        $dias = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $inicio->modify("+{$i} days");
            $fecha = $d->format("Y-m-d");
            $dias[] = ["fecha" => $fecha, "letra" => $letras[(int) $d->format("w")], "nombre" => $nombres[(int) $d->format("w")],
                       "tipo" => $tipos[$fecha] ?? null, "hoy" => $fecha === $hoy];
        }
        return $dias;
    }

    /* =========================================================
       ESCRITURA (se llama DENTRO de la transacción de quien suma XP:
       completar lección, aprobar actividad, terminar lección de la ruta)
    ========================================================= */

    /**
     * Suma XP, guarda de dónde salió y actualiza la racha de hoy.
     * Devuelve la racha nueva.
     */
    public static function registrarActividad(PDO $conexion, int $usuarioId, int $puntos, string $motivo, ?int $referenciaId): int
    {
        $conProtectores = self::conProtectores($conexion);
        $hoy  = self::hoy(self::zonaDe($conexion, $usuarioId));
        $ayer = self::diaAnterior($hoy);

        // Crea la fila del estudiante si es su primera vez (con sus 2 protectores del mes)
        if ($conProtectores) {
            $conexion->prepare("INSERT IGNORE INTO estudiante_stats (usuario_id, protectores, protectores_mes) VALUES (?, ?, ?)")
                     ->execute([$usuarioId, self::PROTECTORES_MES, substr($hoy, 0, 7)]);
            self::actualizarRacha($conexion, $usuarioId, $hoy);   // primero se gastan los protectores que hagan falta
        } else {
            $conexion->prepare("INSERT IGNORE INTO estudiante_stats (usuario_id) VALUES (:usuario_id)")
                     ->execute([":usuario_id" => $usuarioId]);
        }

        // FOR UPDATE: bloquea la fila para que dos clics seguidos no
        // calculen la racha dos veces con los mismos datos
        $stmt = $conexion->prepare("
            SELECT racha_actual, racha_maxima, ultima_actividad" . ($conProtectores ? ", racha_hasta" : "") . "
            FROM estudiante_stats
            WHERE usuario_id = :usuario_id
            FOR UPDATE
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $hasta = $fila["racha_hasta"] ?? $fila["ultima_actividad"];

        if ($hasta === $hoy && (int) $fila["racha_actual"] > 0) {
            $racha = (int) $fila["racha_actual"];          // ya estudió hoy: no cambia
        } elseif ($hasta === $ayer && (int) $fila["racha_actual"] > 0) {
            $racha = (int) $fila["racha_actual"] + 1;      // ayer estudió (o lo cubrió un protector): sigue
        } else {
            $racha = 1;                                    // se cortó (o es la primera vez)
        }

        $conexion->prepare("
            UPDATE estudiante_stats
            SET xp_total = xp_total + :puntos,
                racha_actual = :racha,
                racha_maxima = GREATEST(racha_maxima, :racha2),
                ultima_actividad = :hoy" . ($conProtectores ? ", racha_hasta = :hoy2" : "") . "
            WHERE usuario_id = :usuario_id
        ")->execute([
            ":puntos"     => $puntos,
            ":racha"      => $racha,
            ":racha2"     => $racha,
            ":hoy"        => $hoy,
            ":usuario_id" => $usuarioId,
        ] + ($conProtectores ? [":hoy2" => $hoy] : []));

        if ($conProtectores) {
            $conexion->prepare("
                INSERT INTO racha_dias (usuario_id, fecha, tipo) VALUES (?, ?, 'estudio')
                ON DUPLICATE KEY UPDATE tipo = 'estudio'
            ")->execute([$usuarioId, $hoy]);
        }

        self::guardarMovimiento($conexion, $usuarioId, $puntos, $motivo, $referenciaId);

        return $racha;
    }

    /** XP extra sin tocar la racha (por ejemplo, al terminar un curso). */
    public static function sumarXp(PDO $conexion, int $usuarioId, int $puntos, string $motivo, ?int $referenciaId): void
    {
        $conexion->prepare("
            UPDATE estudiante_stats SET xp_total = xp_total + :puntos WHERE usuario_id = :usuario_id
        ")->execute([":puntos" => $puntos, ":usuario_id" => $usuarioId]);

        self::guardarMovimiento($conexion, $usuarioId, $puntos, $motivo, $referenciaId);
    }

    /* =========================================================
       AYUDANTES
    ========================================================= */

    private static function guardarMovimiento(PDO $conexion, int $usuarioId, int $puntos, string $motivo, ?int $referenciaId): void
    {
        $conexion->prepare("
            INSERT INTO xp_movimientos (usuario_id, puntos, motivo, referencia_id)
            VALUES (:usuario_id, :puntos, :motivo, :referencia_id)
        ")->execute([
            ":usuario_id"    => $usuarioId,
            ":puntos"        => $puntos,
            ":motivo"        => $motivo,
            ":referencia_id" => $referenciaId,
        ]);
    }

    private static function diaAnterior(string $fecha): string
    {
        return (new DateTimeImmutable($fecha))->modify("-1 day")->format("Y-m-d");
    }
}
