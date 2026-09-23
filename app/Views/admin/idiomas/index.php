<?php

/** @var array $idiomas */
/** @var int $totalIdiomas */
/** @var int $idiomasActivos */
/** @var int $idiomasInactivos */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Idiomas</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <div class="admin-container">

        <?php
        $paginaActiva = "idiomas";
        require __DIR__ . "/../../partials/admin_sidebar.php";
        ?>

        <main class="main-content">

            <?php
            $tituloPagina = "Idiomas";
            require __DIR__ . "/../../partials/admin_topbar.php";
            ?>

            <section class="users-page">

                <div class="users-heading">
                    <div>
                        <span class="section-label">Gestión académica</span>
                        <h2>Idiomas disponibles</h2>
                        <p>Administra los idiomas disponibles en la plataforma BiGlobal.</p>
                    </div>
                    <button class="add-user-btn" id="openLanguageModal" type="button">
                        <i class="fa-solid fa-plus"></i>
                        Nuevo idioma
                    </button>
                </div>

                <div class="user-stats">

                    <div class="user-stat-card">
                        <div class="user-stat-icon blue">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <div>
                            <span>Total idiomas</span>
                            <strong><?= $totalIdiomas ?></strong>
                        </div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-icon green">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <span>Activos</span>
                            <strong><?= $idiomasActivos ?></strong>
                        </div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-icon orange">
                            <i class="fa-solid fa-circle-pause"></i>
                        </div>
                        <div>
                            <span>Inactivos</span>
                            <strong><?= $idiomasInactivos ?></strong>
                        </div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-icon purple">
                            <i class="fa-solid fa-language"></i>
                        </div>
                        <div>
                            <span>Código ISO</span>
                            <strong><?= $totalIdiomas ?></strong>
                        </div>
                    </div>

                </div>

                <div class="users-card">

                    <div class="users-toolbar">
                        <div>
                            <h3>Lista de idiomas</h3>
                            <span><?= $totalIdiomas ?> idiomas registrados</span>
                        </div>

                        <div class="toolbar-actions">
                            <div class="search-box">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="searchLanguage" placeholder="Buscar idioma...">
                            </div>

                            <select id="statusFilter">
                                <option value="all">Todos los estados</option>
                                <option value="Activo">Activos</option>
                                <option value="Inactivo">Inactivos</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Idioma</th>
                                    <th>Código</th>
                                    <th>Estado</th>
                                    <th>Fecha de creación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="languagesTable">

                                <?php if (count($idiomas) > 0): ?>

                                    <?php foreach ($idiomas as $idioma): ?>

                                        <tr data-status="<?= htmlspecialchars($idioma["estado"]) ?>">

                                            <td>
                                                <div class="user-cell">
                                                    <div class="user-avatar blue">
                                                        <i class="fa-solid fa-language"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($idioma["nombre"]) ?></strong>
                                                        <span>Idioma disponible en BiGlobal</span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="role admin-role"><?= htmlspecialchars($idioma["codigo"]) ?></span>
                                            </td>

                                            <td>
                                                <?php if ($idioma["estado"] === "Activo"): ?>
                                                    <span class="status active-status">Activo</span>
                                                <?php else: ?>
                                                    <span class="status inactive-status">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><?= date("d M Y", strtotime($idioma["fecha_creacion"])) ?></td>

                                            <td>
                                                <div class="action-buttons">
                                                    <a href="editar_idioma.php?id=<?= $idioma["id"] ?>" class="table-action edit" title="Editar">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <form action="eliminar_idioma.php" method="POST" style="display:inline;" onsubmit="return confirmarEliminacionIdioma();">
                                                        <input type="hidden" name="id" value="<?= $idioma['id'] ?>">
                                                        <button type="submit" class="table-action delete" title="Eliminar">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="5" style="text-align:center; padding:40px;">
                                            No hay idiomas registrados.
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <span>Mostrando <?= $totalIdiomas ?> idiomas</span>
                        <div class="pagination-buttons">
                            <button disabled><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="page-active">1</button>
                            <button disabled><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="languageModal">
        <div class="modal">

            <div class="modal-header">
                <div>
                    <span>Gestión académica</span>
                    <h3>Agregar nuevo idioma</h3>
                </div>
                <button class="close-modal" id="closeLanguageModal" type="button">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="guardar_idioma.php" method="POST">

                <div class="form-group">
                    <label for="nombre">Nombre del idioma</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej. Alemán" required>
                </div>

                <div class="form-group">
                    <label for="codigo">Código</label>
                    <input type="text" id="codigo" name="codigo" placeholder="Ej. DE" maxlength="10" required>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelLanguageModal">Cancelar</button>
                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-plus"></i>
                        Crear idioma
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        const menuToggle = document.getElementById("menuToggle");
        const sidebar = document.querySelector(".sidebar");
        if (menuToggle) {
            menuToggle.addEventListener("click", () => sidebar.classList.toggle("show"));
        }

        const languageModal = document.getElementById("languageModal");
        const openLanguageModal = document.getElementById("openLanguageModal");
        const closeLanguageModal = document.getElementById("closeLanguageModal");
        const cancelLanguageModal = document.getElementById("cancelLanguageModal");

        openLanguageModal.addEventListener("click", () => languageModal.classList.add("show"));
        closeLanguageModal.addEventListener("click", () => languageModal.classList.remove("show"));
        cancelLanguageModal.addEventListener("click", () => languageModal.classList.remove("show"));
        languageModal.addEventListener("click", (event) => {
            if (event.target === languageModal) languageModal.classList.remove("show");
        });

        const searchLanguage = document.getElementById("searchLanguage");
        searchLanguage.addEventListener("input", function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll("#languagesTable tr").forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(search) ? "" : "none";
            });
        });

        const statusFilter = document.getElementById("statusFilter");
        statusFilter.addEventListener("change", function() {
            const selectedStatus = this.value;
            document.querySelectorAll("#languagesTable tr").forEach(row => {
                const rowStatus = row.dataset.status;
                row.style.display = (selectedStatus === "all" || rowStatus === selectedStatus) ? "" : "none";
            });
        });

        function confirmarEliminacionIdioma() {
            return confirm("¿Estás seguro de que deseas eliminar este idioma?");
        }
    </script>

</body>

</html>