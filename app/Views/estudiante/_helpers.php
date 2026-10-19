<?php
/**
 * Funciones pequeñas que usan las vistas del estudiante.
 */

/**
 * Etiqueta de idioma de un curso (EN, ES, LSC…) y su color.
 * Usa el idioma guardado en el curso (cursos.idioma_id). Si el curso
 * todavía no tiene idioma, lo adivina por el nombre.
 */
function idiomaDeCurso(string $nombre, ?string $codigo = null): array
{
    if ($codigo !== null && $codigo !== "") {
        $codigo = mb_strtoupper($codigo);
        $clases = ["EN" => "idioma--en", "ES" => "idioma--es", "LSC" => "idioma--lsc"];
        return ["codigo" => $codigo, "clase" => $clases[$codigo] ?? "idioma--otro"];
    }

    $n = mb_strtolower($nombre);

    if (str_contains($n, "señas") || str_contains($n, "senas") || str_contains($n, "lsc")) {
        return ["codigo" => "LSC", "clase" => "idioma--lsc"];
    }
    if (str_contains($n, "ingl") || str_contains($n, "english")) {
        return ["codigo" => "EN", "clase" => "idioma--en"];
    }
    if (str_contains($n, "españ") || str_contains($n, "espan") || str_contains($n, "spanish")) {
        return ["codigo" => "ES", "clase" => "idioma--es"];
    }
    if (str_contains($n, "franc") || str_contains($n, "french")) {
        return ["codigo" => "FR", "clase" => "idioma--otro"];
    }
    if (str_contains($n, "portug")) {
        return ["codigo" => "PT", "clase" => "idioma--otro"];
    }

    return ["codigo" => mb_strtoupper(mb_substr($nombre, 0, 2)) ?: "BG", "clase" => "idioma--otro"];
}

/** Porcentaje entero seguro (sin dividir entre cero). */
function porcentaje(int $hechas, int $total): int
{
    return $total > 0 ? (int) min(100, round(($hechas / $total) * 100)) : 0;
}

/** "1 lección" / "3 lecciones" */
function plural(int $n, string $singular, string $plural): string
{
    return $n . " " . ($n === 1 ? $singular : $plural);
}

/** Fecha corta en español: 18 oct 2026 */
function fechaCorta(?string $fecha): string
{
    if (!$fecha) return "";
    $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    $t = strtotime($fecha);
    return $t ? date("j", $t) . " " . $meses[(int) date("n", $t) - 1] . " " . date("Y", $t) : "";
}
