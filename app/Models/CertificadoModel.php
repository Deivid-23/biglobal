<?php

/**
 * CertificadoModel
 *
 * Verificación pública de certificados (src/certificado.php).
 * Cualquiera con el código puede comprobar que un certificado es real.
 */
class CertificadoModel
{
    /** Formato de los códigos que emite BiGlobal: BG- y 8 caracteres hexadecimales. */
    public static function codigoValido(string $codigo): bool
    {
        return (bool) preg_match('/^BG-[A-F0-9]{8}$/', $codigo);
    }

    /** Pasa "bg-1a2b3c4d " a "BG-1A2B3C4D". */
    public static function normalizar(string $codigo): string
    {
        return strtoupper(trim($codigo));
    }

    /** Datos para mostrar el certificado, o false si el código no existe. */
    public static function buscarPorCodigo(PDO $c, string $codigo)
    {
        $stmt = $c->prepare("
            SELECT ce.codigo, ce.fecha_emision,
                   u.nombre, u.apellido,
                   cu.nombre AS curso, cu.nivel,
                   i.nombre AS idioma
            FROM certificados ce
            INNER JOIN usuarios u ON u.id = ce.usuario_id
            INNER JOIN cursos cu  ON cu.id = ce.curso_id
            LEFT JOIN idiomas i   ON i.id = cu.idioma_id
            WHERE ce.codigo = ?
            LIMIT 1
        ");
        $stmt->execute([$codigo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
