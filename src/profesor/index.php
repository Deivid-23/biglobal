<?php

require_once __DIR__ . "/../../app/bootstrap.php";

protegerRol("docente");

$conexion = Conexion::conectar();
$docenteId = (int) $_SESSION["usuario_id"];

/* =========================================================
   DATOS DEL DOCENTE
========================================================= */
$stmt = $conexion->prepare("SELECT nombre, apellido, correo FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([$docenteId]);
$docente = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    "nombre"   => $_SESSION["nombre"]   ?? "Docente",
    "apellido" => $_SESSION["apellido"] ?? "",
    "correo"   => $_SESSION["correo"]   ?? "",
];

$nombreDocente = trim($docente["nombre"] . " " . $docente["apellido"]);
$avatarDocente = strtoupper(substr($docente["nombre"] ?? "D", 0, 1));

/* =========================================================
   HELPER DE CONTEO
========================================================= */
function contarSeguroDoc(PDO $c, string $sql): int {
    try { return (int) ($c->query($sql)->fetch()["t"] ?? 0); }
    catch (PDOException $e) { return 0; }
}

/* =========================================================
   FLASH
========================================================= */
$flash = $_SESSION["flash_docente"] ?? null;
unset($_SESSION["flash_docente"]);

/* =========================================================
   PÁGINA ACTUAL
========================================================= */
$pagina = $_GET["page"] ?? "inicio";
$paginasValidas = ["inicio", "cursos", "curso", "lecciones", "actividades"];
if (!in_array($pagina, $paginasValidas, true)) $pagina = "inicio";

$cursoIdActual = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

