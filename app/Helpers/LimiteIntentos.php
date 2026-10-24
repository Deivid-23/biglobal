<?php

/**
 * LimiteIntentos
 *
 * Frena a quien prueba contraseñas o manda formularios en masa.
 * A diferencia del contador viejo (que vivía en la sesión y se
 * "reiniciaba" con solo borrar la cookie), este guarda los conteos
 * en archivos temporales del servidor, uno por clave.
 *
 * Claves de ejemplo (siempre se guardan como huella SHA-256):
 *   "login:correo:ana@correo.com"   → intentos fallidos de esa cuenta
 *   "login:ip:1.2.3.4"              → intentos fallidos desde esa IP
 *   "registro:global"               → registros en la última hora
 *
 * No necesita tablas nuevas. Si el servidor se reinicia, los conteos
 * empiezan de cero (aceptable: los bloqueos son de minutos).
 */
class LimiteIntentos
{
    /** Carpeta donde viven los conteos. */
    private static function carpeta(): string
    {
        $dir = rtrim(sys_get_temp_dir(), "/\\") . DIRECTORY_SEPARATOR . "biglobal_limites";
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return $dir;
    }

    private static function ruta(string $clave): string
    {
        return self::carpeta() . DIRECTORY_SEPARATOR . hash("sha256", $clave) . ".json";
    }

    /**
     * Lee y modifica el registro de una clave con el archivo bloqueado,
     * para que dos peticiones al mismo tiempo no se pisen.
     * $cambiar recibe el arreglo actual y devuelve [nuevoArreglo, resultado].
     */
    private static function conBloqueo(string $clave, callable $cambiar)
    {
        $archivo = @fopen(self::ruta($clave), "c+");
        if ($archivo === false) {
            // Si no se puede escribir, no bloqueamos a nadie (mejor que tumbar el login)
            error_log("[BiGlobal][LimiteIntentos] No se pudo abrir el archivo de conteo.");
            [, $resultado] = $cambiar(["eventos" => [], "fallos" => 0, "hasta" => 0]);
            return $resultado;
        }

        flock($archivo, LOCK_EX);
        $contenido = stream_get_contents($archivo);
        $datos = json_decode((string) $contenido, true);
        if (!is_array($datos)) {
            $datos = [];
        }
        $datos += ["eventos" => [], "fallos" => 0, "hasta" => 0];

        [$nuevos, $resultado] = $cambiar($datos);

        ftruncate($archivo, 0);
        rewind($archivo);
        fwrite($archivo, json_encode($nuevos));
        fflush($archivo);
        flock($archivo, LOCK_UN);
        fclose($archivo);

        return $resultado;
    }

    /* =========================================================
       BLOQUEO POR FALLOS (login)
    ========================================================= */

    /** Segundos que faltan para poder volver a intentar (0 = libre). */
    public static function espera(string $clave): int
    {
        $ruta = self::ruta($clave);
        if (!is_file($ruta)) {
            return 0;
        }
        $datos = json_decode((string) @file_get_contents($ruta), true);
        $hasta = (int) ($datos["hasta"] ?? 0);
        return max(0, $hasta - time());
    }

    /** Suma un fallo; al llegar a $maximo queda bloqueada $bloqueoSegundos. */
    public static function fallo(string $clave, int $maximo, int $bloqueoSegundos): void
    {
        self::conBloqueo($clave, function (array $d) use ($maximo, $bloqueoSegundos) {
            $d["fallos"] = (int) $d["fallos"] + 1;
            if ($d["fallos"] >= $maximo) {
                $d["hasta"] = time() + $bloqueoSegundos;
                $d["fallos"] = 0;
            }
            return [$d, null];
        });
    }

    /** Borra los fallos (después de un acceso correcto). */
    public static function limpiar(string $clave): void
    {
        @unlink(self::ruta($clave));
    }

    /* =========================================================
       TOPE POR VENTANA DE TIEMPO (registro, recuperación)
    ========================================================= */

    /**
     * ¿Se permite una acción más? Cuenta las acciones de los últimos
     * $ventanaSegundos; si no pasa de $maximo, la registra y devuelve true.
     */
    public static function permitir(string $clave, int $maximo, int $ventanaSegundos): bool
    {
        return self::conBloqueo($clave, function (array $d) use ($maximo, $ventanaSegundos) {
            $desde = time() - $ventanaSegundos;
            $d["eventos"] = array_values(array_filter(
                (array) $d["eventos"],
                fn($t) => (int) $t > $desde
            ));

            if (count($d["eventos"]) >= $maximo) {
                return [$d, false];
            }

            $d["eventos"][] = time();
            return [$d, true];
        });
    }

    /* =========================================================
       IP DEL VISITANTE
    ========================================================= */

    /**
     * IP del visitante. En Render todo pasa por Cloudflare, que pone la IP
     * real en CF-Connecting-IP y REEMPLAZA la que mande el cliente.
     * X-Forwarded-For llega así: "cliente, Cloudflare, red interna de Render",
     * pero lo que va antes de las dos últimas lo puede inventar quien hace la
     * petición; por eso solo se usa si falta CF-Connecting-IP, y contando
     * desde el final (la que agregó Cloudflare). En local queda REMOTE_ADDR.
     * La IP es una capa extra: las protecciones principales van por cuenta
     * (correo) y con topes globales.
     */
    public static function ipCliente(): string
    {
        $cloudflare = trim((string) ($_SERVER["HTTP_CF_CONNECTING_IP"] ?? ""));
        if ($cloudflare !== "" && filter_var($cloudflare, FILTER_VALIDATE_IP)) {
            return $cloudflare;
        }
        $reenviada = array_map("trim", explode(",", (string) ($_SERVER["HTTP_X_FORWARDED_FOR"] ?? "")));
        if (count($reenviada) >= 3 && filter_var($reenviada[count($reenviada) - 3], FILTER_VALIDATE_IP)) {
            return $reenviada[count($reenviada) - 3];
        }
        return (string) ($_SERVER["REMOTE_ADDR"] ?? "desconocida");
    }
}
