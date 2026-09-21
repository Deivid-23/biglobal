<?php

require_once "../auth/proteger.php";

protegerRol("administrador");

require_once "../bd/conexion.php";

$conexion = Conexion::conectar();

$id = $_GET["id"] ?? null;

if (!$id || !is_numeric($id)) {
    header("Location: usuarios.php");
    exit;
}


/* =========================================
   OBTENER DATOS DEL USUARIO
========================================= */

$sql = "
    SELECT
        u.id,
        u.nombre,
        u.apellido,
        u.correo,
        u.estado,
        r.nombre AS rol
    FROM usuarios u

    LEFT JOIN roles r
        ON u.rol_id = r.id

    WHERE u.id = :id
    LIMIT 1
";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    ":id" => $id
]);

$usuario = $stmt->fetch();


if (!$usuario) {
    die("Usuario no encontrado.");
}


/* =========================================
   ACTUALIZAR USUARIO
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $rol = $_POST["rol"] ?? "";
    $estado = $_POST["estado"] ?? "Activo";
    $password = $_POST["password"] ?? "";


    if (
        empty($nombre) ||
        empty($apellido) ||
        empty($correo) ||
        empty($rol)
    ) {
        die("Los campos obligatorios deben estar completos.");
    }


    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die("El correo electrónico no es válido.");
    }


    try {

        $conexion->beginTransaction();


        /* =========================================
           ACTUALIZAR DATOS PRINCIPALES
        ========================================= */

        if (!empty($password)) {

            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $sqlUpdate = "
                UPDATE usuarios

                SET
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    password = :password,
                    estado = :estado

                WHERE id = :id
            ";

            $stmtUpdate = $conexion->prepare($sqlUpdate);

            $stmtUpdate->execute([
                ":nombre" => $nombre,
                ":apellido" => $apellido,
                ":correo" => $correo,
                ":password" => $passwordHash,
                ":estado" => $estado,
                ":id" => $id
            ]);

        } else {

            $sqlUpdate = "
                UPDATE usuarios

                SET
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    estado = :estado

                WHERE id = :id
            ";

            $stmtUpdate = $conexion->prepare($sqlUpdate);

            $stmtUpdate->execute([
                ":nombre" => $nombre,
                ":apellido" => $apellido,
                ":correo" => $correo,
                ":estado" => $estado,
                ":id" => $id
            ]);
        }


        /* =========================================
           OBTENER ID DEL ROL
        ========================================= */

        $stmtRol = $conexion->prepare("
            SELECT id
            FROM roles
            WHERE nombre = :rol
            LIMIT 1
        ");

        $stmtRol->execute([
            ":rol" => $rol
        ]);

        $rolEncontrado = $stmtRol->fetch();


        if (!$rolEncontrado) {
            throw new Exception("El rol seleccionado no existe.");
        }


        /* =========================================
           ACTUALIZAR ROL
        ========================================= */

        $stmtActualizarRol = $conexion->prepare("
            UPDATE usuarios
            SET rol_id = :rol_id
            WHERE id = :id
        ");

        $stmtActualizarRol->execute([
            ":rol_id" => $rolEncontrado["id"],
            ":id" => $id
        ]);


        $conexion->commit();


        header("Location: usuarios.php?editado=1");
        exit;


    } catch (Exception $e) {

        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }

        die("Error al actualizar el usuario: " . $e->getMessage());
    }
}

?>

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
                                required
                            >

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
                                required
                            >

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
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Nueva contraseña
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Dejar vacío para conservar la actual"
                        >

                    </div>


                    <div class="form-row">


                        <div class="form-group">

                            <label for="rol">
                                Rol
                            </label>

                            <select
                                id="rol"
                                name="rol"
                                required
                            >

                                <option
                                    value="Administrador"
                                    <?= $usuario["rol"] === "Administrador" ? "selected" : "" ?>
                                >
                                    Administrador
                                </option>

                                <option
                                    value="Instructor"
                                    <?= $usuario["rol"] === "Instructor" ? "selected" : "" ?>
                                >
                                    Instructor
                                </option>

                                <option
                                    value="Estudiante"
                                    <?= $usuario["rol"] === "Estudiante" ? "selected" : "" ?>
                                >
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
                                name="estado"
                            >

                                <option
                                    value="Activo"
                                    <?= $usuario["estado"] === "Activo" ? "selected" : "" ?>
                                >
                                    Activo
                                </option>

                                <option
                                    value="Pendiente"
                                    <?= $usuario["estado"] === "Pendiente" ? "selected" : "" ?>
                                >
                                    Pendiente (solicitud de profesor sin aprobar)
                                </option>

                                <option
                                    value="Inactivo"
                                    <?= $usuario["estado"] === "Inactivo" ? "selected" : "" ?>
                                >
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
                            style="display:flex; align-items:center;"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="save-btn"
                        >

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