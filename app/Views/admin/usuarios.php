<?php
/** Usuarios. Variables: $usuarios, $porRol, $esSuper, $modal */
$tipoNuevo = "usuarios";
$estados = [1 => ["Activo", "chip chip--exito"], 2 => ["Pendiente", "chip chip--alerta"], 0 => ["Inactivo", "chip chip--neutro"]];
$chipsRol = ["admin" => "chip", "superadmin" => "chip chip--super", "docente" => "chip chip--logro", "estudiante" => "chip chip--exito"];
?>

<header class="pnl-encabezado">
    <div>
        <h1>Usuarios</h1>
        <p><?= e(plural_panel(count($usuarios), "cuenta registrada", "cuentas registradas")) ?>.
            <?php if ($esSuper): ?>Para cambiar roles o eliminar cuentas usa <a href="../superadmin/index.php?page=cuentas">Cuentas y roles</a>.<?php endif; ?>
        </p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-nuevo">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Nuevo usuario
    </button>
</header>

<?= Panel::grafico("grafico-usuarios-rol", "Usuarios por rol", "Distribución actual",
    array_map("ucfirst", array_column($porRol, "etiqueta")), array_column($porRol, "total"), "doughnut") ?>

<section class="pnl-tarjeta" aria-labelledby="tabla-usuarios">
    <div class="pnl-tarjeta__cabecera"><h2 id="tabla-usuarios">Todas las cuentas</h2></div>

    <?php if (empty($usuarios)): ?>
        <div class="vacio">
            <span class="vacio__icono"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
            <p class="vacio__titulo">Todavía no hay usuarios</p>
        </div>
    <?php else: ?>
        <div class="pnl-tabla-caja">
            <table class="pnl-tabla">
                <thead><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Registro</th></tr></thead>
                <tbody>
                    <?php foreach ($usuarios as $u): [$textoEstado, $claseEstado] = $estados[(int) $u["activo"]] ?? $estados[0]; ?>
                        <tr>
                            <td class="col-id">#<?= (int) $u["id"] ?></td>
                            <td class="col-fuerte"><?= e(nombre_panel($u)) ?></td>
                            <td class="col-suave"><?= e($u["correo"]) ?></td>
                            <td><span class="<?= $chipsRol[$u["rol"]] ?? "chip" ?>"><?= e($u["rol"] ?? "—") ?></span></td>
                            <td><span class="<?= $claseEstado ?>"><?= $textoEstado ?></span></td>
                            <td class="col-suave"><?= e(fecha_panel($u["fecha_registro"])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . "/_modal_nuevo.php"; ?>
