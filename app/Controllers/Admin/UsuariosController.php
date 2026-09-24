<?php

require_once __DIR__ . "/../../Models/UsuarioModel.php";

/**
 * UsuariosController (admin)
 *
 * index()    -> lista de usuarios (antes usuarios.php)
 * editar()   -> ver/actualizar un usuario (antes editar_usuario.php,
 *               que hacia GET y POST en el mismo archivo)
 * crear()    -> crear usuario nuevo (antes guardar_usuario.php)
 * eliminar() -> borrar usuario (antes eliminar_usuario.php)
 */
class UsuariosController
{
    private PDO $conexion;

    public function __construct()
    {
        protegerRol("administrador");
        $this->conexion = Conexion::conectar();
    }

    public function index(): void
    {
        $conexion = $this->conexion;

        $usuarios = UsuarioModel::obtenerTodos($conexion);

        $totalUsuariosActivos = count(array_filter($usuarios, fn($u) => $u["estado"] === "Activo"));
        $totalInstructores = count(array_filter($usuarios, fn($u) => $u["rol"] === "Instructor"));
        $totalPendientes = count(array_filter($usuarios, fn($u) => $u["estado"] === "Pendiente"));

        require __DIR__ . "/../../Views/admin/usuarios/index.php";
    }

    public function editar(): void
    {
        $conexion = $this->conexion;

        $id = $_GET["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: usuarios.php");
            exit;
        }

        $id = (int) $id;

        $usuario = UsuarioModel::obtenerPorId($conexion, $id);

        if (!$usuario) {
            die("Usuario no encontrado.");
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            verificarCsrfFormulario($_POST["csrf_token"] ?? "");

            $nombre = trim($_POST["nombre"] ?? "");
            $apellido = trim($_POST["apellido"] ?? "");
            $correo = trim($_POST["correo"] ?? "");
            $rol = $_POST["rol"] ?? "";
            $estado = $_POST["estado"] ?? "Activo";
            $password = $_POST["password"] ?? "";

            if (empty($nombre) || empty($apellido) || empty($correo) || empty($rol)) {
                die("Los campos obligatorios deben estar completos.");
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                die("El correo electrónico no es válido.");
            }

            try {
                $conexion->beginTransaction();

                $passwordHash = !empty($password)
                    ? password_hash($password, PASSWORD_DEFAULT)
                    : null;

                UsuarioModel::actualizarDatos($conexion, $id, [
                    "nombre" => $nombre,
                    "apellido" => $apellido,
                    "correo" => $correo,
                    "estado" => $estado,
                    "passwordHash" => $passwordHash,
                ]);

                $rolId = UsuarioModel::obtenerRolIdPorNombre($conexion, $rol);

                if (!$rolId) {
                    throw new Exception("El rol seleccionado no existe.");
                }

                UsuarioModel::actualizarRol($conexion, $id, $rolId);

                $conexion->commit();

                header("Location: usuarios.php?editado=1");
                exit;
            } catch (Exception $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                manejarErrorBD($e, "Error al actualizar el usuario. Intenta de nuevo.");
            }
        }

        require __DIR__ . "/../../Views/admin/usuarios/editar.php";
    }

    public function crear(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: usuarios.php");
            exit;
        }

        $conexion = $this->conexion;

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $nombre = trim($_POST["nombre"] ?? "");
        $apellido = trim($_POST["apellido"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $password = $_POST["password"] ?? "";
        $rol = $_POST["rol"] ?? "";
        $estado = $_POST["estado"] ?? "Activo";

        if (empty($nombre) || empty($apellido) || empty($correo) || empty($password) || empty($rol)) {
            die("Todos los campos son obligatorios.");
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            die("El correo electrónico no es válido.");
        }

        try {
            $rolId = UsuarioModel::obtenerRolIdPorNombre($conexion, $rol);

            if (!$rolId) {
                die("El rol seleccionado no existe.");
            }

            if (UsuarioModel::existeCorreo($conexion, $correo)) {
                die("Ya existe un usuario con ese correo.");
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $conexion->beginTransaction();

            UsuarioModel::crear($conexion, [
                "nombre" => $nombre,
                "apellido" => $apellido,
                "correo" => $correo,
                "passwordHash" => $passwordHash,
                "rolId" => $rolId,
                "estado" => $estado,
            ]);

            $conexion->commit();

            header("Location: usuarios.php?creado=1");
            exit;
        } catch (PDOException $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            manejarErrorBD($e, "Error al crear el usuario. Intenta de nuevo.");
        }
    }

    public function eliminar(): void
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: usuarios.php");
            exit;
        }

        $conexion = $this->conexion;

        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $id = $_POST["id"] ?? null;

        if (!$id || !is_numeric($id)) {
            header("Location: usuarios.php");
            exit;
        }

        try {
            $conexion->beginTransaction();

            UsuarioModel::eliminar($conexion, (int) $id);

            $conexion->commit();

            header("Location: usuarios.php?eliminado=1");
            exit;
        } catch (PDOException $e) {

            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            manejarErrorBD($e, "Error al eliminar el usuario. Intenta de nuevo.");
        }
    }
}
