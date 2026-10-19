<?php
/* bootstrap carga: config, sesión segura, protegerRol, conexión, CSRF y Config.
   OJO: la carpeta es app/Helpers (con H mayúscula) para que funcione en Linux. */
require_once __DIR__ . "/../../app/bootstrap.php";

protegerRol("admin");
$conexion = Conexion::conectar();

/* =========================================================
   HELPERS SEGUROS
========================================================= */
function contarSeguro(PDO $c, string $sql): int {
    try { return (int) ($c->query($sql)->fetch()['t'] ?? 0); }
    catch (PDOException $e) { return 0; }
}
function consultarSeguro(PDO $c, string $sql): array {
    try { return $c->query($sql)->fetchAll(); }
    catch (PDOException $e) { return []; }
}

/* =========================================================
   POST HANDLER — CREAR REGISTROS (modal "Nuevo")
   ---------------------------------------------------------
   - Verifica el token CSRF.
   - Valida los datos antes de guardar.
   - Después de guardar redirige (patrón POST → Redirect → GET) para que
     al recargar la página no se cree el registro dos veces.
   - El campo oculto se llama "registro" (antes "tipo") porque el
     formulario de actividades también tiene un campo "tipo" (Quiz,
     Tarea...) y se pisaban: las actividades nunca se creaban.
========================================================= */
$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);

