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
    public static function menu(string $rol, int $solicitudesPendientes = 0): array
    {
        if ($rol === "docente") {
            return [
                "Mi enseñanza" => [
                    ["inicio",      "Inicio",      "fa-house",      "../profesor/index.php"],
                    ["cursos",      "Mis cursos",  "fa-book",       "../profesor/index.php?page=cursos"],
                    ["lecciones",   "Lecciones",   "fa-book-open",  "../profesor/index.php?page=lecciones"],
                    ["actividades", "Actividades", "fa-list-check", "../profesor/index.php?page=actividades"],
                ],
            ];
        }

        $menu = [];

        if ($rol === "superadmin") {
            $menu["Super administración"] = [
                ["super-inicio",  "Resumen del sistema", "fa-gauge-high",     "../superadmin/index.php"],
                ["super-cuentas", "Cuentas y roles",     "fa-user-shield",    "../superadmin/index.php?page=cuentas"],
                ["super-config",  "Configuración",       "fa-sliders",        "../superadmin/index.php?page=configuracion"],
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
            ["solicitudes", "Solicitudes", "fa-user-clock",  "../admin/index.php?page=solicitudes", $solicitudesPendientes],
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
        $menuPanel    = $opciones["menu"] ?? self::menu($usuarioPanel["rol"], (int) ($opciones["pendientes"] ?? 0));
        $conGraficos  = !empty($opciones["graficos"]);
        $perfilUrl    = $usuarioPanel["rol"] === "docente" ? "../profesor/index.php?page=perfil" : "../admin/index.php?page=perfil";

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

/** Fecha corta en español: 18 oct 2026 */
function fecha_panel(?string $fecha): string
{
    if (!$fecha) return "—";
    $meses = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
    $t = strtotime($fecha);
    return $t ? date("j", $t) . " " . $meses[(int) date("n", $t) - 1] . " " . date("Y", $t) : "—";
}
