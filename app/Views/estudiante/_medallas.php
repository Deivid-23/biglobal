<?php
/**
 * Medallas del estudiante: las ganadas en naranja y las que faltan en gris.
 * Variable: $gamificacion["medallas"] (de GamificacionModel::obtenerMedallas)
 */
$g = $gamificacion;
$ganadas = array_filter($g["medallas"], fn($m) => $m["fecha_ganada"] !== null);
?>

<?php if (!empty($g["medallas"])): ?>
    <section class="bloque" aria-labelledby="titulo-medallas">
        <div class="bloque__cabecera">
            <h2 id="titulo-medallas">Medallas</h2>
            <span class="texto-suave numeros"><?= count($ganadas) ?> de <?= count($g["medallas"]) ?></span>
        </div>

        <ul class="medallas">
            <?php foreach ($g["medallas"] as $m):
                $ganada = $m["fecha_ganada"] !== null;
            ?>
                <li class="medalla <?= $ganada ? "medalla--ganada" : "medalla--pendiente" ?>">
                    <span class="medalla__icono" aria-hidden="true">
                        <i class="fa-solid <?= e($m["icono"]) ?>"></i>
                    </span>
                    <span class="medalla__nombre"><?= e($m["nombre"]) ?></span>
                    <span class="medalla__texto"><?= e($m["descripcion"]) ?></span>
                    <span class="medalla__estado">
                        <?php if ($ganada): ?>
                            Ganada el <?= e(fechaCorta($m["fecha_ganada"])) ?>
                        <?php else: ?>
                            <i class="fa-solid fa-lock" aria-hidden="true"></i> Por ganar
                        <?php endif; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
