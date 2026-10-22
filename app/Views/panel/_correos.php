<?php
/**
 * Tarjeta "Correos" (todos los roles): apagar los correos no esenciales.
 * Variable opcional: $claseTarjeta (estudiante: "perfil-tarjeta"; panel: "pnl-tarjeta").
 * Guarda en src/auth/preferencias_correo.php.
 */
$claseTarjeta = $claseTarjeta ?? "pnl-tarjeta";
$recibeOpcionales = Notificador::recibeOpcionales((int) $_SESSION["usuario_id"]);
$rutaAuth = "../auth/preferencias_correo.php";
?>
<section class="<?= e($claseTarjeta) ?> tarjeta-correos" aria-labelledby="titulo-correos">
    <h2 id="titulo-correos">Correos</h2>
    <p class="<?= $claseTarjeta === "perfil-tarjeta" ? "perfil-tarjeta__ayuda" : "campo__ayuda" ?>">
        Todos los avisos llegan a la campana. Por correo siempre te escribimos lo esencial: pagos, aprobación de tu cuenta y cambios importantes en ella.
    </p>
    <form method="POST" action="<?= e($rutaAuth) ?>">
        <?= campoCsrf() ?>
        <label class="pnl-interruptor correos-opcion">
            <input type="checkbox" name="correos_opcionales" value="1" <?= $recibeOpcionales ? "checked" : "" ?>>
            <span><strong>Recibir también correos no esenciales</strong>
                <small>Invitaciones a clases gratuitas, cupos liberados en la lista de espera, revisión de tus cursos y recordatorios de Premium.</small></span>
        </label>
        <button class="boton boton--secundario" type="submit">Guardar preferencia</button>
    </form>
</section>
