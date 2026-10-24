<?php

/**
 * Notificador
 *
 * Avisa al equipo (cuentas activas con rol admin o superadmin) cuando
 * pasa algo importante. Cada aviso:
 *   1. Se guarda en la tabla `notificaciones` (la campana del panel).
 *   2. Se manda por correo, si el SMTP está configurado.
 *
 * Cada tipo se puede apagar en Super admin → Configuración → Notificaciones.
 *
 * Uso:
 *   Notificador::avisar("nuevo_curso", "Nuevo curso: Inglés A1", "Lo creó Ana.", "admin/index.php?page=cursos");
 *
 * Un fallo aquí NUNCA debe romper lo que el usuario estaba haciendo
 * (registrarse, crear un curso...): todo error va al log y ya.
 */
class Notificador
{
    /** Tipo de aviso => clave en la tabla configuracion que lo enciende/apaga. */
    public const TIPOS = [
        "nuevo_usuario"     => "notif_nuevo_usuario",
        "docente_pendiente" => "notif_docente_pendiente",
        "nuevo_curso"       => "notif_nuevo_curso",
        "resumen_semanal"   => "notif_resumen_semanal",
        "contenido_revision"=> "notif_contenido_revision",
    ];

    /** Ícono de Font Awesome de cada tipo (para la campana). */
    public const ICONOS = [
        "nuevo_usuario"     => "fa-user-plus",
        "docente_pendiente" => "fa-user-clock",
        "nuevo_curso"       => "fa-book",
        "resumen_semanal"   => "fa-chart-simple",
        "contenido_revision"=> "fa-clipboard-check",
        "contenido_revisado"=> "fa-clipboard-check",
        "racha_recordatorio"=> "fa-fire",
        "docente_aprobado"  => "fa-user-check",
        "docente_rechazado" => "fa-user-xmark",
        "cuenta_eliminacion"=> "fa-user-slash",
        // Avisos personales (ver aUsuario)
        "pago_aprobado"     => "fa-circle-check",
        "pago_rechazado"    => "fa-triangle-exclamation",
        "premium_terminado" => "fa-crown",
        "premium_por_vencer"=> "fa-clock",
        "clase_cupo"        => "fa-video",
        "clase_cancelada"   => "fa-calendar-xmark",
        "clase_invitacion"  => "fa-gift",
        "grupo_anuncio"     => "fa-bullhorn",
        "grupo_material"    => "fa-folder-open",
        "grupo_miembro"     => "fa-user-group",
        "grupo_comentario"  => "fa-comment",
        "grupo_tarea"       => "fa-clipboard-list",
        "tarea_entrega"     => "fa-file-arrow-up",
        "tarea_calificada"  => "fa-star",
        "insignia"          => "fa-award",
        "racha_protector"   => "fa-shield-halved",
        "racha_perdida"     => "fa-fire-flame-curved",
    ];

    /**
     * Crea el aviso para todo el equipo y manda el correo.
     *
     * @param string      $enlace    Ruta dentro de src/ (sin "/" al inicio) o null.
     * @param int|null    $excluirId Quien hizo la acción no se avisa a sí mismo.
     */
    public static function avisar(string $tipo, string $titulo, string $mensaje = "", ?string $enlace = null, ?int $excluirId = null): void
    {
        if (!isset(self::TIPOS[$tipo])) {
            error_log("[BiGlobal][Notificador] Tipo desconocido: {$tipo}");
            return;
        }

        // Si la clave aún no existe en la BD, el resumen queda apagado y el resto encendido
        if (!Config::getBool(self::TIPOS[$tipo], $tipo !== "resumen_semanal")) {
            return;
        }

        try {
            $conexion = Conexion::conectar();
            $equipo = array_values(array_filter(
                self::equipo($conexion),
                fn($fila) => (int) $fila["id"] !== (int) $excluirId
            ));

            if (!$equipo) {
                return;
            }

            self::guardar($conexion, $equipo, $tipo, $titulo, $mensaje, $enlace);
            self::mandarCorreo(array_column($equipo, "correo"), $titulo, $mensaje, $enlace);

        } catch (Throwable $e) {
            error_log("[BiGlobal][Notificador] " . $e->getMessage());
        }
    }

