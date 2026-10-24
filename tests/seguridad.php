<?php
/**
 * Seguridad: IP del visitante detrás de Cloudflare y lo que se borra al
 * eliminar una cuenta.
 */

// ---------- IP (LimiteIntentos::ipCliente) ----------
$servidor = $_SERVER;
$ip = function (array $s): string {
    $_SERVER = $s;
    return LimiteIntentos::ipCliente();
};
$real = "181.79.83.114";
P::ver("IP: visita normal en Render", $ip(["REMOTE_ADDR" => "::1", "HTTP_CF_CONNECTING_IP" => $real, "HTTP_X_FORWARDED_FOR" => "$real, 172.64.208.57, 10.194.13.185"]) === $real);
P::ver("IP: X-Forwarded-For inventado no cambia nada", $ip(["REMOTE_ADDR" => "::1", "HTTP_CF_CONNECTING_IP" => $real, "HTTP_X_FORWARDED_FOR" => "1.2.3.4, $real, 172.64.208.57, 10.194.13.185"]) === $real);
P::ver("IP: sin CF-Connecting-IP, la de Cloudflare contando desde el final", $ip(["REMOTE_ADDR" => "::1", "HTTP_X_FORWARDED_FOR" => "1.2.3.4, $real, 172.64.208.57, 10.194.13.185"]) === $real);
P::ver("IP: un CF-Connecting-IP inválido no se usa", $ip(["REMOTE_ADDR" => "::1", "HTTP_CF_CONNECTING_IP" => "<script>", "HTTP_X_FORWARDED_FOR" => "$real, 172.64.208.57, 10.194.13.185"]) === $real);
P::ver("IP: en local, REMOTE_ADDR aunque inventen X-Forwarded-For", $ip(["REMOTE_ADDR" => "127.0.0.1", "HTTP_X_FORWARDED_FOR" => "9.9.9.9"]) === "127.0.0.1");
$_SERVER = $servidor;

// ---------- Eliminar una cuenta (CuentaEliminacionModel::anonimizar) ----------
$en = P::idioma($c, "EN");
$d = P::usuario($c, "docente");
$c->prepare("INSERT INTO docente_documentos (usuario_id, nombre_original, tipo_mime, tamano, sha256, contenido) VALUES (?, 'cedula.pdf', 'application/pdf', 5, ?, '%PDF-')")->execute([$d, hash("sha256", "x")]);
$c->prepare("INSERT INTO docente_solicitudes (usuario_id, estado) VALUES (?, 'aprobada')")->execute([$d]);
$c->prepare("INSERT INTO ruta_ubicacion (usuario_id, idioma_id, nivel_codigo) VALUES (?, ?, 'B1')")->execute([$d, $en]);
$c->prepare("INSERT INTO ruta_intentos (usuario_id, leccion_id) VALUES (?, ?)")->execute([$d, (int) P::lecciones($c, $en, "A1")[0]["id"]]);
$cuenta = fn(string $t) => (int) $c->query("SELECT COUNT(*) FROM $t WHERE usuario_id = $d")->fetchColumn();
ob_start();
CuentaEliminacionModel::anonimizar($c, $d);
ob_end_clean();
P::ver("eliminar: borra los documentos del docente", $cuenta("docente_documentos") === 0);
P::ver("eliminar: borra la solicitud de docente", $cuenta("docente_solicitudes") === 0);
P::ver("eliminar: borra el examen de ubicación", $cuenta("ruta_ubicacion") === 0);
P::ver("eliminar: borra los intentos de la ruta", $cuenta("ruta_intentos") === 0);
$fila = $c->query("SELECT nombre, apellido, correo, password, activo FROM usuarios WHERE id = $d")->fetch(PDO::FETCH_ASSOC);
P::ver("eliminar: la cuenta queda anónima y sin forma de entrar",
    $fila["nombre"] === "Cuenta" && $fila["apellido"] === "eliminada" && str_ends_with($fila["correo"], "@biglobal.invalid") && $fila["password"] === null && (int) $fila["activo"] === CuentaEliminacionModel::ELIMINADA);
