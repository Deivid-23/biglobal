<?php

/**
 * Archivo
 *
 * Subida y entrega SEGURA de archivos (se guardan en la BD porque el disco
 * del plan gratis de Render se borra en cada despliegue).
 *
 *   Archivo::validar($_FILES["campo"], ["pdf", "imagen", "audio"], 5)  -> lista lista para guardar | "error"
 *   Archivo::servir($fila)                                             -> envía el archivo y termina
 *
 * - El tipo se decide por el CONTENIDO (primeros bytes), nunca por la
 *   extensión ni por lo que diga el navegador.
 * - Máximo 5 MB por archivo.
 * - Se entregan con su tipo exacto, nosniff y CSP sandbox: nunca se ejecutan.
 */
class Archivo
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    /** Grupos de tipos que se pueden pedir => [mime => extensión] */
    public const GRUPOS = [
        "pdf"    => ["application/pdf" => "pdf"],
        "imagen" => ["image/jpeg" => "jpg", "image/png" => "png"],
        "audio"  => ["audio/mpeg" => "mp3", "audio/mp4" => "m4a"],
    ];

    /** Lo que se escribe en el atributo accept="" del input. */
    public static function accept(array $grupos): string
    {
        $mapa = ["pdf" => ".pdf,application/pdf", "imagen" => ".jpg,.jpeg,.png,image/jpeg,image/png", "audio" => ".mp3,.m4a,audio/mpeg,audio/mp4,audio/x-m4a"];
        return implode(",", array_map(fn($g) => $mapa[$g] ?? "", $grupos));
    }

    /** "PDF, JPG, PNG, MP3 o M4A" para los textos de ayuda. */
    public static function textoTipos(array $grupos): string
    {
        $ext = [];
        foreach ($grupos as $g) {
            foreach (self::GRUPOS[$g] ?? [] as $e) {
                $ext[] = strtoupper($e);
            }
        }
        $ultimo = array_pop($ext);
        return $ext ? implode(", ", $ext) . " o " . $ultimo : (string) $ultimo;
    }

    /**
     * Revisa un input de archivos (con o sin multiple).
     * @return array|string Lista [nombre, mime, tamano, sha256, contenido] o el texto del error.
     */
    public static function validar($campo, array $grupos, int $maxArchivos, int $yaGuardados = 0, bool $obligatorio = true)
    {
        $lista = [];
        if (is_array($campo) && isset($campo["name"])) {
            foreach ((array) $campo["name"] as $i => $nombre) {
                $error = (int) (((array) $campo["error"])[$i] ?? UPLOAD_ERR_NO_FILE);
                if ($error === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $lista[] = [
                    "nombre" => (string) $nombre,
                    "tmp"    => (string) (((array) $campo["tmp_name"])[$i] ?? ""),
                    "tamano" => (int) (((array) $campo["size"])[$i] ?? 0),
                    "error"  => $error,
                ];
            }
        }

        $tipos = self::textoTipos($grupos);
        if (!$lista) {
            return $obligatorio ? "Sube al menos un archivo ({$tipos})." : [];
        }
        if (count($lista) + $yaGuardados > $maxArchivos) {
            return "Puedes subir máximo {$maxArchivos} archivos en total.";
        }

        $archivos = [];
        foreach ($lista as $a) {
            if ($a["error"] === UPLOAD_ERR_INI_SIZE || $a["error"] === UPLOAD_ERR_FORM_SIZE || $a["tamano"] > self::MAX_BYTES) {
                return "«" . self::nombreLimpio($a["nombre"], "") . "» pesa más de 5 MB.";
            }
            if ($a["error"] !== UPLOAD_ERR_OK || !is_uploaded_file($a["tmp"])) {
                return "No pudimos recibir uno de los archivos. Inténtalo de nuevo.";
            }
            $contenido = (string) file_get_contents($a["tmp"]);
            $mime = self::tipoReal($contenido, $grupos);
            if ($mime === null || $contenido === "") {
                return "«" . self::nombreLimpio($a["nombre"], "") . "» no es un archivo válido ({$tipos}).";
            }
            $archivos[] = [
                "nombre"    => self::nombreLimpio($a["nombre"], self::extension($mime)),
                "mime"      => $mime,
                "tamano"    => strlen($contenido),
                "sha256"    => hash("sha256", $contenido),
                "contenido" => $contenido,
            ];
        }
        return $archivos;
    }

    /** Tipo según los primeros bytes, solo entre los grupos permitidos. */
    public static function tipoReal(string $c, array $grupos): ?string
    {
        $permitidos = [];
        foreach ($grupos as $g) {
            $permitidos += self::GRUPOS[$g] ?? [];
        }
        $detectado = null;
        if (strncmp($c, "%PDF-", 5) === 0) {
            $detectado = "application/pdf";
        } elseif (strncmp($c, "\xFF\xD8\xFF", 3) === 0) {
            $detectado = "image/jpeg";
        } elseif (strncmp($c, "\x89PNG\r\n\x1A\n", 8) === 0) {
            $detectado = "image/png";
        } elseif (strncmp($c, "ID3", 3) === 0 || (strlen($c) > 1 && ord($c[0]) === 0xFF && (ord($c[1]) & 0xE0) === 0xE0)) {
            $detectado = "audio/mpeg";   // MP3: etiqueta ID3 o marco de audio
        } elseif (substr($c, 4, 4) === "ftyp" && in_array(substr($c, 8, 4), ["M4A ", "M4B ", "mp42", "isom", "mp41", "dash"], true)) {
            $detectado = "audio/mp4";    // M4A (contenedor MP4 de audio)
        }
        return ($detectado !== null && isset($permitidos[$detectado])) ? $detectado : null;
    }

    public static function extension(string $mime): string
    {
        foreach (self::GRUPOS as $tipos) {
            if (isset($tipos[$mime])) {
                return $tipos[$mime];
            }
        }
        return "bin";
    }

    /** "../../Mi tarea (final).PDF" -> "Mi tarea (final).pdf" */
    public static function nombreLimpio(string $nombre, string $extension): string
    {
        $nombre = basename(str_replace("\\", "/", $nombre));
        $base = pathinfo($nombre, PATHINFO_FILENAME);
        $base = trim((string) preg_replace('/[^\p{L}\p{N} ._()\-]+/u', "", $base));
        $base = mb_substr($base !== "" ? $base : "archivo", 0, 120);
        return $extension !== "" ? $base . "." . $extension : $base;
    }

    /** Ícono de Font Awesome para listas. */
    public static function icono(string $mime): string
    {
        return str_starts_with($mime, "image/") ? "fa-file-image" : (str_starts_with($mime, "audio/") ? "fa-file-audio" : "fa-file-pdf");
    }

    public static function peso(int $bytes): string
    {
        return $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ",", ".") . " MB" : max(1, (int) round($bytes / 1024)) . " KB";
    }

    /**
     * Envía el archivo al navegador de forma segura y termina.
     * $fila necesita: nombre (o nombre_original), mime (o tipo_mime) y contenido.
     */
    public static function servir(array $fila): void
    {
        $mime = (string) ($fila["mime"] ?? $fila["tipo_mime"] ?? "");
        $conocido = false;
        foreach (self::GRUPOS as $tipos) {
            $conocido = $conocido || isset($tipos[$mime]);
        }
        $nombre = str_replace(['"', "\r", "\n"], "", (string) ($fila["nombre"] ?? $fila["nombre_original"] ?? "archivo"));
        $contenido = (string) $fila["contenido"];

        header("Content-Type: " . ($conocido ? $mime : "application/octet-stream"));
        header("Content-Length: " . strlen($contenido));
        header("Content-Disposition: inline; filename=\"" . preg_replace('/[^\x20-\x7E]/', "_", $nombre) . "\"; filename*=UTF-8''" . rawurlencode($nombre));
        header("X-Content-Type-Options: nosniff");
        header("Content-Security-Policy: sandbox; default-src 'none'; img-src 'self'; media-src 'self'; style-src 'unsafe-inline'");
        header("Cache-Control: private, no-store");
        echo $contenido;
        exit;
    }
}