/* =========================================================
   ESTADÍSTICAS
========================================================= */
$totalCursos      = contarSeguroDoc($conexion, "SELECT COUNT(*) AS t FROM cursos WHERE instructor_id = $docenteId");
$totalLecciones   = contarSeguroDoc($conexion, "
    SELECT COUNT(*) AS t FROM lecciones l
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
");
$totalActividades = contarSeguroDoc($conexion, "
    SELECT COUNT(*) AS t FROM actividades a
    INNER JOIN lecciones l ON a.leccion_id = l.id
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
");

/* =========================================================
   LISTADOS
========================================================= */
$cursosDocente = $conexion->query("
    SELECT id, nombre, descripcion, nivel, fecha_creacion
    FROM cursos
    WHERE instructor_id = $docenteId
    ORDER BY fecha_creacion DESC
")->fetchAll(PDO::FETCH_ASSOC);

$leccionesDocente = $conexion->query("
    SELECT l.id, l.titulo, l.descripcion, l.orden, c.nombre AS curso, c.id AS curso_id
    FROM lecciones l
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
    ORDER BY c.nombre ASC, l.orden ASC
")->fetchAll(PDO::FETCH_ASSOC);

$actividadesDocente = $conexion->query("
    SELECT a.id, a.titulo, a.tipo, a.descripcion, l.titulo AS leccion, c.nombre AS curso
    FROM actividades a
    INNER JOIN lecciones l ON a.leccion_id = l.id
    INNER JOIN cursos c ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
    ORDER BY a.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   CURSO ACTUAL
========================================================= */
$cursoActual = null;
$leccionesCurso = [];

if ($cursoIdActual > 0) {

    $stmt = $conexion->prepare("
        SELECT * FROM cursos
        WHERE id = :id AND instructor_id = :docente
        LIMIT 1
    ");
    $stmt->execute([":id" => $cursoIdActual, ":docente" => $docenteId]);
    $cursoActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cursoActual) {
        $_SESSION["flash_docente"] = [
            "tipo" => "error",
            "mensaje" => "El curso no existe o no tienes permiso para verlo."
        ];
        header("Location: index.php?page=cursos");
        exit;
    }

    $stmt = $conexion->prepare("
        SELECT * FROM lecciones
        WHERE curso_id = :curso_id
        ORDER BY orden ASC, id ASC
    ");
    $stmt->execute([":curso_id" => $cursoIdActual]);
    $leccionesCurso = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* =========================================================
   GRÁFICO
========================================================= */
$leccionesPorCurso = $conexion->query("
    SELECT c.nombre AS curso, COUNT(l.id) AS total
    FROM cursos c
    LEFT JOIN lecciones l ON l.curso_id = c.id
    WHERE c.instructor_id = $docenteId
    GROUP BY c.id, c.nombre
    ORDER BY total DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$labelsLecciones  = array_map(fn($r) => $r["curso"], $leccionesPorCurso);
$valoresLecciones = array_map(fn($r) => (int) $r["total"], $leccionesPorCurso);

/* =========================================================
   CSRF
========================================================= */
$csrfToken = generarCsrfToken();

/* =========================================================
   MENÚ
========================================================= */
$menu = [
    "PRINCIPAL" => [
        ["page"=>"inicio",      "label"=>"Inicio",      "icon"=>"fa-house"],
        ["page"=>"cursos",      "label"=>"Mis cursos",  "icon"=>"fa-book"],
        ["page"=>"lecciones",   "label"=>"Lecciones",   "icon"=>"fa-book-open"],
        ["page"=>"actividades", "label"=>"Actividades", "icon"=>"fa-list-check"],
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BiGlobal | Panel Docente</title>

<!-- Chart.js (CDN) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Tailwind local (compartido con admin) -->
<script src="../admin/assets/js/tailwind.js"></script>
<script>
  if (typeof tailwind !== 'undefined') {
    tailwind.config = { darkMode: 'class' };
  }
</script>

<!-- Aplicar tema guardado antes de renderizar -->
<script>
  (function () {
    const saved = localStorage.getItem('biglobal-theme') || 'dark';
    if (saved === 'dark') document.documentElement.classList.add('dark');
  })();
</script>

<!-- Fuentes e iconos -->
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- CSS propio del docente -->
<link rel="stylesheet" href="css/docente.css">
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
          $activo = ($pagina === $item["page"]) ? "active" : "";
        ?>
          <a href="?page=<?= urlencode($item["page"]) ?>" class="nav-link <?= $activo ?> flex items-center gap-3 px-3 py-2 rounded-lg">
            <i class="fa-solid <?= htmlspecialchars($item["icon"]) ?> w-4 text-center"></i>
            <span><?= htmlspecialchars($item["label"]) ?></span>
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
          <?= htmlspecialchars($avatarDocente) ?>
        </div>
        <div class="flex-1 leading-tight min-w-0">
          <p class="txt text-[13px] font-semibold truncate"><?= htmlspecialchars($nombreDocente) ?></p>
          <p class="txt-muted text-[11px]">Docente</p>
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

      <div>
        <p class="txt-muted text-[11px] uppercase tracking-wider font-bold">Panel docente</p>
        <p class="txt font-bold text-[15px]"><?= htmlspecialchars(ucfirst($pagina)) ?></p>
      </div>

      <div class="ml-auto flex items-center gap-3">
        <button id="themeToggle" type="button"
                class="w-9 h-9 rounded-lg surface border divider txt-muted hover:opacity-80 flex items-center justify-center"
                title="Cambiar tema">
          <i id="themeIcon" class="fa-solid fa-sun text-sm"></i>
        </button>

        <div class="flex items-center gap-2 pl-3 border-l divider">
          <div class="w-8 h-8 rounded-full grad-accent flex items-center justify-center text-white text-xs font-bold">
            <?= htmlspecialchars($avatarDocente) ?>
          </div>
          <span class="txt text-sm font-semibold hidden md:inline"><?= htmlspecialchars($nombreDocente) ?></span>
        </div>
      </div>
    </header>

    <!-- CONTENIDO -->
    <main class="p-4 sm:p-6 space-y-5 max-w-[1500px]">

      <!-- FLASH -->
      <?php if ($flash): ?>
        <div id="flashDocente"
             class="card p-4 flex items-center gap-3 border-l-4"
             style="border-left-color: <?= $flash['tipo'] === 'ok' ? '#10b981' : '#ef4444' ?>;">
          <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0
                       <?= $flash['tipo'] === 'ok' ? 'bg-emerald-500/15 text-emerald-500' : 'bg-red-500/15 text-red-500' ?>">
            <i class="fa-solid <?= $flash['tipo'] === 'ok' ? 'fa-check' : 'fa-triangle-exclamation' ?>"></i>
          </span>
          <p class="txt text-sm flex-1"><?= htmlspecialchars($flash["mensaje"]) ?></p>
          <button onclick="document.getElementById('flashDocente').remove()" class="txt-muted hover:opacity-70">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
      <?php endif; ?>

      <?php /* ============================================
             INICIO
      ============================================ */ ?>
      <?php if ($pagina === "inicio"): ?>

        <div class="grad-welcome rounded-2xl p-6 flex flex-col lg:flex-row items-start lg:items-center gap-6 relative overflow-hidden">
          <div class="absolute -right-20 -top-24 w-80 h-80 rounded-full bg-white/10"></div>

          <div class="relative z-10 flex-1">
            <p class="text-white/80 text-xs uppercase font-bold tracking-widest mb-1">Panel docente</p>
            <h2 class="text-white text-2xl font-extrabold mb-1">Hola, <?= htmlspecialchars($docente["nombre"]) ?> 👋</h2>
            <p class="text-white/85 text-sm">Gestiona tus cursos, lecciones y actividades desde un solo lugar.</p>
          </div>

          <div class="relative z-10 grid grid-cols-3 gap-3 w-full lg:w-auto">
            <div class="bg-white/15 backdrop-blur rounded-xl p-3 text-center min-w-[100px]">
              <p class="text-white text-lg font-bold"><?= $totalCursos ?></p>
              <p class="text-white/80 text-[10px] uppercase">Cursos</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl p-3 text-center min-w-[100px]">
              <p class="text-white text-lg font-bold"><?= $totalLecciones ?></p>
              <p class="text-white/80 text-[10px] uppercase">Lecciones</p>
            </div>
            <div class="bg-white/15 backdrop-blur rounded-xl p-3 text-center min-w-[100px]">
              <p class="text-white text-lg font-bold"><?= $totalActividades ?></p>
              <p class="text-white/80 text-[10px] uppercase">Actividades</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <a href="?page=cursos" class="card p-5 block hover:opacity-95 transition">
            <div class="w-9 h-9 rounded-lg chip flex items-center justify-center mb-3"><i class="fa-solid fa-book text-sm"></i></div>
            <p class="txt text-2xl font-extrabold"><?= $totalCursos ?></p>
            <p class="txt-muted text-xs">Cursos creados</p>
          </a>
          <a href="?page=lecciones" class="card p-5 block hover:opacity-95 transition">
            <div class="w-9 h-9 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center mb-3"><i class="fa-solid fa-book-open text-sm"></i></div>
            <p class="txt text-2xl font-extrabold"><?= $totalLecciones ?></p>
            <p class="txt-muted text-xs">Lecciones</p>
          </a>
          <a href="?page=actividades" class="card p-5 block hover:opacity-95 transition">
            <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center mb-3"><i class="fa-solid fa-list-check text-sm"></i></div>
            <p class="txt text-2xl font-extrabold"><?= $totalActividades ?></p>
            <p class="txt-muted text-xs">Actividades</p>
          </a>
          <div class="card p-5">
            <div class="w-9 h-9 rounded-lg bg-cyan-500/15 text-cyan-500 flex items-center justify-center mb-3"><i class="fa-solid fa-users text-sm"></i></div>
            <p class="txt text-2xl font-extrabold">0</p>
            <p class="txt-muted text-xs">Estudiantes</p>
          </div>
        </div>

        <?php if (!empty($leccionesPorCurso)): ?>
          <div class="card p-5">
            <h3 class="txt font-bold text-[15px] mb-1">Lecciones por curso</h3>
            <p class="txt-muted text-xs mb-4">Tus cursos con más contenido</p>
            <div style="height: 300px;">
              <canvas id="chartLecciones"
                      data-labels='<?= htmlspecialchars(json_encode($labelsLecciones, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'
                      data-valores='<?= htmlspecialchars(json_encode($valoresLecciones, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>'></canvas>
            </div>
          </div>
        <?php endif; ?>

      <?php /* ============================================
             CURSOS
      ============================================ */ ?>
      <?php elseif ($pagina === "cursos"): ?>

        <div class="card p-5">
          <div class="mb-4">
            <h3 class="txt font-bold text-[16px]">Crear nuevo curso</h3>
            <p class="txt-muted text-xs">Completa la información para crear un nuevo curso.</p>
          </div>

          <form method="POST" action="acciones.php" class="space-y-4 max-w-2xl">
            <input type="hidden" name="accion" value="crear_curso">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div>
              <label class="txt-muted text-xs font-semibold">Nombre del curso</label>
              <input name="nombre" required placeholder="Ej. Inglés Profesional"
                     class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
            </div>

            <div>
              <label class="txt-muted text-xs font-semibold">Descripción</label>
              <textarea name="descripcion" rows="3" placeholder="Escribe una breve descripción..."
                        class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
            </div>

            <div>
              <label class="txt-muted text-xs font-semibold">Nivel</label>
              <select name="nivel" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                <option value="">Selecciona un nivel</option>
                <option value="Básico">Básico</option>
                <option value="Intermedio">Intermedio</option>
                <option value="Avanzado">Avanzado</option>
              </select>
            </div>

            <button type="submit" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
              <i class="fa-solid fa-check"></i> Guardar curso
            </button>
          </form>
        </div>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="txt font-bold text-[16px]">Mis cursos</h3>
              <p class="txt-muted text-xs"><?= count($cursosDocente) ?> cursos creados</p>
            </div>
          </div>

          <?php if (empty($cursosDocente)): ?>
            <p class="txt-muted text-sm text-center py-10">Aún no has creado ningún curso.</p>
          <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <?php foreach ($cursosDocente as $c): ?>
                <div class="p-4 rounded-lg surface border divider flex flex-col gap-3">
                  <div class="flex items-start gap-3">
                    <div class="w-12 h-12 rounded-lg grad-accent flex items-center justify-center text-white font-bold flex-shrink-0">
                      <?= htmlspecialchars(strtoupper(substr($c["nombre"] ?? "C", 0, 2))) ?>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="txt font-semibold truncate"><?= htmlspecialchars($c["nombre"] ?? "") ?></p>
                      <p class="txt-muted text-[11px]">Nivel: <?= htmlspecialchars($c["nivel"] ?? "—") ?></p>
                    </div>
                  </div>

                  <p class="txt-muted text-xs line-clamp-2">
                    <?= htmlspecialchars($c["descripcion"] ?? "") ?>
                  </p>

                  <div class="flex items-center justify-between pt-3 border-t divider">
                    <span class="txt-muted text-[11px]"><?= htmlspecialchars($c["fecha_creacion"] ?? "") ?></span>
                    <a href="?page=curso&id=<?= (int) $c["id"] ?>"
                       class="text-xs font-semibold" style="color: var(--accent)">
                      Administrar →
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      <?php /* ============================================
             CURSO DETALLE
      ============================================ */ ?>
      <?php elseif ($pagina === "curso"): ?>

        <div class="card p-5">
          <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
              <p class="txt-muted text-[11px] uppercase tracking-wider font-bold">Curso</p>
              <h3 class="txt font-bold text-[18px]"><?= htmlspecialchars($cursoActual["nombre"] ?? "") ?></h3>
              <p class="txt-muted text-xs mt-1"><?= htmlspecialchars($cursoActual["descripcion"] ?? "") ?></p>
            </div>
            <a href="?page=cursos" class="px-4 py-2 rounded-lg text-sm surface border divider txt-muted hover:opacity-80">
              ← Volver
            </a>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-4 rounded-lg surface border divider">
              <p class="txt-muted text-[10px] uppercase">Nivel</p>
              <p class="txt font-bold text-lg"><?= htmlspecialchars($cursoActual["nivel"] ?? "—") ?></p>
            </div>
            <div class="p-4 rounded-lg surface border divider">
              <p class="txt-muted text-[10px] uppercase">Lecciones</p>
              <p class="txt font-bold text-lg"><?= count($leccionesCurso) ?></p>
            </div>
            <div class="p-4 rounded-lg surface border divider">
              <p class="txt-muted text-[10px] uppercase">Estudiantes</p>
              <p class="txt font-bold text-lg">0</p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <a href="?page=lecciones&id=<?= (int) $cursoActual["id"] ?>"
             class="card p-5 flex items-center gap-4 hover:opacity-95 transition">
            <div class="w-12 h-12 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center">
              <i class="fa-solid fa-book-open text-lg"></i>
            </div>
            <div class="flex-1">
              <p class="txt font-bold">Lecciones</p>
              <p class="txt-muted text-xs">Crea y administra las lecciones</p>
            </div>
            <i class="fa-solid fa-arrow-right txt-muted"></i>
          </a>

          <a href="?page=actividades&id=<?= (int) $cursoActual["id"] ?>"
             class="card p-5 flex items-center gap-4 hover:opacity-95 transition">
            <div class="w-12 h-12 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center">
              <i class="fa-solid fa-list-check text-lg"></i>
            </div>
            <div class="flex-1">
              <p class="txt font-bold">Actividades</p>
              <p class="txt-muted text-xs">Crea actividades para tus estudiantes</p>
            </div>
            <i class="fa-solid fa-arrow-right txt-muted"></i>
          </a>
        </div>

      <?php /* ============================================
             LECCIONES
      ============================================ */ ?>
      <?php elseif ($pagina === "lecciones"): ?>

        <?php if ($cursoActual): ?>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
              <div>
                <p class="txt-muted text-[11px] uppercase tracking-wider font-bold">Lecciones de</p>
                <h3 class="txt font-bold text-[18px]"><?= htmlspecialchars($cursoActual["nombre"]) ?></h3>
              </div>
              <a href="?page=curso&id=<?= (int) $cursoActual["id"] ?>" class="px-4 py-2 rounded-lg text-sm surface border divider txt-muted hover:opacity-80">
                ← Volver al curso
              </a>
            </div>

            <form method="POST" action="acciones.php" class="space-y-4 max-w-2xl">
              <input type="hidden" name="accion" value="crear_leccion">
              <input type="hidden" name="curso_id" value="<?= (int) $cursoActual["id"] ?>">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

              <div>
                <label class="txt-muted text-xs font-semibold">Título de la lección</label>
                <input name="titulo" required placeholder="Ej. Saludos y presentaciones"
                       class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
              </div>

              <div>
                <label class="txt-muted text-xs font-semibold">Descripción</label>
                <textarea name="descripcion" rows="2"
                          class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
              </div>

              <div>
                <label class="txt-muted text-xs font-semibold">Contenido</label>
                <textarea name="contenido" rows="5"
                          class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
              </div>

              <div class="max-w-[200px]">
                <label class="txt-muted text-xs font-semibold">Orden</label>
                <input type="number" name="orden" min="1" value="<?= count($leccionesCurso) + 1 ?>" required
                       class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
              </div>

              <button type="submit" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                <i class="fa-solid fa-check"></i> Guardar lección
              </button>
            </form>
          </div>

          <div class="card p-5">
            <h3 class="txt font-bold text-[16px] mb-4"><?= count($leccionesCurso) ?> lecciones creadas</h3>

            <?php if (empty($leccionesCurso)): ?>
              <p class="txt-muted text-sm text-center py-10">Aún no hay lecciones creadas para este curso.</p>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($leccionesCurso as $l): ?>
                  <div class="flex items-center gap-4 p-4 rounded-lg surface border divider">
                    <div class="w-10 h-10 rounded-lg grad-accent flex items-center justify-center text-white font-bold flex-shrink-0">
                      <?= (int) $l["orden"] ?>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="txt font-semibold truncate"><?= htmlspecialchars($l["titulo"] ?? "") ?></p>
                      <p class="txt-muted text-xs truncate"><?= htmlspecialchars($l["descripcion"] ?? "") ?></p>
                    </div>
                    <form method="POST" action="acciones.php" class="inline"
                          onsubmit="return confirm('¿Eliminar esta lección?');">
                      <input type="hidden" name="accion" value="eliminar_leccion">
                      <input type="hidden" name="leccion_id" value="<?= (int) $l["id"] ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <button type="submit" class="text-red-500 hover:opacity-70 text-sm">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        <?php else: ?>

          <div class="card p-5">
            <div class="mb-4">
              <h3 class="txt font-bold text-[16px]">Todas mis lecciones</h3>
              <p class="txt-muted text-xs"><?= count($leccionesDocente) ?> lecciones en todos tus cursos</p>
            </div>

            <?php if (empty($leccionesDocente)): ?>
              <p class="txt-muted text-sm text-center py-10">Aún no has creado lecciones. Ve a un curso para agregar.</p>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="w-full text-[13px]">
                  <thead>
                    <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                      <th class="text-left py-2.5 font-bold">ID</th>
                      <th class="text-left py-2.5 font-bold">Título</th>
                      <th class="text-left py-2.5 font-bold">Curso</th>
                      <th class="text-left py-2.5 font-bold">Orden</th>
                      <th class="text-left py-2.5 font-bold">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($leccionesDocente as $l): ?>
                      <tr class="border-b divider">
                        <td class="py-3 txt-muted">#<?= (int) $l["id"] ?></td>
                        <td class="py-3 txt font-semibold"><?= htmlspecialchars($l["titulo"] ?? "") ?></td>
                        <td class="py-3 txt-muted"><?= htmlspecialchars($l["curso"] ?? "") ?></td>
                        <td class="py-3 txt-muted"><?= (int) $l["orden"] ?></td>
                        <td class="py-3">
                          <a href="?page=lecciones&id=<?= (int) $l["curso_id"] ?>"
                             class="text-xs font-semibold" style="color: var(--accent)">
                            Ver curso
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

        <?php endif; ?>

      <?php /* ============================================
             ACTIVIDADES
      ============================================ */ ?>
      <?php elseif ($pagina === "actividades"): ?>

        <?php if ($cursoActual): ?>

          <div class="card p-5">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
              <div>
                <p class="txt-muted text-[11px] uppercase tracking-wider font-bold">Nueva actividad para</p>
                <h3 class="txt font-bold text-[18px]"><?= htmlspecialchars($cursoActual["nombre"]) ?></h3>
              </div>
              <a href="?page=curso&id=<?= (int) $cursoActual["id"] ?>" class="px-4 py-2 rounded-lg text-sm surface border divider txt-muted hover:opacity-80">
                ← Volver al curso
              </a>
            </div>

            <?php if (empty($leccionesCurso)): ?>
              <div class="p-4 rounded-lg" style="background: #fef3c7; color: #b45309;">
                <i class="fa-solid fa-info-circle"></i>
                Este curso aún no tiene lecciones. Primero crea una lección para poder agregarle actividades.
              </div>
            <?php else: ?>

              <form method="POST" action="acciones.php" class="space-y-4 max-w-2xl">
                <input type="hidden" name="accion" value="crear_actividad">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div>
                  <label class="txt-muted text-xs font-semibold">Lección</label>
                  <select name="leccion_id" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                    <option value="">Elige una lección</option>
                    <?php foreach ($leccionesCurso as $l): ?>
                      <option value="<?= (int) $l["id"] ?>">
                        #<?= (int) $l["orden"] ?> — <?= htmlspecialchars($l["titulo"]) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Título de la actividad</label>
                  <input name="titulo" required placeholder="Ej. Cuestionario de vocabulario"
                         class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Descripción</label>
                  <textarea name="descripcion" rows="3" placeholder="Describe la actividad..."
                            class="input w-full rounded-lg px-3 py-2 text-sm mt-1"></textarea>
                </div>

                <div>
                  <label class="txt-muted text-xs font-semibold">Tipo</label>
                  <select name="tipo" required class="input w-full rounded-lg px-3 py-2 text-sm mt-1">
                    <option value="">Elige un tipo</option>
                    <option value="Quiz">Quiz</option>
                    <option value="Tarea">Tarea</option>
                    <option value="Ejercicio">Ejercicio</option>
                    <option value="Proyecto">Proyecto</option>
                    <option value="Examen">Examen</option>
                  </select>
                </div>

                <button type="submit" class="grad-accent text-white text-sm font-semibold px-4 py-2 rounded-lg btn flex items-center gap-2">
                  <i class="fa-solid fa-check"></i> Guardar actividad
                </button>
              </form>

            <?php endif; ?>
          </div>

          <div class="card p-5">
            <h3 class="txt font-bold text-[16px] mb-4">Actividades de este curso</h3>

            <?php
              $actividadesCurso = [];
              if (!empty($leccionesCurso)) {
                  $idsLecciones = implode(",", array_map(fn($l) => (int) $l["id"], $leccionesCurso));
                  if ($idsLecciones) {
                      $actividadesCurso = $conexion->query("
                          SELECT a.id, a.titulo, a.tipo, a.descripcion, l.titulo AS leccion
                          FROM actividades a
                          INNER JOIN lecciones l ON a.leccion_id = l.id
                          WHERE a.leccion_id IN ($idsLecciones)
                          ORDER BY a.id DESC
                      ")->fetchAll(PDO::FETCH_ASSOC);
                  }
              }
            ?>

            <?php if (empty($actividadesCurso)): ?>
              <p class="txt-muted text-sm text-center py-10">Este curso aún no tiene actividades.</p>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($actividadesCurso as $a): ?>
                  <div class="flex items-center gap-4 p-4 rounded-lg surface border divider">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/15 text-amber-500 flex items-center justify-center flex-shrink-0">
                      <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p class="txt font-semibold truncate"><?= htmlspecialchars($a["titulo"]) ?></p>
                      <p class="txt-muted text-xs truncate">
                        <span class="chip px-2 py-0.5 rounded-full text-[10px] font-bold mr-2"><?= htmlspecialchars($a["tipo"]) ?></span>
                        <?= htmlspecialchars($a["leccion"]) ?>
                      </p>
                    </div>
                    <form method="POST" action="acciones.php" class="inline"
                          onsubmit="return confirm('¿Eliminar esta actividad?');">
                      <input type="hidden" name="accion" value="eliminar_actividad">
                      <input type="hidden" name="actividad_id" value="<?= (int) $a["id"] ?>">
                      <input type="hidden" name="curso_id" value="<?= (int) $cursoActual["id"] ?>">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                      <button type="submit" class="text-red-500 hover:opacity-70 text-sm">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        <?php else: ?>

          <div class="card p-5">
            <div class="mb-4">
              <h3 class="txt font-bold text-[16px]">Mis actividades</h3>
              <p class="txt-muted text-xs"><?= count($actividadesDocente) ?> actividades en todos tus cursos</p>
            </div>

            <?php if (empty($actividadesDocente)): ?>
              <p class="txt-muted text-sm text-center py-10">Aún no has creado actividades. Entra a un curso para agregar.</p>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="w-full text-[13px]">
                  <thead>
                    <tr class="text-[10.5px] uppercase tracking-wider border-b divider">
                      <th class="text-left py-2.5 font-bold">ID</th>
                      <th class="text-left py-2.5 font-bold">Título</th>
                      <th class="text-left py-2.5 font-bold">Tipo</th>
                      <th class="text-left py-2.5 font-bold">Lección</th>
                      <th class="text-left py-2.5 font-bold">Curso</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($actividadesDocente as $a): ?>
                      <tr class="border-b divider">
                        <td class="py-3 txt-muted">#<?= (int) $a["id"] ?></td>
                        <td class="py-3 txt font-semibold"><?= htmlspecialchars($a["titulo"] ?? "") ?></td>
                        <td class="py-3"><span class="chip px-2 py-0.5 rounded-full text-[10px] font-bold"><?= htmlspecialchars($a["tipo"] ?? "—") ?></span></td>
                        <td class="py-3 txt-muted"><?= htmlspecialchars($a["leccion"] ?? "") ?></td>
                        <td class="py-3 txt-muted"><?= htmlspecialchars($a["curso"] ?? "") ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

        <?php endif; ?>

      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Overlay móvil -->
<div id="sidebarOverlay"
     class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"
     style="backdrop-filter: blur(2px);"></div>

<!-- JS propio del docente -->
<script src="js/docente.js"></script>

</body>
</html>