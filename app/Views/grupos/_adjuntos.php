<?php
/**
 * Lista de adjuntos de una tarea o una entrega (docente y estudiante).
 * Variables: $listaAdjuntos (filas de archivos sin contenido)
 * Se abren por ../grupos/archivo.php, que revisa permisos.
 */
?>
<?php if ($listaAdjuntos): ?>
    <ul class="grupo-adjuntos">
        <?php foreach ($listaAdjuntos as $adj): $urlAdj = "../grupos/archivo.php?id=" . (int) $adj["id"]; ?>
            <li>
                <?php if (str_starts_with((string) $adj["mime"], "audio/")): ?>
                    <span class="grupo-adjuntos__nombre"><i class="fa-solid fa-file-audio" aria-hidden="true"></i> <?= e($adj["nombre"]) ?></span>
                    <audio controls preload="none" src="<?= e($urlAdj) ?>"></audio>
                <?php else: ?>
                    <a class="grupo-adjuntos__nombre" href="<?= e($urlAdj) ?>" target="_blank" rel="noopener">
                        <i class="fa-solid <?= e(Archivo::icono($adj["mime"])) ?>" aria-hidden="true"></i> <?= e($adj["nombre"]) ?>
                    </a>
                <?php endif; ?>
                <small><?= e(Archivo::peso((int) $adj["tamano"])) ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
