<?php
/** Mis grupos: entrar con código y lista de grupos. Variables: $disponible, $grupos (con "permitido") */
?>
<header class="encabezado-pagina">
    <h1>Mis grupos</h1>
    <p>Los grupos de tus docentes: anuncios, comentarios y material de apoyo en un solo lugar.</p>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--info">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <span>Los grupos aún no están disponibles.</span>
    </div>
<?php else: ?>

    <section class="grupo-unirse" aria-labelledby="titulo-unirse">
        <div>
            <h2 id="titulo-unirse">Entrar a un grupo</h2>
            <p>Escribe el código de <?= GrupoModel::LARGO_CODIGO ?> caracteres que te dio tu docente. Los grupos con docentes son parte de Premium, salvo los que son gratuitos para todos.
                <?php if (!empty($puedeProbar)): ?><a href="index.php?page=plan#prueba">Prueba Premium gratis <?= Plan::diasPrueba() ?> días</a>.<?php endif; ?></p>
        </div>
        <form class="grupo-unirse__form" method="POST" action="grupo.php">
            <?= campoCsrf() ?>
            <input type="hidden" name="accion" value="unirse">
            <label class="sr-only" for="codigo-grupo">Código del grupo</label>
            <input class="entrada grupo-unirse__codigo" id="codigo-grupo" name="codigo" required
                   maxlength="12" autocomplete="off" autocapitalize="characters" spellcheck="false"
                   placeholder="Ej. K7M2QXP">
            <button class="boton boton--primario" type="submit">Entrar</button>
        </form>
    </section>

    <?php if (!$grupos): ?>
        <div class="vacio">
            <span class="vacio__icono" aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
            <p class="vacio__titulo">Todavía no estás en ningún grupo</p>
            <p class="vacio__texto">Cuando tu docente te comparta un código, escríbelo arriba para entrar.</p>
        </div>
    <?php else: ?>
        <ul class="grupos-lista">
            <?php foreach ($grupos as $g): ?>
                <li>
                    <a class="grupo-tarjeta" href="<?= $g["permitido"] ? "index.php?page=grupo&amp;id=" . (int) $g["id"] : "index.php?page=plan" ?>">
                        <span class="grupo-tarjeta__icono" aria-hidden="true"><i class="fa-solid fa-people-group"></i></span>
                        <span class="grupo-tarjeta__cuerpo">
                            <strong><?= e($g["nombre"]) ?></strong>
                            <small>
                                <i class="fa-solid fa-chalkboard-user" aria-hidden="true"></i> <?= e($g["docente"]) ?>
                                · <?= e(plural((int) $g["miembros"], "estudiante", "estudiantes")) ?>
                                <?php if ($g["curso"]): ?> · <?= e($g["curso"]) ?><?php endif; ?>
                            </small>
                            <?php if (!$g["permitido"]): ?><small><span class="chip chip--logro"><i class="fa-solid fa-crown" aria-hidden="true"></i> Premium</span> Este grupo es parte de Premium. Toca para ver el plan<?= !empty($puedeProbar) ? " y la prueba gratis" : "" ?>.</small>
                            <?php elseif ($g["ultimo_anuncio"]): ?><small>Último anuncio <?= e(hace_panel($g["ultimo_anuncio"])) ?></small><?php endif; ?>
                        </span>
                        <i class="fa-solid fa-chevron-right grupo-tarjeta__flecha" aria-hidden="true"></i>
                    </a>
                    <?php if (!$g["permitido"]): ?>
                        <!-- Sin Premium no entra al grupo, pero sí puede quitarlo de su lista -->
                        <form class="grupo-salir" method="POST" action="grupo.php"
                              data-confirmar="¿Salir de «<?= e($g["nombre"]) ?>»? Para volver necesitarás el código.">
                            <?= campoCsrf() ?>
                            <input type="hidden" name="accion" value="salir">
                            <input type="hidden" name="grupo_id" value="<?= (int) $g["id"] ?>">
                            <button class="boton boton--fantasma boton--sm" type="submit">
                                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Salir del grupo
                            </button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

<?php endif; ?>