function volverAdmin(string $pagina, string $tipo, string $mensaje): void
{
    $_SESSION['flash_admin'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    header('Location: index.php?page=' . urlencode($pagina));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear') {
    verificarCsrfFormulario($_POST['csrf_token'] ?? '');

    $registro = $_POST['registro'] ?? '';
    $paginasCrear = ['usuarios', 'cursos', 'idiomas', 'lecciones', 'actividades'];
    if (!in_array($registro, $paginasCrear, true)) {
        volverAdmin('inicio', 'error', 'Tipo de registro no válido.');
    }

    try {
        switch ($registro) {

            case 'usuarios':
                $nombre   = trim($_POST['nombre']   ?? '');
                $apellido = trim($_POST['apellido'] ?? '');
                $correo   = trim($_POST['correo']   ?? '');
                $password = $_POST['password'] ?? '';
                $rolId    = (int) ($_POST['rol_id'] ?? 0);

                if ($nombre === '' || $apellido === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    volverAdmin('usuarios', 'error', 'Revisa nombre, apellido y correo.');
                }
                if (($errorPassword = Seguridad::validarPassword($password)) !== null) {
                    volverAdmin('usuarios', 'error', $errorPassword);
                }

                $stmt = $conexion->prepare("SELECT id FROM roles WHERE id = ?");
                $stmt->execute([$rolId]);
                if (!$stmt->fetch()) {
                    volverAdmin('usuarios', 'error', 'El rol seleccionado no existe.');
                }

                $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ? LIMIT 1");
                $stmt->execute([$correo]);
                if ($stmt->fetch()) {
                    volverAdmin('usuarios', 'error', 'Ya existe un usuario con ese correo.');
                }

                $stmt = $conexion->prepare("
                    INSERT INTO usuarios (nombre, apellido, correo, password, rol_id, activo, estado)
                    VALUES (?, ?, ?, ?, ?, 1, 1)
                ");
                $stmt->execute([$nombre, $apellido, $correo, password_hash($password, PASSWORD_DEFAULT), $rolId]);
                volverAdmin('usuarios', 'ok', 'Usuario creado correctamente.');

            case 'cursos':
                $nombre = trim($_POST['nombre'] ?? '');
                if ($nombre === '') {
                    volverAdmin('cursos', 'error', 'El nombre del curso es obligatorio.');
                }

                $stmt = $conexion->prepare("
                    INSERT INTO cursos (nombre, descripcion, nivel, instructor_id, estado)
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $nombre,
                    trim($_POST['descripcion'] ?? ''),
                    trim($_POST['nivel'] ?? '') ?: null,
                    (int) ($_POST['instructor_id'] ?? 0) ?: null,
                ]);
                volverAdmin('cursos', 'ok', 'Curso creado correctamente.');

            case 'idiomas':
                $nombre = trim($_POST['nombre'] ?? '');
                $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
                if ($nombre === '' || $codigo === '') {
                    volverAdmin('idiomas', 'error', 'El nombre y el código son obligatorios.');
                }

                $stmt = $conexion->prepare("SELECT id FROM idiomas WHERE nombre = ? OR codigo = ? LIMIT 1");
                $stmt->execute([$nombre, $codigo]);
                if ($stmt->fetch()) {
                    volverAdmin('idiomas', 'error', 'Ya existe un idioma con ese nombre o código.');
                }

                $stmt = $conexion->prepare("INSERT INTO idiomas (nombre, codigo, estado) VALUES (?, ?, 1)");
                $stmt->execute([$nombre, $codigo]);
                volverAdmin('idiomas', 'ok', 'Idioma creado correctamente.');

            case 'lecciones':
                $cursoId = (int) ($_POST['curso_id'] ?? 0);
                $titulo  = trim($_POST['titulo'] ?? '');
                if ($cursoId <= 0 || $titulo === '') {
                    volverAdmin('lecciones', 'error', 'El curso y el título son obligatorios.');
                }

                $stmt = $conexion->prepare("
                    INSERT INTO lecciones (curso_id, titulo, descripcion, contenido, orden, estado)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->execute([
                    $cursoId,
                    $titulo,
                    trim($_POST['descripcion'] ?? ''),
                    trim($_POST['contenido'] ?? ''),
                    max(1, (int) ($_POST['orden'] ?? 1)),
                ]);
                volverAdmin('lecciones', 'ok', 'Lección creada correctamente.');

            case 'actividades':
                $leccionId = (int) ($_POST['leccion_id'] ?? 0);
                $titulo    = trim($_POST['titulo'] ?? '');
                if ($leccionId <= 0 || $titulo === '') {
                    volverAdmin('actividades', 'error', 'La lección y el título son obligatorios.');
                }

                $stmt = $conexion->prepare("
                    INSERT INTO actividades (leccion_id, titulo, descripcion, tipo)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $leccionId,
                    $titulo,
                    trim($_POST['descripcion'] ?? ''),
                    trim($_POST['tipo'] ?? '') ?: null,
                ]);
                volverAdmin('actividades', 'ok', 'Actividad creada correctamente.');
        }
    } catch (PDOException $e) {
        // El detalle va al log; el admin ve un mensaje claro
        error_log('[BiGlobal][admin crear] ' . $e->getMessage());
        volverAdmin($registro, 'error', 'No se pudo guardar. Revisa los datos e inténtalo de nuevo.');
    }
}

/* =========================================================
   DATOS PARA EL MODAL
========================================================= */
$rolesDisponibles        = consultarSeguro($conexion, "SELECT id, nombre FROM roles ORDER BY id");
$cursosDisponibles       = consultarSeguro($conexion, "SELECT id, nombre FROM cursos ORDER BY nombre");
$leccionesDisponibles    = consultarSeguro($conexion, "SELECT id, titulo FROM lecciones ORDER BY titulo");
$instructoresDisponibles = consultarSeguro($conexion, "
    SELECT u.id, CONCAT(u.nombre, ' ', u.apellido) AS nombre
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'docente'
    ORDER BY u.nombre
");

/* =========================================================
   ESTADÍSTICAS
========================================================= */
$totalUsuarios      = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM usuarios");
$totalCursos        = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM cursos");
$totalIdiomas       = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM idiomas");
$totalLecciones     = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM lecciones");
$totalActividades   = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM actividades");
$totalInstructores  = contarSeguro($conexion, "
    SELECT COUNT(*) AS t FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'docente' AND u.activo = 1
");
$usuariosActivosHoy = contarSeguro($conexion, "
    SELECT COUNT(*) AS t FROM usuarios
    WHERE activo = 1 AND DATE(fecha_registro) = CURDATE()
");

/* =========================================================
   CONTADOR DE SOLICITUDES PENDIENTES
========================================================= */
$solicitudesPendientes = contarSeguro($conexion, "
    SELECT COUNT(*) AS t FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'docente' AND u.activo = 2
");

/* =========================================================
   DATOS PARA GRÁFICOS
========================================================= */
$cursosPorNivel = consultarSeguro($conexion, "
    SELECT COALESCE(nivel, 'Sin nivel') AS nivel, COUNT(*) AS total
    FROM cursos GROUP BY nivel ORDER BY total DESC
");
$usuariosPorRol = consultarSeguro($conexion, "
    SELECT r.nombre AS rol, COUNT(u.id) AS total
    FROM roles r LEFT JOIN usuarios u ON u.rol_id = r.id
    GROUP BY r.id, r.nombre ORDER BY total DESC
");
$idiomasActivos    = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM idiomas WHERE estado = 1");
$idiomasInactivos  = contarSeguro($conexion, "SELECT COUNT(*) AS t FROM idiomas WHERE estado = 0");
$leccionesPorCurso = consultarSeguro($conexion, "
    SELECT c.nombre AS curso, COUNT(l.id) AS total
    FROM cursos c LEFT JOIN lecciones l ON l.curso_id = c.id
    GROUP BY c.id, c.nombre ORDER BY total DESC LIMIT 5
");
$actividadesPorTipo = consultarSeguro($conexion, "
    SELECT COALESCE(tipo, 'Sin tipo') AS tipo, COUNT(*) AS total
    FROM actividades GROUP BY tipo ORDER BY total DESC
");
$cursosPorMes = consultarSeguro($conexion, "
    SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes, COUNT(*) AS total
    FROM cursos
    WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes ASC
");

/* =========================================================
   HELPER: GRÁFICO REUTILIZABLE
========================================================= */
function grafico(array $opts): string {
    $id      = htmlspecialchars($opts['id']);
    $titulo  = htmlspecialchars($opts['titulo']);
    $subtit  = htmlspecialchars($opts['subtitulo'] ?? 'Visualización de datos');
    $labels  = $opts['labels']  ?? [];
    $valores = $opts['valores'] ?? [];
    $tipo    = $opts['tipo']    ?? 'bar';

    $jsonLabels  = htmlspecialchars(json_encode($labels,  JSON_UNESCAPED_UNICODE), ENT_QUOTES);
    $jsonValores = htmlspecialchars(json_encode($valores, JSON_UNESCAPED_UNICODE), ENT_QUOTES);

    ob_start(); ?>
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <div>
                <h3 class="txt font-bold text-[15px]"><?= $titulo ?></h3>
                <p class="txt-muted text-xs"><?= $subtit ?></p>
            </div>
            <div class="flex items-center gap-2">
                <label class="txt-muted text-xs whitespace-nowrap">Ver como:</label>
                <select class="input rounded-lg px-3 py-1.5 text-xs chart-tipo" data-target="<?= $id ?>">
                    <option value="bar">Barras</option>
                    <option value="line">Líneas</option>
                    <option value="area">Área</option>
                    <option value="pie">Circular</option>
                    <option value="doughnut">Dona</option>
                    <option value="radar">Radar</option>
                </select>
            </div>
        </div>
        <div class="relative" style="height: 300px;">
            <canvas id="<?= $id ?>"
                    data-labels='<?= $jsonLabels ?>'
                    data-valores='<?= $jsonValores ?>'
                    data-tipo="<?= htmlspecialchars($tipo) ?>"></canvas>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* =========================================================
   PÁGINA ACTUAL
========================================================= */
$pagina = $_GET['page'] ?? 'inicio';
$paginasValidas = ['inicio','usuarios','cursos','idiomas','lecciones','actividades','solicitudes','reportes','errores','moderacion','configuracion'];
if (!in_array($pagina, $paginasValidas, true)) $pagina = 'inicio';

/* =========================================================
   LISTADOS
========================================================= */
$listaUsuarios = consultarSeguro($conexion, "
    SELECT u.id, u.nombre, u.apellido, u.correo, u.activo, u.fecha_registro,
           r.nombre AS rol
    FROM usuarios u
    LEFT JOIN roles r ON u.rol_id = r.id
    ORDER BY u.id DESC
");
$listaCursos      = consultarSeguro($conexion, "SELECT * FROM cursos ORDER BY id DESC");
$listaIdiomas     = consultarSeguro($conexion, "SELECT * FROM idiomas ORDER BY id DESC");
$listaLecciones   = consultarSeguro($conexion, "SELECT * FROM lecciones ORDER BY id DESC LIMIT 50");
$listaActividades = consultarSeguro($conexion, "SELECT * FROM actividades ORDER BY id DESC LIMIT 50");

$ultimosUsuarios = array_slice($listaUsuarios, 0, 4);
$ultimosCursos   = array_slice($listaCursos, 0, 4);

/* =========================================================
   SOLICITUDES DE DOCENTES
========================================================= */
$docentesPendientes = consultarSeguro($conexion, "
    SELECT u.id, u.nombre, u.apellido, u.correo, u.fecha_registro, u.activo
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'docente' AND u.activo = 2
    ORDER BY u.fecha_registro DESC
");
$docentesAprobados = consultarSeguro($conexion, "
    SELECT u.id, u.nombre, u.apellido, u.correo, u.fecha_registro, u.activo
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'docente' AND u.activo = 1
    ORDER BY u.fecha_registro DESC
");
$docentesRechazados = consultarSeguro($conexion, "
    SELECT u.id, u.nombre, u.apellido, u.correo, u.fecha_registro, u.activo
    FROM usuarios u
    INNER JOIN roles r ON u.rol_id = r.id
    WHERE LOWER(r.nombre) = 'docente' AND u.activo = 0
    ORDER BY u.fecha_registro DESC
");

/* =========================================================
   ADMIN
========================================================= */
$nombreAdmin   = $_SESSION["nombre"]   ?? "Administrador";
$apellidoAdmin = $_SESSION["apellido"] ?? "";
$nombreCompletoAdmin = trim("$nombreAdmin $apellidoAdmin") ?: "Administrador";
$avatar = strtoupper(substr($nombreAdmin, 0, 1));

/* =========================================================
   FLASH DE CONFIGURACIÓN + CSRF
========================================================= */
$flashConfig = $_SESSION["flash_config"] ?? null;
unset($_SESSION["flash_config"]);

$csrfConfig = generarCsrfToken();

/* Tab activa de configuración */
$tabsValidasConfig = ["perfil", "general", "apariencia", "notificaciones", "seguridad", "correo", "sistema"];
$tabConfig = $_GET["tab"] ?? "perfil";
if (!in_array($tabConfig, $tabsValidasConfig, true)) $tabConfig = "perfil";

/* Datos del usuario logueado (para el perfil) */
$stmtPerfil = $conexion->prepare("
    SELECT id, nombre, apellido, correo FROM usuarios WHERE id = ? LIMIT 1
");
$stmtPerfil->execute([(int) $_SESSION["usuario_id"]]);
$datosPerfil = $stmtPerfil->fetch(PDO::FETCH_ASSOC) ?: [
    "nombre"   => $nombreAdmin,
    "apellido" => $apellidoAdmin,
    "correo"   => "",
];

/* =========================================================
   MENÚ
========================================================= */
$menu = [
    'PANEL PRINCIPAL' => [
        ['page'=>'inicio','label'=>'Inicio','icon'=>'fa-house'],
    ],
    'GESTIÓN' => [
        ['page'=>'usuarios','label'=>'Usuarios y roles','icon'=>'fa-users'],
        ['page'=>'cursos','label'=>'Catálogo de cursos','icon'=>'fa-book'],
        ['page'=>'idiomas','label'=>'Idiomas','icon'=>'fa-globe'],
        ['page'=>'lecciones','label'=>'Lecciones','icon'=>'fa-book-open'],
        ['page'=>'actividades','label'=>'Actividades','icon'=>'fa-list-check'],
    ],
    'CONTROL' => [
        ['page'=>'solicitudes','label'=>'Solicitudes','icon'=>'fa-user-clock','badge'=>$solicitudesPendientes],
        ['page'=>'reportes','label'=>'Reportes','icon'=>'fa-chart-column'],
        ['page'=>'errores','label'=>'Reportes y errores','icon'=>'fa-triangle-exclamation'],
        ['page'=>'moderacion','label'=>'Moderación','icon'=>'fa-comments'],
    ],
    'SISTEMA' => [
        ['page'=>'configuracion','label'=>'Configuración','icon'=>'fa-gear'],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BiGlobal | <?= htmlspecialchars(ucfirst($pagina)) ?></title>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Tailwind ya NO se compila en el navegador: css/admin.css se genera una vez
     con el CLI (ver tools/tailwind/LEEME.md) y solo trae las clases usadas. -->
<link rel="stylesheet" href="../css/tokens.css">
<link rel="stylesheet" href="../css/componentes.css">
<link rel="stylesheet" href="css/admin.css">

<script>
  /* Tema: claro por defecto. Si el admin acaba de guardar "Apariencia",
     se usa ese valor; si no, lo último que eligió en este navegador. */
  (function () {
    const recienGuardado = <?= json_encode(
        ($flashConfig && ($flashConfig['tab'] ?? '') === 'apariencia' && ($flashConfig['tipo'] ?? '') === 'ok')
            ? Config::get('tema_default', 'light')
            : null
    ) ?>;

    try {
      if (recienGuardado === 'dark' || recienGuardado === 'light') {
        localStorage.setItem('biglobal-theme', recienGuardado);
      }
      const guardado = localStorage.getItem('biglobal-theme') || <?= json_encode(Config::get('tema_default', 'light')) ?>;
      if (guardado === 'dark') document.documentElement.classList.add('dark');
    } catch (e) { /* navegador sin localStorage: queda el tema claro */ }
  })();
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
  /* Variables del panel, conectadas a los tokens de la marca (tokens.css) */
  :root {
    --bg: var(--fondo-app);
    --panel: var(--superficie);
    --card: var(--superficie);
    --border: var(--borde);
    --text: var(--texto);
    --text-soft: #334155;
    --muted: var(--texto-suave);
  }
  /* Modo oscuro opcional: azul marino, sin morados */
  .dark {
    --bg: #0B1324;
    --panel: #0F1A30;
    --card: #13213B;
    --border: #1F3253;
    --text: #F1F5F9;
    --text-soft: #CBD5E1;
    --muted: #94A3B8;
    color-scheme: dark;
  }

  <?php
    /* Color de acento guardado en configuracion.color_acento.
       Por defecto es el azul de la marca. */
    $acentoActual = Config::get('color_acento', 'blue');

    $mapaAcentos = [
        'blue'   => ['#2563EB', '#1D4ED8', '#2563EB'],
        'green'  => ['#15803D', '#166534', '#15803D'],
        'orange' => ['#C2410C', '#9A3412', '#C2410C'],
        'purple' => ['#7C3AED', '#6D28D9', '#7C3AED'],
        'pink'   => ['#DB2777', '#BE185D', '#DB2777'],
    ];

    [$cAccent, $cAccent2, $cSidebar] = $mapaAcentos[$acentoActual] ?? $mapaAcentos['blue'];
  ?>

  :root, .dark {
    --accent: <?= $cAccent ?>;
    --accent-2: <?= $cAccent2 ?>;
    --sidebar-active: <?= $cSidebar ?>;
  }

  body {
    font-family: var(--fuente-texto);
    background: var(--bg);
    color: var(--text-soft);
  }

  h1, h2, h3, h4, .font-extrabold { font-family: var(--fuente-titulo); }

  .surface { background: var(--panel); border-color: var(--border); }
  .card    { background: var(--card);  border: 1px solid var(--border); border-radius: var(--radio-lg); box-shadow: var(--sombra-1); }
  .dark .card { box-shadow: none; }
  .txt     { color: var(--text); }
  .txt-muted { color: var(--muted); }

  /* Bienvenida: el azul claro de la página principal */
  .grad-welcome {
    background: linear-gradient(135deg, var(--azul-600) 0%, var(--azul-700) 100%);
  }

  /* Botón principal del panel (antes era un degradado) */
  .grad-accent {
    background: var(--accent) !important;
    color: #ffffff !important;
    box-shadow: 0 3px 0 var(--accent-2);
  }
  .grad-accent i, .grad-accent svg, .grad-accent span { color: #ffffff !important; }

  .grad-ring { background: conic-gradient(var(--accent) 0% 78%, var(--border) 78% 100%); }

  /* Menú lateral: activo en azul claro (como el panel del estudiante) */
  .nav-link { transition: background-color var(--dur) ease, color var(--dur) ease; color: var(--text-soft); }
  .nav-link.active {
    background: color-mix(in srgb, var(--sidebar-active) 14%, transparent);
    color: var(--sidebar-active);
    font-weight: 700;
  }
  .nav-link.active i { color: var(--sidebar-active); }
  .dark .nav-link.active { background: color-mix(in srgb, var(--sidebar-active) 28%, transparent); color: #fff; }
  .dark .nav-link.active i { color: #fff; }

  @media (hover: hover) and (pointer: fine) {
    .nav-link:not(.active):hover { background: color-mix(in srgb, var(--accent) 8%, transparent); color: var(--text); }
  }

  /* Botones: se hunden al presionar en vez de "saltar" al pasar el mouse */
  .btn { transition: transform var(--dur-rapida) var(--ease-out), box-shadow var(--dur-rapida) var(--ease-out), background-color var(--dur) ease; }
  .btn:active { transform: translateY(2px); box-shadow: 0 1px 0 var(--accent-2); }

  .input { background: var(--panel); border: 1.5px solid var(--border); color: var(--text); font-size: 16px; }
  .input:focus { outline: none; border-color: var(--accent); box-shadow: var(--foco); }

  .chip { background: color-mix(in srgb, var(--accent) 12%, transparent); color: var(--accent); }
  .dark .chip { color: #BFDBFE; }

  .divider { border-color: var(--border); }
  .spark { stroke-linecap: round; stroke-linejoin: round; fill: none; }

  table thead { color: var(--muted); }
  table tbody tr { border-color: var(--border); }
</style>
</head>

<body>

<div class="flex min-h-screen">

  <!-- ==================== SIDEBAR ==================== -->
  <aside id="sidebar"
         class="w-64 surface border-r flex flex-col fixed inset-y-0 left-0 z-40
                -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-out"
         style="border-color: var(--border);">

    <div class="h-16 flex items-center gap-2 px-5 border-b divider">
      <img src="../img/logo-Biglobal.png" class="w-8 h-8 object-contain rounded-lg" alt="BiGlobal"
           onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
      <div class="w-8 h-8 rounded-lg grad-accent hidden items-center justify-center text-white font-bold" style="display:none;">B</div>
      <span class="font-extrabold txt text-[17px]">Bi<span style="color: var(--accent)">Global</span></span>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-[13.5px]">
      <?php foreach ($menu as $seccion => $items): ?>
        <p class="text-[10px] font-bold tracking-widest txt-muted uppercase px-3 pt-4 pb-1"><?= htmlspecialchars($seccion) ?></p>
        <?php foreach ($items as $item):
          $activo = ($pagina === $item['page']) ? 'active' : '';
          $badge  = $item['badge'] ?? 0;
        ?>
          <a href="?page=<?= urlencode($item['page']) ?>" class="nav-link <?= $activo ?> flex items-center gap-3 px-3 py-2 rounded-lg">
            <i class="fa-solid <?= htmlspecialchars($item['icon']) ?> w-4 text-center"></i>
            <span class="flex-1"><?= htmlspecialchars($item['label']) ?></span>
            <?php if ($badge > 0): ?>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full text-white" style="background: #ef4444;">
                <?= (int) $badge ?>
              </span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>

   <div class="border-t divider p-3 space-y-2">
  <a href="../auth/logout.php" class="nav-link flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] text-red-500 hover:bg-red-500/10">
    <i class="fa-solid fa-arrow-right-from-bracket w-4 text-center"></i>
    <span>Cerrar sesión</span>
  </a>
  <div class="flex items-center gap-3 pt-2 border-t divider">
        <div class="w-9 h-9 rounded-full grad-accent flex items-center justify-center text-white font-bold text-sm">
          <?= htmlspecialchars($avatar) ?>
        </div>
        <div class="flex-1 leading-tight min-w-0">
          <p class="txt text-[13px] font-semibold truncate"><?= htmlspecialchars($nombreCompletoAdmin) ?></p>
          <p class="txt-muted text-[11px]">Administrador</p>
        </div>
      </div>
    </div>
  </aside>

  <!-- ==================== MAIN ==================== -->
  <div class="flex-1 lg:ml-64">

    <!-- TOPBAR -->
    <header class="h-16 surface border-b divider flex items-center gap-4 px-4 sm:px-6 sticky top-0 z-30">

      <button id="sidebarToggle" type="button"
              class="lg:hidden w-9 h-9 rounded-lg surface border divider txt-muted hover:opacity-80 flex items-center justify-center flex-shrink-0"
              aria-label="Abrir menú">
        <i class="fa-solid fa-bars text-sm"></i>
      </button>

      <div class="relative flex-1 max-w-md hidden sm:block">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 txt-muted text-sm"></i>
        <input type="text" placeholder="Buscar..."
               class="input w-full rounded-lg pl-9 pr-4 py-2 text-sm">
      </div>

      <div class="ml-auto flex items-center gap-3">

        <button type="button" data-nuevo="usuarios" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
          <i class="fa-solid fa-plus"></i>
          <span class="hidden sm:inline">Nuevo</span>
        </button>

        <button id="themeToggle" type="button"
                class="w-9 h-9 rounded-lg surface border divider txt-muted hover:opacity-80 relative"
                title="Cambiar tema">
          <i id="themeIcon" class="fa-solid fa-sun text-sm"></i>
        </button>

        <a href="?page=configuracion"
           class="w-9 h-9 rounded-lg surface border divider txt-muted hover:opacity-80 hidden sm:flex items-center justify-center"
           title="Configuración">
          <i class="fa-solid fa-gear text-sm"></i>
        </a>

        <a href="?page=errores"
           class="w-9 h-9 rounded-lg surface border divider txt-muted hover:opacity-80 relative hidden sm:flex items-center justify-center"
           title="Reportes y errores">
          <i class="fa-regular fa-bell text-sm"></i>

        </a>

        <div class="flex items-center gap-2 pl-3 border-l divider">
          <div class="w-8 h-8 rounded-full grad-accent flex items-center justify-center text-white text-xs font-bold">
            <?= htmlspecialchars($avatar) ?>
          </div>
          <span class="txt text-sm font-semibold hidden md:inline"><?= htmlspecialchars($nombreCompletoAdmin) ?></span>
        </div>
      </div>
    </header>

    <!-- CONTENIDO -->
    <main class="p-4 sm:p-6 space-y-5 max-w-[1500px]">

      <?php if ($pagina === 'inicio'): ?>

        <!-- ====================================================
             INICIO / DASHBOARD
        ===================================================== -->
        <div class="grad-welcome rounded-2xl p-6 flex flex-col lg:flex-row items-start lg:items-center gap-6 relative overflow-hidden">
          <div class="absolute -right-20 -top-24 w-80 h-80 rounded-full bg-white/10"></div>

          <div class="relative z-10 flex-1">
            <p class="text-white/80 text-xs uppercase font-bold tracking-widest mb-1">Panel administrativo</p>
            <h2 class="text-white text-2xl font-extrabold mb-1">
              Hola, <?= htmlspecialchars($nombreAdmin) ?> 👋
            </h2>
            <p class="text-white/85 text-sm mb-4">
              Gestiona usuarios, cursos, idiomas y todos los recursos de BiGlobal desde un solo lugar.
            </p>
            <a href="?page=reportes" class="inline-block bg-white/20 hover:bg-white/30 backdrop-blur text-white text-sm font-semibold px-4 py-2 rounded-lg btn">
              Ver reportes
            </a>
          </div>

          <div class="relative z-10 grid grid-cols-3 gap-3 w-full lg:w-auto">
            <div class="bg-white/15 backdrop-blur rounded-xl p-3 text-center min-w-[110px]">
              <p class="text-white text-lg font-bold"><?= $totalUsuarios ?></p>
              <p class="text-white/80 text-[10px] uppercase">Usuarios</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl p-3 text-center min-w-[110px]">
              <p class="text-white text-lg font-bold"><?= $totalCursos ?></p>
              <p class="text-white/80 text-[10px] uppercase">Cursos</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl p-3 text-center min-w-[110px]">
              <p class="text-white text-lg font-bold"><?= $totalLecciones ?></p>
              <p class="text-white/80 text-[10px] uppercase">Lecciones</p>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <a href="?page=usuarios" class="card p-5 block hover:opacity-95 transition">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg chip flex items-center justify-center"><i class="fa-solid fa-users text-sm"></i></div>
              <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-full">↗ Registrados</span>
            </div>
            <p class="txt text-2xl font-extrabold"><?= $totalUsuarios ?></p>
            <p class="txt-muted text-xs mb-3">Usuarios totales</p>
            <svg viewBox="0 0 200 40" class="w-full h-8">
              <polyline class="spark" stroke="var(--accent)" stroke-width="2" points="0,30 20,25 40,28 60,15 80,20 100,10 120,18 140,8 160,14 180,5 200,10"/>
            </svg>
          </a>

          <a href="?page=cursos" class="card p-5 block hover:opacity-95 transition">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center"><i class="fa-solid fa-book text-sm"></i></div>
              <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-full">↗ Activos</span>
            </div>
            <p class="txt text-2xl font-extrabold"><?= $totalCursos ?></p>
            <p class="txt-muted text-xs mb-3">Cursos registrados</p>
            <svg viewBox="0 0 200 40" class="w-full h-8">
              <polyline class="spark" stroke="#10b981" stroke-width="2" points="0,25 20,20 40,22 60,12 80,18 100,8 120,15 140,6 160,12 180,4 200,9"/>
            </svg>
          </a>

          <a href="?page=usuarios" class="card p-5 block hover:opacity-95 transition">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center"><i class="fa-solid fa-chalkboard-user text-sm"></i></div>
              <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-full">↗ Activos</span>
            </div>
            <p class="txt text-2xl font-extrabold"><?= $totalInstructores ?></p>
            <p class="txt-muted text-xs mb-3">Docentes</p>
            <svg viewBox="0 0 200 40" class="w-full h-8">
              <polyline class="spark" stroke="#f59e0b" stroke-width="2" points="0,28 20,22 40,25 60,18 80,22 100,12 120,20 140,10 160,16 180,6 200,12"/>
            </svg>
          </a>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg bg-cyan-500/15 text-cyan-500 flex items-center justify-center"><i class="fa-solid fa-user-check text-sm"></i></div>
              <span class="text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-full">↗ Hoy</span>
            </div>
            <p class="txt text-2xl font-extrabold"><?= $usuariosActivosHoy ?></p>
            <p class="txt-muted text-xs mb-3">Activos hoy</p>
            <svg viewBox="0 0 200 40" class="w-full h-8">
              <polyline class="spark" stroke="#06b6d4" stroke-width="2" points="0,32 20,26 40,30 60,20 80,24 100,14 120,22 140,12 160,18 180,8 200,14"/>
            </svg>
          </div>
        </div>

        <?php
          $labelsMes  = array_map(fn($r) => $r['mes'], $cursosPorMes);
          $valoresMes = array_map(fn($r) => (int)$r['total'], $cursosPorMes);

          echo grafico([
              'id'        => 'chart-inicio-mes',
              'titulo'    => 'Cursos creados por mes',
              'subtitulo' => 'Últimos 6 meses',
              'labels'    => $labelsMes,
              'valores'   => $valoresMes,
              'tipo'      => 'line',
          ]);
        ?>

        <!-- Resumen + Actividades -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="txt font-bold text-[15px]">Resumen general</h3>
                <p class="txt-muted text-xs">Estado actual de la plataforma</p>
              </div>
              <span class="txt-muted text-xs flex items-center gap-1.5">
                <i class="fa-regular fa-clock"></i> Actualizado hoy
              </span>
            </div>

            <div class="space-y-4 text-[13px]">
              <?php
                $barras = [
                  ['Usuarios',  $totalUsuarios,  'var(--accent)', 5],
                  ['Cursos',    $totalCursos,    '#10b981',       10],
                  ['Idiomas',   $totalIdiomas,   '#06b6d4',       10],
                  ['Lecciones', $totalLecciones, '#f59e0b',       3],
                ];
                foreach ($barras as $b):
                  [$lbl, $val, $color, $mult] = $b;
                  $w = min(100, max(5, $val * $mult));
              ?>
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-circle text-[8px]" style="color: <?= $color ?>"></i>
                    <span class="txt flex-1"><?= $lbl ?></span>
                    <span class="txt-muted text-xs"><?= $val ?></span>
                  </div>
                  <div class="h-1.5 rounded-full overflow-hidden" style="background: var(--border)">
                    <div class="h-full" style="width:<?= $w ?>%; background: <?= $color ?>"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="card p-5 flex flex-col">
            <h3 class="txt font-bold text-[15px]">Actividades</h3>
            <p class="txt-muted text-xs mb-4">Total registradas</p>

            <div class="flex-1 flex flex-col items-center justify-center">
              <div class="relative w-40 h-40 rounded-full grad-ring flex items-center justify-center">
                <div class="absolute inset-3 rounded-full" style="background: var(--card)"></div>
                <div class="relative text-center">
                  <p class="txt text-3xl font-extrabold"><?= $totalActividades ?></p>
                  <p class="txt-muted text-[10px] uppercase tracking-wider">actividades</p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3 w-full mt-5 text-center">
                <div>
                  <p class="txt-muted text-[10px] uppercase">Cursos</p>
                  <p class="txt text-sm font-bold"><?= $totalCursos ?></p>
                </div>
                <div>
                  <p class="txt-muted text-[10px] uppercase">Lecciones</p>
                  <p class="txt text-sm font-bold"><?= $totalLecciones ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Últimos usuarios / cursos / accesos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="txt font-bold text-[15px]">Últimos usuarios</h3>
                <p class="txt-muted text-xs">Registros recientes</p>
              </div>
              <a href="?page=usuarios" class="text-xs font-semibold" style="color:var(--accent)">Ver todos →</a>
            </div>
            <div class="space-y-3 text-[13px]">
              <?php if (empty($ultimosUsuarios)): ?>
                <p class="txt-muted text-xs text-center py-4">Sin usuarios</p>
              <?php else: foreach ($ultimosUsuarios as $u): ?>
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg grad-accent text-white flex items-center justify-center text-xs font-bold">
                    <?= htmlspecialchars(strtoupper(substr($u['nombre'] ?? 'U', 0, 1))) ?>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="txt font-semibold truncate"><?= htmlspecialchars(trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? ''))) ?></p>
                    <p class="txt-muted text-[11px] truncate"><?= htmlspecialchars($u['correo'] ?? '') ?></p>
                  </div>
                  <span class="text-[10px] font-bold px-2 py-1 rounded-full chip"><?= htmlspecialchars($u['rol'] ?? 'usuario') ?></span>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="txt font-bold text-[15px]">Últimos cursos</h3>
                <p class="txt-muted text-xs">Catálogo reciente</p>
              </div>
              <a href="?page=cursos" class="text-xs font-semibold" style="color:var(--accent)">Ver todos →</a>
            </div>
            <div class="space-y-3 text-[13px]">
              <?php if (empty($ultimosCursos)): ?>
                <p class="txt-muted text-xs text-center py-4">Sin cursos</p>
              <?php else: foreach ($ultimosCursos as $c): ?>
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center">
                    <i class="fa-solid fa-book text-sm"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="txt font-semibold truncate"><?= htmlspecialchars($c['nombre'] ?? '') ?></p>
                    <p class="txt-muted text-[11px]"><?= htmlspecialchars($c['fecha_creacion'] ?? '') ?></p>
                  </div>
                </div>
              <?php endforeach; endif; ?>
            </div>
          </div>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="txt font-bold text-[15px]">Accesos rápidos</h3>
                <p class="txt-muted text-xs">Administración</p>
              </div>
              <i class="fa-solid fa-bolt" style="color:var(--accent)"></i>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
              <?php
                $accesos = [
                  ['usuarios',    'Usuarios',    'fa-users',        'var(--accent)'],
                  ['cursos',      'Cursos',      'fa-book',         '#10b981'],
                  ['idiomas',     'Idiomas',     'fa-globe',        '#06b6d4'],
                  ['lecciones',   'Lecciones',   'fa-book-open',    '#f59e0b'],
                  ['actividades', 'Actividades', 'fa-list-check',   '#ec4899'],
                  ['reportes',    'Reportes',    'fa-chart-column', '#eab308'],
                ];
                foreach ($accesos as $a):
                  [$pg, $lbl, $ic, $cl] = $a;
              ?>
                <a href="?page=<?= $pg ?>" class="flex items-center gap-2 p-3 rounded-lg surface border divider hover:opacity-90 transition text-[12.5px]">
                  <span class="w-7 h-7 rounded-md flex items-center justify-center" style="background: color-mix(in srgb, <?= $cl ?> 18%, transparent); color: <?= $cl ?>">
                    <i class="fa-solid <?= $ic ?> text-[11px]"></i>
                  </span>
                  <span class="txt"><?= $lbl ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Actividad reciente -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[15px]">Actividad reciente</h3>
              <p class="txt-muted text-xs">Movimientos del sistema</p>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                  <th class="text-left py-2.5 font-bold">Actividad</th>
                  <th class="text-left py-2.5 font-bold">Usuario</th>
                  <th class="text-left py-2.5 font-bold">Fecha</th>
                  <th class="text-left py-2.5 font-bold">Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($ultimosUsuarios) && empty($ultimosCursos)): ?>
                  <tr><td colspan="4" class="py-6 text-center txt-muted text-xs">Sin actividad</td></tr>
                <?php else: ?>
                  <?php foreach ($ultimosUsuarios as $u): ?>
                    <tr class="border-b divider">
                      <td class="py-3">
                        <div class="flex items-center gap-2">
                          <span class="w-7 h-7 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center"><i class="fa-solid fa-user-plus text-[11px]"></i></span>
                          <span class="txt font-semibold">Usuario registrado</span>
                        </div>
                      </td>
                      <td class="py-3"><?= htmlspecialchars(trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? ''))) ?></td>
                      <td class="py-3 txt-muted"><?= htmlspecialchars($u['fecha_registro'] ?? '') ?></td>
                      <td class="py-3"><span class="text-[10px] font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-full">Completado</span></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php foreach ($ultimosCursos as $c): ?>
                    <tr class="border-b divider last:border-0">
                      <td class="py-3">
                        <div class="flex items-center gap-2">
                          <span class="w-7 h-7 rounded-lg chip flex items-center justify-center"><i class="fa-solid fa-book text-[11px]"></i></span>
                          <span class="txt font-semibold">Curso creado</span>
                        </div>
                      </td>
                      <td class="py-3"><?= htmlspecialchars($c['nombre'] ?? '') ?></td>
                      <td class="py-3 txt-muted"><?= htmlspecialchars($c['fecha_creacion'] ?? '') ?></td>
                      <td class="py-3"><span class="text-[10px] font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-full">Completado</span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($pagina === 'usuarios'): ?>

        <?php
          $labelsRol  = array_map(fn($r) => ucfirst($r['rol']), $usuariosPorRol);
          $valoresRol = array_map(fn($r) => (int)$r['total'], $usuariosPorRol);

          echo grafico([
              'id'        => 'chart-usuarios-rol',
              'titulo'    => 'Usuarios por rol',
              'subtitulo' => 'Distribución actual',
              'labels'    => $labelsRol,
              'valores'   => $valoresRol,
              'tipo'      => 'doughnut',
          ]);
        ?>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Usuarios y roles</h3>
              <p class="txt-muted text-xs">Total: <?= count($listaUsuarios) ?> registros</p>
            </div>
            <button type="button" data-nuevo="usuarios" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
              <i class="fa-solid fa-plus"></i> Nuevo usuario
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                  <th class="text-left py-2.5 font-bold">ID</th>
                  <th class="text-left py-2.5 font-bold">Nombre</th>
                  <th class="text-left py-2.5 font-bold">Correo</th>
                  <th class="text-left py-2.5 font-bold">Rol</th>
                  <th class="text-left py-2.5 font-bold">Estado</th>
                  <th class="text-left py-2.5 font-bold">Registro</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($listaUsuarios)): ?>
                  <tr><td colspan="6" class="py-6 text-center txt-muted text-xs">Sin usuarios</td></tr>
                <?php else: foreach ($listaUsuarios as $u): ?>
                  <tr class="border-b divider">
                    <td class="py-3 txt-muted">#<?= (int)$u['id'] ?></td>
                    <td class="py-3 txt font-semibold"><?= htmlspecialchars(trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? ''))) ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($u['correo'] ?? '') ?></td>
                    <td class="py-3"><span class="text-[10px] font-bold px-2 py-1 rounded-full chip"><?= htmlspecialchars($u['rol'] ?? 'usuario') ?></span></td>
                    <td class="py-3">
                      <?php if ((int)$u['activo'] === 1): ?>
                        <span class="text-[10px] font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-full">Activo</span>
                      <?php elseif ((int)$u['activo'] === 2): ?>
                        <span class="text-[10px] font-bold text-amber-500 bg-amber-500/10 px-2 py-1 rounded-full">Pendiente</span>
                      <?php else: ?>
                        <span class="text-[10px] font-bold txt-muted bg-slate-500/10 px-2 py-1 rounded-full">Inactivo</span>
                      <?php endif; ?>
                    </td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($u['fecha_registro'] ?? '') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($pagina === 'cursos'): ?>

        <?php
          $labelsNivel  = array_map(fn($r) => $r['nivel'], $cursosPorNivel);
          $valoresNivel = array_map(fn($r) => (int)$r['total'], $cursosPorNivel);

          echo grafico([
              'id'        => 'chart-cursos-nivel',
              'titulo'    => 'Cursos por nivel',
              'subtitulo' => 'Distribución del catálogo',
              'labels'    => $labelsNivel,
              'valores'   => $valoresNivel,
              'tipo'      => 'bar',
          ]);
        ?>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Catálogo de cursos</h3>
              <p class="txt-muted text-xs">Total: <?= count($listaCursos) ?> registros</p>
            </div>
            <button type="button" data-nuevo="cursos" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
              <i class="fa-solid fa-plus"></i> Nuevo curso
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                  <th class="text-left py-2.5 font-bold">ID</th>
                  <th class="text-left py-2.5 font-bold">Nombre</th>
                  <th class="text-left py-2.5 font-bold">Descripción</th>
                  <th class="text-left py-2.5 font-bold">Nivel</th>
                  <th class="text-left py-2.5 font-bold">Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($listaCursos)): ?>
                  <tr><td colspan="5" class="py-6 text-center txt-muted text-xs">Sin cursos</td></tr>
                <?php else: foreach ($listaCursos as $c): ?>
                  <tr class="border-b divider">
                    <td class="py-3 txt-muted">#<?= (int)($c['id'] ?? 0) ?></td>
                    <td class="py-3 txt font-semibold"><?= htmlspecialchars($c['nombre'] ?? '') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars(mb_strimwidth($c['descripcion'] ?? '', 0, 80, '…')) ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($c['nivel'] ?? '—') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($c['fecha_creacion'] ?? '') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($pagina === 'idiomas'): ?>

        <?php
          echo grafico([
              'id'        => 'chart-idiomas-estado',
              'titulo'    => 'Idiomas por estado',
              'subtitulo' => 'Activos vs inactivos',
              'labels'    => ['Activos', 'Inactivos'],
              'valores'   => [$idiomasActivos, $idiomasInactivos],
              'tipo'      => 'pie',
          ]);
        ?>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Idiomas</h3>
              <p class="txt-muted text-xs">Total: <?= count($listaIdiomas) ?> registros</p>
            </div>
            <button type="button" data-nuevo="idiomas" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
              <i class="fa-solid fa-plus"></i> Nuevo idioma
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                  <th class="text-left py-2.5 font-bold">ID</th>
                  <th class="text-left py-2.5 font-bold">Nombre</th>
                  <th class="text-left py-2.5 font-bold">Código</th>
                  <th class="text-left py-2.5 font-bold">Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($listaIdiomas)): ?>
                  <tr><td colspan="4" class="py-6 text-center txt-muted text-xs">Sin idiomas</td></tr>
                <?php else: foreach ($listaIdiomas as $i): ?>
                  <tr class="border-b divider">
                    <td class="py-3 txt-muted">#<?= (int)($i['id'] ?? 0) ?></td>
                    <td class="py-3 txt font-semibold"><?= htmlspecialchars($i['nombre'] ?? '') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($i['codigo'] ?? '') ?></td>
                    <td class="py-3">
                      <?php if (!empty($i['estado'])): ?>
                        <span class="text-[10px] font-bold text-emerald-500 bg-emerald-500/10 px-2 py-1 rounded-full">Activo</span>
                      <?php else: ?>
                        <span class="text-[10px] font-bold txt-muted bg-slate-500/10 px-2 py-1 rounded-full">Inactivo</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($pagina === 'lecciones'): ?>

        <?php
          $labelsLecc  = array_map(fn($r) => $r['curso'], $leccionesPorCurso);
          $valoresLecc = array_map(fn($r) => (int)$r['total'], $leccionesPorCurso);

          echo grafico([
              'id'        => 'chart-lecciones-curso',
              'titulo'    => 'Lecciones por curso',
              'subtitulo' => 'Top 5 cursos',
              'labels'    => $labelsLecc,
              'valores'   => $valoresLecc,
              'tipo'      => 'bar',
          ]);
        ?>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Lecciones</h3>
              <p class="txt-muted text-xs">Total: <?= count($listaLecciones) ?> registros</p>
            </div>
            <button type="button" data-nuevo="lecciones" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
              <i class="fa-solid fa-plus"></i> Nueva lección
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                  <th class="text-left py-2.5 font-bold">ID</th>
                  <th class="text-left py-2.5 font-bold">Título</th>
                  <th class="text-left py-2.5 font-bold">Curso</th>
                  <th class="text-left py-2.5 font-bold">Orden</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($listaLecciones)): ?>
                  <tr><td colspan="4" class="py-6 text-center txt-muted text-xs">Sin lecciones</td></tr>
                <?php else: foreach ($listaLecciones as $l): ?>
                  <tr class="border-b divider">
                    <td class="py-3 txt-muted">#<?= (int)($l['id'] ?? 0) ?></td>
                    <td class="py-3 txt font-semibold"><?= htmlspecialchars($l['titulo'] ?? '') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($l['curso_id'] ?? '') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($l['orden'] ?? '') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($pagina === 'actividades'): ?>

        <?php
          $labelsAct  = array_map(fn($r) => ucfirst($r['tipo']), $actividadesPorTipo);
          $valoresAct = array_map(fn($r) => (int)$r['total'], $actividadesPorTipo);

          echo grafico([
              'id'        => 'chart-actividades-tipo',
              'titulo'    => 'Actividades por tipo',
              'subtitulo' => 'Distribución por categoría',
              'labels'    => $labelsAct,
              'valores'   => $valoresAct,
              'tipo'      => 'bar',
          ]);
        ?>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Actividades</h3>
              <p class="txt-muted text-xs">Total: <?= count($listaActividades) ?> registros</p>
            </div>
            <button type="button" data-nuevo="actividades" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
              <i class="fa-solid fa-plus"></i> Nueva actividad
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-[13px]">
              <thead>
                <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                  <th class="text-left py-2.5 font-bold">ID</th>
                  <th class="text-left py-2.5 font-bold">Título</th>
                  <th class="text-left py-2.5 font-bold">Tipo</th>
                  <th class="text-left py-2.5 font-bold">Descripción</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($listaActividades)): ?>
                  <tr><td colspan="4" class="py-6 text-center txt-muted text-xs">Sin actividades</td></tr>
                <?php else: foreach ($listaActividades as $a): ?>
                  <tr class="border-b divider">
                    <td class="py-3 txt-muted">#<?= (int)($a['id'] ?? 0) ?></td>
                    <td class="py-3 txt font-semibold"><?= htmlspecialchars($a['titulo'] ?? '') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars($a['tipo'] ?? '—') ?></td>
                    <td class="py-3 txt-muted"><?= htmlspecialchars(mb_strimwidth($a['descripcion'] ?? '', 0, 80, '…')) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      <?php elseif ($pagina === 'solicitudes'): ?>

        <!-- ====================================================
             SOLICITUDES DE DOCENTES
        ===================================================== -->

        <?php
          $flashSolicitud = $_SESSION["flash_solicitudes"] ?? null;
          unset($_SESSION["flash_solicitudes"]);
        ?>
        <?php if ($flashSolicitud): ?>
          <div id="flashSolicitud"
               class="card p-4 flex items-center gap-3 border-l-4"
               style="border-left-color: <?= $flashSolicitud['tipo'] === 'ok' ? '#10b981' : '#ef4444' ?>;">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                         <?= $flashSolicitud['tipo'] === 'ok'
                              ? 'bg-emerald-500/15 text-emerald-500'
                              : 'bg-red-500/15 text-red-500' ?>">
              <i class="fa-solid <?= $flashSolicitud['tipo'] === 'ok' ? 'fa-check' : 'fa-triangle-exclamation' ?>"></i>
            </span>
            <p class="txt text-sm flex-1"><?= htmlspecialchars($flashSolicitud["mensaje"]) ?></p>
            <button onclick="document.getElementById('flashSolicitud').remove()"
                    class="txt-muted hover:opacity-70">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center">
                <i class="fa-solid fa-hourglass-half text-sm"></i>
              </div>
            </div>
            <p class="txt text-2xl font-extrabold"><?= count($docentesPendientes) ?></p>
            <p class="txt-muted text-xs">Pendientes</p>
          </div>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center">
                <i class="fa-solid fa-user-check text-sm"></i>
              </div>
            </div>
            <p class="txt text-2xl font-extrabold"><?= count($docentesAprobados) ?></p>
            <p class="txt-muted text-xs">Aprobados</p>
          </div>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
              <div class="w-9 h-9 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center">
                <i class="fa-solid fa-user-xmark text-sm"></i>
              </div>
            </div>
            <p class="txt text-2xl font-extrabold"><?= count($docentesRechazados) ?></p>
            <p class="txt-muted text-xs">Rechazados</p>
          </div>
        </div>

        <!-- PENDIENTES -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Solicitudes pendientes</h3>
              <p class="txt-muted text-xs">Docentes esperando aprobación</p>
            </div>
            <?php if (count($docentesPendientes) > 0): ?>
              <span class="text-[10px] font-bold px-3 py-1 rounded-full text-white" style="background: #ef4444;">
                <?= count($docentesPendientes) ?> nuevas
              </span>
            <?php endif; ?>
          </div>

          <?php if (empty($docentesPendientes)): ?>
            <div class="text-center py-10">
              <i class="fa-solid fa-check-circle text-4xl mb-3" style="color: var(--accent)"></i>
              <p class="txt-muted text-sm">No hay solicitudes pendientes</p>
            </div>
          <?php else: ?>
            <div class="space-y-3">
              <?php foreach ($docentesPendientes as $d): ?>
                <div class="flex items-center gap-4 p-4 rounded-lg surface border divider flex-wrap">
                  <div class="w-11 h-11 rounded-full grad-accent flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                    <?= htmlspecialchars(strtoupper(substr($d['nombre'] ?? 'D', 0, 1))) ?>
                  </div>

                  <div class="flex-1 min-w-0">
                    <p class="txt font-semibold"><?= htmlspecialchars(trim(($d['nombre'] ?? '') . ' ' . ($d['apellido'] ?? ''))) ?></p>
                    <p class="txt-muted text-xs truncate"><?= htmlspecialchars($d['correo'] ?? '') ?></p>
                  </div>

                  <div class="text-right txt-muted text-xs hidden sm:block">
                    <i class="fa-regular fa-clock mr-1"></i>
                    <?= htmlspecialchars($d['fecha_registro'] ?? '') ?>
                  </div>

                  <div class="flex gap-2">
                    <form method="POST" action="solicitudes.php" class="inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">
                      <input type="hidden" name="accion" value="aprobar">
                      <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                      <button type="submit"
                              class="px-3 py-2 rounded-lg text-xs font-semibold text-white flex items-center gap-1.5"
                              style="background: #10b981;">
                        <i class="fa-solid fa-check"></i> Aprobar
                      </button>
                    </form>

                    <form method="POST" action="solicitudes.php" class="inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">
                      <input type="hidden" name="accion" value="rechazar">
                      <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                      <button type="submit"
                              class="px-3 py-2 rounded-lg text-xs font-semibold bg-red-500/10 text-red-500 hover:bg-red-500/20 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-xmark"></i> Rechazar
                      </button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- APROBADOS -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Docentes aprobados</h3>
              <p class="txt-muted text-xs"><?= count($docentesAprobados) ?> activos</p>
            </div>
          </div>

          <?php if (empty($docentesAprobados)): ?>
            <p class="txt-muted text-xs text-center py-6">Sin docentes aprobados</p>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-[13px]">
                <thead>
                  <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                    <th class="text-left py-2.5 font-bold">ID</th>
                    <th class="text-left py-2.5 font-bold">Nombre</th>
                    <th class="text-left py-2.5 font-bold">Correo</th>
                    <th class="text-left py-2.5 font-bold">Registro</th>
                    <th class="text-left py-2.5 font-bold">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($docentesAprobados as $d): ?>
                    <tr class="border-b divider">
                      <td class="py-3 txt-muted">#<?= (int)$d['id'] ?></td>
                      <td class="py-3 txt font-semibold"><?= htmlspecialchars(trim(($d['nombre'] ?? '') . ' ' . ($d['apellido'] ?? ''))) ?></td>
                      <td class="py-3 txt-muted"><?= htmlspecialchars($d['correo'] ?? '') ?></td>
                      <td class="py-3 txt-muted"><?= htmlspecialchars($d['fecha_registro'] ?? '') ?></td>
                      <td class="py-3">
                        <form method="POST" action="solicitudes.php" class="inline"
                              onsubmit="return confirm('¿Eliminar este docente definitivamente?');">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">
                          <input type="hidden" name="accion" value="eliminar">
                          <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                          <button type="submit"
                                  class="text-red-500 hover:opacity-70 text-xs font-semibold">
                            <i class="fa-solid fa-trash"></i> Eliminar
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- RECHAZADOS -->
        <?php if (!empty($docentesRechazados)): ?>
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="txt font-bold text-[16px]">Rechazados</h3>
                <p class="txt-muted text-xs"><?= count($docentesRechazados) ?> inactivos</p>
              </div>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-[13px]">
                <thead>
                  <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                    <th class="text-left py-2.5 font-bold">ID</th>
                    <th class="text-left py-2.5 font-bold">Nombre</th>
                    <th class="text-left py-2.5 font-bold">Correo</th>
                    <th class="text-left py-2.5 font-bold">Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($docentesRechazados as $d): ?>
                    <tr class="border-b divider">
                      <td class="py-3 txt-muted">#<?= (int)$d['id'] ?></td>
                      <td class="py-3 txt font-semibold"><?= htmlspecialchars(trim(($d['nombre'] ?? '') . ' ' . ($d['apellido'] ?? ''))) ?></td>
                      <td class="py-3 txt-muted"><?= htmlspecialchars($d['correo'] ?? '') ?></td>
                      <td class="py-3">
                        <div class="flex gap-2">
                          <form method="POST" action="solicitudes.php" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">
                            <input type="hidden" name="accion" value="aprobar">
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button type="submit" class="text-emerald-500 hover:opacity-70 text-xs font-semibold">
                              <i class="fa-solid fa-rotate-left"></i> Reactivar
                            </button>
                          </form>

                          <form method="POST" action="solicitudes.php" class="inline"
                                onsubmit="return confirm('¿Eliminar definitivamente?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                            <button type="submit" class="text-red-500 hover:opacity-70 text-xs font-semibold">
                              <i class="fa-solid fa-trash"></i> Eliminar
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

      <?php elseif ($pagina === 'configuracion'): ?>

        <!-- ====================================================
             CONFIGURACIÓN — TABS
        ===================================================== -->

        <?php if ($flashConfig): ?>
          <div id="flashConfig"
               class="card p-4 flex items-center gap-3 border-l-4"
               style="border-left-color: <?= $flashConfig['tipo'] === 'ok' ? '#10b981' : '#ef4444' ?>;">
            <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                         <?= $flashConfig['tipo'] === 'ok'
                              ? 'bg-emerald-500/15 text-emerald-500'
                              : 'bg-red-500/15 text-red-500' ?>">
              <i class="fa-solid <?= $flashConfig['tipo'] === 'ok' ? 'fa-check' : 'fa-triangle-exclamation' ?>"></i>
            </span>
            <p class="txt text-sm flex-1"><?= htmlspecialchars($flashConfig["mensaje"]) ?></p>
            <button onclick="document.getElementById('flashConfig').remove()"
                    class="txt-muted hover:opacity-70">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="card p-1.5 flex flex-wrap gap-1 overflow-x-auto">
          <?php
            $tabs = [
              "perfil"         => ["Perfil",         "fa-user"],
              "general"        => ["General",        "fa-globe"],
              "apariencia"     => ["Apariencia",     "fa-palette"],
              "notificaciones" => ["Notificaciones", "fa-bell"],
              "seguridad"      => ["Seguridad",      "fa-shield-halved"],
              "correo"         => ["Correo",         "fa-envelope"],
              "sistema"        => ["Sistema",        "fa-server"],
            ];
            foreach ($tabs as $key => [$lbl, $ic]):
              $activo = ($tabConfig === $key);
          ?>
            <a href="?page=configuracion&tab=<?= $key ?>"
               class="flex items-center gap-2 px-4 py-2 rounded-lg text-[13px] font-semibold transition
                      <?= $activo ? 'text-white' : 'txt-muted hover:opacity-80' ?>"
               style="<?= $activo ? 'background: var(--accent)' : '' ?>">
              <i class="fa-solid <?= $ic ?> text-[12px]"></i>
              <?= $lbl ?>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($tabConfig === "perfil"): ?>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="card p-5">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg chip flex items-center justify-center">
                  <i class="fa-solid fa-user"></i>
                </div>
                <div>
                  <h3 class="txt font-bold text-[15px]">Datos personales</h3>
                  <p class="txt-muted text-xs">Tu información de administrador</p>
                </div>
              </div>

              <form method="POST" action="configuracion.php" class="space-y-4">
                <input type="hidden" name="accion" value="perfil">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

                <div>
                  <label class="txt-muted text-xs font-semibold">Nombre</label>
                  <input name="nombre" required value="<?= htmlspecialchars($datosPerfil['nombre'] ?? '') ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Apellido</label>
                  <input name="apellido" value="<?= htmlspecialchars($datosPerfil['apellido'] ?? '') ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Correo electrónico</label>
                  <input type="email" name="correo" required value="<?= htmlspecialchars($datosPerfil['correo'] ?? '') ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <button type="submit"
                        class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                  <i class="fa-solid fa-check"></i> Guardar cambios
                </button>
              </form>
            </div>

            <div class="card p-5">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center">
                  <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                  <h3 class="txt font-bold text-[15px]">Cambiar contraseña</h3>
                  <p class="txt-muted text-xs">Actualiza tu clave de acceso</p>
                </div>
              </div>

              <form method="POST" action="configuracion.php" class="space-y-4">
                <input type="hidden" name="accion" value="password">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

                <div>
                  <label class="txt-muted text-xs font-semibold">Contraseña actual</label>
                  <input type="password" name="password_actual" required
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Nueva contraseña</label>
                  <input type="password" name="password_nueva" required minlength="6"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Confirmar nueva contraseña</label>
                  <input type="password" name="password_confirmar" required minlength="6"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <button type="submit"
                        class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                  <i class="fa-solid fa-shield-halved"></i> Actualizar contraseña
                </button>
              </form>
            </div>
          </div>

        <?php elseif ($tabConfig === "general"): ?>

          <div class="card p-5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-lg chip flex items-center justify-center">
                <i class="fa-solid fa-globe"></i>
              </div>
              <div>
                <h3 class="txt font-bold text-[15px]">Configuración general</h3>
                <p class="txt-muted text-xs">Información básica del sitio</p>
              </div>
            </div>

            <form method="POST" action="configuracion.php" class="space-y-4 max-w-2xl">
              <input type="hidden" name="accion" value="general">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

              <div>
                <label class="txt-muted text-xs font-semibold">Nombre del sitio</label>
                <input name="sitio_nombre" required value="<?= htmlspecialchars(Config::get('sitio_nombre', '')) ?>"
                       class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
              </div>

              <div>
                <label class="txt-muted text-xs font-semibold">Descripción</label>
                <textarea name="sitio_descripcion" rows="2"
                          class="input w-full rounded-lg px-3 py-2 text-sm mt-1"><?= htmlspecialchars(Config::get('sitio_descripcion', '')) ?></textarea>
              </div>

              <div>
                <label class="txt-muted text-xs font-semibold">Correo de contacto</label>
                <input type="email" name="sitio_correo" value="<?= htmlspecialchars(Config::get('sitio_correo', '')) ?>"
                       class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="txt-muted text-xs font-semibold">Idioma por defecto</label>
                  <select name="sitio_idioma" class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                    <?php foreach (["es" => "Español", "en" => "English", "pt" => "Português"] as $code => $lbl):
                      $sel = Config::get('sitio_idioma') === $code ? 'selected' : '';
                    ?>
                      <option value="<?= $code ?>" <?= $sel ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Zona horaria</label>
                  <input name="sitio_zona" value="<?= htmlspecialchars(Config::get('sitio_zona', 'America/Bogota')) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>
              </div>

              <button type="submit"
                      class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Guardar
              </button>
            </form>
          </div>

        <?php elseif ($tabConfig === "apariencia"): ?>

          <div class="card p-5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-lg chip flex items-center justify-center">
                <i class="fa-solid fa-palette"></i>
              </div>
              <div>
                <h3 class="txt font-bold text-[15px]">Apariencia</h3>
                <p class="txt-muted text-xs">Tema y color de acento por defecto</p>
              </div>
            </div>

            <form method="POST" action="configuracion.php" class="space-y-5 max-w-2xl">
              <input type="hidden" name="accion" value="apariencia">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

              <div>
                <label class="txt-muted text-xs font-semibold mb-2 block">Tema por defecto</label>
                <div class="flex gap-3">
                  <?php foreach (["dark" => ["Oscuro", "fa-moon"], "light" => ["Claro", "fa-sun"]] as $key => [$lbl, $ic]):
                    $checked = Config::get('tema_default') === $key;
                  ?>
                    <label class="flex items-center gap-2 px-4 py-3 rounded-lg surface border divider cursor-pointer hover:opacity-90 flex-1"
                           style="<?= $checked ? 'border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent)' : '' ?>">
                      <input type="radio" name="tema_default" value="<?= $key ?>" <?= $checked ? 'checked' : '' ?>>
                      <i class="fa-solid <?= $ic ?> txt-muted"></i>
                      <span class="txt text-sm font-semibold"><?= $lbl ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <div>
                <label class="txt-muted text-xs font-semibold mb-2 block">Color de acento</label>
                <div class="grid grid-cols-5 gap-2">
                  <?php
                    $acentos = [
                      /* Azul = color de la marca (recomendado) */
                      "blue"   => ["#2563EB", "Azul (marca)"],
                      "green"  => ["#16A34A", "Verde"],
                      "orange" => ["#C2410C", "Naranja"],
                      "purple" => ["#7C3AED", "Morado"],
                      "pink"   => ["#DB2777", "Rosa"],
                    ];
                    $actualAcento = Config::get('color_acento', 'blue');
                    foreach ($acentos as $key => [$hex, $lbl]):
                      $sel = ($actualAcento === $key);
                  ?>
                    <label class="cursor-pointer text-center">
                      <input type="radio" name="color_acento" value="<?= $key ?>" <?= $sel ? 'checked' : '' ?> class="peer hidden">
                      <div class="h-14 rounded-lg border-2 transition"
                           style="background: <?= $hex ?>; border-color: <?= $sel ? $hex : 'transparent' ?>;"></div>
                      <p class="txt-muted text-[11px] mt-1.5"><?= $lbl ?></p>
                    </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <button type="submit"
                      class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Guardar
              </button>
            </form>
          </div>

        <?php elseif ($tabConfig === "notificaciones"): ?>

          <div class="card p-5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-lg chip flex items-center justify-center">
                <i class="fa-solid fa-bell"></i>
              </div>
              <div>
                <h3 class="txt font-bold text-[15px]">Notificaciones</h3>
                <p class="txt-muted text-xs">Elige qué avisos quieres recibir</p>
              </div>
            </div>

            <form method="POST" action="configuracion.php" class="space-y-3 max-w-2xl">
              <input type="hidden" name="accion" value="notificaciones">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

              <?php
                $notifs = [
                  "notif_nuevo_usuario"   => ["Nuevo usuario registrado",  "Aviso cuando alguien crea una cuenta"],
                  "notif_nuevo_curso"     => ["Nuevo curso creado",         "Aviso cuando se publica un curso"],
                  "notif_nuevo_reporte"   => ["Nuevo reporte recibido",     "Aviso cuando un usuario reporta contenido"],
                  "notif_resumen_semanal" => ["Resumen semanal por correo", "Recibe un resumen cada lunes"],
                ];
                foreach ($notifs as $key => [$titulo, $desc]):
                  $checked = Config::getBool($key);
              ?>
                <label class="flex items-center justify-between p-4 rounded-lg surface border divider cursor-pointer hover:opacity-95">
                  <div class="flex-1">
                    <p class="txt text-sm font-semibold"><?= $titulo ?></p>
                    <p class="txt-muted text-xs mt-0.5"><?= $desc ?></p>
                  </div>
                  <input type="checkbox" name="<?= $key ?>" <?= $checked ? 'checked' : '' ?>
                         class="w-4 h-4 rounded" style="accent-color: var(--accent);">
                </label>
              <?php endforeach; ?>

              <button type="submit"
                      class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2 mt-2">
                <i class="fa-solid fa-check"></i> Guardar
              </button>
            </form>
          </div>

        <?php elseif ($tabConfig === "seguridad"): ?>

          <div class="card p-5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-lg bg-red-500/15 text-red-500 flex items-center justify-center">
                <i class="fa-solid fa-shield-halved"></i>
              </div>
              <div>
                <h3 class="txt font-bold text-[15px]">Seguridad</h3>
                <p class="txt-muted text-xs">Reglas de contraseñas y bloqueo de cuentas</p>
              </div>
            </div>

            <form method="POST" action="configuracion.php" class="space-y-4 max-w-2xl">
              <input type="hidden" name="accion" value="seguridad">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="txt-muted text-xs font-semibold">Largo mínimo de contraseña</label>
                  <input type="number" name="seg_min_password" min="4" max="32"
                         value="<?= Config::getInt('seg_min_password', 6) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Intentos antes de bloquear</label>
                  <input type="number" name="seg_intentos_login" min="1" max="20"
                         value="<?= Config::getInt('seg_intentos_login', 5) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Minutos de bloqueo</label>
                  <input type="number" name="seg_bloqueo_minutos" min="1" max="120"
                         value="<?= Config::getInt('seg_bloqueo_minutos', 1) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>
              </div>

              <div class="space-y-2 pt-2">
                <label class="flex items-center gap-3 p-3 rounded-lg surface border divider cursor-pointer">
                  <input type="checkbox" name="seg_requiere_mayuscula" <?= Config::getBool('seg_requiere_mayuscula') ? 'checked' : '' ?>
                         class="w-4 h-4 rounded" style="accent-color: var(--accent);">
                  <span class="txt text-sm">Requerir al menos una mayúscula</span>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-lg surface border divider cursor-pointer">
                  <input type="checkbox" name="seg_requiere_numero" <?= Config::getBool('seg_requiere_numero') ? 'checked' : '' ?>
                         class="w-4 h-4 rounded" style="accent-color: var(--accent);">
                  <span class="txt text-sm">Requerir al menos un número</span>
                </label>
              </div>

              <button type="submit"
                      class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Guardar
              </button>
            </form>
          </div>

        <?php elseif ($tabConfig === "correo"): ?>

          <div class="card p-5">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-10 h-10 rounded-lg bg-cyan-500/15 text-cyan-500 flex items-center justify-center">
                <i class="fa-solid fa-envelope"></i>
              </div>
              <div>
                <h3 class="txt font-bold text-[15px]">Configuración de correo</h3>
                <p class="txt-muted text-xs">Servidor SMTP para envío de emails</p>
              </div>
            </div>

            <form method="POST" action="configuracion.php" class="space-y-4 max-w-2xl">
              <input type="hidden" name="accion" value="correo">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                  <label class="txt-muted text-xs font-semibold">Servidor SMTP</label>
                  <input name="smtp_host" value="<?= htmlspecialchars(Config::get('smtp_host', '')) ?>"
                         placeholder="smtp.gmail.com"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Puerto</label>
                  <input type="number" name="smtp_puerto" value="<?= Config::getInt('smtp_puerto', 587) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Encriptación</label>
                  <select name="smtp_encriptacion" class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                    <?php foreach (["tls" => "TLS", "ssl" => "SSL", "none" => "Sin encriptación"] as $code => $lbl):
                      $sel = Config::get('smtp_encriptacion') === $code ? 'selected' : '';
                    ?>
                      <option value="<?= $code ?>" <?= $sel ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div class="sm:col-span-2">
                  <label class="txt-muted text-xs font-semibold">Usuario SMTP</label>
                  <input name="smtp_usuario" value="<?= htmlspecialchars(Config::get('smtp_usuario', '')) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div class="sm:col-span-2">
                  <label class="txt-muted text-xs font-semibold">Contraseña SMTP</label>
                  <input type="password" name="smtp_password" placeholder="•••••••• (dejar vacío para no cambiar)"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                  <p class="txt-muted text-[11px] mt-1">Si lo dejas vacío, se conserva la contraseña actual.</p>
                </div>

                <div class="sm:col-span-2">
                  <label class="txt-muted text-xs font-semibold">Correo remitente</label>
                  <input type="email" name="smtp_remitente" value="<?= htmlspecialchars(Config::get('smtp_remitente', '')) ?>"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>
              </div>

              <button type="submit"
                      class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Guardar
              </button>
            </form>
          </div>

        <?php elseif ($tabConfig === "sistema"): ?>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <div class="card p-5">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg chip flex items-center justify-center">
                  <i class="fa-solid fa-server"></i>
                </div>
                <div>
                  <h3 class="txt font-bold text-[15px]">Información del sistema</h3>
                  <p class="txt-muted text-xs">Detalles del entorno</p>
                </div>
              </div>

              <div class="space-y-3 text-[13px]">
                <?php
                  $info = [
                    "Versión de PHP"      => PHP_VERSION,
                    "Servidor"            => $_SERVER["SERVER_SOFTWARE"] ?? "—",
                    "Base de datos"       => $conexion->query("SELECT DATABASE()")->fetchColumn() ?: "—",
                    "Versión de MySQL"    => $conexion->query("SELECT VERSION()")->fetchColumn() ?: "—",
                    "Zona horaria (PHP)"  => date_default_timezone_get(),
                    "Memoria límite"      => ini_get("memory_limit"),
                  ];
                  foreach ($info as $k => $v):
                ?>
                  <div class="flex items-center justify-between py-2 border-b divider last:border-0">
                    <span class="txt-muted"><?= $k ?></span>
                    <span class="txt font-semibold"><?= htmlspecialchars((string)$v) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="card p-5">
              <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center">
                  <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>
                <div>
                  <h3 class="txt font-bold text-[15px]">Mantenimiento</h3>
                  <p class="txt-muted text-xs">Acciones rápidas</p>
                </div>
              </div>

              <form method="POST" action="configuracion.php" class="space-y-3">
                <input type="hidden" name="accion" value="sistema">
                <input type="hidden" name="sub" value="limpiar_cache">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

                <button type="submit"
                        class="w-full flex items-center gap-3 p-4 rounded-lg surface border divider hover:opacity-90 transition text-left">
                  <span class="w-9 h-9 rounded-lg bg-purple-500/15 text-purple-500 flex items-center justify-center">
                    <i class="fa-solid fa-broom"></i>
                  </span>
                  <div>
                    <p class="txt text-sm font-semibold">Limpiar caché de configuración</p>
                    <p class="txt-muted text-xs">Fuerza la recarga de la config desde la BD</p>
                  </div>
                </button>
              </form>

              <div class="mt-4 p-4 rounded-lg surface border divider">
                <p class="txt-muted text-xs">
                  <i class="fa-solid fa-info-circle mr-1" style="color: var(--accent)"></i>
                  La descarga de backups estará disponible próximamente.
                </p>
              </div>
            </div>
          </div>

        <?php endif; ?>

      <?php else: ?>

        <div class="card p-8 text-center">
          <div class="w-16 h-16 rounded-2xl chip flex items-center justify-center mx-auto mb-4 text-2xl">
            <i class="fa-solid fa-hammer"></i>
          </div>
          <h3 class="txt font-bold text-lg mb-1"><?= htmlspecialchars(ucfirst($pagina)) ?></h3>
          <p class="txt-muted text-sm">Esta sección estará disponible próximamente.</p>
        </div>

      <?php endif; ?>

    </main>

    <!-- Overlay para móvil -->
    <div id="sidebarOverlay"
         class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"
         style="backdrop-filter: blur(2px);"></div>
  </div>
</div>

<!-- ==================== SCRIPT DEL TEMA ==================== -->
<script>
  (function () {
    const toggle = document.getElementById('themeToggle');
    const icon   = document.getElementById('themeIcon');

    function actualizarIcono() {
      const esOscuro = document.documentElement.classList.contains('dark');
      icon.className = esOscuro ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
    }

    actualizarIcono();

    toggle.addEventListener('click', () => {
      const esOscuro = document.documentElement.classList.toggle('dark');
      localStorage.setItem('biglobal-theme', esOscuro ? 'dark' : 'light');
      actualizarIcono();
    });
  })();
</script>

<!-- ==================== SCRIPT DEL SIDEBAR RESPONSIVE ==================== -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggle  = document.getElementById("sidebarToggle");
    const overlay = document.getElementById("sidebarOverlay");

    if (!sidebar || !toggle || !overlay) return;

    function abrirSidebar() {
        sidebar.classList.remove("-translate-x-full");
        sidebar.classList.add("translate-x-0");
        overlay.classList.remove("hidden");
        document.body.style.overflow = "hidden";
    }

    function cerrarSidebar() {
        sidebar.classList.add("-translate-x-full");
        sidebar.classList.remove("translate-x-0");
        overlay.classList.add("hidden");
        document.body.style.overflow = "";
    }

    toggle.addEventListener("click", () => {
        const abierto = !sidebar.classList.contains("-translate-x-full");
        abierto ? cerrarSidebar() : abrirSidebar();
    });

    overlay.addEventListener("click", cerrarSidebar);

    sidebar.querySelectorAll("a").forEach(a => {
        a.addEventListener("click", () => {
            if (window.innerWidth < 1024) cerrarSidebar();
        });
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth >= 1024) cerrarSidebar();
    });

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") cerrarSidebar();
    });
});
</script>

<!-- ==================== SCRIPT DE GRÁFICOS ==================== -->
<script>
(function () {
    const charts = {};
    const isDark = () => document.documentElement.classList.contains('dark');

    function paleta() {
        return isDark()
            ? ["#60A5FA","#38BDF8","#4ADE80","#FB923C","#A78BFA","#F472B6","#FACC15","#94A3B8"]
            : ["#2563EB","#0284C7","#16A34A","#EA580C","#7C3AED","#DB2777","#CA8A04","#64748B"];
    }
    function gridColor() { return isDark() ? 'rgba(148,163,184,.10)' : 'rgba(100,116,139,.12)'; }
    function textColor() { return isDark() ? '#94a3b8' : '#64748b'; }

    function crearChart(canvas, tipo) {
        const labels  = JSON.parse(canvas.dataset.labels  || '[]');
        const valores = JSON.parse(canvas.dataset.valores || '[]');
        const colores = paleta();

        const tipoReal   = tipo === 'area' ? 'line' : tipo;
        const conRelleno = tipo === 'area';
        const esCircular = tipoReal === 'pie' || tipoReal === 'doughnut';
        const esRadar    = tipoReal === 'radar';

        return new Chart(canvas.getContext('2d'), {
            type: tipoReal,
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: valores,
                    backgroundColor: (esCircular || esRadar) ? colores : colores[0] + 'cc',
                    borderColor: (esCircular || esRadar) ? colores : colores[0],
                    borderWidth: 2,
                    borderRadius: tipoReal === 'bar' ? 8 : 0,
                    fill: conRelleno,
                    tension: 0.35,
                    pointRadius: 4,
                    pointBackgroundColor: colores[0],
                    pointBorderColor: isDark() ? "#13213B" : "#fff",
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: esCircular || esRadar,
                        position: 'bottom',
                        labels: { color: textColor(), boxWidth: 12, font: { size: 11 } }
                    },
                    tooltip: {
                        backgroundColor: isDark() ? "#13213B" : "#fff",
                        titleColor: isDark() ? '#fff' : '#0f172a',
                        bodyColor: isDark() ? '#cbd5e1' : '#334155',
                        borderColor: gridColor(),
                        borderWidth: 1,
                        padding: 10,
                    }
                },
                scales: (esCircular || esRadar) ? {} : {
                    x: {
                        grid: { color: gridColor(), display: false },
                        ticks: { color: textColor(), font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor() },
                        ticks: { color: textColor(), font: { size: 11 }, precision: 0 }
                    }
                }
            }
        });
    }

    function inicializarTodos() {
        document.querySelectorAll('canvas[data-tipo]').forEach(canvas => {
            const id = canvas.id;
            const select = document.querySelector('.chart-tipo[data-target="' + id + '"]');
            const guardado = localStorage.getItem('chart-tipo-' + id);
            const tipoFinal = guardado || canvas.dataset.tipo || 'bar';

            if (select) select.value = tipoFinal;

            if (charts[id]) charts[id].destroy();
            charts[id] = crearChart(canvas, tipoFinal);
        });
    }

    document.addEventListener('change', e => {
        if (!e.target.classList.contains('chart-tipo')) return;
        const id = e.target.dataset.target;
        const tipo = e.target.value;
        const canvas = document.getElementById(id);
        if (!canvas) return;

        localStorage.setItem('chart-tipo-' + id, tipo);
        if (charts[id]) charts[id].destroy();
        charts[id] = crearChart(canvas, tipo);
    });

    const observer = new MutationObserver(() => {
        Object.keys(charts).forEach(id => {
            const canvas = document.getElementById(id);
            const select = document.querySelector('.chart-tipo[data-target="' + id + '"]');
            const tipo = select ? select.value : (canvas?.dataset.tipo || 'bar');
            if (charts[id]) charts[id].destroy();
            if (canvas) charts[id] = crearChart(canvas, tipo);
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

    if (typeof Chart !== 'undefined') {
        inicializarTodos();
    } else {
        window.addEventListener('load', inicializarTodos);
    }
})();
</script>

<!-- ==================== SCRIPT DEL MODAL ==================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal     = document.getElementById('modalCrear');
    const tipoInput = document.getElementById('modalTipo');
    const titulo    = document.getElementById('modalTitulo');

    const titulos = {
        usuarios:    'Nuevo usuario',
        cursos:      'Nuevo curso',
        idiomas:     'Nuevo idioma',
        lecciones:   'Nueva lección',
        actividades: 'Nueva actividad',
    };

    window.abrirModal = function (tipo) {
        if (!modal) return;
        tipoInput.value = tipo;
        titulo.textContent = titulos[tipo] || 'Nuevo registro';

        document.querySelectorAll('.form-tipo').forEach(f => {
            const esActivo = (f.dataset.tipo === tipo);
            f.style.display = esActivo ? '' : 'none';

            f.querySelectorAll('input, select, textarea').forEach(campo => {
                campo.disabled = !esActivo;
            });
        });

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    window.cerrarModal = function () {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-nuevo]');
        if (btn) {
            e.preventDefault();
            abrirModal(btn.dataset.nuevo);
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') cerrarModal();
    });

    if (modal) {
        modal.addEventListener('click', e => {
            if (e.target === modal) cerrarModal();
        });
    }

    const formModal = document.getElementById('formModal');
    if (formModal) {
        formModal.addEventListener('submit', function (e) {
            const tipo = tipoInput.value;
            const formActivo = document.querySelector('.form-tipo[data-tipo="' + tipo + '"]');
            if (!formActivo) return;

            let valido = true;
            formActivo.querySelectorAll('input[required], select[required], textarea[required]').forEach(campo => {
                if (!campo.value.trim()) {
                    campo.focus();
                    campo.style.borderColor = '#ef4444';
                    valido = false;
                } else {
                    campo.style.borderColor = '';
                }
            });

            if (!valido) {
                e.preventDefault();
                alert('Completa todos los campos obligatorios.');
            }
        });
    }
});
</script>

<!-- ==================== MODAL CREAR ==================== -->
<div id="modalCrear"
     class="fixed inset-0 z-50 hidden items-center justify-center p-4"
     style="background: rgba(0,0,0,.6); backdrop-filter: blur(4px);">

  <div class="card w-full max-w-lg max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between p-5 border-b divider sticky top-0 surface z-10">
      <div>
        <h3 id="modalTitulo" class="txt font-bold text-[16px]">Nuevo registro</h3>
        <p class="txt-muted text-xs">Completa los campos</p>
      </div>
      <button type="button" onclick="cerrarModal()" class="txt-muted hover:opacity-70 text-lg">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <form method="POST" class="p-5 space-y-4" id="formModal" novalidate>
      <input type="hidden" name="accion" value="crear">
      <input type="hidden" name="registro" id="modalTipo">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfConfig) ?>">

      <div class="form-tipo" data-tipo="usuarios" style="display:none">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="txt-muted text-xs font-semibold">Nombre</label>
            <input name="nombre" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
          </div>
          <div>
            <label class="txt-muted text-xs font-semibold">Apellido</label>
            <input name="apellido" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
          </div>
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Correo</label>
          <input type="email" name="correo" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Contraseña</label>
          <input type="password" name="password" required autocomplete="new-password" placeholder="Mínimo <?= (int) Seguridad::reglasPassword()["minimo"] ?> caracteres" class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Rol</label>
          <select name="rol_id" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
            <?php foreach ($rolesDisponibles as $r): ?>
              <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-tipo" data-tipo="cursos" style="display:none">
        <div>
          <label class="txt-muted text-xs font-semibold">Nombre</label>
          <input name="nombre" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Descripción</label>
          <textarea name="descripcion" rows="3" class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
          <div>
            <label class="txt-muted text-xs font-semibold">Nivel</label>
            <input name="nivel" placeholder="Básico, Intermedio..." class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
          </div>
          <div>
            <label class="txt-muted text-xs font-semibold">Instructor</label>
            <select name="instructor_id" class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
              <option value="">— Sin asignar —</option>
              <?php foreach ($instructoresDisponibles as $i): ?>
                <option value="<?= (int)$i['id'] ?>"><?= htmlspecialchars($i['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="form-tipo" data-tipo="idiomas" style="display:none">
        <div>
          <label class="txt-muted text-xs font-semibold">Nombre</label>
          <input name="nombre" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Código (ISO)</label>
          <input name="codigo" required maxlength="10" placeholder="ES, EN, PT..." class="input w-full rounded-lg px-3 py-2 text-sm mt-1 uppercase">
        </div>
      </div>

      <div class="form-tipo" data-tipo="lecciones" style="display:none">
        <div>
          <label class="txt-muted text-xs font-semibold">Curso</label>
          <select name="curso_id" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
            <?php foreach ($cursosDisponibles as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Título</label>
          <input name="titulo" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Descripción</label>
          <textarea name="descripcion" rows="2" class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Contenido</label>
          <textarea name="contenido" rows="3" class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Orden</label>
          <input type="number" name="orden" value="1" min="1" class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
      </div>

      <div class="form-tipo" data-tipo="actividades" style="display:none">
        <div>
          <label class="txt-muted text-xs font-semibold">Lección</label>
          <select name="leccion_id" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
            <?php foreach ($leccionesDisponibles as $l): ?>
              <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['titulo']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Título</label>
          <input name="titulo" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Descripción</label>
          <textarea name="descripcion" rows="2" class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
        </div>
        <div class="mt-3">
          <label class="txt-muted text-xs font-semibold">Tipo</label>
          <input name="tipo" placeholder="Quiz, Tarea, Ejercicio..." class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t divider">
        <button type="button" onclick="cerrarModal()" class="px-4 py-2 rounded-lg text-sm surface border divider txt-muted hover:opacity-80">
          Cancelar
        </button>
        <button type="submit" class="grad-accent text-white px-4 py-2 rounded-lg text-sm font-semibold btn">
          <i class="fa-solid fa-check mr-1"></i> Guardar
        </button>
      </div>
    </form>
  </div>
</div>

<?php if ($flash): ?>
<!-- Aviso flotante después de crear un registro (se cierra solo) -->
<div class="toast-biglobal toast-biglobal--<?= $flash['tipo'] === 'ok' ? 'exito' : 'error' ?>" role="status" data-toast>
  <i class="fa-solid <?= $flash['tipo'] === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
  <span><?= htmlspecialchars($flash['mensaje']) ?></span>
</div>
<script>
  (function () {
    var t = document.querySelector('[data-toast]');
    if (!t) return;
    function ocultar() { t.dataset.oculto = 'true'; setTimeout(function () { t.remove(); }, 300); }
    t.addEventListener('click', ocultar);
    setTimeout(ocultar, 4500);
  })();
</script>
<?php endif; ?>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const flash = document.getElementById("flashConfig");
    if (flash) {
      setTimeout(() => {
        flash.style.transition = "opacity .4s, transform .4s";
        flash.style.opacity = "0";
        flash.style.transform = "translateY(-8px)";
        setTimeout(() => flash.remove(), 400);
      }, 4000);
    }

    const flashSol = document.getElementById("flashSolicitud");
    if (flashSol) {
      setTimeout(() => {
        flashSol.style.transition = "opacity .4s, transform .4s";
        flashSol.style.opacity = "0";
        flashSol.style.transform = "translateY(-8px)";
        setTimeout(() => flashSol.remove(), 400);
      }, 4000);
    }
  });
</script>

</body>
</html>