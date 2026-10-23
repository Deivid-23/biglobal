<?php

/**
 * DocenteSolicitudModel
 *
 * Solicitud de un docente (database/docente_documentos.sql): el idioma que
 * enseñará y los diplomas o certificados que lo acreditan.
 *
 * Reglas de los archivos (se revisan en el servidor, no se confía en el navegador):
 *   - PDF, JPG o PNG: se mira el contenido real (los primeros bytes), no la extensión.
 *   - Máximo 5 MB cada uno y 5 archivos por docente.
 *   - Se guardan en la BD (el disco de Render gratis no es permanente).
 *   - Se sirven con su tipo exacto, nosniff y CSP sandbox: nunca se ejecutan.
 *
 * La cuenta queda "Pendiente de aprobación" (usuarios.activo = 2) hasta que
 * un admin la apruebe o la rechace con un motivo. El docente recibe el
 * resultado por la campana y por correo (aviso esencial).
 */
class DocenteSolicitudModel
{
    public const MAX_ARCHIVOS = 5;
    public const MAX_BYTES = 5 * 1024 * 1024;

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name IN ('docente_solicitudes', 'docente_documentos')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       ARCHIVOS SUBIDOS
    ========================================================= */

    /**
     * Revisa $_FILES["documentos"] (input con multiple).
     * Devuelve la lista lista para guardar, o un texto con el error.
     * @return array|string
     */
    public static function validarArchivos($campo, int $yaGuardados = 0, bool $obligatorio = true)
    {
        $r = Archivo::validar($campo, ["pdf", "imagen"], self::MAX_ARCHIVOS, $yaGuardados, $obligatorio);
        if (!is_array($r)) {
            return $obligatorio && str_starts_with((string) $r, "Sube al menos") ? "Sube al menos un diploma o certificado (PDF, JPG o PNG)." : $r;
        }
        return $r;
    }

    /* =========================================================
       GUARDAR Y LEER
    ========================================================= */

    public static function guardarSolicitud(PDO $c, int $usuarioId, ?int $idiomaId): void
    {
        $c->prepare("
            INSERT INTO docente_solicitudes (usuario_id, idioma_id) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE idioma_id = COALESCE(VALUES(idioma_id), idioma_id)
        ")->execute([$usuarioId, $idiomaId]);
    }

    public static function guardarDocumentos(PDO $c, int $usuarioId, array $archivos): void
    {
        $stmt = $c->prepare("
            INSERT INTO docente_documentos (usuario_id, nombre_original, tipo_mime, tamano, sha256, contenido)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($archivos as $a) {
            $stmt->bindValue(1, $usuarioId, PDO::PARAM_INT);
            $stmt->bindValue(2, $a["nombre"]);
            $stmt->bindValue(3, $a["mime"]);
            $stmt->bindValue(4, $a["tamano"], PDO::PARAM_INT);
            $stmt->bindValue(5, $a["sha256"]);
            $stmt->bindValue(6, $a["contenido"], PDO::PARAM_LOB);
            $stmt->execute();
        }
    }

    /** Documentos sin el contenido (para listas). */
    public static function documentosDe(PDO $c, int $usuarioId): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $stmt = $c->prepare("SELECT id, nombre_original, tipo_mime, tamano, subido_en FROM docente_documentos WHERE usuario_id = ? ORDER BY id");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Documentos de varios docentes: [usuario_id => [...]] */
    public static function documentosDeVarios(PDO $c, array $usuarioIds): array
    {
        $ids = array_values(array_filter(array_map("intval", $usuarioIds)));
        if (!$ids || !self::disponible($c)) {
            return [];
        }
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("SELECT id, usuario_id, nombre_original, tipo_mime, tamano FROM docente_documentos WHERE usuario_id IN ($marcas) ORDER BY id");
        $stmt->execute($ids);
        $por = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $por[(int) $d["usuario_id"]][] = $d;
        }
        return $por;
    }

    /** Solicitudes (idioma, estado, motivo) de varios docentes: [usuario_id => fila] */
    public static function solicitudesDe(PDO $c, array $usuarioIds): array
    {
        $ids = array_values(array_filter(array_map("intval", $usuarioIds)));
        if (!$ids || !self::disponible($c)) {
            return [];
        }
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT s.*, i.nombre AS idioma FROM docente_solicitudes s LEFT JOIN idiomas i ON i.id = s.idioma_id
            WHERE s.usuario_id IN ($marcas)
        ");
        $stmt->execute($ids);
        $por = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $por[(int) $s["usuario_id"]] = $s;
        }
        return $por;
    }

    /** Un documento con su contenido. $dueñoId: si se pasa, solo si es de esa persona. */
    public static function documento(PDO $c, int $id, ?int $duenoId = null): ?array
    {
        if (!self::disponible($c)) {
            return null;
        }
        $sql = "SELECT * FROM docente_documentos WHERE id = ?" . ($duenoId !== null ? " AND usuario_id = ?" : "") . " LIMIT 1";
        $stmt = $c->prepare($sql);
        $stmt->execute($duenoId !== null ? [$id, $duenoId] : [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Envía el archivo al navegador de forma segura y termina. */
    public static function servir(array $doc): void
    {
        Archivo::servir($doc);
    }

    /* =========================================================
       REVISIÓN (admin)
    ========================================================= */

    public static function aprobar(PDO $c, int $usuarioId, int $adminId): void
    {
        if (self::disponible($c)) {
            $c->prepare("
                INSERT INTO docente_solicitudes (usuario_id, estado, revisado_por, revisado_en) VALUES (?, 'aprobada', ?, NOW())
                ON DUPLICATE KEY UPDATE estado = 'aprobada', motivo = NULL, revisado_por = VALUES(revisado_por), revisado_en = NOW()
            ")->execute([$usuarioId, $adminId]);
        }
        Notificador::aUsuario($usuarioId, "docente_aprobado", "¡Tu cuenta de docente fue aprobada!",
            "Ya puedes entrar a tu panel, crear cursos y programar clases en vivo. Recuerda: tus cursos y lecciones pasan por revisión antes de publicarse.",
            "profesor/index.php");
    }

    public static function rechazar(PDO $c, int $usuarioId, int $adminId, string $motivo): void
    {
        if (self::disponible($c)) {
            $c->prepare("
                INSERT INTO docente_solicitudes (usuario_id, estado, motivo, revisado_por, revisado_en) VALUES (?, 'rechazada', ?, ?, NOW())
                ON DUPLICATE KEY UPDATE estado = 'rechazada', motivo = VALUES(motivo), revisado_por = VALUES(revisado_por), revisado_en = NOW()
            ")->execute([$usuarioId, $motivo, $adminId]);
        }
        Notificador::aUsuario($usuarioId, "docente_rechazado", "Tu solicitud de docente no fue aprobada",
            "Revisamos tus documentos y por ahora no podemos aprobar tu cuenta de docente. Motivo: {$motivo} "
            . "Si crees que es un error o tienes otros certificados, escríbenos desde la página de contacto de BiGlobal.");
    }

    public static function pesoLegible(int $bytes): string
    {
        return Archivo::peso($bytes);
    }
}
