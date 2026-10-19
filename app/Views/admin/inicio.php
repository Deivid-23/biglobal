<?php
/** Inicio del panel admin. Variables: $stats, $cursosPorMes, $ultimosUsuarios, $ultimosCursos, $pendientesN, $usuarioPanel */
$etiquetasRol = ["admin" => "chip", "superadmin" => "chip chip--super", "docente" => "chip chip--logro", "estudiante" => "chip chip--exito"];
?>

<section class="pnl-bienvenida" aria-labelledby="bienvenida-titulo">
    <div>
        <h1 id="bienvenida-titulo">Hola, <?= e($usuarioPanel["primer"]) ?></h1>
        <p>Gestiona usuarios, cursos e idiomas de BiGlobal desde un solo lugar.</p>
    </div>
    <div class="pnl-bienvenida__acciones">
        <?php if ($pendientesN > 0): ?>
            <a class="boton boton--primario" href="index.php?page=solicitudes">
                <i class="fa-solid fa-user-clock" aria-hidden="true"></i>
                Revisar <?= e(plural_panel($pendientesN, "solicitud", "solicitudes")) ?>
            </a>
        <?php endif; ?>
        <a class="boton boton--secundario" href="index.php?page=cursos">Ver cursos</a>
    </div>
</section>

<section class="pnl-datos" aria-label="Resumen">
    <a class="pnl-dato" href="index.php?page=usuarios">
        <span class="pnl-dato__icono"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= $stats["usuarios"] ?></span><br><span class="pnl-dato__texto">Usuarios</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=cursos">
        <span class="pnl-dato__icono pnl-dato__icono--verde"><i class="fa-solid fa-book" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= $stats["cursos"] ?></span><br><span class="pnl-dato__texto">Cursos</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=usuarios">
        <span class="pnl-dato__icono pnl-dato__icono--naranja"><i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= $stats["docentes"] ?></span><br><span class="pnl-dato__texto">Docentes activos</span></span>
    </a>
    <a class="pnl-dato" href="index.php?page=lecciones">
        <span class="pnl-dato__icono pnl-dato__icono--celeste"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
        <span><span class="pnl-dato__numero"><?= $stats["lecciones"] ?></span><br><span class="pnl-dato__texto">Lecciones</span></span>
    </a>
</section>

<?= Panel::grafico("grafico-cursos-mes", "Cursos creados por mes", "Últimos 6 meses",
    array_column($cursosPorMes, "etiqueta"), array_column($cursosPorMes, "total"), "line") ?>

<div class="pnl-rejilla pnl-rejilla--2">
    <section class="pnl-tarjeta" aria-labelledby="ultimos-usuarios">
        <div class="pnl-tarjeta__cabecera">
            <div><h2 id="ultimos-usuarios">Últimos usuarios</h2><p>Registros recientes</p></div>
            <a href="index.php?page=usuarios">Ver todos</a>
        </div>
        <?php if (empty($ultimosUsuarios)): ?>
            <p class="texto-suave">Todavía no hay usuarios.</p>
        <?php else: ?>
            <ul class="pnl-lista pnl-lista--compacta">
                <?php foreach ($ultimosUsuarios as $u): ?>
                    <li class="pnl-item">
                        <span class="pnl-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u["nombre"], 0, 1))) ?></span>
                        <div class="pnl-item__cuerpo">
                            <p class="pnl-item__titulo"><?= e(trim($u["nombre"] . " " . ($u["apellido"] === "No especificado" ? "" : $u["apellido"]))) ?></p>
                            <p class="pnl-item__texto"><?= e($u["correo"]) ?></p>
                        </div>
                        <span class="<?= $etiquetasRol[$u["rol"]] ?? "chip" ?>"><?= e($u["rol"]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pnl-tarjeta" aria-labelledby="ultimos-cursos">
        <div class="pnl-tarjeta__cabecera">
            <div><h2 id="ultimos-cursos">Últimos cursos</h2><p>Catálogo reciente</p></div>
            <a href="index.php?page=cursos">Ver todos</a>
        </div>
        <?php if (empty($ultimosCursos)): ?>
            <p class="texto-suave">Todavía no hay cursos.</p>
        <?php else: ?>
            <ul class="pnl-lista pnl-lista--compacta">
                <?php foreach ($ultimosCursos as $cu): ?>
                    <li class="pnl-item">
                        <span class="pnl-dato__icono pnl-dato__icono--verde" aria-hidden="true"><i class="fa-solid fa-book"></i></span>
                        <div class="pnl-item__cuerpo">
                            <p class="pnl-item__titulo"><?= e($cu["nombre"]) ?></p>
                            <p class="pnl-item__texto"><?= e($cu["nivel"] ?: "Sin nivel") ?> · <?= e($cu["docente"] ?: "Sin docente") ?></p>
                        </div>
                        <?= (int) $cu["estado"] === 1 ? '<span class="chip chip--exito">Publicado</span>' : '<span class="chip chip--neutro">Borrador</span>' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
