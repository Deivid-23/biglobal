<?php
/**
 * Funciones pequeñas que usan las vistas del estudiante.
 */

/**
 * Adivina el idioma de un curso por su nombre para darle un color.
 * (La tabla cursos todavía no tiene columna idioma_id; cuando exista,
 * esta función debe leer ese dato en vez de adivinar.)
 */
function idiomaDeCurso(string $nombre): array
{
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