    /**
     * Aviso para UNA persona (no para el equipo): campana + correo.
     * Ej.: resultado de un pago, Premium por vencer.
     *
     * El correo depende de Notificador::CANAL y de la preferencia del usuario.
     * @param bool $porCorreo false = solo la campana aunque el tipo lleve correo.
     * @param string|null $pie texto al final del correo (por defecto, el de la cuenta).
     * @return bool true si además salió el correo.
     */
    public static function aUsuario(int $usuarioId, string $tipo, string $titulo, string $mensaje = "", ?string $enlace = null, bool $porCorreo = true, ?string $pie = null): bool
    {
        try {
            $conexion = Conexion::conectar();
            $stmt = $conexion->prepare("SELECT " . self::columnasCorreo($conexion) . " FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$usuario) {
                return false;
            }

            self::guardar($conexion, [$usuario], $tipo, $titulo, $mensaje, $enlace);

            if ($porCorreo && self::quiereCorreo($tipo, $usuario) && Correo::estaConfigurado()) {
                self::mandarCorreo([$usuario["correo"]], $titulo, $mensaje, $enlace,
                    $pie ?? "Recibes este correo porque tienes una cuenta en BiGlobal.");
                return true;
            }
        } catch (Throwable $e) {
            error_log("[BiGlobal][Notificador][aUsuario] " . $e->getMessage());
        }
        return false;
    }

    /**
     * Por dónde sale cada aviso PERSONAL (aUsuario / aVarios):
     *   "esencial" -> campana + correo siempre (pagos, cuenta, aprobación de docente)
     *   "opcional" -> campana + correo, salvo que la persona apague los correos
     *                 no esenciales (usuarios.correos_opcionales = 0)
     *   "campana"  -> solo la campana, nunca correo
     * Un tipo que no esté aquí va solo a la campana.
     */
    public const CANAL = [
        "pago_aprobado"      => "esencial",
        "pago_rechazado"     => "esencial",
        "premium_terminado"  => "esencial",
        "docente_aprobado"   => "esencial",
        "docente_rechazado"  => "esencial",
        "cuenta_eliminacion" => "esencial",
        "premium_por_vencer" => "opcional",
        "clase_cupo"         => "opcional",
        "clase_invitacion"   => "opcional",
        "contenido_revisado" => "opcional",
        "clase_cancelada"    => "campana",
        "grupo_anuncio"      => "opcional",
        "grupo_material"     => "campana",
        "grupo_miembro"      => "campana",
        "grupo_comentario"   => "campana",
        "grupo_tarea"        => "opcional",
        "tarea_entrega"      => "campana",
        "tarea_calificada"   => "opcional",
        "insignia"           => "campana",
        "racha_protector"    => "campana",
        "racha_perdida"      => "campana",
        "racha_recordatorio" => "opcional",
    ];

