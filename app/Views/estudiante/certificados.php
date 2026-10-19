<?php
/** Certificados obtenidos. Variables: $certificados */
?>
<header class="encabezado-pagina">
    <h1>Certificados</h1>
    <p>Recibes uno cada vez que completas todas las lecciones de un curso.</p>
</header>

<?php if (empty($certificados)): ?>
    <div class="vacio">
        <span class="vacio__icono vacio__icono--logro"><i class="fa-solid fa-award" aria-hidden="true"></i></span>
        <p class="vacio__titulo">Tu primer certificado te espera</p>
        <p class="vacio__texto">Completa todas las lecciones de un curso y aquí aparecerá tu certificado con su código.</p>
        <a class="boton boton--primario" href="index.php?page=mis-cursos">Ir a mis cursos</a>
    </div>
<?php else: ?>
    <ul class="certificados">
        <?php foreach ($certificados as $cert): ?>
            <li class="certificado">
                <span class="certificado__sello" aria-hidden="true"><i class="fa-solid fa-award"></i></span>
                <div class="certificado__cuerpo">
                    <p class="certificado__curso"><?= e($cert["curso_nombre"]) ?></p>
                    <p class="curso__meta"><?= e($cert["nivel"] ?: "Sin nivel") ?> · Emitido el <?= e(fechaCorta($cert["fecha_emision"])) ?></p>
                </div>
                <p class="certificado__codigo">
                    <span class="sr-only">Código de verificación:</span>
                    <code><?= e($cert["codigo"]) ?></code>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
