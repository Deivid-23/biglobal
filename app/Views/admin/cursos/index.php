<?php

/** @var array $cursos */
/** @var array $instructores */
/** @var int $totalCursos */
/** @var int $cursosPublicados */
/** @var int $cursosBorrador */
/** @var int $cursosInactivos */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BiGlobal | Catálogo de cursos</title>

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <div class="admin-container">

        <?php
        $paginaActiva = "cursos";
        require __DIR__ . "/../../partials/admin_sidebar.php";
        ?>

        <main class="main-content">

            <?php
            $tituloPagina = "Catálogo de cursos";
            require __DIR__ . "/../../partials/admin_topbar.php";
            ?>

            <section class="users-page">

                <div class="users-heading">
                    <div>
                        <span class="section-label">Gestión académica</span>
                        <h2>Catálogo de cursos</h2>
                        <p>
                            Crea, administra, publica y supervisa los cursos
                            disponibles en BiGlobal.
                        </p>
                    </div>
                    <button class="add-user-btn" id="openCourseModal">
                        <i class="fa-solid fa-plus"></i>
                        Nuevo curso
                    </button>
                </div>

                <div class="user-stats">

                    <div class="user-stat-card">
                        <div class="user-stat-icon blue">
                            <i class="fa-solid fa-book"></i>
                        </div>
                        <div>
                            <span>Total cursos</span>
                            <strong><?= $totalCursos ?></strong>
                        </div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-icon green">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <span>Publicados</span>
                            <strong><?= $cursosPublicados ?></strong>
                        </div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-icon orange">
                            <i class="fa-solid fa-file-pen"></i>
                        </div>
                        <div>
                            <span>Borradores</span>
                            <strong><?= $cursosBorrador ?></strong>
                        </div>
                    </div>

                    <div class="user-stat-card">
                        <div class="user-stat-icon purple">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div>
                            <span>Inactivos</span>
                            <strong><?= $cursosInactivos ?></strong>
                        </div>
                    </div>

                </div>

                <div class="users-card">

                    <div class="users-toolbar">
                        <div>
                            <h3>Lista de cursos</h3>
                            <span><?= $totalCursos ?> cursos registrados</span>
                        </div>

                        <div class="toolbar-actions">
                            <div class="search-box">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="searchCourse" placeholder="Buscar curso...">
                            </div>

                            <select id="levelFilter">
                                <option value="all">Todos los niveles</option>
                                <option value="Básico">Básico</option>
                                <option value="Intermedio">Intermedio</option>
                                <option value="Avanzado">Avanzado</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Nivel</th>
                                    <th>Instructor</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody id="coursesTable">

                                <?php if (count($cursos) > 0): ?>

                                    <?php foreach ($cursos as $curso): ?>

                                        <?php
                                        $nivel = $curso["nivel"] ?: "Sin nivel";
                                        $estado = $curso["estado"] ?: "Borrador";
                                        $instructor = trim(
                                            ($curso["instructor_nombre"] ?? "") . " " . ($curso["instructor_apellido"] ?? "")
                                        );
                                        if ($instructor === "") {
                                            $instructor = "Sin asignar";
                                        }
                                        ?>

                                        <tr data-level="<?= htmlspecialchars($nivel) ?>">

                                            <td>
                                                <div class="user-cell">
                                                    <div class="user-avatar blue">
                                                        <i class="fa-solid fa-book"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?= htmlspecialchars($curso["nombre"]) ?></strong>
                                                        <span><?= htmlspecialchars($curso["descripcion"] ?: "Sin descripción") ?></span>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <span class="role admin-role"><?= htmlspecialchars($nivel) ?></span>
                                            </td>

                                            <td><?= htmlspecialchars($instructor) ?></td>

                                            <td>
                                                <?php if ($estado === "Publicado"): ?>
                                                    <span class="status active-status">Publicado</span>
                                                <?php elseif ($estado === "Inactivo"): ?>
                                                    <span class="status inactive-status">Inactivo</span>
                                                <?php else: ?>
                                                    <span class="status" style="background:#fff7ed; color:#f97316;">Borrador</span>
                                                <?php endif; ?>
                                            </td>

                                            <td><?= date("d M Y", strtotime($curso["fecha_creacion"])) ?></td>

                                            <td>
                                                <div class="action-buttons">
                                                    <a href="editar_curso.php?id=<?= $curso['id'] ?>" class="table-action edit" title="Editar">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>

                                                    <form action="eliminar_curso.php" method="POST" style="display:inline;" onsubmit="return confirmarEliminacionCurso();">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCsrfToken()) ?>">
                                                        <input type="hidden" name="id" value="<?= $curso['id'] ?>">
                                                        <button class="table-action delete" type="submit" title="Eliminar">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php else: ?>

                                    <tr>
                                        <td colspan="6" style="text-align:center; padding:40px;">
                                            <i class="fa-solid fa-book-open" style="font-size:30px; color:#98a2b3; margin-bottom:10px;"></i>
                                            <p style="color:#667085;">Todavía no hay cursos registrados.</p>
                                        </td>
                                    </tr>

                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <span>Mostrando <?= $totalCursos ?> cursos</span>
                        <div class="pagination-buttons">
                            <button disabled><i class="fa-solid fa-chevron-left"></i></button>
                            <button class="page-active">1</button>
                            <button><i class="fa-solid fa-chevron-right"></i></button>
                        </div>
                    </div>

                </div>
            </section>
        </main>
    </div>

    <div class="modal-overlay" id="courseModal">
        <div class="modal">

            <div class="modal-header">
                <div>
                    <span>Gestión académica</span>
                    <h3>Crear nuevo curso</h3>
                </div>
                <button class="close-modal" id="closeCourseModal" type="button">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="courseForm" action="guardar_curso.php" method="POST">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCsrfToken()) ?>">

                <div class="form-group">
                    <label for="courseName">Nombre del curso</label>
                    <input type="text" id="courseName" name="nombre" placeholder="Ej. Inglés profesional" required>
                </div>

                <div class="form-group">
                    <label for="courseDescription">Descripción</label>
                    <textarea id="courseDescription" name="descripcion" rows="4" placeholder="Descripción del curso..."
                        style="width:100%; border:1px solid #d0d5dd; border-radius:9px; padding:12px; resize:vertical; font-family:inherit; font-size:12px; outline:none;"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="courseLevel">Nivel</label>
                        <select id="courseLevel" name="nivel" required>
                            <option value="">Seleccionar nivel</option>
                            <option value="Básico">Básico</option>
                            <option value="Intermedio">Intermedio</option>
                            <option value="Avanzado">Avanzado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="courseStatus">Estado</label>
                        <select id="courseStatus" name="estado">
                            <option value="Borrador">Borrador</option>
                            <option value="Publicado">Publicado</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="instructor">Instructor</label>
                    <select id="instructor" name="instructor_id">
                        <option value="">Sin instructor</option>
                        <?php foreach ($instructores as $instructor): ?>
                            <option value="<?= $instructor["id"] ?>">
                                <?= htmlspecialchars($instructor["nombre"] . " " . $instructor["apellido"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelCourseModal">Cancelar</button>
                    <button type="submit" class="save-btn">
                        <i class="fa-solid fa-plus"></i>
                        Crear curso
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

        const courseModal = document.getElementById("courseModal");
        const openCourseModal = document.getElementById("openCourseModal");
        const closeCourseModal = document.getElementById("closeCourseModal");
        const cancelCourseModal = document.getElementById("cancelCourseModal");

        openCourseModal.addEventListener("click", () => courseModal.classList.add("show"));
        closeCourseModal.addEventListener("click", () => courseModal.classList.remove("show"));
        cancelCourseModal.addEventListener("click", () => courseModal.classList.remove("show"));
        courseModal.addEventListener("click", (event) => {
            if (event.target === courseModal) courseModal.classList.remove("show");
        });

        const searchCourse = document.getElementById("searchCourse");
        searchCourse.addEventListener("input", function() {
            const search = this.value.toLowerCase();
            document.querySelectorAll("#coursesTable tr").forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(search) ? "" : "none";
            });
        });

        const levelFilter = document.getElementById("levelFilter");
        levelFilter.addEventListener("change", function() {
            const selectedLevel = this.value;
            document.querySelectorAll("#coursesTable tr").forEach(row => {
                const rowLevel = row.dataset.level;
                row.style.display = (selectedLevel === "all" || rowLevel === selectedLevel) ? "" : "none";
            });
        });

        function confirmarEliminacionCurso() {
            return confirm("¿Estás seguro de que deseas eliminar este curso?");
        }
    </script>

</body>

</html>