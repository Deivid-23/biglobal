<?php

/**
 * Video
 *
 * Videos de las lecciones: solo YouTube y Vimeo (la CSP del Dockerfile
 * solo deja mostrar esos dos reproductores).
 *
 *   Video::normalizar($texto)  -> ["url" => enlace limpio] | null si está vacío | "mensaje de error"
 *   Video::reproductor($url)   -> ["src" => dirección del iframe, "proveedor" => "YouTube"] | null
 *
 * Nunca se mete en la página el enlace tal cual lo escribió alguien:
 * se saca el id del video y se arma la dirección nosotros mismos.
 */
class Video
{
    /** @return array|string|null */
    public static function normalizar(string $texto)
    {
        $texto = trim($texto);
        if ($texto === "") {
            return null;
        }
        if (mb_strlen($texto) > 255) {
            return "El enlace del video es demasiado largo.";
        }

        $id = self::idYoutube($texto);
        if ($id !== null) {
            return ["url" => "https://www.youtube.com/watch?v=" . $id];
        }

        $vimeo = self::idVimeo($texto);
        if ($vimeo !== null) {
            return ["url" => "https://vimeo.com/" . $vimeo[0] . ($vimeo[1] !== "" ? "/" . $vimeo[1] : "")];
        }

        return "El video debe ser un enlace de YouTube o de Vimeo (por ejemplo https://youtu.be/... o https://vimeo.com/...).";
    }

    /** Dirección segura del reproductor, o null si el enlace no es válido. */
    public static function reproductor(?string $url): ?array
    {
        if ($url === null || trim($url) === "") {
            return null;
        }

        $id = self::idYoutube($url);
        if ($id !== null) {
            // youtube-nocookie: no deja cookies de seguimiento hasta que se da play
            return ["proveedor" => "YouTube", "src" => "https://www.youtube-nocookie.com/embed/" . $id . "?rel=0"];
        }

        $vimeo = self::idVimeo($url);
        if ($vimeo !== null) {
            $src = "https://player.vimeo.com/video/" . $vimeo[0] . "?dnt=1";
            if ($vimeo[1] !== "") {
                $src .= "&h=" . $vimeo[1];   // videos "no listados" de Vimeo
            }
            return ["proveedor" => "Vimeo", "src" => $src];
        }

        return null;
    }

    /** id de 11 caracteres de YouTube, o null. */
    private static function idYoutube(string $url): ?string
    {
        $partes = self::partes($url);
        if ($partes === null) {
            return null;
        }
        [$host, $ruta, $query] = $partes;

        $id = null;
        if ($host === "youtu.be") {
            $id = explode("/", trim($ruta, "/"))[0] ?? null;
        } elseif (in_array($host, ["youtube.com", "m.youtube.com", "music.youtube.com", "youtube-nocookie.com"], true)) {
            if (trim($ruta, "/") === "watch") {
                parse_str($query, $q);
                $id = is_string($q["v"] ?? null) ? $q["v"] : null;
            } elseif (preg_match('#^/(embed|shorts|live|v)/([^/?]+)#', $ruta, $m)) {
                $id = $m[2];
            }
        }

        return ($id !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) ? $id : null;
    }

    /** [id numérico, hash privado o ""] de Vimeo, o null. */
    private static function idVimeo(string $url): ?array
    {
        $partes = self::partes($url);
        if ($partes === null) {
            return null;
        }
        [$host, $ruta, $query] = $partes;

        if ($host === "vimeo.com" && preg_match('#^/(\d{1,12})(?:/([0-9a-f]{6,20}))?/?$#', $ruta, $m)) {
            return [$m[1], $m[2] ?? ""];
        }
        if ($host === "player.vimeo.com" && preg_match('#^/video/(\d{1,12})/?$#', $ruta, $m)) {
            parse_str($query, $q);
            $hash = is_string($q["h"] ?? null) && preg_match('/^[0-9a-f]{6,20}$/', $q["h"]) ? $q["h"] : "";
            return [$m[1], $hash];
        }
        return null;
    }

    /** [host sin "www.", ruta, query] de un enlace http(s), o null. */
    private static function partes(string $url): ?array
    {
        if (!preg_match('#^https?://#i', $url)) {
            $url = "https://" . $url;   // aceptar "youtu.be/abc" sin https://
        }
        $p = parse_url($url);
        if (!$p || empty($p["host"]) || !in_array(strtolower($p["scheme"] ?? ""), ["http", "https"], true)) {
            return null;
        }
        $host = strtolower($p["host"]);
        if (strpos($host, "www.") === 0) {
            $host = substr($host, 4);
        }
        return [$host, $p["path"] ?? "/", $p["query"] ?? ""];
    }
}
