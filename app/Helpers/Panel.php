<?php

/**
 * Panel
 *
 * Arma el layout común de los paneles de gestión (admin, super
 * administrador y profesor): barra lateral, barra superior con el botón
 * de tema y el contenido de cada página.
 *
 * Uso desde un controlador:
 *   Panel::render(__DIR__ . "/../Views/admin/inicio.php", [
 *       "titulo"  => "Inicio",
 *       "activa"  => "inicio",          // qué opción del menú se resalta
 *       "datos"   => [...],             // variables para la vista
 *       "graficos"=> true,              // carga Chart.js
 *   ]);
 */
class Panel
{
    /**
     * Tema guardado en la cuenta ("claro", "oscuro" o "" si no eligió).
     * Si la base todavía no tiene la columna usuarios.tema, devuelve ""
     * y el tema queda solo en el navegador (no se rompe nada).
     */
    public static function temaGuardado(): string
    {
        if (!isset($_SESSION["usuario_id"])) return "";
        try {
            $stmt = Conexion::conectar()->prepare("SELECT tema FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([(int) $_SESSION["usuario_id"]]);
            $tema = (string) $stmt->fetchColumn();
            return in_array($tema, ["claro", "oscuro"], true) ? $tema : "";
        } catch (PDOException $e) {
            return "";
        }
    }

    /**
     * Color de acento elegido por el usuario en "Mi perfil" (#RRGGBB) o "".
     * Si la columna usuarios.color_acento todavía no existe, devuelve "".
     */
    public static function acentoUsuario(): string
    {
        if (!isset($_SESSION["usuario_id"])) return "";
        try {
            $stmt = Conexion::conectar()->prepare("SELECT color_acento FROM usuarios WHERE id = ? LIMIT 1");
            $stmt->execute([(int) $_SESSION["usuario_id"]]);
            $color = (string) $stmt->fetchColumn();
            return self::esColor($color) ? strtolower($color) : "";
        } catch (PDOException $e) {
            return "";
        }
    }

    /**
     * Color por defecto del sitio (Configuración > Apariencia, lo elige el super admin).
     * Acepta los nombres de antes (blue, green...) o un #RRGGBB.
     */
    public static function acentoSitio(): string
    {
        $valor = strtolower((string) Config::get("color_acento", "blue"));
        $nombres = [
            "blue"   => "",          // "" = azul de la marca (no hace falta calcular nada)
            "green"  => "#15803d",
            "orange" => "#c2410c",
            "purple" => "#7c3aed",
            "pink"   => "#db2777",
        ];
        if (array_key_exists($valor, $nombres)) return $nombres[$valor];
        return self::esColor($valor) ? $valor : "";
    }

    public static function esColor(string $valor): bool
    {
        return (bool) preg_match("/^#[0-9a-f]{6}$/i", $valor);
    }

    /** Colores sugeridos en "Mi perfil" (todos cumplen contraste con texto blanco). */
    public static function coloresSugeridos(): array
    {
        return [
            "#2563eb" => "Azul BiGlobal",
            "#0284c7" => "Celeste",
            "#0d9488" => "Turquesa",
            "#15803d" => "Verde",
            "#c2410c" => "Naranja",
            "#e11d48" => "Rojo",
            "#db2777" => "Rosa",
            "#7c3aed" => "Morado",
        ];
    }

    /** Atributos para <html>: tema y color guardados + direcciones para guardarlos. */
    public static function atributosHtml(): string
    {
        $attrs = [
            "data-tema-usuario"   => self::temaGuardado(),
            "data-acento-usuario" => self::acentoUsuario(),
            "data-acento-sitio"   => self::acentoSitio(),
            "data-tema-url"       => "../auth/guardar_tema.php",
            "data-acento-url"     => "../auth/guardar_acento.php",
            "data-csrf"           => generarCsrfToken(),
        ];
        $html = "";
        foreach ($attrs as $nombre => $valor) {
            $html .= ' ' . $nombre . '="' . htmlspecialchars($valor, ENT_QUOTES, "UTF-8") . '"';
        }
        return $html;
    }

    /** Datos del usuario de la sesión para la barra (nombre, iniciales, rol, tema). */
    public static function usuario(PDO $conexion): array
    {
        $stmt = $conexion->prepare("
            SELECT u.nombre, u.apellido, u.correo
            FROM usuarios u
            WHERE u.id = :id
            LIMIT 1
        ");
        $stmt->execute([":id" => (int) ($_SESSION["usuario_id"] ?? 0)]);
        $fila = $stmt->fetch() ?: ["nombre" => $_SESSION["nombre"] ?? "", "apellido" => "", "correo" => ""];
        $fila["tema"] = self::temaGuardado();

        $apellido = ($fila["apellido"] ?? "") === "No especificado" ? "" : ($fila["apellido"] ?? "");
        $rol = rolActual();

        $etiquetas = [
            "superadmin" => "Super administrador",
            "admin"      => "Administrador",
            "docente"    => "Docente",
        ];

        return [
            "nombre"    => trim(($fila["nombre"] ?? "") . " " . $apellido) ?: "Usuario",
            "primer"    => explode(" ", trim($fila["nombre"] ?? "Hola"))[0] ?: "Hola",
            "correo"    => $fila["correo"] ?? "",
            "iniciales" => mb_strtoupper(mb_substr($fila["nombre"] ?? "U", 0, 1) . mb_substr($apellido, 0, 1)) ?: "U",
            "rol"       => $rol,
            "etiqueta"  => $etiquetas[$rol] ?? ucfirst($rol),
            "tema"      => in_array($fila["tema"] ?? null, ["claro", "oscuro"], true) ? $fila["tema"] : null,
        ];
    }

    /**
     * Menú lateral según el rol. Las rutas son relativas a una carpeta
     * de src/ (admin/, superadmin/ o profesor/): por eso empiezan con "../".
     */
    public static function menu(string $rol, int $solicitudesPendientes = 0, int $revisionesPendientes = 0): array
    {
        if ($rol === "docente") {
            return [
                "Mi enseñanza" => [
                    ["inicio",      "Inicio",      "fa-house",      "../profesor/index.php"],
                    ["cursos",      "Mis cursos",  "fa-book",       "../profesor/index.php?page=cursos"],
                    ["lecciones",   "Lecciones",   "fa-book-open",  "../profesor/index.php?page=lecciones"],
                    ["actividades", "Actividades", "fa-list-check", "../profesor/index.php?page=actividades"],
                    ["clases",      "Clases en vivo", "fa-video",   "../profesor/index.php?page=clases"],
                    ["grupos",      "Grupos",      "fa-people-group", "../profesor/index.php?page=grupos"],
                ],
            ];
        }

        $menu = [];

        if ($rol === "superadmin") {
            $menu["Super administración"] = [
                ["super-inicio",  "Resumen del sistema", "fa-gauge-high",     "../superadmin/index.php"],
                ["super-cuentas", "Cuentas y roles",     "fa-user-shield",    "../superadmin/index.php?page=cuentas"],
                ["super-config",  "Configuración",       "fa-sliders",        "../superadmin/index.php?page=configuracion"],
                ["super-auditoria", "Auditoría",         "fa-clipboard-list", "../superadmin/index.php?page=auditoria"],
            ];
        }

        $menu["Panel"] = [
            ["inicio", "Inicio", "fa-house", "../admin/index.php"],
        ];

        $menu["Gestión"] = [
            ["usuarios",    "Usuarios",    "fa-users",       "../admin/index.php?page=usuarios"],
            ["cursos",      "Cursos",      "fa-book",        "../admin/index.php?page=cursos"],
            ["idiomas",     "Idiomas",     "fa-language",    "../admin/index.php?page=idiomas"],
            ["lecciones",   "Lecciones",   "fa-book-open",   "../admin/index.php?page=lecciones"],
            ["actividades", "Actividades", "fa-list-check",  "../admin/index.php?page=actividades"],
            ["clases",      "Clases en vivo", "fa-video",    "../admin/index.php?page=clases"],
            ["solicitudes", "Solicitudes", "fa-user-clock",  "../admin/index.php?page=solicitudes", $solicitudesPendientes],
            ["revision",    "Revisión de contenido", "fa-clipboard-check", "../admin/index.php?page=revision", $revisionesPendientes],
            ["ruta",        "Ruta de aprendizaje", "fa-route",  "../admin/index.php?page=ruta"],
        ];

        return $menu;
    }

    /** Muestra la página completa. */
    public static function render(string $vista, array $opciones): void
    {
        $conexion = Conexion::conectar();

        $usuarioPanel = self::usuario($conexion);
        $tituloPagina = $opciones["titulo"] ?? "Panel";
        $paginaActiva = $opciones["activa"] ?? "";
        $menuPanel    = $opciones["menu"] ?? self::menu($usuarioPanel["rol"], (int) ($opciones["pendientes"] ?? 0), (int) ($opciones["revisiones"] ?? 0));
        $conGraficos  = !empty($opciones["graficos"]);
        $perfilUrl    = $usuarioPanel["rol"] === "docente" ? "../profesor/index.php?page=perfil" : "../admin/index.php?page=perfil";

        // Campana de notificaciones: admin, super admin y docente (cada quien ve solo las suyas)
        $campanaPanel = null;
        if (in_array($usuarioPanel["rol"], ["admin", "superadmin", "docente"], true)) {
            $idUsuario = (int) $_SESSION["usuario_id"];
            $campanaPanel = [
                "noLeidas" => Notificador::noLeidas($idUsuario),
                "lista"    => Notificador::recientes($idUsuario, 10),
            ];
        }

        // Mensaje de una sola vez (después de guardar algo)
        $flashPanel = $_SESSION["flash_panel"] ?? null;
        unset($_SESSION["flash_panel"]);

        extract($opciones["datos"] ?? [], EXTR_SKIP);

        require __DIR__ . "/../Views/panel/_layout_inicio.php";
        require $vista;
        require __DIR__ . "/../Views/panel/_layout_fin.php";
    }

    /** Guarda un aviso para la próxima página y redirige. */
    public static function volver(string $url, string $tipo, string $mensaje): void
    {
        $_SESSION["flash_panel"] = ["tipo" => $tipo, "mensaje" => $mensaje];
        header("Location: " . $url);
        exit;
    }

    /** Datos de un gráfico para Chart.js (se leen desde panel.js). */
    public static function grafico(string $id, string $titulo, string $subtitulo, array $etiquetas, array $valores, string $tipo = "bar"): string
    {
        $etiquetasJson = htmlspecialchars(json_encode(array_values($etiquetas), JSON_UNESCAPED_UNICODE), ENT_QUOTES, "UTF-8");
        $valoresJson   = htmlspecialchars(json_encode(array_map("intval", array_values($valores))), ENT_QUOTES, "UTF-8");
        $vacio = empty($valores) || array_sum(array_map("intval", $valores)) === 0;

        ob_start(); ?>
        <section class="pnl-tarjeta" aria-labelledby="<?= e($id) ?>-titulo">
            <div class="pnl-tarjeta__cabecera">
                <div>
                    <h2 id="<?= e($id) ?>-titulo"><?= e($titulo) ?></h2>
                    <p><?= e($subtitulo) ?></p>
                </div>
                <?php if (!$vacio): ?>
                    <label class="sr-only" for="<?= e($id) ?>-tipo">Tipo de gráfico</label>
                    <select class="pnl-selector" id="<?= e($id) ?>-tipo" data-grafico-tipo="<?= e($id) ?>">
                        <?php foreach (["bar" => "Barras", "line" => "Líneas", "doughnut" => "Dona", "pie" => "Circular"] as $valor => $texto): ?>
                            <option value="<?= $valor ?>" <?= $valor === $tipo ? "selected" : "" ?>><?= $texto ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <?php if ($vacio): ?>
                <p class="texto-suave">Todavía no hay datos para este gráfico.</p>
            <?php else: ?>
                <div class="pnl-grafico">
                    <canvas id="<?= e($id) ?>" role="img" aria-label="<?= e($titulo) ?>"
                            data-etiquetas="<?= $etiquetasJson ?>" data-valores="<?= $valoresJson ?>" data-tipo="<?= e($tipo) ?>"></canvas>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }
}

/** "1 solicitud" / "3 solicitudes" (para los textos de los paneles). */
function plural_panel(int $n, string $singular, string $plural): string
{
    return $n . " " . ($n === 1 ? $singular : $plural);
}

/** Nombre completo sin el "No especificado" que guarda el registro. */
function nombre_panel(array $fila): string
{
    $apellido = ($fila["apellido"] ?? "") === "No especificado" ? "" : ($fila["apellido"] ?? "");
    return trim(($fila["nombre"] ?? "") . " " . $apellido) ?: "Sin nombre";
}

/** Tiempo relativo para la campana: "hace 5 min", "hace 2 h", "ayer", o la fecha corta. */
function hace_panel(?string $fecha): string
{
    $t = $fecha ? strtotime($fecha) : false;
    if (!$t) return "";
    $segundos = max(0, time() - $t);
    if ($segundos < 60)     return "hace un momento";
    if ($segundos < 3600)   return "hace " . intdiv($segundos, 60) . " min";
    if ($segundos < 86400)  return "hace " . intdiv($segundos, 3600) . " h";
    if ($segundos < 172800) return "ayer";
    return fecha_panel($fecha);
}

/** Fecha corta en español: 18 oct 2026 */
function fecha_panel(?string $fecha): string
{
    if (!$fecha) return "—";
    $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    $t = strtotime($fecha);
    return $t ? date("j", $t) . " " . $meses[(int) date("n", $t) - 1] . " " . date("Y", $t) : "—";
}
