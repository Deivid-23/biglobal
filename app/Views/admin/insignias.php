<?php
/**
 * Insignias (admin).
 * Variables: $disponible, $insignias (con "ganadores"), $editar (o null), $form (valores del formulario), $niveles
 */
$csrf = generarCsrfToken();
$v = fn(string $k) => e($form[$k] ?? "");
$condicionesConMeta = implode(" ", array_diff(array_keys(InsigniaModel::CONDICIONES), ["ruta_nivel"]));
$nivelElegido = isset($form["nivel_ruta"]) ? (string) $form["nivel_ruta"] : (($form["idioma_id"] ?? null) ? $form["idioma_id"] . "-" . $form["nivel"] : "");

/** "Meta: 10 lecciones" o "A1 de Inglés" para la lista. */
$textoCondicion = function (array $m): string {
    if (!isset(InsigniaModel::CONDICIONES[$m["condicion"]])) {
        return "Sin condición (no se da sola)";
    }
    if ($m["condicion"] === "ruta_nivel") {
        return "Terminar el " . $m["nivel"] . " de " . ($m["idioma"] ?? "?");
    }
    return InsigniaModel::CONDICIONES[$m["condicion"]][0] . ": " . number_format((int) $m["meta"], 0, ",", ".");
};
?>

<header class="pnl-encabezado">
    <div>
        <h1>Insignias</h1>
        <p>Los estudiantes las ganan solos al cumplir la condición, y les llega un aviso a la campana. Se ven en su perfil.</p>
    </div>
</header>

<?php if (!$disponible): ?>
    <div class="aviso aviso--alerta">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span>Las insignias aún no están disponibles.</span>
    </div>
<?php return; endif; ?>

