<?php
/**
 * Un grupo del estudiante: tablón, tareas o material.
 * Variables: $grupo, $tab, $anuncios, $material, $tareasDisponibles,
 *            $tareas, $tarea, $entrega, $adjuntosTarea, $adjuntosEntrega
 */
$grupoId = (int) $grupo["id"];

// Para las piezas compartidas
$accionUrl = "grupo.php";
$usuarioId = (int) $_SESSION["usuario_id"];
$esDocente = false;
$archivado = false;
?>
<a class="enlace-atras" href="index.php?page=grupos"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Mis grupos</a>

<header class="encabezado-pagina">
    <h1><?= e($grupo["nombre"]) ?></h1>
    <p>
        <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i> <?= e($grupo["docente"]) ?>
        · <?= e(plural((int) $grupo["miembros"], "estudiante", "estudiantes")) ?>
        <?php if ($grupo["curso"]): ?>
            · <a href="index.php?page=curso&amp;id=<?= (int) $grupo["curso_id"] ?>"><?= e($grupo["curso"]) ?></a>
        <?php endif; ?>
    </p>
    <?php if ($grupo["descripcion"]): ?><p><?= nl2br(e($grupo["descripcion"])) ?></p><?php endif; ?>
</header>

<nav class="grupo-pestanas" aria-label="Secciones del grupo">
    <?php
    $pestanas = ["tablon" => ["Tablón", "fa-bullhorn"], "tareas" => ["Tareas", "fa-clipboard-list"], "material" => ["Material", "fa-folder-open"]];
    if (!$tareasDisponibles) {
        unset($pestanas["tareas"]);
    }
    foreach ($pestanas as $clave => [$texto, $icono]): ?>
        <a class="grupo-pestana <?= $tab === $clave ? "es-activa" : "" ?>"
           href="index.php?page=grupo&amp;id=<?= $grupoId ?><?= $clave !== "tablon" ? "&amp;tab=" . $clave : "" ?>"
           <?= $tab === $clave ? 'aria-current="page"' : "" ?>>
            <i class="fa-solid <?= $icono ?>" aria-hidden="true"></i> <?= $texto ?>
        </a>
    <?php endforeach; ?>
</nav>

<section class="grupo-seccion" aria-label="<?= ["tablon" => "Anuncios", "tareas" => "Tareas", "material" => "Material"][$tab] ?>">
    <?php
    if ($tab === "tareas") {
        require __DIR__ . "/_tareas.php";
    } else {
        require __DIR__ . "/../grupos/" . ($tab === "material" ? "_material.php" : "_tablon.php");
    }
    ?>
</section>

<form class="grupo-salir" method="POST" action="grupo.php"
      data-confirmar="¿Salir de «<?= e($grupo["nombre"]) ?>»? Para volver necesitarás el código.">
    <?= campoCsrf() ?>
    <input type="hidden" name="accion" value="salir">
    <input type="hidden" name="grupo_id" value="<?= $grupoId ?>">
    <button class="boton boton--fantasma boton--sm" type="submit">
        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Salir del grupo
    </button>
</form>
