<?php

/**
 * ClaseModel
 *
 * Clases en vivo (tablas clases_vivo y clase_inscripciones).
 * Lo usan el docente (src/profesor), el admin (AdminController) y el
 * estudiante (EstudianteController). Todas las reglas viven aquí:
 *
 * - Cupo máximo 30 por clase. Lleno -> lista de espera por orden de llegada.
 * - Si un inscrito se retira, entra el primero de la espera que todavía
 *   tenga permiso por su plan, y se le avisa (campana + correo).
 * - Premium: todas las clases. Gratuito: solo las "abiertas para todos"
 *   (Plan::puedeUnirseAClase). Se revisa al inscribirse Y al entrar.
 * - Solo el admin marca una clase como gratuita para todos (marcarAbierta).
 *   El docente crea y edita sus clases, pero no cambia esa marca.
 * - El enlace nunca va en la página: "Entrar" pasa por el servidor, que
 *   redirige solo si está inscrito y faltan 15 minutos o menos.
 * - Las horas se comparan en SQL con NOW() (la conexión usa hora de Colombia).
 */
class ClaseModel
{
    public const CUPO_MAX = 30;
    public const MINUTOS_ANTES = 15;
    public const DURACIONES = [30, 45, 60, 90, 120];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE() AND table_name IN ('clases_vivo', 'clase_inscripciones')
                ")->fetchColumn() === 2;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       VALIDAR EL FORMULARIO DEL DOCENTE
    ========================================================= */

    /** @return array|string */
    public static function datosClase(PDO $c, array $post, int $inscritosActuales = 0)
    {
        $titulo = trim((string) ($post["titulo"] ?? ""));
        if ($titulo === "" || mb_strlen($titulo) > 150) {
            return "El título es obligatorio (máximo 150 caracteres).";
        }

        $inicio = DateTimeImmutable::createFromFormat("Y-m-d\TH:i", (string) ($post["inicio"] ?? ""), new DateTimeZone("America/Bogota"));
        if (!$inicio) {
            return "Elige la fecha y la hora de inicio.";
        }

        $duracion = (int) ($post["duracion_min"] ?? 60);
        if (!in_array($duracion, self::DURACIONES, true)) {
            return "Elige una duración de la lista.";
        }

        $cupo = (int) ($post["cupo"] ?? self::CUPO_MAX);
        if ($cupo < 1 || $cupo > self::CUPO_MAX) {
            return "El cupo va de 1 a " . self::CUPO_MAX . " estudiantes.";
        }
        if ($cupo < $inscritosActuales) {
            return "Ya hay {$inscritosActuales} inscritos: el cupo no puede ser menor.";
        }

        $enlace = trim((string) ($post["enlace"] ?? ""));
        $partes = parse_url($enlace);
        if ($enlace === "" || mb_strlen($enlace) > 500 || !$partes || strtolower($partes["scheme"] ?? "") !== "https" || empty($partes["host"])) {
            return "El enlace de la clase debe empezar por https:// (Google Meet, Zoom, Teams...).";
        }

        [$idiomaId, $errorIdioma] = ContenidoModel::idiomaElegido($c, $post["idioma_id"] ?? "");
        if ($errorIdioma) {
            return $errorIdioma;
        }

        return [
            "titulo"       => $titulo,
            "descripcion"  => trim((string) ($post["descripcion"] ?? "")),
            "inicio"       => $inicio->format("Y-m-d H:i:00"),
            "duracion_min" => $duracion,
            "enlace"       => $enlace,
            "cupo"         => $cupo,
            "idioma_id"    => $idiomaId,
        ];
    }

    /** ¿La fecha ya pasó? (se pregunta a la BD para usar su reloj) */
    public static function yaPaso(PDO $c, string $fecha): bool
    {
        $stmt = $c->prepare("SELECT ? <= NOW()");
        $stmt->execute([$fecha]);
        return (bool) $stmt->fetchColumn();
    }

