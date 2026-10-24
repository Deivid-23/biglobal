<?php
/**
 * Tarjeta "Correos" (todos los roles): apagar los correos no esenciales.
 * Variable opcional: $claseTarjeta (estudiante: "perfil-tarjeta"; panel: "pnl-tarjeta").
 * Estudiante: también la casilla del recordatorio de racha.
 * Guarda en src/auth/preferencias_correo.php.
 */
$claseTarjeta = $claseTarjeta ?? "pnl-tarjeta";
$recibeOpcionales = Notificador::recibeOpcionales((int) $_SESSION["usuario_id"]);
$esEstudiante = rolActual() === "estudiante";
$quiereRecordatorio = $esEstudiante && GamificacionModel::quiereRecordatorio(Conexion::conectar(), (int) $_SESSION["usuario_id"]);
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
        <?php if ($esEstudiante): ?>
            <input type="hidden" name="con_recordatorio" value="1">
            <label class="pnl-interruptor correos-opcion">
                <input type="checkbox" name="recordatorio_racha" value="1" <?= $quiereRecordatorio ? "checked" : "" ?>>
                <span><strong>Recordatorio de racha</strong>
                    <small>Si hoy no has estudiado, un aviso entre las 7 y las 10 p. m. para que no pierdas tu racha. Llega a la campana y, si recibes los correos no esenciales, también por correo.</small></span>
            </label>
        <?php endif; ?>
        <button class="boton boton--secundario" type="submit">Guardar preferencia</button>
    </form>
</section>