<div class="pnl-rejilla pnl-rejilla--ancha">
    <section class="pnl-tarjeta" aria-labelledby="titulo-lista-insignias">
        <div class="pnl-tarjeta__cabecera">
            <h2 id="titulo-lista-insignias">Todas (<?= count($insignias) ?>)</h2>
            <p><?= count(array_filter($insignias, fn($m) => (int) $m["activa"] === 1)) ?> activas</p>
        </div>
        <ul class="pnl-lista pnl-lista--compacta">
            <?php foreach ($insignias as $m): $activa = (int) $m["activa"] === 1; ?>
                <li class="pnl-item<?= $activa ? "" : " insignia--inactiva" ?>" id="insignia-<?= (int) $m["id"] ?>">
                    <span class="insignia-icono insignia-icono--<?= e($m["color"]) ?>" aria-hidden="true"><i class="fa-solid <?= e($m["icono"]) ?>"></i></span>
                    <div class="pnl-item__cuerpo">
                        <p class="pnl-item__titulo"><?= e($m["nombre"]) ?></p>
                        <p class="pnl-item__texto"><?= e($m["descripcion"]) ?></p>
                        <p class="pnl-item__texto">
                            <span class="chip"><?= e(InsigniaModel::CATEGORIAS[$m["categoria"]] ?? $m["categoria"]) ?></span>
                            <?= e($textoCondicion($m)) ?> ·
                            <?= e(plural_panel((int) $m["ganadores"], "estudiante la tiene", "estudiantes la tienen")) ?>
                            <?php if ((int) $m["secreta"] === 1): ?><span class="chip chip--neutro"><i class="fa-solid fa-eye-slash" aria-hidden="true"></i> Secreta</span><?php endif; ?>
                            <?php if (!$activa): ?><span class="chip chip--error">Desactivada</span><?php endif; ?>
                        </p>
                    </div>
                    <div class="pnl-item__acciones">
                        <a class="boton boton--secundario boton--sm" href="index.php?page=insignias&amp;editar=<?= (int) $m["id"] ?>#form-insignia">Editar</a>
                        <form method="POST" action="insignias_acciones.php"
                              <?= $activa ? 'data-confirmar="¿Desactivar «' . e($m["nombre"]) . '»? Ya no se dará ni se mostrará; quienes la tienen la conservan."' : "" ?>>
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="accion" value="activar">
                            <input type="hidden" name="insignia_id" value="<?= (int) $m["id"] ?>">
                            <input type="hidden" name="activa" value="<?= $activa ? "0" : "1" ?>">
                            <button class="boton boton--fantasma boton--sm" type="submit"><?= $activa ? "Desactivar" : "Activar" ?></button>
                        </form>
                        <?php if ($activa && isset(InsigniaModel::CONDICIONES[$m["condicion"]])): ?>
                            <form method="POST" action="insignias_acciones.php"
                                  data-confirmar="¿Dar «<?= e($m["nombre"]) ?>» ahora a todos los estudiantes que ya cumplen la condición? Les llegará un aviso a la campana.">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="accion" value="dar_ahora">
                                <input type="hidden" name="insignia_id" value="<?= (int) $m["id"] ?>">
                                <button class="boton boton--fantasma boton--sm" type="submit"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Dar ahora</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="pnl-tarjeta" id="form-insignia" aria-labelledby="titulo-form-insignia">
        <div class="pnl-tarjeta__cabecera">
            <h2 id="titulo-form-insignia"><?= $editar ? "Editar insignia" : "Nueva insignia" ?></h2>
            <?php if ($editar): ?><a href="index.php?page=insignias#form-insignia">Cancelar</a><?php endif; ?>
        </div>
        <form class="pnl-form" method="POST" action="insignias_acciones.php" data-tipos>
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="insignia_id" value="<?= (int) ($editar["id"] ?? 0) ?>">
            <div class="campo">
                <label class="campo__etiqueta" for="i-nombre">Nombre</label>
                <input class="entrada" id="i-nombre" name="nombre" maxlength="100" required value="<?= $v("nombre") ?>" placeholder="Ej. En camino">
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="i-desc">Descripción</label>
                <input class="entrada" id="i-desc" name="descripcion" maxlength="255" required value="<?= $v("descripcion") ?>"
                       placeholder="Ej. Completaste 10 lecciones de la ruta">
            </div>
            <div class="pnl-form__fila">
                <div class="campo">
                    <label class="campo__etiqueta" for="i-categoria">Categoría</label>
                    <select class="entrada" id="i-categoria" name="categoria">
                        <?php foreach (InsigniaModel::CATEGORIAS as $clave => $texto): ?>
                            <option value="<?= $clave ?>" <?= ($form["categoria"] ?? "") === $clave ? "selected" : "" ?>><?= e($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="i-color">Color</label>
                    <select class="entrada" id="i-color" name="color">
                        <?php foreach (InsigniaModel::COLORES as $clave => $texto): ?>
                            <option value="<?= $clave ?>" <?= ($form["color"] ?? "") === $clave ? "selected" : "" ?>><?= e($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="i-condicion">Se gana con…</label>
                <select class="entrada" id="i-condicion" name="condicion" data-tipo-selector>
                    <?php foreach (InsigniaModel::CONDICIONES as $clave => [$texto]): ?>
                        <option value="<?= $clave ?>" <?= ($form["condicion"] ?? "") === $clave ? "selected" : "" ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo" data-para-tipo="<?= e($condicionesConMeta) ?>">
                <label class="campo__etiqueta" for="i-meta">Meta</label>
                <input class="entrada" type="number" id="i-meta" name="meta" min="1" max="100000" value="<?= (int) ($form["meta"] ?? 1) ?>"
                       aria-describedby="i-meta-ayuda">
                <p class="campo__ayuda" id="i-meta-ayuda">Cuánto hace falta. Ej. 10 lecciones, 7 días de racha, 1.000 XP.</p>
            </div>
            <div class="campo" data-para-tipo="ruta_nivel">
                <label class="campo__etiqueta" for="i-nivel">Nivel de la ruta</label>
                <select class="entrada" id="i-nivel" name="nivel_ruta">
                    <?php foreach ($niveles as $n): $valor = (int) $n["idioma_id"] . "-" . $n["codigo"]; ?>
                        <option value="<?= e($valor) ?>" <?= $nivelElegido === $valor ? "selected" : "" ?>><?= e($n["idioma"]) ?> · <?= e($n["codigo"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pnl-form__fila">
                <div class="campo">
                    <label class="campo__etiqueta" for="i-icono">Ícono</label>
                    <input class="entrada" id="i-icono" name="icono" maxlength="43" required value="<?= $v("icono") ?>"
                           pattern="fa-[a-z0-9-]{2,40}" aria-describedby="i-icono-ayuda" placeholder="fa-star">
                    <p class="campo__ayuda" id="i-icono-ayuda">Nombre de Font Awesome (gratis), ej. fa-star, fa-fire, fa-trophy.</p>
                </div>
                <div class="campo">
                    <label class="campo__etiqueta" for="i-orden">Orden</label>
                    <input class="entrada" type="number" id="i-orden" name="orden" min="1" max="9999" value="<?= (int) ($form["orden"] ?? 100) ?>">
                </div>
            </div>
            <label class="pnl-interruptor">
                <span><strong>Secreta</strong><small>No se muestra al estudiante hasta que la gana.</small></span>
                <input type="checkbox" name="secreta" value="1" <?= !empty($form["secreta"]) ? "checked" : "" ?>>
            </label>
            <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit"><?= $editar ? "Guardar insignia" : "Crear insignia" ?></button></div>
        </form>
    </section>
</div>
