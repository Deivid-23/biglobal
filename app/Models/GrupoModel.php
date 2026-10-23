<?php

/**
 * GrupoModel
 *
 * Grupos tipo Classroom (tablas de database/grupos_classroom.sql).
 * Lo usan el docente (src/profesor), el estudiante (EstudianteController)
 * y el visor de archivos (src/grupos/archivo.php). Todas las reglas viven aquí:
 *
 * - Cada grupo es de UN docente; solo él lo edita, publica y ve el progreso.
 * - Los estudiantes entran con un código de 7 caracteres (sin 0/O, 1/I/L
 *   para que no se confundan al dictarlo). Se limita el número de intentos.
 * - Un grupo archivado no acepta estudiantes nuevos y desaparece para los
 *   estudiantes; el docente lo sigue viendo y lo puede restaurar.
 * - Tablón: solo el docente publica; docente y miembros comentan. Cada quien
 *   borra sus comentarios y el docente puede borrar cualquiera de su grupo.
 * - Material: archivo (PDF, imagen o audio, máx. 5 MB), enlace https o video
 *   de YouTube/Vimeo. Los archivos van a la tabla archivos (contexto material).
 */
class GrupoModel
{
    public const ALFABETO = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
    public const LARGO_CODIGO = 7;
    public const TIPOS_MATERIAL = ["archivo", "enlace", "video"];
    public const GRUPOS_ARCHIVO = ["pdf", "imagen", "audio"];

