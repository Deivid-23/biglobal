<?php

/**
 * PosicionesModel
 *
 * Tabla de posiciones: XP ganado en la semana (lunes a domingo, hora de
 * Colombia), de toda la plataforma o de un grupo. Solo lectura.
 * Datos de xp_movimientos; privacidad en usuarios.posiciones_visible
 * (database/posiciones.sql). Reglas:
 *
 * - Solo estudiantes activos. Nunca docentes, admins, cuentas eliminadas
 *   ni usuarios de las pruebas automáticas.
 * - Quien se oculta no aparece para los demás, pero ve su propia posición.
 * - Se muestra el nombre y la inicial del apellido ("Ana G."), nunca el correo.
 * - Empates: comparten puesto (1, 2, 2, 4) y el orden lo da quien llegó
 *   primero a ese XP.
 */
class PosicionesModel
{
    public const LIMITE = 20;

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'posiciones_visible'
                ")->fetchColumn() === 1 && GamificacionModel::disponible($c);
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /** Lunes de esta semana a las 00:00 (hora de Colombia, la de la conexión). */
    public static function inicioSemana(PDO $c): string
    {
        return (string) $c->query("SELECT CONCAT(CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY, ' 00:00:00')")->fetchColumn();
    }

    public static function visible(PDO $c, int $usuarioId): bool
    {
        if (!self::disponible($c)) {
            return true;
        }
        $stmt = $c->prepare("SELECT posiciones_visible FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn() !== 0;
    }

    public static function guardarVisible(PDO $c, int $usuarioId, bool $visible): bool
    {
        if (!self::disponible($c)) {
            return false;
        }
        $c->prepare("UPDATE usuarios SET posiciones_visible = ? WHERE id = ?")->execute([$visible ? 1 : 0, $usuarioId]);
        return true;
    }

    /** Toda la plataforma. @return array ["filas" => top LIMITE, "yo" => mi fila o null, "total" => cuántos suman XP] */
    public static function general(PDO $c, int $usuarioId): array
    {
        return self::tabla($c, $usuarioId, null);
    }

    /** Solo los miembros de un grupo (quien lo pide debe tener acceso: lo revisa el controlador). */
    public static function grupo(PDO $c, int $usuarioId, int $grupoId): array
    {
        return self::tabla($c, $usuarioId, $grupoId);
    }

    private static function tabla(PDO $c, int $usuarioId, ?int $grupoId): array
    {
        $vacia = ["filas" => [], "yo" => null, "total" => 0];
        if (!self::disponible($c)) {
            return $vacia;
        }
        $params = [self::inicioSemana($c), $usuarioId];
        $grupo = "";
        if ($grupoId !== null) {
            $grupo = "AND EXISTS (SELECT 1 FROM grupo_miembros m WHERE m.grupo_id = ? AND m.usuario_id = u.id)";
            $params[] = $grupoId;
        }
        $stmt = $c->prepare("
            SELECT u.id, u.nombre, u.apellido, SUM(x.puntos) AS xp, MAX(x.fecha) AS ultima
            FROM xp_movimientos x
            INNER JOIN usuarios u ON u.id = x.usuario_id AND u.activo = 1
            INNER JOIN roles r ON r.id = u.rol_id AND r.nombre = 'estudiante'
            WHERE x.fecha >= ?
              AND (u.posiciones_visible = 1 OR u.id = ?)
              AND u.correo NOT LIKE 'prueba-auto-%@prueba.test'
              $grupo
            GROUP BY u.id, u.nombre, u.apellido
            HAVING SUM(x.puntos) > 0
            ORDER BY SUM(x.puntos) DESC, MAX(x.fecha) ASC, u.id ASC
        ");
        $stmt->execute($params);

        $filas = [];
        $puesto = 0;
        $anterior = null;
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $i => $f) {
            $xp = (int) $f["xp"];
            if ($xp !== $anterior) {
                $puesto = $i + 1;   // empates: mismo puesto (1, 2, 2, 4)
                $anterior = $xp;
            }
            $filas[] = [
                "id"        => (int) $f["id"],
                "puesto"    => $puesto,
                "nombre"    => self::nombreCorto((string) $f["nombre"], (string) $f["apellido"]),
                "iniciales" => self::iniciales((string) $f["nombre"], (string) $f["apellido"]),
                "xp"        => $xp,
                "soyYo"     => (int) $f["id"] === $usuarioId,
            ];
        }
        $yo = null;
        foreach ($filas as $f) {
            if ($f["soyYo"]) {
                $yo = $f;
            }
        }
        return ["filas" => array_slice($filas, 0, self::LIMITE), "yo" => $yo, "total" => count($filas)];
    }

    /** "Ana María" + "Gómez Ruiz" -> "Ana G." */
    public static function nombreCorto(string $nombre, string $apellido): string
    {
        $primero = explode(" ", trim($nombre))[0] ?: "Estudiante";
        $apellido = trim($apellido) === "No especificado" ? "" : trim($apellido);
        return $apellido !== "" ? $primero . " " . mb_strtoupper(mb_substr($apellido, 0, 1)) . "." : $primero;
    }

    private static function iniciales(string $nombre, string $apellido): string
    {
        $apellido = trim($apellido) === "No especificado" ? "" : trim($apellido);
        return mb_strtoupper(mb_substr(trim($nombre), 0, 1) . mb_substr($apellido, 0, 1)) ?: "E";
    }
}
