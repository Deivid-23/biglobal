<?php
/** Grupos de Classroom (todos). Variables: $disponible, $grupos */
?>
<header class="pnl-encabezado">
    <div>
        <h1>Grupos</h1>
        <p>Los crean los docentes. Los grupos son parte de Premium; aquí puedes hacer uno gratuito para todos (también entran los estudiantes del plan Gratuito) o quitarle esa marca.</p>
    </div>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Los grupos aún no están disponibles: falta aplicar <code>database/grupos_classroom.sql</code>.</span>
    </div>
<?php else: ?>
    <section class="pnl-tarjeta" aria-labelledby="tabla-grupos">
        <div class="pnl-tarjeta__cabecera"><h2 id="tabla-grupos"><?= e(plural_panel(count($grupos), "grupo", "grupos")) ?></h2></div>
        <?php if (!$grupos): ?>
            <p class="texto-suave">Ningún docente ha creado grupos todavía.</p>
        <?php else: ?>
            <div class="pnl-tabla-caja">
                <table class="pnl-tabla">
                    <thead><tr><th>Grupo</th><th>Docente</th><th>Estudiantes</th><th>Acceso</th><th class="col-acciones">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($grupos as $g): $gratuito = (int) $g["gratuito"] === 1; ?>
                            <tr>
                                <td>
                                    <span class="col-fuerte"><?= e($g["nombre"]) ?></span>
                                    <?php if ($g["curso"]): ?><span class="chip chip--neutro"><?= e($g["curso"]) ?></span><?php endif; ?>
                                    <?php if ((int) $g["archivado"] === 1): ?><span class="chip chip--neutro">Archivado</span><?php endif; ?>
                                </td>
                                <td class="col-suave"><?= e($g["docente"]) ?></td>
                                <td><?= (int) $g["miembros"] ?></td>
                                <td><span class="chip <?= $gratuito ? "chip--exito" : "chip--logro" ?>"><?= $gratuito ? "Gratuito para todos" : "Solo Premium" ?></span></td>
                                <td class="col-acciones">
                                    <form method="POST" action="index.php"
                                          data-confirmar="<?= $gratuito
                                              ? "¿Quitar «" . e($g["nombre"]) . "» de los gratuitos? Los miembros del plan Gratuito dejarán de verlo hasta tener Premium (no pierden su trabajo)."
                                              : "¿Hacer gratuito para todos el grupo «" . e($g["nombre"]) . "»? Podrán entrar también los estudiantes del plan Gratuito." ?>">
                                        <?= campoCsrf() ?>
                                        <input type="hidden" name="accion" value="gratuito_grupo">
                                        <input type="hidden" name="grupo_id" value="<?= (int) $g["id"] ?>">
                                        <input type="hidden" name="gratuito" value="<?= $gratuito ? 0 : 1 ?>">
                                        <button class="boton boton--secundario boton--sm" type="submit"><?= $gratuito ? "Quitar gratuito" : "Hacer gratuito" ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
