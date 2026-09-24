<?php

require_once __DIR__ . "/../Models/AuthModel.php";

/**
 * AuthController
 *
 * login()     -> autenticar usuario, responde JSON (antes login.php)
 * registro()  -> crear cuenta nueva, responde JSON (antes registro.php)
 * logout()    -> cerrar sesión y redirigir (antes logout.php)
 * csrfToken() -> entregar token CSRF, responde JSON (antes csrf_token.php)
 */
class AuthController
{
    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        verificarCsrf($_POST["csrf_token"] ?? "");

        $maxIntentos = 5;
        $tiempoBloqueoSegundos = 60;

        if (!isset($_SESSION["login_intentos"])) {
            $_SESSION["login_intentos"] = 0;
        }

        if (
            isset($_SESSION["login_bloqueado_hasta"]) &&
            time() < $_SESSION["login_bloqueado_hasta"]
        ) {
            $segundosRestantes = $_SESSION["login_bloqueado_hasta"] - time();

            echo json_encode([
                "success" => false,
                "message" => "Demasiados intentos. Inténtalo de nuevo en {$segundosRestantes} segundos."
            ]);
            exit;
        }

        try {

            $conexion = Conexion::conectar();

            $correo = trim($_POST["correo"] ?? "");
            $password = $_POST["password"] ?? "";

            if (empty($correo) || empty($password)) {
                echo json_encode(["success" => false, "message" => "Correo y contraseña son obligatorios."]);
                exit;
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(["success" => false, "message" => "El correo electrónico no es válido."]);
                exit;
            }

            $usuario = AuthModel::buscarPorCorreo($conexion, $correo);

            if (!$usuario) {
                echo json_encode(["success" => false, "message" => "El correo o la contraseña son incorrectos."]);
                exit;
            }

            if (($usuario["estado"] ?? "") !== "Activo") {

                $mensajeEstado = ($usuario["estado"] ?? "") === "Pendiente"
                    ? "Tu solicitud como profesor todavía está pendiente de aprobación por un administrador."
                    : "Tu cuenta está inactiva. Contacta al administrador.";

                echo json_encode(["success" => false, "message" => $mensajeEstado]);
                exit;
            }

            if (!password_verify($password, $usuario["password"])) {

                $_SESSION["login_intentos"]++;

                if ($_SESSION["login_intentos"] >= $maxIntentos) {
                    $_SESSION["login_bloqueado_hasta"] = time() + $tiempoBloqueoSegundos;
                    $_SESSION["login_intentos"] = 0;
                }

                echo json_encode(["success" => false, "message" => "El correo o la contraseña son incorrectos."]);
                exit;
            }

            $_SESSION["login_intentos"] = 0;
            unset($_SESSION["login_bloqueado_hasta"]);

            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["nombre"] = $usuario["nombre"];
            $_SESSION["apellido"] = $usuario["apellido"];
            $_SESSION["correo"] = $usuario["correo"];
            $_SESSION["rol_id"] = $usuario["rol_id"];
            $_SESSION["rol"] = $usuario["rol"];

            $redireccion = "../index.html";

            switch (strtolower($usuario["rol"])) {
                case "admin":
                case "administrador":
                    $redireccion = "../admin/index.php";
                    break;
                case "instructor":
                    $redireccion = "../profesor/index.php";
                    break;
                case "estudiante":
                case "usuario":
                    $redireccion = "../estudiante/index.php";
                    break;
                default:
                    $redireccion = "../index.html";
                    break;
            }

            echo json_encode([
                "success" => true,
                "message" => "Inicio de sesión correcto.",
                "redirect" => $redireccion
            ]);
            exit;



            echo json_encode([
                "success" => false,
                "message" => "Error de conexión con la base de datos.",
                "error" => $e->getMessage()
            ]);
            exit;
        } catch (PDOException $e) {

            error_log("[BiGlobal] " . $e->getMessage());

            echo json_encode([
                "success" => false,
                "message" => "Error de conexión con la base de datos. Intenta de nuevo más tarde."
            ]);
            exit;
        }
    }

    public function registro(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        verificarCsrf($_POST["csrf_token"] ?? "");

        try {

            $conexion = Conexion::conectar();

            $nombreCompleto = trim($_POST["nombre"] ?? "");
            $correo = trim($_POST["correo"] ?? "");
            $password = $_POST["password"] ?? "";
            $confirmPassword = $_POST["confirm_password"] ?? "";
            $rolSolicitado = $_POST["rol"] ?? "estudiante";

            if ($rolSolicitado !== "profesor") {
                $rolSolicitado = "estudiante";
            }

            if (empty($nombreCompleto) || empty($correo) || empty($password) || empty($confirmPassword)) {
                echo json_encode(["success" => false, "message" => "Todos los campos son obligatorios."]);
                exit;
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(["success" => false, "message" => "El correo electrónico no es válido."]);
                exit;
            }

            if ($password !== $confirmPassword) {
                echo json_encode(["success" => false, "message" => "Las contraseñas no coinciden."]);
                exit;
            }

            if (strlen($password) < 6) {
                echo json_encode(["success" => false, "message" => "La contraseña debe tener al menos 6 caracteres."]);
                exit;
            }

            $partesNombre = preg_split('/\s+/', $nombreCompleto, 2);
            $nombre = $partesNombre[0];
            $apellido = $partesNombre[1] ?? "No especificado";

            if (AuthModel::correoExiste($conexion, $correo)) {
                echo json_encode(["success" => false, "message" => "Ya existe una cuenta con ese correo."]);
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            if ($rolSolicitado === "profesor") {
                $rolId = 3;
                $estadoInicial = "Pendiente";
            } else {
                $rolId = 2;
                $estadoInicial = "Activo";
            }

            AuthModel::crear($conexion, [
                "nombre" => $nombre,
                "apellido" => $apellido,
                "correo" => $correo,
                "password" => $passwordHash,
                "rolId" => $rolId,
                "estado" => $estadoInicial,
            ]);

            if ($estadoInicial === "Pendiente") {
                echo json_encode([
                    "success" => true,
                    "message" => "Tu solicitud como profesor fue enviada. Un administrador debe aprobarla antes de que puedas iniciar sesión."
                ]);
                exit;
            }

            echo json_encode([
                "success" => true,
                "message" => "Cuenta creada correctamente.",
                "redirect" => "login.html"
            ]);
            exit;
            
        } catch (PDOException $e) {

            error_log("[BiGlobal] " . $e->getMessage());

            echo json_encode([
                "success" => false,
                "message" => "Error de conexión con la base de datos. Intenta de nuevo más tarde."
            ]);
            exit;
        }
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $parametros = session_get_cookie_params();
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $parametros["path"],
                $parametros["domain"],
                $parametros["secure"],
                $parametros["httponly"]
            );
        }

        session_destroy();

        header("Location: login.html");
        exit;
    }

    public function csrfToken(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            "csrf_token" => generarCsrfToken()
        ]);
    }
}
