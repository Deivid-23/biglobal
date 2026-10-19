<?php
/** Cuentas y roles. Variables: $cuentas, $roles, $filtro, $miId, $modal */
$estados = [1 => ["Activo", "chip chip--exito"], 2 => ["Pendiente", "chip chip--alerta"], 0 => ["Inactivo", "chip chip--neutro"]];

/* Para el modal "Nuevo usuario" (se guarda con el mismo código del admin) */
$tipoNuevo = "usuarios";
$esSuper = true;
$modal += ["cursos" => [], "lecciones" => [], "docentes" => []];
?>

<header class="pnl-encabezado">
    <div>
        <h1>Cuentas y roles</h1>
        <p>Cambia el rol de cualquier cuenta, desactívala o elimínala. Siempre debe quedar al menos un super administrador activo.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-nuevo">
        <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Nueva cuenta
    </button>
</header>

<nav class="pnl-pestanas" aria-label="Filtrar por rol">
    <a class="pnl-pestana <?= $filtro === "" ? "es-activa" : "" ?>" href="index.php?page=cuentas">Todas</a>
    <?php foreach ($roles as $r): ?>
        <a class="pnl-pestana <?= $filtro === $r["nombre"] ? "es-activa" : "" ?>" href="index.php?page=cuentas&amp;rol=<?= e($r["nombre"]) ?>">
            <?= e(ucfirst($r["nombre"])) ?>
        </a>
    <?php endforeach; ?>
</nav>

<section class="pnl-tarjeta" aria-labelledby="tabla-cuentas">
    <div class="pnl-tarjeta__cabecera"><h2 id="tabla-cuentas"><?= e(plural_panel(count($cuentas), "cuenta", "cuentas")) ?></h2></div>

    <?php if (empty($cuentas)): ?>
        <p class="texto-suave">No hay cuentas con ese rol.</p>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>Cuenta</th><th>Rol</th><th>Estado</th><th class="col-acciones">Acciones</th></tr></thead>
                <tbody>
                    <?php foreach ($cuentas as $u):
                        [$txt, $cls] = $estados[(int) $u["activo"]] ?? $estados[0];
                        $esYo = (int) $u["id"] === $miId;
                    ?>
                        <tr>
                            <td>
                                <span class="col-fuerte"><?= e(nombre_panel($u)) ?></span><?= $esYo ? ' <span class="chip chip--neutro">Tú</span>' : "" ?><br>
                                <span class="col-suave"><?= e($u["correo"]) ?></span>
                            </td>
                            <td>
                                <?php if ($esYo): ?>
                                    <span class="chip chip--super">superadmin</span>
                                <?php else: ?>
                                    <form method="POST" action="index.php" class="cuenta-rol">
                                        <?= campoCsrf() ?>
                                        <input type="hidden" name="accion" value="cambiar_rol">
                                        <input type="hidden" name="id" value="<?= (int) $u["id"] ?>">
                                        <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
                                        <label class="sr-only" for="rol-<?= (int) $u["id"] ?>">Rol de <?= e(nombre_panel($u)) ?></label>
                                        <select class="pnl-selector" id="rol-<?= (int) $u["id"] ?>" name="rol_id" data-enviar-al-cambiar>
                                            <?php foreach ($roles as $r): ?>
                                                <option value="<?= (int) $r["id"] ?>" <?= (int) $r["id"] === (int) $u["rol_id"] ? "selected" : "" ?>><?= e($r["nombre"]) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <noscript><button class="boton boton--secundario boton--sm" type="submit">Guardar</button></noscript>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><span class="<?= $cls ?>"><?= $txt ?></span></td>
                            <td class="col-acciones">
                                <?php if (!$esYo): ?>
                                    <div class="pnl-item__acciones" style="justify-content: flex-end;">
                                        <form method="POST" action="index.php">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id" value="<?= (int) $u["id"] ?>">
                                            <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
                                            <input type="hidden" name="activo" value="<?= (int) $u["activo"] === 1 ? 0 : 1 ?>">
                                            <button class="boton boton--secundario boton--sm" type="submit">
                                                <?= (int) $u["activo"] === 1 ? "Desactivar" : "Activar" ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="index.php"
                                              data-confirmar="¿Eliminar definitivamente la cuenta de <?= e(nombre_panel($u)) ?>? Se borran su progreso y sus certificados. No se puede deshacer.">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id" value="<?= (int) $u["id"] ?>">
                                            <input type="hidden" name="filtro" value="<?= e($filtro) ?>">
                                            <button class="boton boton--peligro boton--sm" type="submit" aria-label="Eliminar a <?= e(nombre_panel($u)) ?>">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
/* El modal guarda con el código del panel admin (misma validación de roles en el servidor) */
$accionModal = "../admin/index.php";
require __DIR__ . "/../admin/_modal_nuevo.php";
?>
