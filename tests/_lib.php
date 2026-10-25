<?php

/**
 * Ayudantes de las pruebas (los usa tools/probar.php).
 *
 * - P::ver("nombre", condición) anota una comprobación.
 * - P::usuario($c, "estudiante") crea un usuario TEMPORAL (correo
 *   prueba-auto-...@prueba.test). Al terminar cada archivo, P::limpiar()
 *   lo borra y con él, en cascada, todo lo suyo (progreso, racha, grupos...).
 * - P::config() guarda un valor de la tabla configuracion y lo restaura al final.
 *
 * Las pruebas nunca dependen de las cuentas fijas de biglobal_prueba.
 */
final class P
{
    public static int $bien = 0;
    public static int $mal = 0;
    public static array $fallos = [];
    public static string $archivo = "";
    private static array $usuarios = [];
    private static array $configs = [];

    public static function ver(string $nombre, bool $condicion): void
    {
        if ($condicion) {
            self::$bien++;
            return;
        }
        self::$mal++;
        self::$fallos[] = self::$archivo . ": " . $nombre;
        echo "    MAL  {$nombre}\n";
    }

    /** Usuario temporal activo con ese rol. Devuelve el id. */
    public static function usuario(PDO $c, string $rol, string $zona = "America/Bogota"): int
    {
        $stmt = $c->prepare("SELECT id FROM roles WHERE nombre = ? LIMIT 1");
        $stmt->execute([$rol]);
        $rolId = (int) $stmt->fetchColumn();
        $correo = "prueba-auto-" . bin2hex(random_bytes(5)) . "@prueba.test";
        $c->prepare("INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, activo, zona_horaria) VALUES ('Prueba', 'Automática', ?, NULL, ?, 1, ?)")
          ->execute([$correo, $rolId, $zona]);
        $id = (int) $c->lastInsertId();
        self::$usuarios[] = $id;
        return $id;
    }

    /** Guarda el valor actual de una clave de configuración para restaurarlo al final. */
    public static function config(string $clave, string $valor): void
    {
        if (!array_key_exists($clave, self::$configs)) {
            self::$configs[$clave] = Config::get($clave, null);
        }
        Config::set($clave, $valor);
    }

    /** Borra los usuarios temporales y restaura la configuración. */
    public static function limpiar(PDO $c): void
    {
        if ($c->inTransaction()) {
            $c->rollBack();
        }
        if (self::$usuarios) {
            $marcas = implode(",", array_fill(0, count(self::$usuarios), "?"));
            $c->prepare("DELETE FROM auditoria WHERE objeto_id IN ($marcas) AND objeto_tipo = 'cuenta'")->execute(self::$usuarios);
            $c->prepare("DELETE FROM usuarios WHERE id IN ($marcas)")->execute(self::$usuarios);
        }
        foreach (self::$configs as $clave => $valor) {
            if ($valor === null) {
                $c->prepare("DELETE FROM configuracion WHERE clave = ?")->execute([$clave]);
            } else {
                Config::set($clave, (string) $valor);
            }
        }
        self::$usuarios = [];
        self::$configs = [];
        $_SESSION = [];
    }

    /** Respuesta correcta de un ejercicio, sacada de sus datos (solo para las pruebas). */
    public static function respuestaCorrecta(array $e)
    {
        $d = json_decode((string) $e["datos"], true) ?: [];
        switch ($e["tipo"]) {
            case "opcion": case "sena": case "escuchar": return (string) $d["opciones"][(int) ($d["correcta"] ?? 0)];
            case "vf": return !empty($d["correcta"]) ? "1" : "0";
            case "completar": case "traducir": return (string) $d["respuestas"][0];
            case "ordenar": return (string) $d["frase"];
            case "dictado": return (string) $d["texto"];
            case "emparejar": return $d["pares"];
            case "error": return (string) $d["error"];
            case "clasificar": return array_map(fn($it) => [$it[0], $d["grupos"][$it[1]]], $d["items"]);
            case "huecos": return $d["respuestas"];
        }
        return "?";
    }

    /** Id de un idioma por código ("EN"). */
    public static function idioma(PDO $c, string $codigo): int
    {
        $stmt = $c->prepare("SELECT id FROM idiomas WHERE codigo = ? LIMIT 1");
        $stmt->execute([$codigo]);
        return (int) $stmt->fetchColumn();
    }

    /** Lecciones publicadas y jugables de un nivel, en orden. */
    public static function lecciones(PDO $c, int $idiomaId, string $nivel): array
    {
        $stmt = $c->prepare("
            SELECT l.* FROM ruta_lecciones l
            INNER JOIN ruta_unidades u ON u.id = l.unidad_id AND u.publicado = 1
            INNER JOIN ruta_niveles n ON n.id = u.nivel_id AND n.publicado = 1
            WHERE n.idioma_id = ? AND n.codigo = ? AND l.publicado = 1
              AND EXISTS (SELECT 1 FROM ruta_ejercicios e WHERE e.leccion_id = l.id AND e.activo = 1 AND (e.tipo NOT IN ('sena', 'deletreo') OR COALESCE(e.video_url, '') <> ''))
            ORDER BY u.orden, u.id, l.orden, l.id
        ");
        $stmt->execute([$idiomaId, $nivel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Marca lecciones como completadas (sin XP) para un usuario. */
    public static function completar(PDO $c, int $usuarioId, array $lecciones, int $porcentaje = 100): void
    {
        $stmt = $c->prepare("INSERT IGNORE INTO ruta_progreso (usuario_id, leccion_id, mejor_porcentaje) VALUES (?, ?, ?)");
        foreach ($lecciones as $l) {
            $stmt->execute([$usuarioId, (int) $l["id"], $porcentaje]);
        }
    }
}
