<?php

require_once __DIR__ . "/../Models/PerfilModel.php";

/**
 * CuentaController
 *
 * Acciones sobre la PROPIA cuenta para los paneles de gestión
 * (admin, super administrador y docente):
 *   guardarPerfil()   -> nombre, apellido y correo
 *   guardarPassword() -> cambiar contraseña
 *   guardarTema()     -> claro / oscuro (lo llama tema.js con fetch)
 *
 * El estudiante tiene su propio perfil en EstudianteController.
 */
class CuentaController
{
    private PDO $c;
    private int $id;

    public function __construct()
    {
        protegerSesion();
        $this->c = Conexion::conectar();
        $this->id = (int) $_SESSION["usuario_id"];
    }

    /** A dónde vuelve cada rol después de guardar. */
    private function urlPerfil(): string
    {
        return rolActual() === "docente" ? "../profesor/index.php?page=perfil" : "../admin/index.php?page=perfil";
    }

    public function guardarPerfil(): void
    {
        protegerRol(["admin", "docente"]);
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $nombre   = trim($_POST["nombre"] ?? "");
        $apellido = trim($_POST["apellido"] ?? "");
        $correo   = mb_strtolower(trim($_POST["correo"] ?? ""));

        if ($nombre === "" || mb_strlen($nombre) > 100 || mb_strlen($apellido) > 100) {
            Panel::volver($this->urlPerfil(), "error", "Revisa el nombre (máximo 100 caracteres).");
        }
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 150) {
            Panel::volver($this->urlPerfil(), "error", "Escribe un correo válido.");
        }
        if (PerfilModel::correoEnUso($this->c, $correo, $this->id)) {
            Panel::volver($this->urlPerfil(), "error", "Ese correo ya lo usa otra cuenta.");
        }

        PerfilModel::actualizarDatos($this->c, $this->id, $nombre, $apellido !== "" ? $apellido : "No especificado", $correo);

        $_SESSION["nombre"]   = $nombre;
        $_SESSION["apellido"] = $apellido !== "" ? $apellido : "No especificado";
        $_SESSION["correo"]   = $correo;

        Panel::volver($this->urlPerfil(), "ok", "Datos guardados.");
    }

    public function guardarPassword(): void
    {
        protegerRol(["admin", "docente"]);
        verificarCsrfFormulario($_POST["csrf_token"] ?? "");

        $actual    = $_POST["password_actual"] ?? "";
        $nueva     = $_POST["password_nueva"] ?? "";
        $confirmar = $_POST["password_confirmar"] ?? "";
        $hash      = PerfilModel::obtenerHash($this->c, $this->id);

        if ($hash === null) {
            Panel::volver($this->urlPerfil(), "error", "Tu cuenta entra con Google. Usa «Crear contraseña».");
        }

        // Límite de intentos: 5 fallos seguidos -> 15 minutos de espera
        if (time() < ($_SESSION["cuenta_bloqueo"] ?? 0)) {
            Panel::volver($this->urlPerfil(), "error", "Demasiados intentos. Espera unos minutos.");
        }

        if (!password_verify($actual, $hash)) {
            $_SESSION["cuenta_intentos"] = ($_SESSION["cuenta_intentos"] ?? 0) + 1;
            if ($_SESSION["cuenta_intentos"] >= 5) {
                $_SESSION["cuenta_bloqueo"] = time() + 15 * 60;
                $_SESSION["cuenta_intentos"] = 0;
            }
            Panel::volver($this->urlPerfil(), "error", "La contraseña actual no es correcta.");
        }

        $_SESSION["cuenta_intentos"] = 0;

        if ($nueva !== $confirmar) {
            Panel::volver($this->urlPerfil(), "error", "La nueva contraseña y su confirmación no coinciden.");
        }
        if (($error = Seguridad::validarPassword($nueva)) !== null) {
            Panel::volver($this->urlPerfil(), "error", $error);
        }

        PerfilModel::actualizarPassword($this->c, $this->id, $nueva);
        session_regenerate_id(true);

        Panel::volver($this->urlPerfil(), "ok", "Contraseña actualizada.");
    }

    /**
     * Guarda el color de acento personal (#RRGGBB) en usuarios.color_acento.
     * Un valor vacío lo borra y la persona vuelve al color del sitio.
     * Lo usan todos los roles desde "Mi perfil". Responde JSON.
     */
    public function guardarAcento(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        if (!csrfValido($_POST["csrf_token"] ?? "")) {
            http_response_code(419);
            echo json_encode(["ok" => false, "mensaje" => "Tu sesión expiró. Recarga la página."]);
            return;
        }

        $color = strtolower(trim((string) ($_POST["color"] ?? "")));

        if ($color !== "" && !Panel::esColor($color)) {
            http_response_code(422);
            echo json_encode(["ok" => false, "mensaje" => "Ese color no es válido."]);
            return;
        }

        try {
            $this->c->prepare("UPDATE usuarios SET color_acento = ? WHERE id = ?")
                ->execute([$color !== "" ? $color : null, $this->id]);
            echo json_encode(["ok" => true]);
        } catch (PDOException $e) {
            error_log("[BiGlobal][acento] " . $e->getMessage());
            echo json_encode(["ok" => false, "mensaje" => "No pudimos guardar el color."]);
        }
    }

    /** Guarda "claro" u "oscuro" en usuarios.tema. Responde JSON. */
    public function guardarTema(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        if (!csrfValido($_POST["csrf_token"] ?? "")) {
            http_response_code(419);
            echo json_encode(["ok" => false]);
            return;
        }

        $tema = $_POST["tema"] ?? "";
        if (!in_array($tema, ["claro", "oscuro"], true)) {
            http_response_code(422);
            echo json_encode(["ok" => false]);
            return;
        }

        try {
            $this->c->prepare("UPDATE usuarios SET tema = ? WHERE id = ?")->execute([$tema, $this->id]);
            $_SESSION["tema"] = $tema;
            echo json_encode(["ok" => true]);
        } catch (PDOException $e) {
            // Si la columna "tema" todavía no existe, el tema queda solo en el navegador
            error_log("[BiGlobal][tema] " . $e->getMessage());
            echo json_encode(["ok" => false]);
        }
    }
}
