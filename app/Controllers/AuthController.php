<?php

require_once __DIR__ . "/../Models/AuthModel.php";
require_once __DIR__ . "/../Models/ContenidoModel.php";
require_once __DIR__ . "/../Models/DocenteSolicitudModel.php";

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

        $correo = mb_strtolower(trim($_POST["correo"] ?? ""));
        $password = $_POST["password"] ?? "";

        if ($correo === "" || $password === "") {
            echo json_encode(["success" => false, "message" => "Correo y contraseña son obligatorios."]);
            exit;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "El correo electrónico no es válido."]);
            exit;
        }

        /* Límite de intentos guardado en el servidor (ya no en la sesión,
           que se reiniciaba con solo borrar la cookie):
             - por cuenta: los fallos de ese correo, venga de donde venga
             - por IP: un tope más amplio para quien prueba muchos correos */
        $claveCuenta = "login:correo:" . $correo;
        $claveIp     = "login:ip:" . LimiteIntentos::ipCliente();

        $espera = max(LimiteIntentos::espera($claveCuenta), LimiteIntentos::espera($claveIp));
        if ($espera > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Demasiados intentos. Inténtalo de nuevo en {$espera} segundos."
            ]);
            exit;
        }

        /* Mismo mensaje para todo fallo: así nadie averigua qué correos
           existen, cuáles están inactivos o cuáles se crearon con Google. */
        $mensajeFallo = "El correo o la contraseña son incorrectos. Si creaste tu cuenta con Google, usa «Continuar con Google».";

        try {

            $conexion = Conexion::conectar();

            $usuario = AuthModel::buscarPorCorreo($conexion, $correo);

            /* Siempre se compara un hash (aunque el correo no exista o la cuenta
               sea de Google) para que el tiempo de respuesta no delate nada. */
            $hash = ($usuario && !empty($usuario["password"]))
                ? $usuario["password"]
                : '$2y$10$x1Evr22uJowPvyjQpleQQ.KpPzbz.jVgwBs7FHmOw2bnxpMDKYbfW';
            $passwordCorrecta = password_verify($password, $hash) && $usuario && !empty($usuario["password"]);

            if (!$passwordCorrecta) {
                LimiteIntentos::fallo($claveCuenta, $maxIntentos, $tiempoBloqueoSegundos);
                LimiteIntentos::fallo($claveIp, $maxIntentos * 4, $tiempoBloqueoSegundos);

                echo json_encode(["success" => false, "message" => $mensajeFallo]);
                exit;
            }

            /* Desde aquí la contraseña ya es correcta */
            LimiteIntentos::limpiar($claveCuenta);

            $activo = (int) ($usuario["activo"] ?? 0);

            /* activo = 3 → pidió eliminar su cuenta: si todavía está en los 30 días, puede recuperarla */
            if ($activo === 3) {
                $destino = iniciarRecuperacionCuenta((int) $usuario["id"]);
                echo json_encode($destino
                    ? ["success" => true, "message" => "Tu cuenta tiene una eliminación pendiente.", "redirect" => $destino]
                    : ["success" => false, "message" => "Esta cuenta ya fue eliminada."]);
                exit;
            }

            /* activo = 0 (inactiva) o 4 (eliminada) → no entra */
            if (!in_array($activo, [1, 2], true)) {
                echo json_encode([
                    "success" => false,
                    "message" => "Tu cuenta está inactiva. Contacta al administrador."
                ]);
                exit;
            }

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

            /* activo = 1 → login normal */
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

            /* Tope de registros: protege la BD y el cupo diario de correos
               (cada registro avisa al equipo). Por IP y en total por hora. */
            if (!LimiteIntentos::permitir("registro:ip:" . LimiteIntentos::ipCliente(), 5, 3600)
                || !LimiteIntentos::permitir("registro:global", 40, 3600)) {
                error_log("[BiGlobal][registro] Tope de registros alcanzado.");
                echo json_encode(["success" => false, "message" => "Hay demasiados registros en este momento. Inténtalo de nuevo en un rato."]);
                exit;
            }

            /* Docente: idioma que enseñará y diplomas o certificados (obligatorios) */
            $idiomaDocente = null;
            $archivosDocente = [];
            $conDocumentos = $rolSolicitado === "docente" && DocenteSolicitudModel::disponible($conexion);
            if ($conDocumentos) {
                [$idiomaDocente, $errorIdioma] = ContenidoModel::idiomaElegido($conexion, $_POST["idioma_id"] ?? "");
                if ($errorIdioma || $idiomaDocente === null) {
                    echo json_encode(["success" => false, "message" => "Elige el idioma que vas a enseñar."]);
                    exit;
                }
                $archivosDocente = DocenteSolicitudModel::validarArchivos($_FILES["documentos"] ?? null);
                if (is_string($archivosDocente)) {
                    echo json_encode(["success" => false, "message" => $archivosDocente]);
                    exit;
                }
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

            // Cuenta y documentos juntos: o se guarda todo o nada
            $conexion->beginTransaction();
            try {
                $nuevoId = AuthModel::crear($conexion, [
                    "nombre"   => $nombre,
                    "apellido" => $apellido,
                    "correo"   => $correo,
                    "password" => $passwordHash,
                    "rolId"    => $rolId,
                    "activo"   => $activoInicial,
                ]);
                if ($conDocumentos) {
                    DocenteSolicitudModel::guardarSolicitud($conexion, (int) $nuevoId, $idiomaDocente);
                    DocenteSolicitudModel::guardarDocumentos($conexion, (int) $nuevoId, $archivosDocente);
                }
                $conexion->commit();
            } catch (Throwable $e) {
                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                throw $e;
            }

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

    /**
     * Cerrar sesión.
     * Solo por POST con el token CSRF: así otra página no puede sacarte
     * de tu cuenta con un simple enlace o una imagen.
     * Por GET (o con un token vencido) se muestra una pantalla para
     * confirmar con un botón.
     */
    public function logout(): void
    {
        $sinSesion = empty($_SESSION["usuario_id"]);

        if ($sinSesion || ($_SERVER["REQUEST_METHOD"] === "POST" && csrfValido((string) ($_POST["csrf_token"] ?? "")))) {
            cerrarSesion();
            header("Location: ../index.html");
            exit;
        }

        require __DIR__ . "/../Views/auth/confirmar_salida.php";
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
            // Idiomas para el campo "Idioma que enseñarás" del registro de docente
            "idiomas"    => array_map(fn($i) => ["id" => (int) $i["id"], "nombre" => $i["nombre"]], self::idiomasPublicos()),
        ]);
    }

    private static function idiomasPublicos(): array
    {
        try {
            return ContenidoModel::idiomasActivos(Conexion::conectar());
        } catch (Throwable $e) {
            return [];
        }
    }
}