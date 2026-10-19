<?php
/** Idiomas. Variables: $idiomas, $porEstado, $modal */
$tipoNuevo = "idiomas";
?>

<header class="pnl-encabezado">
    <div>
        <h1>Idiomas</h1>
        <p><?= e(plural_panel(count($idiomas), "idioma registrado", "idiomas registrados")) ?>.</p>
    </div>
    <button class="boton boton--primario" type="button" data-abrir-modal="modal-nuevo">
        <i class="fa-solid fa-plus" aria-hidden="true"></i> Nuevo idioma
    </button>
</header>

<div class="pnl-rejilla pnl-rejilla--ancha">
    <section class="pnl-tarjeta" aria-labelledby="tabla-idiomas">
        <div class="pnl-tarjeta__cabecera"><h2 id="tabla-idiomas">Lista de idiomas</h2></div>
        <?php if (empty($idiomas)): ?>
            <p class="texto-suave">Todavía no hay idiomas.</p>
        <?php else: ?>
            <div class="pnl-tabla-caja">
                <table class="pnl-tabla">
                    <thead><tr><th>ID</th><th>Nombre</th><th>Código</th><th>Estado</th><th class="col-acciones">Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($idiomas as $i): ?>
                            <tr>
                                <td class="col-id">#<?= (int) $i["id"] ?></td>
                                <td class="col-fuerte"><?= e($i["nombre"]) ?></td>
                                <td class="col-suave"><?= e($i["codigo"]) ?></td>
                                <td><?= (int) $i["estado"] === 1 ? '<span class="chip chip--exito">Activo</span>' : '<span class="chip chip--neutro">Inactivo</span>' ?></td>
                                <td class="col-acciones">
                                    <?php $acciones = [
                                        "nombre" => $i["nombre"], "modal" => "modal-editar", "action" => "index.php",
                                        "datos" => ["id" => $i["id"], "nombre" => $i["nombre"], "codigo" => $i["codigo"], "estado" => $i["estado"]],
                                        "eliminar" => null,
                                    ]; require __DIR__ . "/../panel/_acciones_fila.php"; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?= Panel::grafico("grafico-idiomas", "Por estado", "Activos e inactivos",
        array_column($porEstado, "etiqueta"), array_column($porEstado, "total"), "doughnut") ?>
</div>

<?php require __DIR__ . "/_modal_nuevo.php"; ?>
<?php require __DIR__ . "/_modal_editar.php"; ?>
