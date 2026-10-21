<?php

/**
 * Config
 *
 * Helper para leer/escribir la configuración del sistema
 * almacenada en la tabla `configuracion`.
 *
 * Uso:
 *   Config::get('sitio_nombre')              → "BiGlobal"
 *   Config::get('sitio_nombre', 'Default')   → valor con fallback
 *   Config::set('sitio_nombre', 'Nuevo')     → guarda
 *   Config::all()                            → array completo
 *   Config::getBool('notif_nuevo_usuario')   → true / false
 *   Config::getInt('seg_min_password')       → 8
 */
class Config
{
    /** @var array<string,string>|null Cache en memoria */
    private static ?array $cache = null;

    /* =========================================================
       OBTENER TODA LA CONFIGURACIÓN (con cache)
    ========================================================= */
    private static function cargar(): void
    {
        if (self::$cache !== null) return;

        self::$cache = [];

        try {
            $conexion = Conexion::conectar();
            $stmt = $conexion->query("SELECT clave, valor FROM configuracion");
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                self::$cache[$fila["clave"]] = $fila["valor"];
            }
        } catch (PDOException $e) {
            error_log("[BiGlobal][Config] " . $e->getMessage());
            self::$cache = [];
        }
    }

    /* =========================================================
       GET — obtener un valor
    ========================================================= */
    public static function get(string $clave, $default = null)
    {
        self::cargar();
        return self::$cache[$clave] ?? $default;
    }

    /* =========================================================
       GET BOOL — devuelve true/false
    ========================================================= */
    public static function getBool(string $clave, bool $default = false): bool
    {
        $valor = self::get($clave, $default ? "1" : "0");
        return in_array((string)$valor, ["1", "true", "yes", "on"], true);
    }

    /* =========================================================
       GET INT — devuelve entero
    ========================================================= */
    public static function getInt(string $clave, int $default = 0): int
    {
        $valor = self::get($clave, $default);
        return is_numeric($valor) ? (int)$valor : $default;
    }

    /* =========================================================
       SET — guardar un valor (UPDATE o INSERT)
    ========================================================= */
    public static function set(string $clave, $valor): bool
    {
        try {
            $conexion = Conexion::conectar();

            $stmt = $conexion->prepare("
                INSERT INTO configuracion (clave, valor)
                VALUES (:clave, :valor)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");

            $ok = $stmt->execute([
                ":clave" => $clave,
                ":valor" => is_bool($valor) ? ($valor ? "1" : "0") : (string)$valor,
            ]);

            /* Actualizar cache en memoria */
            self::cargar();
            self::$cache[$clave] = is_bool($valor) ? ($valor ? "1" : "0") : (string)$valor;

            return $ok;

        } catch (PDOException $e) {
            error_log("[BiGlobal][Config][set] " . $e->getMessage());
            return false;
        }
    }

    /* =========================================================
       SET MÚLTIPLE — guardar varios valores a la vez
       Recibe: ['clave1' => 'valor1', 'clave2' => 'valor2']
    ========================================================= */
    public static function setMany(array $datos): bool
    {
        if (empty($datos)) return true;

        try {
            $conexion = Conexion::conectar();
            $conexion->beginTransaction();

            $stmt = $conexion->prepare("
                INSERT INTO configuracion (clave, valor)
                VALUES (:clave, :valor)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");

            foreach ($datos as $clave => $valor) {
                $stmt->execute([
                    ":clave" => $clave,
                    ":valor" => is_bool($valor) ? ($valor ? "1" : "0") : (string)$valor,
                ]);

                self::$cache[$clave] = is_bool($valor) ? ($valor ? "1" : "0") : (string)$valor;
            }

            $conexion->commit();
            return true;

        } catch (PDOException $e) {
            if (isset($conexion) && $conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("[BiGlobal][Config][setMany] " . $e->getMessage());
            return false;
        }
    }

    /* =========================================================
       ALL — devuelve toda la configuración
    ========================================================= */
    public static function all(): array
    {
        self::cargar();
        return self::$cache ?? [];
    }

    /* =========================================================
       REFRESH — limpiar cache (por si cambias algo externo)
    ========================================================= */
    public static function refresh(): void
    {
        self::$cache = null;
    }
}