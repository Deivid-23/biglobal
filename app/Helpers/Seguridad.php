<?php

/**
 * Seguridad
 *
 * Reglas de contraseña que el admin define en Configuración > Seguridad
 * (tabla configuracion). Se usan en registro, cambio y recuperación
 * de contraseña para que todas las pantallas pidan lo mismo.
 */
class Seguridad
{
    /**
     * Revisa la contraseña. Devuelve null si cumple, o un mensaje
     * en español explicando qué le falta.
     */
    public static function validarPassword(string $password): ?string
    {
        $minimo = max(6, Config::getInt("seg_min_password", 6));

        if (mb_strlen($password) < $minimo) {
            return "La contraseña debe tener al menos {$minimo} caracteres.";
        }

        if (Config::getBool("seg_requiere_mayuscula") && !preg_match('/[A-ZÁÉÍÓÚÑ]/u', $password)) {
            return "La contraseña debe tener al menos una letra mayúscula.";
        }

        if (Config::getBool("seg_requiere_numero") && !preg_match('/\d/', $password)) {
            return "La contraseña debe tener al menos un número.";
        }

        return null;
    }

    /** Reglas en formato simple para mostrarlas en el formulario. */
    public static function reglasPassword(): array
    {
        return [
            "minimo"    => max(6, Config::getInt("seg_min_password", 6)),
            "mayuscula" => Config::getBool("seg_requiere_mayuscula"),
            "numero"    => Config::getBool("seg_requiere_numero"),
        ];
    }
}
