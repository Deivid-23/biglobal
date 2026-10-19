<?php

/**
 * GamificacionModel
 *
 * Racha diaria, XP, nivel y medallas del estudiante.
 * Usa las tablas de database/gamificacion.sql:
 *   estudiante_stats, xp_movimientos, medallas, usuario_medallas
 *
 * Si esas tablas todavía no existen (por ejemplo, en TiDB antes de
 * ejecutar el SQL), disponible() devuelve false y el panel sigue
 * funcionando sin gamificación en vez de mostrar un error.
 */
class GamificacionModel
{
    /** Puntos que se ganan */
    public const XP_LECCION = 10;
    public const XP_CURSO   = 50;

    /** XP que hace falta para subir cada nivel */
    public const XP_POR_NIVEL = 100;

    /** La racha se cuenta con la fecha de Colombia, no la del servidor */
    private const ZONA_HORARIA = "America/Bogota";

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

    /** Fecha de hoy en Colombia, formato AAAA-MM-DD */
    public static function hoy(): string
    {
        return (new DateTimeImmutable("now", new DateTimeZone(self::ZONA_HORARIA)))->format("Y-m-d");
    }

    /* =========================================================
       LECTURA (para el inicio)
    ========================================================= */

    /**
     * XP, nivel y racha listos para mostrar.
     * La racha "vigente" es 0 si el estudiante no estudió ni hoy ni ayer.
     */
    public static function obtenerResumen(PDO $conexion, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT xp_total, racha_actual, racha_maxima, ultima_actividad
            FROM estudiante_stats
            WHERE usuario_id = :usuario_id
            LIMIT 1
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        $fila = $stmt->fetch() ?: [
            "xp_total" => 0, "racha_actual" => 0, "racha_maxima" => 0, "ultima_actividad" => null,
        ];

        $hoy  = self::hoy();
        $ayer = self::diaAnterior($hoy);
        $ultima = $fila["ultima_actividad"];

        $rachaVigente = ($ultima === $hoy || $ultima === $ayer) ? (int) $fila["racha_actual"] : 0;

        $xp = (int) $fila["xp_total"];

        return [
            "xp"            => $xp,
            "nivel"         => intdiv($xp, self::XP_POR_NIVEL) + 1,
            "xpEnNivel"     => $xp % self::XP_POR_NIVEL,
            "xpPorNivel"    => self::XP_POR_NIVEL,
            "racha"         => $rachaVigente,
            "rachaMaxima"   => (int) $fila["racha_maxima"],
            "estudioHoy"    => $ultima === $hoy,
        ];
    }

    /** Todas las medallas del catálogo, marcando cuáles ya ganó. */
    public static function obtenerMedallas(PDO $conexion, int $usuarioId): array
    {
        $stmt = $conexion->prepare("
            SELECT m.codigo, m.nombre, m.descripcion, m.icono, um.fecha AS fecha_ganada
            FROM medallas m
            LEFT JOIN usuario_medallas um
                   ON um.medalla_id = m.id AND um.usuario_id = :usuario_id
            ORDER BY m.id ASC
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        return $stmt->fetchAll();
    }

    /* =========================================================
       ESCRITURA (se llama DENTRO de la transacción de
       EstudianteController::completarLeccion)
    ========================================================= */

    /**
     * Suma XP, guarda de dónde salió y actualiza la racha de hoy.
     * Devuelve la racha nueva.
     */
    public static function registrarActividad(PDO $conexion, int $usuarioId, int $puntos, string $motivo, ?int $referenciaId): int
    {
        // Crea la fila del estudiante si es su primera vez
        $conexion->prepare("INSERT IGNORE INTO estudiante_stats (usuario_id) VALUES (:usuario_id)")
                 ->execute([":usuario_id" => $usuarioId]);

        // FOR UPDATE: bloquea la fila para que dos clics seguidos no
        // calculen la racha dos veces con los mismos datos
        $stmt = $conexion->prepare("
            SELECT racha_actual, racha_maxima, ultima_actividad
            FROM estudiante_stats
            WHERE usuario_id = :usuario_id
            FOR UPDATE
        ");
        $stmt->execute([":usuario_id" => $usuarioId]);
        $fila = $stmt->fetch();

        $hoy  = self::hoy();
        $ayer = self::diaAnterior($hoy);

        if ($fila["ultima_actividad"] === $hoy) {
            $racha = (int) $fila["racha_actual"];          // ya estudió hoy: no cambia
        } elseif ($fila["ultima_actividad"] === $ayer) {
            $racha = (int) $fila["racha_actual"] + 1;      // estudió ayer: sigue la racha
        } else {
            $racha = 1;                                    // se cortó (o es la primera vez)
        }

        $conexion->prepare("
            UPDATE estudiante_stats
            SET xp_total = xp_total + :puntos,
                racha_actual = :racha,
                racha_maxima = GREATEST(racha_maxima, :racha2),
                ultima_actividad = :hoy
            WHERE usuario_id = :usuario_id
        ")->execute([
            ":puntos"     => $puntos,
            ":racha"      => $racha,
            ":racha2"     => $racha,
            ":hoy"        => $hoy,
            ":usuario_id" => $usuarioId,
        ]);

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

    /**
     * Da una medalla por su código. Devuelve su nombre si es NUEVA,
     * o null si ya la tenía (o el código no existe).
     */
    public static function otorgarMedalla(PDO $conexion, int $usuarioId, string $codigo): ?string
    {
        $stmt = $conexion->prepare("
            INSERT IGNORE INTO usuario_medallas (usuario_id, medalla_id)
            SELECT :usuario_id, id FROM medallas WHERE codigo = :codigo
        ");
        $stmt->execute([":usuario_id" => $usuarioId, ":codigo" => $codigo]);

        if ($stmt->rowCount() === 0) {
            return null;
        }

        $nombre = $conexion->prepare("SELECT nombre FROM medallas WHERE codigo = :codigo LIMIT 1");
        $nombre->execute([":codigo" => $codigo]);
        return $nombre->fetchColumn() ?: null;
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
