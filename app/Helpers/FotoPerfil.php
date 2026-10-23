<?php

/**
 * FotoPerfil
 *
 * Foto de perfil de cualquier usuario (tabla usuario_fotos).
 *
 *   FotoPerfil::avatar($id, "AB", "pnl-avatar")  -> <span> con la foto o con las iniciales
 *   FotoPerfil::versiones([ids])                 -> precarga varias de una vez (tablón de un grupo)
 *   FotoPerfil::guardar($id, $_FILES["foto"])    -> true | "texto del error"
 *   FotoPerfil::quitar($id)
 *
 * La foto se entrega por src/auth/foto.php?u=ID&v=VERSIÓN, solo con sesión.
 * La versión (fecha de la foto) hace que al cambiarla se vea la nueva al instante.
 * Si la tabla todavía no existe, todo sigue funcionando con las iniciales.
 */
class FotoPerfil
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** id => versión (0 = sin foto). */
    private static array $versiones = [];

    public static function disponible(): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) Conexion::conectar()->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name = 'usuario_fotos'
                ")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /** Carga de una vez las versiones de varios usuarios (evita una consulta por avatar). */
    public static function versiones(array $ids): void
    {
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids), fn($i) => $i > 0 && !isset(self::$versiones[$i]))));
        if (!$ids || !self::disponible()) {
            return;
        }
        foreach ($ids as $i) {
            self::$versiones[$i] = 0;
        }
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = Conexion::conectar()->prepare("SELECT usuario_id, UNIX_TIMESTAMP(actualizada) FROM usuario_fotos WHERE usuario_id IN ($marcas)");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as [$id, $version]) {
            self::$versiones[(int) $id] = (int) $version;
        }
    }

    public static function version(int $usuarioId): int
    {
        self::versiones([$usuarioId]);
        return self::$versiones[$usuarioId] ?? 0;
    }

    /** Dirección de la foto (relativa a una carpeta de src/), o null si no tiene. */
    public static function url(int $usuarioId): ?string
    {
        $v = self::version($usuarioId);
        return $v > 0 ? "../auth/foto.php?u=" . $usuarioId . "&v=" . $v : null;
    }

    /**
     * El avatar: la foto si tiene, o las iniciales. Siempre decorativo
     * (aria-hidden): el nombre ya está escrito al lado.
     */
    public static function avatar(int $usuarioId, string $iniciales, string $clase): string
    {
        $url = self::url($usuarioId);
        if ($url === null) {
            return '<span class="' . e($clase) . '" aria-hidden="true">' . e($iniciales) . '</span>';
        }
        return '<span class="' . e($clase) . ' con-foto" aria-hidden="true"><img src="' . e($url) . '" alt="" loading="lazy" decoding="async"></span>';
    }

    /** @return true|string */
    public static function guardar(int $usuarioId, $campo)
    {
        if (!self::disponible()) {
            return "La foto de perfil aún no está disponible.";
        }
        $archivos = Archivo::validar($campo, ["imagen"], 1);
        if (!is_array($archivos)) {
            return str_starts_with($archivos, "Sube al menos") ? "Elige una foto (JPG o PNG)." : $archivos;
        }
        $a = $archivos[0];
        if ($a["tamano"] > self::MAX_BYTES) {
            return "La foto pesa más de 2 MB. Elige una más liviana.";
        }
        $stmt = Conexion::conectar()->prepare("
            INSERT INTO usuario_fotos (usuario_id, mime, tamano, contenido, actualizada) VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE mime = VALUES(mime), tamano = VALUES(tamano), contenido = VALUES(contenido), actualizada = NOW()
        ");
        $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
        $stmt->bindValue(2, $a["mime"]);
        $stmt->bindValue(3, $a["tamano"], PDO::PARAM_INT);
        $stmt->bindValue(4, $a["contenido"], PDO::PARAM_LOB);
        $stmt->execute();
        unset(self::$versiones[$usuarioId]);
        return true;
    }

    public static function quitar(int $usuarioId): bool
    {
        if (!self::disponible()) {
            return false;
        }
        $stmt = Conexion::conectar()->prepare("DELETE FROM usuario_fotos WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        unset(self::$versiones[$usuarioId]);
        return $stmt->rowCount() > 0;
    }

    /** La foto con su contenido (para foto.php), o null. */
    public static function obtener(int $usuarioId): ?array
    {
        if (!self::disponible()) {
            return null;
        }
        $stmt = Conexion::conectar()->prepare("SELECT mime, contenido FROM usuario_fotos WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
        $f = $stmt->fetch(PDO::FETCH_ASSOC);
        return $f ? $f + ["nombre" => "foto-" . $usuarioId . ($f["mime"] === "image/png" ? ".png" : ".jpg")] : null;
    }
}
