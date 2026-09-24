<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>BiGlobal | Editar usuario</title>

    <link rel="stylesheet" href="css/style.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

    <div class="admin-container">


        <main class="main-content"
            style="width: 100%; margin-left: 0;">


            <header class="topbar">

                <div class="topbar-left">

                    <a href="usuarios.php"
                        class="menu-toggle"
                        style="display:flex; align-items:center; justify-content:center;">

                        <i class="fa-solid fa-arrow-left"></i>

                    </a>

                    <div>

                        <p class="panel-label">
                            Gestión de plataforma
                        </p>

                        <h1>
                            Editar usuario
                        </h1>

                    </div>

                </div>

            </header>


            <section class="users-page">

                <div class="users-card"
                    style="max-width:700px; margin:auto; padding:25px;">

                    <div style="margin-bottom:25px;">

                        <span class="section-label">
                            Usuarios y roles
                        </span>

                        <h2 style="font-size:23px;">
                            Editar información
                        </h2>

                        <p style="color:#667085; font-size:13px; margin-top:6px;">
                            Modifica los datos del usuario seleccionado.
                        </p>

                    </div>


                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generarCsrfToken()) ?>">

                        <div class="form-row">

                            <div class="form-group">

                                <label for="nombre">
                                    Nombre
                                </label>

                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    value="<?= htmlspecialchars($usuario["nombre"]) ?>"
                                    required>

                            </div>


                            <div class="form-group">

                                <label for="apellido">
                                    Apellido
                                </label>

                                <input
                                    type="text"
                                    id="apellido"
                                    name="apellido"
                                    value="<?= htmlspecialchars($usuario["apellido"]) ?>"
                                    required>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="correo">
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                value="<?= htmlspecialchars($usuario["correo"]) ?>"
                                required>

                        </div>


                        <div class="form-group">

                            <label for="password">
                                Nueva contraseña
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Dejar vacío para conservar la actual">

                        </div>


                        <div class="form-row">


                            <div class="form-group">

                                <label for="rol">
                                    Rol
                                </label>

                                <select
                                    id="rol"
                                    name="rol"
                                    required>

                                    <option
                                        value="Administrador"
                                        <?= $usuario["rol"] === "Administrador" ? "selected" : "" ?>>
                                        Administrador
                                    </option>

                                    <option
                                        value="Instructor"
                                        <?= $usuario["rol"] === "Instructor" ? "selected" : "" ?>>
                                        Instructor
                                    </option>

                                    <option
                                        value="Estudiante"
                                        <?= $usuario["rol"] === "Estudiante" ? "selected" : "" ?>>
                                        Estudiante
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="estado">
                                    Estado
                                </label>

                                <select
                                    id="estado"
                                    name="estado">

                                    <option
                                        value="Activo"
                                        <?= $usuario["estado"] === "Activo" ? "selected" : "" ?>>
                                        Activo
                                    </option>

                                    <option
                                        value="Pendiente"
                                        <?= $usuario["estado"] === "Pendiente" ? "selected" : "" ?>>
                                        Pendiente (solicitud de profesor sin aprobar)
                                    </option>

                                    <option
                                        value="Inactivo"
                                        <?= $usuario["estado"] === "Inactivo" ? "selected" : "" ?>>
                                        Inactivo
                                    </option>

                                </select>

                            </div>


                        </div>


                        <div class="modal-actions"
                            style="margin-top:25px;">

                            <a
                                href="usuarios.php"
                                class="cancel-btn"
                                style="display:flex; align-items:center;">
                                Cancelar
                            </a>

                            <button
                                type="submit"
                                class="save-btn">

                                <i class="fa-solid fa-floppy-disk"></i>

                                Guardar cambios

                            </button>

                        </div>


                    </form>

                </div>

            </section>

        </main>

    </div>

</body>

</html>