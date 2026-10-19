<?php

require_once __DIR__ . "/../Models/AuthModel.php";

/**
 * AuthController
 *
 * login()     -> autenticar usuario, responde JSON
 * registro()  -> crear cuenta nueva, responde JSON
 * logout()    -> cerrar sesión y redirigir
 * csrfToken() -> entregar token CSRF, responde JSON
 */
class AuthController
{
    public function login(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        verificarCsrf($_POST["csrf_token"] ?? "");

        /* Valores definidos por el admin en Configuración > Seguridad */
        $maxIntentos = max(1, Config::getInt("seg_intentos_login", 5));
        $tiempoBloqueoSegundos = max(1, Config::getInt("seg_bloqueo_minutos", 1)) * 60;

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

            $activo = (int) ($usuario["activo"] ?? 0);

            /* activo = 2 → docente pendiente de aprobación */
            if ($activo === 2) {
                session_regenerate_id(true);

                $_SESSION["usuario_id"] = $usuario["id"];
                $_SESSION["nombre"]     = $usuario["nombre"];
                $_SESSION["apellido"]   = $usuario["apellido"];
                $_SESSION["correo"]     = $usuario["correo"];
                $_SESSION["rol_id"]     = $usuario["rol_id"];
                $_SESSION["rol"]        = $usuario["rol"];

                echo json_encode([
                    "success"  => true,
                    "message"  => "Tu cuenta está pendiente de aprobación.",
                    "redirect" => "../profesor/pendiente.php"
                ]);
                exit;
            }

            /* activo = 0 → cuenta inactiva/bloqueada */
            if ($activo === 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Tu cuenta está inactiva. Contacta al administrador."
                ]);
                exit;
            }

            /* activo = 1 → login normal */

            /* Cuenta creada con Google: no tiene contraseña guardada */
            if (empty($usuario["password"])) {
                echo json_encode([
                    "success" => false,
                    "message" => "Esta cuenta se creó con Google. Usa «Continuar con Google» o crea una contraseña con «¿Olvidaste tu contraseña?»."
                ]);
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
            $_SESSION["nombre"]     = $usuario["nombre"];
            $_SESSION["apellido"]   = $usuario["apellido"];
            $_SESSION["correo"]     = $usuario["correo"];
            $_SESSION["rol_id"]     = $usuario["rol_id"];
            $_SESSION["rol"]        = $usuario["rol"];

            /* Cada rol va a su panel (ver panelDeRol en src/auth/proteger.php) */
            $redireccion = panelDeRol($usuario["rol"]);

            echo json_encode([
                "success" => true,
                "message" => "Inicio de sesión correcto.",
                "redirect" => $redireccion
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

            $nombreCompleto  = trim($_POST["nombre"] ?? "");
            $correo          = trim($_POST["correo"] ?? "");
            $password        = $_POST["password"] ?? "";
            $confirmPassword = $_POST["confirm_password"] ?? "";
            $rolSolicitado   = $_POST["rol"] ?? "estudiante";

            /* Solo se aceptan "estudiante" o "docente" */
            if (!in_array($rolSolicitado, ["estudiante", "docente"], true)) {
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

            /* Reglas de Configuración > Seguridad (largo, mayúscula, número) */
            $errorPassword = Seguridad::validarPassword($password);
            if ($errorPassword !== null) {
                echo json_encode(["success" => false, "message" => $errorPassword]);
                exit;
            }

            if (!mb_check_encoding($nombreCompleto, "UTF-8")) {
                echo json_encode(["success" => false, "message" => "El nombre tiene caracteres no válidos."]);
                exit;
            }

            if (mb_strlen($nombreCompleto) > 200) {
                echo json_encode(["success" => false, "message" => "El nombre es demasiado largo."]);
                exit;
            }

            $partesNombre = preg_split('/\s+/', $nombreCompleto, 2);
            $nombre   = $partesNombre[0];
            $apellido = $partesNombre[1] ?? "No especificado";

            if (AuthModel::correoExiste($conexion, $correo)) {
                echo json_encode(["success" => false, "message" => "Ya existe una cuenta con ese correo."]);
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            /* ---------------------------------------------------
               Rol y estado inicial
               ---------------------------------------------------
               Roles en BD:
                 1 = admin
                 2 = estudiante
                 3 = docente

               activo en BD:
                 0 = inactivo/bloqueado
                 1 = activo
                 2 = pendiente de aprobación (solo docentes)
            --------------------------------------------------- */
            if ($rolSolicitado === "docente") {
                $rolId         = 3;
                $activoInicial = 2;
            } else {
                $rolId         = 2;
                $activoInicial = 1;
            }

            $nuevoId = AuthModel::crear($conexion, [
                "nombre"   => $nombre,
                "apellido" => $apellido,
                "correo"   => $correo,
                "password" => $passwordHash,
                "rolId"    => $rolId,
                "activo"   => $activoInicial,
            ]);

            /* Avisar al equipo (campana + correo). Nunca rompe el registro. */
            Notificador::cuentaNueva(
                trim($nombre . " " . ($apellido === "No especificado" ? "" : $apellido)),
                $correo,
                $rolId === 3,
                "con correo y contraseña"
            );

            /* =========================================================
               DOCENTE (activo = 2, pendiente)
            ========================================================= */
            if ($activoInicial === 2) {

                $hayAdminLogueado = isset($_SESSION["usuario_id"])
                    && in_array(rolActual(), ["admin", "superadmin"], true);

                if ($hayAdminLogueado) {
                    echo json_encode([
                        "success"  => true,
                        "message"  => "Docente creado. Queda pendiente de aprobación.",
                        "redirect" => "../admin/index.php?page=solicitudes"
                    ]);
                    exit;
                }

                /* Visitante → iniciar sesión como docente pendiente */
                session_regenerate_id(true);

                $_SESSION["usuario_id"] = $nuevoId;
                $_SESSION["nombre"]     = $nombre;
                $_SESSION["apellido"]   = $apellido;
                $_SESSION["correo"]     = $correo;
                $_SESSION["rol_id"]     = $rolId;
                $_SESSION["rol"]        = "docente";

                echo json_encode([
                    "success"  => true,
                    "message"  => "Tu solicitud como docente fue enviada. Un administrador debe aprobarla antes de que puedas acceder al panel.",
                    "redirect" => "../profesor/pendiente.php"
                ]);
                exit;
            }

            /* =========================================================
               ESTUDIANTE (activo = 1)
            ========================================================= */
            $hayAdminLogueado = isset($_SESSION["usuario_id"])
                && in_array(rolActual(), ["admin", "superadmin"], true);

            if ($hayAdminLogueado) {
                echo json_encode([
                    "success"  => true,
                    "message"  => "Estudiante creado correctamente.",
                    "redirect" => "../admin/index.php?page=usuarios"
                ]);
                exit;
            }

            /* Visitante → iniciar sesión como estudiante */
            session_regenerate_id(true);

            $_SESSION["usuario_id"] = $nuevoId;
            $_SESSION["nombre"]     = $nombre;
            $_SESSION["apellido"]   = $apellido;
            $_SESSION["correo"]     = $correo;
            $_SESSION["rol_id"]     = $rolId;
            $_SESSION["rol"]        = "estudiante";

            echo json_encode([
                "success"  => true,
                "message"  => "Cuenta creada correctamente. ¡Bienvenido!",
                "redirect" => "../estudiante/index.php"
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

        header("Location: ../index.html");
        exit;
    }

    public function csrfToken(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        /* No guardar en caché: cada sesión tiene su propio token */
        header("Cache-Control: no-store");

        echo json_encode([
            "csrf_token" => generarCsrfToken(),
            // Reglas de Configuración > Seguridad, para mostrarlas en el registro
            "reglas"     => Seguridad::reglasPassword(),
        ]);
    }
}