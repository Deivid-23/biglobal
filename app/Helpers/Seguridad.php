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
    /** Piso de la contraseña: el super admin puede subirlo, nunca bajarlo de aquí. */
    public const MIN_PASSWORD = 8;

    /** Techo para el campo de configuración. */
    public const MAX_PASSWORD = 64;

    /**
     * Revisa la contraseña. Devuelve null si cumple, o un mensaje
     * en español explicando qué le falta.
     */
    public static function validarPassword(string $password): ?string
    {
        $minimo = self::minimoPassword();

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

    /** Mínimo vigente: lo que diga la configuración, pero nunca menos de MIN_PASSWORD. */
    public static function minimoPassword(): int
    {
        return min(self::MAX_PASSWORD, max(self::MIN_PASSWORD, Config::getInt("seg_min_password", self::MIN_PASSWORD)));
    }

    /** Reglas en formato simple para mostrarlas en el formulario. */
    public static function reglasPassword(): array
    {
        return [
            "minimo"    => self::minimoPassword(),
            "mayuscula" => Config::getBool("seg_requiere_mayuscula"),
            "numero"    => Config::getBool("seg_requiere_numero"),
        ];
    }
}
