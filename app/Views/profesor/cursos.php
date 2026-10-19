<?php
/** Mis cursos (crear + lista). Variables: $cursosDocente, $csrfToken */
?>

<header class="pnl-encabezado">
    <div>
        <h1>Mis cursos</h1>
        <p><?= e(plural_panel(count($cursosDocente), "curso creado", "cursos creados")) ?>.</p>
    </div>
</header>

<div class="pnl-rejilla pnl-rejilla--ancha">
    <section class="pnl-tarjeta" aria-labelledby="titulo-lista">
        <div class="pnl-tarjeta__cabecera"><h2 id="titulo-lista">Tus cursos</h2></div>
        <?php if (empty($cursosDocente)): ?>
            <p class="texto-suave">Aún no has creado ningún curso. Usa el formulario para crear el primero.</p>
        <?php else: ?>
            <ul class="pnl-lista">
                <?php foreach ($cursosDocente as $c): ?>
                    <li class="pnl-item">
                        <span class="pnl-dato__icono" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($c["nombre"] ?? "C", 0, 2))) ?></span>
                        <div class="pnl-item__cuerpo">
                            <p class="pnl-item__titulo"><?= e($c["nombre"]) ?></p>
                            <p class="pnl-item__texto"><?= e($c["nivel"] ?: "Sin nivel") ?> · creado el <?= e(fecha_panel($c["fecha_creacion"])) ?></p>
                        </div>
                        <a class="boton boton--secundario boton--sm" href="index.php?page=curso&amp;id=<?= (int) $c["id"] ?>">Administrar</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="pnl-tarjeta" aria-labelledby="titulo-nuevo">
        <div class="pnl-tarjeta__cabecera"><div><h2 id="titulo-nuevo">Crear curso</h2><p>Después le agregas lecciones</p></div></div>
        <form class="pnl-form" method="POST" action="acciones.php">
            <input type="hidden" name="accion" value="crear_curso">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="campo">
                <label class="campo__etiqueta" for="c-nombre">Nombre del curso</label>
                <input class="entrada" id="c-nombre" name="nombre" maxlength="150" required placeholder="Ej. Inglés profesional">
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="c-desc">Descripción</label>
                <textarea class="entrada" id="c-desc" name="descripcion" rows="3" placeholder="¿Qué aprenderán tus estudiantes?"></textarea>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="c-idioma">Idioma</label>
                <select class="entrada" id="c-idioma" name="idioma_id">
                    <option value="">Sin idioma</option>
                    <?php foreach ($idiomasActivos as $idi): ?>
                        <option value="<?= (int) $idi["id"] ?>"><?= e($idi["nombre"]) ?> (<?= e($idi["codigo"]) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="c-nivel">Nivel</label>
                <select class="entrada" id="c-nivel" name="nivel" required>
                    <option value="">Elige un nivel</option>
                    <option value="Básico">Básico</option>
                    <option value="Intermedio">Intermedio</option>
                    <option value="Avanzado">Avanzado</option>
                </select>
            </div>
            <div class="pnl-form__acciones"><button class="boton boton--primario" type="submit"><i class="fa-solid fa-plus" aria-hidden="true"></i> Crear curso</button></div>
        </form>
    </section>
</div>