    public static function disponible(PDO $c): bool
    {
        static $disponible = null;
        if ($disponible === null) {
            try {
                $disponible = (int) $c->query("
                    SELECT COUNT(*) FROM information_schema.tables
                    WHERE table_schema = DATABASE()
                      AND table_name IN ('grupos', 'grupo_miembros', 'grupo_anuncios', 'anuncio_comentarios', 'archivos', 'grupo_material')
                ")->fetchColumn() === 6;
            } catch (Throwable $e) {
                $disponible = false;
            }
        }
        return $disponible;
    }

    /* =========================================================
       CÓDIGO DEL GRUPO
    ========================================================= */

    public static function generarCodigo(PDO $c): string
    {
        $existe = $c->prepare("SELECT 1 FROM grupos WHERE codigo = ? LIMIT 1");
        for ($intento = 0; $intento < 20; $intento++) {
            $codigo = "";
            for ($i = 0; $i < self::LARGO_CODIGO; $i++) {
                $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
            }
            $existe->execute([$codigo]);
            if (!$existe->fetchColumn()) {
                return $codigo;
            }
        }
        throw new RuntimeException("No se pudo generar un código de grupo único.");
    }

    /** " abc-d23 k " -> "ABCD23K" (o "" si no tiene la forma de un código). */
    public static function normalizarCodigo(string $texto): string
    {
        $codigo = strtoupper((string) preg_replace('/[\s\-]+/', "", $texto));
        $permitidos = preg_quote(self::ALFABETO, "/");
        return preg_match('/^[' . $permitidos . ']{' . self::LARGO_CODIGO . '}$/', $codigo) ? $codigo : "";
    }

    /* =========================================================
       DOCENTE: CREAR, EDITAR, ARCHIVAR
    ========================================================= */

    /** @return array|string Datos limpios o el texto del error. */
    public static function datosGrupo(PDO $c, array $post, int $docenteId)
    {
        $nombre = trim((string) ($post["nombre"] ?? ""));
        if ($nombre === "" || mb_strlen($nombre) > 120) {
            return "El nombre del grupo es obligatorio (máximo 120 caracteres).";
        }
        $descripcion = trim((string) ($post["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 1000) {
            return "La descripción puede tener máximo 1000 caracteres.";
        }

        $cursoId = (int) ($post["curso_id"] ?? 0);
        if ($cursoId > 0) {
            $stmt = $c->prepare("SELECT id FROM cursos WHERE id = ? AND instructor_id = ? LIMIT 1");
            $stmt->execute([$cursoId, $docenteId]);
            if (!$stmt->fetchColumn()) {
                return "El curso elegido no existe o no es tuyo.";
            }
        }

        return [
            "nombre"      => $nombre,
            "descripcion" => $descripcion !== "" ? $descripcion : null,
            "curso_id"    => $cursoId > 0 ? $cursoId : null,
        ];
    }

    public static function crear(PDO $c, int $docenteId, array $datos): int
    {
        $stmt = $c->prepare("
            INSERT INTO grupos (docente_id, nombre, descripcion, curso_id, codigo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$docenteId, $datos["nombre"], $datos["descripcion"], $datos["curso_id"], self::generarCodigo($c)]);
        return (int) $c->lastInsertId();
    }

    public static function editar(PDO $c, int $grupoId, int $docenteId, array $datos): bool
    {
        $stmt = $c->prepare("
            UPDATE grupos SET nombre = ?, descripcion = ?, curso_id = ?
            WHERE id = ? AND docente_id = ?
        ");
        $stmt->execute([$datos["nombre"], $datos["descripcion"], $datos["curso_id"], $grupoId, $docenteId]);
        return self::deDocente($c, $grupoId, $docenteId) !== null;
    }

    public static function archivar(PDO $c, int $grupoId, int $docenteId, bool $archivar): bool
    {
        $stmt = $c->prepare("UPDATE grupos SET archivado = ? WHERE id = ? AND docente_id = ?");
        $stmt->execute([$archivar ? 1 : 0, $grupoId, $docenteId]);
        return $stmt->rowCount() > 0;
    }

    /** El código anterior deja de servir (por si se compartió donde no debía). */
    public static function regenerarCodigo(PDO $c, int $grupoId, int $docenteId): ?string
    {
        if (!self::deDocente($c, $grupoId, $docenteId)) {
            return null;
        }
        $codigo = self::generarCodigo($c);
        $c->prepare("UPDATE grupos SET codigo = ? WHERE id = ? AND docente_id = ?")->execute([$codigo, $grupoId, $docenteId]);
        return $codigo;
    }

    /** Un grupo del docente (o null si no existe o es de otro). */
    public static function deDocente(PDO $c, int $grupoId, int $docenteId): ?array
    {
        $stmt = $c->prepare("
            SELECT g.*, cu.nombre AS curso,
                   (SELECT COUNT(*) FROM grupo_miembros m WHERE m.grupo_id = g.id) AS miembros
            FROM grupos g
            LEFT JOIN cursos cu ON cu.id = g.curso_id
            WHERE g.id = ? AND g.docente_id = ?
            LIMIT 1
        ");
        $stmt->execute([$grupoId, $docenteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Grupos del docente: primero los activos, luego los archivados. */
    public static function gruposDocente(PDO $c, int $docenteId): array
    {
        $stmt = $c->prepare("
            SELECT g.id, g.nombre, g.descripcion, g.codigo, g.archivado, g.creado, cu.nombre AS curso,
                   (SELECT COUNT(*) FROM grupo_miembros m WHERE m.grupo_id = g.id) AS miembros
            FROM grupos g
            LEFT JOIN cursos cu ON cu.id = g.curso_id
            WHERE g.docente_id = ?
            ORDER BY g.archivado ASC, g.creado DESC
        ");
        $stmt->execute([$docenteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* =========================================================
       MIEMBROS
    ========================================================= */

    /** @return array ["ok" => bool, "mensaje" => string, "grupo_id" => int] */
    public static function unirse(PDO $c, string $codigoEscrito, int $usuarioId): array
    {
        $codigo = self::normalizarCodigo($codigoEscrito);
        if ($codigo === "") {
            return ["ok" => false, "mensaje" => "El código tiene " . self::LARGO_CODIGO . " letras y números. Revísalo e inténtalo de nuevo.", "grupo_id" => 0];
        }

        $stmt = $c->prepare("SELECT id, nombre, docente_id, archivado FROM grupos WHERE codigo = ? LIMIT 1");
        $stmt->execute([$codigo]);
        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grupo || (int) $grupo["archivado"] === 1) {
            return ["ok" => false, "mensaje" => "No encontramos un grupo activo con ese código. Pídele el código a tu docente.", "grupo_id" => 0];
        }

        $grupoId = (int) $grupo["id"];
        if (self::esMiembro($c, $grupoId, $usuarioId)) {
            return ["ok" => true, "mensaje" => "Ya estabas en el grupo «" . $grupo["nombre"] . "».", "grupo_id" => $grupoId];
        }

        $c->prepare("INSERT INTO grupo_miembros (grupo_id, usuario_id) VALUES (?, ?)")->execute([$grupoId, $usuarioId]);

        Notificador::aUsuario(
            (int) $grupo["docente_id"],
            "grupo_miembro",
            "Nuevo estudiante en «" . $grupo["nombre"] . "»",
            ($_SESSION["nombre"] ?? "Un estudiante") . " entró con el código del grupo.",
            "profesor/index.php?page=grupo&grupo=" . $grupoId . "&tab=estudiantes"
        );

        return ["ok" => true, "mensaje" => "¡Listo! Ya eres parte de «" . $grupo["nombre"] . "».", "grupo_id" => $grupoId];
    }

    public static function salir(PDO $c, int $grupoId, int $usuarioId): bool
    {
        $stmt = $c->prepare("DELETE FROM grupo_miembros WHERE grupo_id = ? AND usuario_id = ?");
        $stmt->execute([$grupoId, $usuarioId]);
        return $stmt->rowCount() > 0;
    }

    public static function quitarMiembro(PDO $c, int $grupoId, int $docenteId, int $usuarioId): bool
    {
        if (!self::deDocente($c, $grupoId, $docenteId)) {
            return false;
        }
        return self::salir($c, $grupoId, $usuarioId);
    }

    public static function esMiembro(PDO $c, int $grupoId, int $usuarioId): bool
    {
        $stmt = $c->prepare("SELECT 1 FROM grupo_miembros WHERE grupo_id = ? AND usuario_id = ? LIMIT 1");
        $stmt->execute([$grupoId, $usuarioId]);
        return (bool) $stmt->fetchColumn();
    }

    /** Grupos activos del estudiante, con su docente. */
    public static function gruposEstudiante(PDO $c, int $usuarioId): array
    {
        $stmt = $c->prepare("
            SELECT g.id, g.nombre, g.descripcion, cu.nombre AS curso, m.unido_en,
                   TRIM(CONCAT(d.nombre, ' ', IF(d.apellido = 'No especificado', '', COALESCE(d.apellido, '')))) AS docente,
                   (SELECT COUNT(*) FROM grupo_miembros m2 WHERE m2.grupo_id = g.id) AS miembros,
                   (SELECT MAX(a.creado) FROM grupo_anuncios a WHERE a.grupo_id = g.id) AS ultimo_anuncio
            FROM grupo_miembros m
            INNER JOIN grupos g ON g.id = m.grupo_id AND g.archivado = 0
            INNER JOIN usuarios d ON d.id = g.docente_id
            LEFT JOIN cursos cu ON cu.id = g.curso_id
            WHERE m.usuario_id = ?
            ORDER BY m.unido_en DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Un grupo para el estudiante: solo si es miembro y no está archivado. */
    public static function paraEstudiante(PDO $c, int $grupoId, int $usuarioId): ?array
    {
        $stmt = $c->prepare("
            SELECT g.id, g.nombre, g.descripcion, g.curso_id, cu.nombre AS curso,
                   TRIM(CONCAT(d.nombre, ' ', IF(d.apellido = 'No especificado', '', COALESCE(d.apellido, '')))) AS docente,
                   (SELECT COUNT(*) FROM grupo_miembros m2 WHERE m2.grupo_id = g.id) AS miembros
            FROM grupos g
            INNER JOIN grupo_miembros m ON m.grupo_id = g.id AND m.usuario_id = ?
            INNER JOIN usuarios d ON d.id = g.docente_id
            LEFT JOIN cursos cu ON cu.id = g.curso_id
            WHERE g.id = ? AND g.archivado = 0
            LIMIT 1
        ");
        $stmt->execute([$usuarioId, $grupoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Estudiantes del grupo. Si el grupo tiene curso, trae cuántas lecciones
     * publicadas completó cada uno ("hechas") y el total del curso.
     * @return array ["total" => int|null, "miembros" => [...]]
     */
    public static function miembrosConProgreso(PDO $c, array $grupo): array
    {
        $cursoId = (int) ($grupo["curso_id"] ?? 0);
        $stmt = $c->prepare("
            SELECT u.id, u.nombre, u.apellido, u.correo, m.unido_en,
                   " . ($cursoId > 0 ? "(
                       SELECT COUNT(*) FROM progreso p
                       INNER JOIN lecciones l ON l.id = p.leccion_id
                       WHERE p.usuario_id = u.id AND p.completado = 1 AND l.curso_id = :curso AND l.estado = 1
                   )" : "NULL") . " AS hechas
            FROM grupo_miembros m
            INNER JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.grupo_id = :grupo AND u.activo <> 4
            ORDER BY u.nombre ASC, u.apellido ASC
        ");
        $parametros = [":grupo" => (int) $grupo["id"]];
        if ($cursoId > 0) {
            $parametros[":curso"] = $cursoId;
        }
        $stmt->execute($parametros);
        $miembros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = null;
        if ($cursoId > 0) {
            $t = $c->prepare("SELECT COUNT(*) FROM lecciones WHERE curso_id = ? AND estado = 1");
            $t->execute([$cursoId]);
            $total = (int) $t->fetchColumn();
        }
        return ["total" => $total, "miembros" => $miembros];
    }

    /** Ids de los estudiantes del grupo (para los avisos). */
    public static function idsMiembros(PDO $c, int $grupoId): array
    {
        $stmt = $c->prepare("SELECT usuario_id FROM grupo_miembros WHERE grupo_id = ?");
        $stmt->execute([$grupoId]);
        return array_map("intval", $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /* =========================================================
       TABLÓN: ANUNCIOS Y COMENTARIOS
    ========================================================= */

    /** Últimos anuncios del grupo con sus comentarios (en orden de llegada). */
    public static function anuncios(PDO $c, int $grupoId, int $limite = 30): array
    {
        $stmt = $c->prepare("
            SELECT a.id, a.autor_id, a.texto, a.creado, u.nombre, u.apellido
            FROM grupo_anuncios a
            INNER JOIN usuarios u ON u.id = a.autor_id
            WHERE a.grupo_id = ?
            ORDER BY a.creado DESC, a.id DESC
            LIMIT " . max(1, min(100, $limite))
        );
        $stmt->execute([$grupoId]);
        $anuncios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$anuncios) {
            return [];
        }

        $ids = array_map("intval", array_column($anuncios, "id"));
        $marcas = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $c->prepare("
            SELECT k.id, k.anuncio_id, k.autor_id, k.texto, k.creado, u.nombre, u.apellido, u.activo
            FROM anuncio_comentarios k
            INNER JOIN usuarios u ON u.id = k.autor_id
            WHERE k.anuncio_id IN ($marcas)
            ORDER BY k.creado ASC, k.id ASC
        ");
        $stmt->execute($ids);
        $porAnuncio = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $k) {
            $porAnuncio[(int) $k["anuncio_id"]][] = $k;
        }
        foreach ($anuncios as &$a) {
            $a["comentarios"] = $porAnuncio[(int) $a["id"]] ?? [];
        }
        unset($a);
        return $anuncios;
    }

    /** @return true|string */
    public static function publicarAnuncio(PDO $c, array $grupo, int $docenteId, string $texto)
    {
        $texto = trim($texto);
        if ($texto === "" || mb_strlen($texto) > 5000) {
            return "Escribe el anuncio (máximo 5000 caracteres).";
        }
        if ((int) $grupo["archivado"] === 1) {
            return "El grupo está archivado. Restáuralo para publicar.";
        }
        $c->prepare("INSERT INTO grupo_anuncios (grupo_id, autor_id, texto) VALUES (?, ?, ?)")
          ->execute([(int) $grupo["id"], $docenteId, $texto]);

        Notificador::aVarios(
            self::idsMiembros($c, (int) $grupo["id"]),
            "grupo_anuncio",
            "Nuevo anuncio en «" . $grupo["nombre"] . "»",
            mb_strimwidth($texto, 0, 160, "…"),
            "estudiante/index.php?page=grupo&id=" . (int) $grupo["id"]
        );
        return true;
    }

    public static function eliminarAnuncio(PDO $c, int $anuncioId, int $grupoId): bool
    {
        $stmt = $c->prepare("DELETE FROM grupo_anuncios WHERE id = ? AND grupo_id = ?");
        $stmt->execute([$anuncioId, $grupoId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Comentario de un miembro o del docente. El que llama ya revisó que
     * $autorId pertenece al grupo $grupoId.
     * @return true|string
     */
    public static function comentar(PDO $c, int $grupoId, int $anuncioId, int $autorId, string $texto)
    {
        $texto = trim($texto);
        if ($texto === "" || mb_strlen($texto) > 1000) {
            return "Escribe el comentario (máximo 1000 caracteres).";
        }
        $stmt = $c->prepare("
            SELECT a.id, a.autor_id, g.nombre, g.docente_id
            FROM grupo_anuncios a
            INNER JOIN grupos g ON g.id = a.grupo_id
            WHERE a.id = ? AND a.grupo_id = ? AND g.archivado = 0
            LIMIT 1
        ");
        $stmt->execute([$anuncioId, $grupoId]);
        $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$anuncio) {
            return "El anuncio ya no existe.";
        }
        $c->prepare("INSERT INTO anuncio_comentarios (anuncio_id, autor_id, texto) VALUES (?, ?, ?)")
          ->execute([$anuncioId, $autorId, $texto]);

        // Si comenta un estudiante, al docente le llega a la campana
        if ($autorId !== (int) $anuncio["docente_id"]) {
            Notificador::aUsuario(
                (int) $anuncio["docente_id"],
                "grupo_comentario",
                "Nuevo comentario en «" . $anuncio["nombre"] . "»",
                ($_SESSION["nombre"] ?? "Un estudiante") . ": " . mb_strimwidth($texto, 0, 120, "…"),
                "profesor/index.php?page=grupo&grupo=" . $grupoId . "#anuncio-" . $anuncioId
            );
        }
        return true;
    }

    /**
     * Borra un comentario del grupo. Si $soloDeAutor es un id, solo borra
     * los comentarios de esa persona (estudiante); null = el docente del grupo.
     */
    public static function borrarComentario(PDO $c, int $grupoId, int $comentarioId, ?int $soloDeAutor): bool
    {
        $sql = "
            DELETE k FROM anuncio_comentarios k
            INNER JOIN grupo_anuncios a ON a.id = k.anuncio_id
            WHERE k.id = ? AND a.grupo_id = ?";
        $parametros = [$comentarioId, $grupoId];
        if ($soloDeAutor !== null) {
            $sql .= " AND k.autor_id = ?";
            $parametros[] = $soloDeAutor;
        }
        $stmt = $c->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->rowCount() > 0;
    }

    /* =========================================================
       MATERIAL
    ========================================================= */

    /** Material del grupo (sin el contenido de los archivos). */
    public static function material(PDO $c, int $grupoId): array
    {
        $stmt = $c->prepare("
            SELECT gm.id, gm.tipo, gm.titulo, gm.descripcion, gm.url, gm.creado,
                   ar.id AS archivo_id, ar.nombre AS archivo_nombre, ar.mime AS archivo_mime, ar.tamano AS archivo_tamano
            FROM grupo_material gm
            LEFT JOIN archivos ar ON ar.id = gm.archivo_id
            WHERE gm.grupo_id = ?
            ORDER BY gm.creado DESC, gm.id DESC
        ");
        $stmt->execute([$grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Agrega material. $archivoCampo es $_FILES["archivo"] (solo para tipo archivo).
     * @return true|string
     */
    public static function agregarMaterial(PDO $c, array $grupo, int $docenteId, array $post, $archivoCampo)
    {
        if ((int) $grupo["archivado"] === 1) {
            return "El grupo está archivado. Restáuralo para agregar material.";
        }
        $tipo = (string) ($post["tipo"] ?? "");
        if (!in_array($tipo, self::TIPOS_MATERIAL, true)) {
            return "Elige el tipo de material.";
        }
        $titulo = trim((string) ($post["titulo"] ?? ""));
        if ($titulo === "" || mb_strlen($titulo) > 150) {
            return "El título es obligatorio (máximo 150 caracteres).";
        }
        $descripcion = trim((string) ($post["descripcion"] ?? ""));
        if (mb_strlen($descripcion) > 1000) {
            return "La descripción puede tener máximo 1000 caracteres.";
        }

        $url = null;
        $archivo = null;
        if ($tipo === "enlace") {
            $url = trim((string) ($post["url"] ?? ""));
            $partes = parse_url($url);
            if ($url === "" || mb_strlen($url) > 500 || !$partes || strtolower($partes["scheme"] ?? "") !== "https" || empty($partes["host"])) {
                return "El enlace debe empezar por https://";
            }
        } elseif ($tipo === "video") {
            $video = Video::normalizar((string) ($post["url"] ?? ""));
            if (!is_array($video)) {
                return is_string($video) ? $video : "Pega el enlace del video de YouTube o Vimeo.";
            }
            $url = $video["url"];
        } else {
            $archivos = Archivo::validar($archivoCampo, self::GRUPOS_ARCHIVO, 1);
            if (!is_array($archivos)) {
                return $archivos;
            }
            $archivo = $archivos[0];
        }

        $c->beginTransaction();
        try {
            $c->prepare("INSERT INTO grupo_material (grupo_id, tipo, titulo, descripcion, url) VALUES (?, ?, ?, ?, ?)")
              ->execute([(int) $grupo["id"], $tipo, $titulo, $descripcion !== "" ? $descripcion : null, $url]);
            $materialId = (int) $c->lastInsertId();

            if ($archivo) {
                $archivoId = self::guardarArchivo($c, $docenteId, "material", $materialId, $archivo);
                $c->prepare("UPDATE grupo_material SET archivo_id = ? WHERE id = ?")->execute([$archivoId, $materialId]);
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }

        Notificador::aVarios(
            self::idsMiembros($c, (int) $grupo["id"]),
            "grupo_material",
            "Nuevo material en «" . $grupo["nombre"] . "»",
            $titulo,
            "estudiante/index.php?page=grupo&id=" . (int) $grupo["id"] . "&tab=material"
        );
        return true;
    }

    public static function eliminarMaterial(PDO $c, int $materialId, int $grupoId): bool
    {
        $stmt = $c->prepare("SELECT archivo_id FROM grupo_material WHERE id = ? AND grupo_id = ? LIMIT 1");
        $stmt->execute([$materialId, $grupoId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) {
            return false;
        }
        $c->beginTransaction();
        try {
            $c->prepare("DELETE FROM grupo_material WHERE id = ?")->execute([$materialId]);
            if ($fila["archivo_id"]) {
                $c->prepare("DELETE FROM archivos WHERE id = ? AND contexto = 'material'")->execute([(int) $fila["archivo_id"]]);
            }
            $c->commit();
        } catch (Throwable $e) {
            $c->rollBack();
            throw $e;
        }
        return true;
    }

    /* =========================================================
       ARCHIVOS
    ========================================================= */

    /** Guarda un archivo ya validado con Archivo::validar() y devuelve su id. */
    public static function guardarArchivo(PDO $c, int $duenoId, string $contexto, int $contextoId, array $a): int
    {
        $stmt = $c->prepare("
            INSERT INTO archivos (dueno_id, contexto, contexto_id, nombre, mime, tamano, sha256, contenido)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bindValue(1, $duenoId, PDO::PARAM_INT);
        $stmt->bindValue(2, $contexto);
        $stmt->bindValue(3, $contextoId, PDO::PARAM_INT);
        $stmt->bindValue(4, $a["nombre"]);
        $stmt->bindValue(5, $a["mime"]);
        $stmt->bindValue(6, $a["tamano"], PDO::PARAM_INT);
        $stmt->bindValue(7, $a["sha256"]);
        $stmt->bindValue(8, $a["contenido"], PDO::PARAM_LOB);
        $stmt->execute();
        return (int) $c->lastInsertId();
    }

    /**
     * El archivo con su contenido, SOLO si la persona puede verlo:
     *   material, tarea -> el docente del grupo, sus miembros (grupo activo) o el equipo admin.
     *   entrega         -> el estudiante que la hizo, el docente del grupo o el equipo admin.
     */
    public static function archivoConPermiso(PDO $c, int $archivoId, int $usuarioId, string $rol): ?array
    {
        $stmt = $c->prepare("SELECT * FROM archivos WHERE id = ? LIMIT 1");
        $stmt->execute([$archivoId]);
        $archivo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$archivo) {
            return null;
        }

        if ($archivo["contexto"] === "material") {
            $stmt = $c->prepare("
                SELECT g.id, g.docente_id, g.archivado
                FROM grupo_material gm
                INNER JOIN grupos g ON g.id = gm.grupo_id
                WHERE gm.id = ? AND gm.archivo_id = ?
                LIMIT 1
            ");
            $stmt->execute([(int) $archivo["contexto_id"], $archivoId]);
            $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$grupo) {
                return null;
            }
            $puede = in_array($rol, ["admin", "superadmin"], true)
                || ($rol === "docente" && (int) $grupo["docente_id"] === $usuarioId)
                || ($rol === "estudiante" && (int) $grupo["archivado"] === 0 && self::esMiembro($c, (int) $grupo["id"], $usuarioId));
            return $puede ? $archivo : null;
        }

        if ($archivo["contexto"] === "tarea" || $archivo["contexto"] === "entrega") {
            $sql = $archivo["contexto"] === "tarea"
                ? "SELECT g.id, g.docente_id, g.archivado, NULL AS estudiante_id
                   FROM grupo_tareas t INNER JOIN grupos g ON g.id = t.grupo_id
                   WHERE t.id = ? LIMIT 1"
                : "SELECT g.id, g.docente_id, g.archivado, e.estudiante_id
                   FROM tarea_entregas e
                   INNER JOIN grupo_tareas t ON t.id = e.tarea_id
                   INNER JOIN grupos g ON g.id = t.grupo_id
                   WHERE e.id = ? LIMIT 1";
            $stmt = $c->prepare($sql);
            $stmt->execute([(int) $archivo["contexto_id"]]);
            $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$grupo) {
                return null;
            }
            $puede = in_array($rol, ["admin", "superadmin"], true)
                || ($rol === "docente" && (int) $grupo["docente_id"] === $usuarioId);
            if (!$puede && $rol === "estudiante") {
                // Su propia entrega la ve siempre; los adjuntos de la tarea, si sigue en el grupo activo
                $puede = $archivo["contexto"] === "entrega"
                    ? (int) $grupo["estudiante_id"] === $usuarioId
                    : (int) $grupo["archivado"] === 0 && self::esMiembro($c, (int) $grupo["id"], $usuarioId);
            }
            return $puede ? $archivo : null;
        }
        return null;
    }
}