    /* =========================================================
       DOCENTE
    ========================================================= */

    public static function crear(PDO $c, int $docenteId, array $d): int
    {
        $c->prepare("
            INSERT INTO clases_vivo (docente_id, idioma_id, titulo, descripcion, inicio, duracion_min, enlace, cupo, abierta_para_todos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)
        ")->execute([$docenteId, $d["idioma_id"], $d["titulo"], $d["descripcion"], $d["inicio"], $d["duracion_min"], $d["enlace"], $d["cupo"]]);
        return (int) $c->lastInsertId();
    }

    /** Guarda los cambios y, si subió el cupo, hace entrar a la lista de espera. Devuelve los avisos. */
    public static function editar(PDO $c, int $claseId, array $d): array
    {
        $c->beginTransaction();
        try {
            $c->prepare("
                UPDATE clases_vivo SET idioma_id = ?, titulo = ?, descripcion = ?, inicio = ?, duracion_min = ?, enlace = ?, cupo = ?
                WHERE id = ?
            ")->execute([$d["idioma_id"], $d["titulo"], $d["descripcion"], $d["inicio"], $d["duracion_min"], $d["enlace"], $d["cupo"], $claseId]);
            $clase = self::bloquear($c, $claseId);
            $avisos = self::promover($c, $clase);
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        self::avisar($avisos);
        return $avisos;
    }

    /** La clase si es de este docente (o null). */
    public static function deDocente(PDO $c, int $claseId, int $docenteId): ?array
    {
        $stmt = $c->prepare("SELECT *, (inicio + INTERVAL duracion_min MINUTE <= NOW()) AS terminada FROM clases_vivo WHERE id = ? AND docente_id = ? LIMIT 1");
        $stmt->execute([$claseId, $docenteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function porId(PDO $c, int $claseId): ?array
    {
        $stmt = $c->prepare("SELECT *, (inicio + INTERVAL duracion_min MINUTE <= NOW()) AS terminada FROM clases_vivo WHERE id = ? LIMIT 1");
        $stmt->execute([$claseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Clases con conteos. $docenteId null = todas (admin). */
    public static function listado(PDO $c, ?int $docenteId = null, bool $soloProximas = false): array
    {
        $donde = [];
        $params = [];
        if ($docenteId !== null) {
            $donde[] = "cv.docente_id = ?";
            $params[] = $docenteId;
        }
        if ($soloProximas) {
            $donde[] = "cv.inicio + INTERVAL cv.duracion_min MINUTE > NOW()";
        }
        $stmt = $c->prepare("
            SELECT cv.*, i.codigo AS idioma_codigo, i.nombre AS idioma_nombre,
                   CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')) AS docente,
                   (SELECT COUNT(*) FROM clase_inscripciones ci WHERE ci.clase_id = cv.id AND ci.estado = 'inscrito') AS inscritos,
                   (SELECT COUNT(*) FROM clase_inscripciones ci WHERE ci.clase_id = cv.id AND ci.estado = 'espera') AS en_espera,
                   (cv.inicio + INTERVAL cv.duracion_min MINUTE <= NOW()) AS terminada
            FROM clases_vivo cv
            INNER JOIN usuarios u ON u.id = cv.docente_id
            LEFT JOIN idiomas i ON i.id = cv.idioma_id
            " . ($donde ? "WHERE " . implode(" AND ", $donde) : "") . "
            ORDER BY terminada ASC, cv.inicio ASC
            LIMIT 300
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Inscritos y lista de espera de una clase (para el docente). */
    public static function participantes(PDO $c, int $claseId): array
    {
        $stmt = $c->prepare("
            SELECT ci.estado, ci.creada_en, u.id, u.nombre, u.apellido, u.correo
            FROM clase_inscripciones ci INNER JOIN usuarios u ON u.id = ci.usuario_id
            WHERE ci.clase_id = ? AND ci.estado IN ('inscrito', 'espera')
            ORDER BY FIELD(ci.estado, 'inscrito', 'espera'), ci.id
        ");
        $stmt->execute([$claseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cancela la clase y avisa a inscritos y lista de espera. */
    public static function cancelar(PDO $c, int $claseId, string $motivo): int
    {
        $clase = self::porId($c, $claseId);
        if (!$clase || $clase["estado"] === "cancelada") {
            return 0;
        }
        $c->prepare("UPDATE clases_vivo SET estado = 'cancelada', motivo_cancelacion = ? WHERE id = ?")
          ->execute([mb_substr(trim($motivo), 0, 255) ?: null, $claseId]);

        $stmt = $c->prepare("SELECT usuario_id FROM clase_inscripciones WHERE clase_id = ? AND estado IN ('inscrito', 'espera')");
        $stmt->execute([$claseId]);
        $usuarios = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));

        $texto = "La clase «{$clase["titulo"]}» del " . self::fechaHora($clase["inicio"]) . " se canceló."
            . (trim($motivo) !== "" ? " Motivo: " . trim($motivo) . "." : "");
        foreach ($usuarios as $u) {
            Notificador::aUsuario($u, "clase_cancelada", "Clase cancelada", $texto, "estudiante/index.php?page=clases");
        }
        return count($usuarios);
    }

    /* =========================================================
       CLASES GRATUITAS PARA TODOS (invitaciones)
    ========================================================= */

    public static function invitacionesDisponibles(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'clase_invitaciones'")->fetchColumn() === 1;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /** Marca o desmarca la clase como gratuita para todos. Si la abre, invita. Devuelve cuántos invitó. */
    public static function marcarAbierta(PDO $c, int $claseId, bool $abierta): int
    {
        $c->prepare("UPDATE clases_vivo SET abierta_para_todos = ? WHERE id = ?")->execute([$abierta ? 1 : 0, $claseId]);
        return $abierta ? self::invitarGratuitos($c, $claseId) : 0;
    }

    /**
     * Invita a cada estudiante activo del plan Gratuito que todavía no esté
     * en la clase (una sola vez por persona). Campana + correo.
     */
    public static function invitarGratuitos(PDO $c, int $claseId): int
    {
        if (!self::invitacionesDisponibles($c)) {
            return 0;
        }
        $clase = self::porId($c, $claseId);
        if (!$clase || $clase["estado"] !== "programada" || (int) $clase["abierta_para_todos"] !== 1 || (int) $clase["terminada"] === 1) {
            return 0;
        }

        $stmt = $c->prepare("
            SELECT u.id FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE r.nombre = 'estudiante' AND u.activo = 1
              AND NOT EXISTS (SELECT 1 FROM clase_inscripciones ci
                              WHERE ci.clase_id = ? AND ci.usuario_id = u.id AND ci.estado IN ('inscrito', 'espera'))
        ");
        $stmt->execute([$claseId]);
        $candidatos = array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));

        // Solo el plan Gratuito: los Premium ya pueden unirse a todas
        $invitados = [];
        $insertar = $c->prepare("INSERT IGNORE INTO clase_invitaciones (clase_id, usuario_id) VALUES (?, ?)");
        foreach (array_chunk($candidatos, 300) as $bloque) {
            $premium = Plan::planesDe($c, $bloque);
            foreach ($bloque as $uid) {
                if (isset($premium[$uid])) {
                    continue;
                }
                $insertar->execute([$claseId, $uid]);
                if ($insertar->rowCount() === 1) {
                    $invitados[] = $uid;   // a quien ya se invitó antes no se le repite
                }
            }
        }

        if ($invitados) {
            Notificador::aVarios($invitados, "clase_invitacion", "Te invitamos a una clase en vivo gratis",
                "«{$clase["titulo"]}» el " . self::fechaHora($clase["inicio"]) . " ({$clase["duracion_min"]} min). "
                . "Es gratuita para todos: entra a Clases en vivo y acepta o rechaza la invitación. Cupo de {$clase["cupo"]} personas.",
                "estudiante/index.php?page=clases");
        }
        return count($invitados);
    }

    /** Invitaciones pendientes de clases que siguen abiertas y por venir. */
    public static function invitacionesPendientes(PDO $c, int $usuarioId): array
    {
        if (!self::invitacionesDisponibles($c)) {
            return [];
        }
        $stmt = $c->prepare("
            SELECT cv.id, cv.titulo, cv.inicio, cv.duracion_min, cv.cupo, CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')) AS docente,
                   (SELECT COUNT(*) FROM clase_inscripciones ci WHERE ci.clase_id = cv.id AND ci.estado = 'inscrito') AS inscritos
            FROM clase_invitaciones inv
            INNER JOIN clases_vivo cv ON cv.id = inv.clase_id
            INNER JOIN usuarios u ON u.id = cv.docente_id
            WHERE inv.usuario_id = ? AND inv.estado = 'pendiente'
              AND cv.estado = 'programada' AND cv.abierta_para_todos = 1
              AND cv.inicio + INTERVAL cv.duracion_min MINUTE > NOW()
              AND NOT EXISTS (SELECT 1 FROM clase_inscripciones ci WHERE ci.clase_id = cv.id AND ci.usuario_id = inv.usuario_id AND ci.estado IN ('inscrito', 'espera'))
            ORDER BY cv.inicio
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Aceptar (= inscribirse, con cupo y lista de espera) o rechazar una invitación. */
    public static function responderInvitacion(PDO $c, int $claseId, int $usuarioId, bool $aceptar): array
    {
        if (!self::invitacionesDisponibles($c)) {
            return ["ok" => false, "mensaje" => "Esta invitación ya no está disponible."];
        }
        $stmt = $c->prepare("SELECT estado FROM clase_invitaciones WHERE clase_id = ? AND usuario_id = ? LIMIT 1");
        $stmt->execute([$claseId, $usuarioId]);
        if ($stmt->fetchColumn() !== "pendiente") {
            return ["ok" => false, "mensaje" => "Esta invitación ya no está disponible."];
        }

        if (!$aceptar) {
            $c->prepare("UPDATE clase_invitaciones SET estado = 'rechazada', respondida_en = NOW() WHERE clase_id = ? AND usuario_id = ?")
              ->execute([$claseId, $usuarioId]);
            return ["ok" => true, "estado" => "rechazada", "mensaje" => "Listo, rechazaste la invitación. Si cambias de idea, puedes unirte desde Próximas clases mientras haya cupo."];
        }

        $r = self::inscribir($c, $claseId, $usuarioId);
        if ($r["ok"]) {
            $c->prepare("UPDATE clase_invitaciones SET estado = 'aceptada', respondida_en = NOW() WHERE clase_id = ? AND usuario_id = ?")
              ->execute([$claseId, $usuarioId]);
        }
        return $r;
    }

    /* =========================================================
       ESTUDIANTE
    ========================================================= */

    /** Próximas clases con el estado del estudiante en cada una. */
    public static function paraEstudiante(PDO $c, int $usuarioId): array
    {
        $premium = Plan::esPremium($c, $usuarioId);
        $stmt = $c->prepare("
            SELECT cv.id, cv.titulo, cv.descripcion, cv.inicio, cv.duracion_min, cv.cupo, cv.abierta_para_todos,
                   i.codigo AS idioma_codigo, i.nombre AS idioma_nombre,
                   CONCAT(u.nombre, ' ', COALESCE(u.apellido, '')) AS docente,
                   (SELECT COUNT(*) FROM clase_inscripciones ci WHERE ci.clase_id = cv.id AND ci.estado = 'inscrito') AS inscritos,
                   (SELECT ci.estado FROM clase_inscripciones ci WHERE ci.clase_id = cv.id AND ci.usuario_id = :u1) AS mi_estado,
                   (SELECT COUNT(*) FROM clase_inscripciones e1 INNER JOIN clase_inscripciones yo
                       ON yo.clase_id = e1.clase_id AND yo.usuario_id = :u2 AND yo.estado = 'espera'
                     WHERE e1.clase_id = cv.id AND e1.estado = 'espera' AND e1.id <= yo.id) AS mi_posicion,
                   (NOW() >= cv.inicio - INTERVAL " . self::MINUTOS_ANTES . " MINUTE) AS enlace_abierto
            FROM clases_vivo cv
            INNER JOIN usuarios u ON u.id = cv.docente_id
            LEFT JOIN idiomas i ON i.id = cv.idioma_id
            WHERE cv.estado = 'programada' AND cv.inicio + INTERVAL cv.duracion_min MINUTE > NOW()
            ORDER BY cv.inicio ASC
            LIMIT 100
        ");
        $stmt->execute([":u1" => $usuarioId, ":u2" => $usuarioId]);

        $clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($clases as &$cl) {
            $cl["permitida"] = $premium || (int) $cl["abierta_para_todos"] === 1;
        }
        return $clases;
    }

    /**
     * Inscribe al estudiante. Devuelve ["ok" => bool, "estado" => inscrito|espera, "posicion" => n, "mensaje" => ...].
     * Todo dentro de una transacción con la clase bloqueada: dos personas
     * no pueden quedarse con el último cupo a la vez.
     */
    public static function inscribir(PDO $c, int $claseId, int $usuarioId): array
    {
        $c->beginTransaction();
        try {
            $clase = self::bloquear($c, $claseId);
            if (!$clase || $clase["estado"] !== "programada" || (int) $clase["terminada"] === 1) {
                $c->rollBack();
                return ["ok" => false, "mensaje" => "Esta clase ya no está disponible."];
            }
            if (!Plan::puedeUnirseAClase($c, $usuarioId, (int) $clase["abierta_para_todos"] === 1)) {
                $c->rollBack();
                return ["ok" => false, "mensaje" => "Esta clase es para el plan Premium. Con Premium te unes a todas las clases en vivo y los grupos con docentes."];
            }

            $stmt = $c->prepare("SELECT id, estado FROM clase_inscripciones WHERE clase_id = ? AND usuario_id = ? FOR UPDATE");
            $stmt->execute([$claseId, $usuarioId]);
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($actual && $actual["estado"] !== "retirado") {
                $c->commit();
                return ["ok" => true, "estado" => $actual["estado"], "posicion" => self::posicion($c, $claseId, $usuarioId),
                        "mensaje" => $actual["estado"] === "inscrito" ? "Ya estás inscrito en esta clase." : "Ya estás en la lista de espera."];
            }
            if ($actual) {
                // Si se había retirado, vuelve al final de la fila (fila nueva = id nuevo)
                $c->prepare("DELETE FROM clase_inscripciones WHERE id = ?")->execute([$actual["id"]]);
            }

            $estado = self::contar($c, $claseId, "inscrito") < (int) $clase["cupo"] ? "inscrito" : "espera";
            $c->prepare("INSERT INTO clase_inscripciones (clase_id, usuario_id, estado) VALUES (?, ?, ?)")
              ->execute([$claseId, $usuarioId, $estado]);
            $posicion = $estado === "espera" ? self::posicion($c, $claseId, $usuarioId) : 0;
            $c->commit();
        } catch (Throwable $e) {
            if ($c->inTransaction()) {
                $c->rollBack();
            }
            throw $e;
        }

        return ["ok" => true, "estado" => $estado, "posicion" => $posicion,
                "mensaje" => $estado === "inscrito"
                    ? "¡Listo! Quedaste inscrito en «{$clase["titulo"]}». El enlace se habilita " . self::MINUTOS_ANTES . " minutos antes."
                    : "La clase está llena: quedaste de {$posicion} en la lista de espera. Te avisaremos si se libera un cupo."];
    }

    /** Se retira de la clase (o de la espera). Si liberó un cupo, entra el siguiente de la espera. */
    public static function retirar(PDO $c, int $claseId, int $usuarioId): bool
    {
        $c->beginTransaction();
        try {
            $clase = self::bloquear($c, $claseId);
            $stmt = $c->prepare("SELECT estado FROM clase_inscripciones WHERE clase_id = ? AND usuario_id = ? FOR UPDATE");
            $stmt->execute([$claseId, $usuarioId]);
            $estado = $stmt->fetchColumn();
            if (!$clase || !in_array($estado, ["inscrito", "espera"], true)) {
                $c->rollBack();
                return false;
            }
            $c->prepare("UPDATE clase_inscripciones SET estado = 'retirado' WHERE clase_id = ? AND usuario_id = ?")
              ->execute([$claseId, $usuarioId]);

            $avisos = ($estado === "inscrito" && $clase["estado"] === "programada" && (int) $clase["terminada"] === 0)
                ? self::promover($c, $clase) : [];
            $c->commit();
        } catch (Throwable $e) {
            if ($c->inTransaction()) {
                $c->rollBack();
            }
            throw $e;
        }
        self::avisar($avisos);
        return true;
    }

    /**
     * ¿Puede entrar ya? Devuelve ["enlace" => url] o ["mensaje" => por qué no].
     * Revisa inscripción, plan, estado de la clase y la hora (15 min antes hasta el final).
     */
    public static function enlaceParaEntrar(PDO $c, int $claseId, int $usuarioId): array
    {
        $stmt = $c->prepare("
            SELECT cv.*, ci.estado AS mi_estado,
                   (NOW() >= cv.inicio - INTERVAL " . self::MINUTOS_ANTES . " MINUTE) AS abierta,
                   (cv.inicio + INTERVAL cv.duracion_min MINUTE <= NOW()) AS terminada
            FROM clases_vivo cv
            LEFT JOIN clase_inscripciones ci ON ci.clase_id = cv.id AND ci.usuario_id = ?
            WHERE cv.id = ? LIMIT 1
        ");
        $stmt->execute([$usuarioId, $claseId]);
        $cl = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cl || $cl["estado"] !== "programada") {
            return ["mensaje" => "Esta clase no está disponible."];
        }
        if ($cl["mi_estado"] !== "inscrito") {
            return ["mensaje" => "El enlace es solo para los estudiantes inscritos."];
        }
        if (!Plan::puedeUnirseAClase($c, $usuarioId, (int) $cl["abierta_para_todos"] === 1)) {
            return ["mensaje" => "Tu plan ya no incluye esta clase. Con Premium te unes a todas las clases en vivo."];
        }
        if ((int) $cl["terminada"] === 1) {
            return ["mensaje" => "Esta clase ya terminó."];
        }
        if ((int) $cl["abierta"] !== 1) {
            return ["mensaje" => "El enlace se habilita " . self::MINUTOS_ANTES . " minutos antes de empezar (" . self::fechaHora($cl["inicio"]) . ")."];
        }
        return ["enlace" => $cl["enlace"]];
    }

    /** Mis próximas clases (inscrito o en espera), para el inicio del estudiante. */
    public static function misProximas(PDO $c, int $usuarioId, int $limite = 3): array
    {
        if (!self::disponible($c)) {
            return [];
        }
        $stmt = $c->prepare("
            SELECT cv.id, cv.titulo, cv.inicio, ci.estado
            FROM clase_inscripciones ci INNER JOIN clases_vivo cv ON cv.id = ci.clase_id
            WHERE ci.usuario_id = ? AND ci.estado IN ('inscrito', 'espera') AND cv.estado = 'programada'
              AND cv.inicio + INTERVAL cv.duracion_min MINUTE > NOW()
            ORDER BY cv.inicio LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
       AYUDANTES
    ========================================================= */

    /** Bloquea la fila de la clase hasta el fin de la transacción. */
    private static function bloquear(PDO $c, int $claseId): ?array
    {
        $stmt = $c->prepare("
            SELECT cv.*, (cv.inicio + INTERVAL cv.duracion_min MINUTE <= NOW()) AS terminada
            FROM clases_vivo cv WHERE cv.id = ? FOR UPDATE
        ");
        $stmt->execute([$claseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private static function contar(PDO $c, int $claseId, string $estado): int
    {
        $stmt = $c->prepare("SELECT COUNT(*) FROM clase_inscripciones WHERE clase_id = ? AND estado = ?");
        $stmt->execute([$claseId, $estado]);
        return (int) $stmt->fetchColumn();
    }

    /** Puesto en la lista de espera (1 = el siguiente en entrar). 0 si no está esperando. */
    private static function posicion(PDO $c, int $claseId, int $usuarioId): int
    {
        $stmt = $c->prepare("
            SELECT COUNT(*) FROM clase_inscripciones e
            INNER JOIN clase_inscripciones yo ON yo.clase_id = e.clase_id AND yo.usuario_id = ? AND yo.estado = 'espera'
            WHERE e.clase_id = ? AND e.estado = 'espera' AND e.id <= yo.id
        ");
        $stmt->execute([$usuarioId, $claseId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Mientras haya cupo, pasa de la espera a inscrito al primero que llegó
     * y que todavía tenga permiso por su plan. Devuelve los avisos a mandar
     * (se mandan DESPUÉS del commit).
     */
    private static function promover(PDO $c, array $clase): array
    {
        if ($clase["estado"] !== "programada" || (int) ($clase["terminada"] ?? 0) === 1) {
            return [];
        }
        $libres = (int) $clase["cupo"] - self::contar($c, (int) $clase["id"], "inscrito");
        if ($libres <= 0) {
            return [];
        }

        $stmt = $c->prepare("SELECT id, usuario_id FROM clase_inscripciones WHERE clase_id = ? AND estado = 'espera' ORDER BY id FOR UPDATE");
        $stmt->execute([$clase["id"]]);

        $avisos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            if ($libres <= 0) {
                break;
            }
            // Si mientras esperaba dejó de ser Premium, no entra (sigue en espera)
            if (!Plan::puedeUnirseAClase($c, (int) $fila["usuario_id"], (int) $clase["abierta_para_todos"] === 1)) {
                continue;
            }
            $c->prepare("UPDATE clase_inscripciones SET estado = 'inscrito' WHERE id = ?")->execute([$fila["id"]]);
            $libres--;
            $avisos[] = [(int) $fila["usuario_id"], "clase_cupo", "¡Se liberó un cupo para ti!",
                "Ya estás inscrito en «{$clase["titulo"]}» (" . self::fechaHora($clase["inicio"]) . "). El enlace se habilita "
                . self::MINUTOS_ANTES . " minutos antes. Si ya no puedes asistir, retírate para darle el cupo a otra persona.",
                "estudiante/index.php?page=clases"];
        }
        return $avisos;
    }

    private static function avisar(array $avisos): void
    {
        foreach ($avisos as $a) {
            Notificador::aUsuario(...$a);
        }
    }

    /** "2026-11-03 18:00:00" -> "3 nov 2026, 6:00 p. m." */
    public static function fechaHora(string $fecha): string
    {
        $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
        $t = strtotime($fecha);
        return date("j", $t) . " " . $meses[(int) date("n", $t) - 1] . " " . date("Y", $t) . ", "
            . date("g:i", $t) . (date("H", $t) < 12 ? " a. m." : " p. m.");
    }
}