    /** ¿Existe usuarios.correos_opcionales? (antes del SQL: todos reciben) */
    private static function hayPreferencia(PDO $conexion): bool
    {
        static $hay = null;
        if ($hay === null) {
            try {
                $hay = (int) $conexion->query("
                    SELECT COUNT(*) FROM information_schema.columns
                    WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'correos_opcionales'
                ")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $hay = false;
            }
        }
        return $hay;
    }

    /** Columnas para saber si a cada usuario se le puede mandar este tipo por correo. */
    private static function columnasCorreo(PDO $conexion): string
    {
        return "id, correo" . (self::hayPreferencia($conexion) ? ", correos_opcionales" : ", 1 AS correos_opcionales");
    }

    /** ¿Este usuario recibe este tipo de aviso por correo? */
    private static function quiereCorreo(string $tipo, array $usuario): bool
    {
        $canal = self::CANAL[$tipo] ?? "campana";
        if ($canal === "campana") {
            return false;
        }
        if ($canal === "opcional" && (int) ($usuario["correos_opcionales"] ?? 1) === 0) {
            return false;
        }
        return (bool) filter_var($usuario["correo"] ?? "", FILTER_VALIDATE_EMAIL);
    }

    /** ¿Recibe los correos no esenciales? (true si la columna aún no existe) */
    public static function recibeOpcionales(int $usuarioId): bool
    {
        try {
            $c = Conexion::conectar();
            if (!self::hayPreferencia($c)) {
                return true;
            }
            $stmt = $c->prepare("SELECT correos_opcionales FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([$usuarioId]);
            return (int) $stmt->fetchColumn() === 1;
        } catch (Throwable $e) {
            return true;
        }
    }

    public static function guardarPreferencia(int $usuarioId, bool $recibir): bool
    {
        try {
            $c = Conexion::conectar();
            if (!self::hayPreferencia($c)) {
                return false;
            }
            $c->prepare("UPDATE usuarios SET correos_opcionales = ? WHERE id = ?")->execute([$recibir ? 1 : 0, $usuarioId]);
            return true;
        } catch (Throwable $e) {
            error_log("[BiGlobal][Notificador][preferencia] " . $e->getMessage());
            return false;
        }
    }

    /** Correos masivos por día (el plan gratis de Brevo permite 300 en total). */
    public const TOPE_CORREOS_MASIVOS_DIA = 200;

    /**
     * Cuántos correos masivos quedan hoy. La cuenta vive en la tabla
     * configuracion como "AAAAMMDD:cantidad" (una sola clave; se reinicia cada día).
     * La comparten aVarios y los recordatorios de racha.
     */
    public static function cupoMasivo(): int
    {
        [$dia, $cuenta] = array_pad(explode(":", (string) Config::get("correos_masivos_hoy", "")), 2, "0");
        $usados = $dia === date("Ymd") ? (int) $cuenta : 0;
        return max(0, self::TOPE_CORREOS_MASIVOS_DIA - $usados);
    }

    public static function gastarCupoMasivo(int $cantidad): void
    {
        if ($cantidad > 0) {
            $usados = self::TOPE_CORREOS_MASIVOS_DIA - self::cupoMasivo();
            Config::set("correos_masivos_hoy", date("Ymd") . ":" . ($usados + $cantidad));
        }
    }

    /**
     * El mismo aviso para MUCHAS personas (ej. invitación a una clase gratuita).
     * - La campana siempre le llega a todos.
     * - El correo sale en grupos de 50 con copia oculta y con un tope diario
     *   (TOPE_CORREOS_MASIVOS_DIA) para no gastar el cupo de Brevo que necesitan
     *   los correos importantes (pagos, cuenta). Quien quede fuera del tope
     *   igual tiene el aviso en la campana.
     * Devuelve cuántos correos salieron.
     */
    public static function aVarios(array $usuarioIds, string $tipo, string $titulo, string $mensaje = "", ?string $enlace = null): int
    {
        $ids = array_values(array_unique(array_filter(array_map("intval", $usuarioIds))));
        if (!$ids) {
            return 0;
        }

        try {
            $conexion = Conexion::conectar();
            $enviados = 0;

            foreach (array_chunk($ids, 200) as $bloque) {
                $marcas = implode(",", array_fill(0, count($bloque), "?"));
                $stmt = $conexion->prepare("SELECT " . self::columnasCorreo($conexion) . " FROM usuarios WHERE id IN ($marcas)");
                $stmt->execute($bloque);
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                self::guardar($conexion, $usuarios, $tipo, $titulo, $mensaje, $enlace);

                // Tope diario de correos masivos (cupoMasivo)
                $correos = array_values(array_column(array_filter($usuarios, fn($u) => self::quiereCorreo($tipo, $u)), "correo"));
                $correos = array_slice($correos, 0, self::cupoMasivo());

                foreach (array_chunk($correos, 50) as $grupo) {
                    self::mandarCorreo($grupo, $titulo, $mensaje, $enlace, "Recibes este correo porque tienes una cuenta en BiGlobal.");
                    $enviados += count($grupo);
                }
                self::gastarCupoMasivo(count($correos));
            }
            return $enviados;
        } catch (Throwable $e) {
            error_log("[BiGlobal][Notificador][aVarios] " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Aviso de cuenta nueva (registro con correo o con Google).
     * Si es docente, además avisa que hay una solicitud por aprobar.
     */
    public static function cuentaNueva(string $nombre, string $correo, bool $esDocente, string $via): void
    {
        $quien = trim($nombre) !== "" ? trim($nombre) : $correo;

        self::avisar(
            "nuevo_usuario",
            $esDocente ? "Nuevo docente registrado" : "Nuevo estudiante registrado",
            "{$quien} ({$correo}) creó su cuenta {$via}.",
            "admin/index.php?page=usuarios"
        );

        if ($esDocente) {
            self::avisar(
                "docente_pendiente",
                "Docente pendiente de aprobar",
                "{$quien} quiere enseñar en la plataforma. Revisa su solicitud para aprobarla o rechazarla.",
                "admin/index.php?page=solicitudes"
            );
        }
    }

    /**
     * Resumen de los últimos 7 días para el equipo. Sale una vez por semana:
     * la semana ya enviada queda guardada en configuracion.resumen_ultimo_envio.
     *
     * Devuelve "enviado", "ya_enviado" o "desactivado".
     */
    public static function resumenSemanal(PDO $conexion): string
    {
        if (!Config::getBool("notif_resumen_semanal", false)) {
            return "desactivado";
        }

        $semana = date("o-\WW"); // semana ISO en hora de Colombia, ej. 2026-W43
        if (Config::get("resumen_ultimo_envio", "") === $semana) {
            return "ya_enviado";
        }

        $contar = function (string $sql) use ($conexion): int {
            try {
                return (int) $conexion->query($sql)->fetchColumn();
            } catch (PDOException $e) {
                error_log("[BiGlobal][Notificador][resumen] " . $e->getMessage());
                return 0;
            }
        };

        $estudiantes = $contar("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
                                WHERE r.nombre = 'estudiante' AND u.fecha_registro >= NOW() - INTERVAL 7 DAY");
        $docentes    = $contar("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
                                WHERE r.nombre = 'docente' AND u.fecha_registro >= NOW() - INTERVAL 7 DAY");
        $pendientes  = $contar("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id = u.rol_id
                                WHERE r.nombre = 'docente' AND u.activo = 2");
        $cursos      = $contar("SELECT COUNT(*) FROM cursos WHERE fecha_creacion >= NOW() - INTERVAL 7 DAY");
        $lecciones   = $contar("SELECT COUNT(*) FROM progreso
                                WHERE completado = 1 AND fecha_completado >= NOW() - INTERVAL 7 DAY");
        $certificados = $contar("SELECT COUNT(*) FROM certificados WHERE fecha_emision >= NOW() - INTERVAL 7 DAY");

        $lineas = [
            plural_panel($estudiantes, "estudiante nuevo", "estudiantes nuevos"),
            plural_panel($docentes, "docente nuevo", "docentes nuevos"),
            plural_panel($cursos, "curso creado", "cursos creados"),
            plural_panel($lecciones, "lección completada", "lecciones completadas"),
            plural_panel($certificados, "certificado entregado", "certificados entregados"),
        ];
        $mensaje = "En los últimos 7 días:\n· " . implode("\n· ", $lineas);
        if ($pendientes > 0) {
            $mensaje .= "\n\nHay " . plural_panel($pendientes, "docente", "docentes") . " esperando aprobación.";
        }

        self::avisar(
            "resumen_semanal",
            "Resumen de la semana",
            $mensaje,
            $pendientes > 0 ? "admin/index.php?page=solicitudes" : "admin/index.php"
        );

        Config::set("resumen_ultimo_envio", $semana);
        return "enviado";
    }

    /** Cuentas activas con rol admin o superadmin. */
    public static function equipo(PDO $conexion): array
    {
        $stmt = $conexion->query("
            SELECT u.id, u.correo
            FROM usuarios u
            JOIN roles r ON r.id = u.rol_id
            WHERE r.nombre IN ('admin', 'superadmin') AND u.activo = 1
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Una fila por persona en la tabla notificaciones (la campana). */
    private static function guardar(PDO $conexion, array $equipo, string $tipo, string $titulo, string $mensaje, ?string $enlace): void
    {
        // La hora sale de PHP (zona de Colombia) para no depender de la zona de la BD
        $ahora = date("Y-m-d H:i:s");

        try {
            $stmt = $conexion->prepare("
                INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje, enlace, creada_en)
                VALUES (:usuario, :tipo, :titulo, :mensaje, :enlace, :creada)
            ");
            foreach ($equipo as $fila) {
                $stmt->execute([
                    ":usuario" => (int) $fila["id"],
                    ":tipo"    => $tipo,
                    ":titulo"  => mb_substr($titulo, 0, 150),
                    ":mensaje" => mb_substr($mensaje, 0, 500),
                    ":enlace"  => $enlace,
                    ":creada"  => $ahora,
                ]);
            }
        } catch (PDOException $e) {
            // Lo más probable: todavía no se corrió database/notificaciones.sql
            error_log("[BiGlobal][Notificador][guardar] " . $e->getMessage());
        }
    }

    /** Correo para todo el equipo en un solo envío (copia oculta). */
    private static function mandarCorreo(array $correos, string $titulo, string $mensaje, ?string $enlace, ?string $pie = null): void
    {
        if (!Correo::estaConfigurado()) {
            return; // sin SMTP solo queda la campana
        }

        $sitio = Config::get("sitio_nombre", "BiGlobal");
        $base  = obtenerConfig()["app"]["url"] ?? "";
        $url   = ($enlace && $base !== "") ? $base . "/" . ltrim($enlace, "/") : "";
        $esc   = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, "UTF-8");

        $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#1E293B">'
            . '<p style="color:#2563EB;font-weight:bold;margin:0 0 12px">' . $esc($sitio) . '</p>'
            . '<h1 style="font-size:20px;margin:0 0 12px">' . $esc($titulo) . '</h1>'
            . ($mensaje !== "" ? '<p style="line-height:1.6;white-space:pre-line">' . $esc($mensaje) . '</p>' : "")
            . ($url !== "" ? '<p style="margin:24px 0"><a href="' . $esc($url) . '" style="background:#2563EB;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:bold">Abrir en BiGlobal</a></p>' : "")
            . '<p style="color:#64748B;font-size:12px;margin-top:32px">'
            . ($pie !== null ? $esc($pie) : 'Recibes este aviso porque eres parte del equipo de ' . $esc($sitio) . '. Se puede apagar en Configuración → Notificaciones.')
            . '</p>'
            . '</div>';

        $texto = $titulo . "\n\n" . $mensaje . ($url !== "" ? "\n\nAbrir en el panel: " . $url : "");

        Correo::enviarAVarios($correos, "[{$sitio}] {$titulo}", $html, $texto);
    }

    /* =========================================================
       CAMPANA: lo que lee y marca cada persona (solo lo suyo)
    ========================================================= */

    public static function noLeidas(int $usuarioId): int
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida_en IS NULL
            ");
            $stmt->execute([$usuarioId]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0; // tabla todavía no creada: la campana sale vacía
        }
    }

    public static function recientes(int $usuarioId, int $limite = 10): array
    {
        try {
            $stmt = Conexion::conectar()->prepare("
                SELECT id, tipo, titulo, mensaje, enlace, leida_en, creada_en
                FROM notificaciones
                WHERE usuario_id = ?
                ORDER BY creada_en DESC, id DESC
                LIMIT " . max(1, min(50, $limite))
            );
            $stmt->execute([$usuarioId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /** Marca como leída una notificación ($id) o todas (null). Solo las de esa persona. */
    public static function marcarLeidas(int $usuarioId, ?int $id = null): void
    {
        $sql = "UPDATE notificaciones SET leida_en = :ahora WHERE usuario_id = :usuario AND leida_en IS NULL";
        $parametros = [":ahora" => date("Y-m-d H:i:s"), ":usuario" => $usuarioId];

        if ($id !== null) {
            $sql .= " AND id = :id";
            $parametros[":id"] = $id;
        }

        $stmt = Conexion::conectar()->prepare($sql);
        $stmt->execute($parametros);
    }
}
