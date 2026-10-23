<?php
/**
 * Tarjeta "Foto de perfil" (Mi perfil de todos los roles).
 * Variables: $claseTarjeta ("perfil-tarjeta" en el estudiante, "pnl-tarjeta" en los paneles), $inicialesFoto
 *
 * js/foto_perfil.js recorta la foto en un cuadrado de 512 px (al centro)
 * antes de subirla. Sin JavaScript se sube tal cual (máx. 2 MB).
 */
if (!FotoPerfil::disponible()) {
    return;
}
$idFoto = (int) $_SESSION["usuario_id"];
$tieneFoto = FotoPerfil::url($idFoto) !== null;
?>
<section class="<?= e($claseTarjeta) ?> foto-perfil" id="foto" aria-labelledby="titulo-foto">
    <h2 id="titulo-foto">Foto de perfil</h2>
    <p class="campo__ayuda">Se muestra en tu cuenta y en tus grupos. JPG o PNG; la recortamos en un cuadrado al centro.</p>

    <div class="foto-perfil__cuerpo">
        <span class="foto-perfil__vista" data-foto-vista>
            <?= FotoPerfil::avatar($idFoto, $inicialesFoto, "foto-perfil__avatar") ?>
        </span>

        <div class="foto-perfil__acciones">
            <form method="POST" action="../auth/foto_perfil.php" enctype="multipart/form-data" data-foto-form>
                <?= campoCsrf() ?>
                <input type="hidden" name="accion" value="subir">
                <label class="boton boton--secundario foto-perfil__elegir">
                    <i class="fa-solid fa-camera" aria-hidden="true"></i> <?= $tieneFoto ? "Cambiar foto" : "Elegir foto" ?>
                    <input class="sr-only" type="file" name="foto" accept="<?= e(Archivo::accept(["imagen"])) ?>" required data-foto-archivo>
                </label>
                <button class="boton boton--primario" type="submit" data-foto-guardar>Guardar foto</button>
                <p class="campo__ayuda" data-foto-estado aria-live="polite"></p>
            </form>
            <?php if ($tieneFoto): ?>
                <form method="POST" action="../auth/foto_perfil.php" data-confirmar="¿Quitar tu foto de perfil? Se mostrarán tus iniciales.">
                    <?= campoCsrf() ?>
                    <input type="hidden" name="accion" value="quitar">
                    <button class="boton boton--fantasma boton--sm" type="submit"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> Quitar foto</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<script src="../js/foto_perfil.js<?= version("js/foto_perfil.js") ?>" defer></script>
