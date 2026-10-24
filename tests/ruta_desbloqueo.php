<?php
/**
 * Desbloqueo de la ruta (RutaModel::mapa, estadoLeccion, siguiente):
 * lección por lección, nivel por nivel, y con examen de ubicación.
 */
$en = P::idioma($c, "EN");
$a1 = P::lecciones($c, $en, "A1");
$a2 = P::lecciones($c, $en, "A2");
P::ver("hay lecciones de A1 y A2 en inglés para probar", count($a1) > 2 && count($a2) > 2);

$estadoDe = function (array $mapa, int $leccionId): string {
    foreach ($mapa as $n) {
        foreach ($n["unidades"] as $u) {
            foreach ($u["lecciones"] as $l) {
                if ((int) $l["id"] === $leccionId) {
                    return $l["estado"];
                }
            }
        }
    }
    return "";
};
$nivel = fn(array $mapa, string $codigo) => array_values(array_filter($mapa, fn($n) => $n["codigo"] === $codigo))[0] ?? null;

// Estudiante nuevo
$u = P::usuario($c, "estudiante");
$mapa = RutaModel::mapa($c, $en, $u);
P::ver("nuevo: A1 abierto", $nivel($mapa, "A1")["estado"] === "abierto");
P::ver("nuevo: A2 bloqueado", $nivel($mapa, "A2")["estado"] === "bloqueado");
P::ver("nuevo: la primera lección disponible", $estadoDe($mapa, (int) $a1[0]["id"]) === "disponible");
P::ver("nuevo: la segunda bloqueada", $estadoDe($mapa, (int) $a1[1]["id"]) === "bloqueada");
P::ver("nuevo: ningún nivel queda como Premium (toda la ruta es gratis)", !in_array("premium", array_column($mapa, "estado"), true));
$sig = RutaModel::siguiente($c, $en, $u);
P::ver("nuevo: «Continúa tu ruta» es la primera lección", $sig && (int) $sig["id"] === (int) $a1[0]["id"]);

// Completa la primera: se abre la segunda
P::completar($c, $u, [$a1[0]]);
$mapa = RutaModel::mapa($c, $en, $u);
P::ver("tras la 1.ª: completada", $estadoDe($mapa, (int) $a1[0]["id"]) === "completada");
P::ver("tras la 1.ª: la 2.ª disponible", $estadoDe($mapa, (int) $a1[1]["id"]) === "disponible");
P::ver("tras la 1.ª: la 3.ª sigue bloqueada", $estadoDe($mapa, (int) $a1[2]["id"]) === "bloqueada");
P::ver("estadoLeccion coincide con el mapa", RutaModel::estadoLeccion($c, ["id" => $a1[1]["id"], "idioma_id" => $en], $u) === "disponible");

// Completa todo el A1: se abre el A2
P::completar($c, $u, $a1);
$mapa = RutaModel::mapa($c, $en, $u);
P::ver("A1 completo: A2 abierto", $nivel($mapa, "A2")["estado"] === "abierto");
P::ver("A1 completo: la 1.ª del A2 disponible", $estadoDe($mapa, (int) $a2[0]["id"]) === "disponible");
P::ver("A1 completo: B1 sigue bloqueado", $nivel($mapa, "B1")["estado"] === "bloqueado");
$sig = RutaModel::siguiente($c, $en, $u);
P::ver("A1 completo: «Continúa tu ruta» va al A2", $sig && $sig["nivel"] === "A2");

// Con examen de ubicación en B1: A1 y A2 libres, B1 abierto, B2 bloqueado
$u2 = P::usuario($c, "estudiante");
$c->prepare("INSERT INTO ruta_ubicacion (usuario_id, idioma_id, nivel_codigo, ultimo_intento) VALUES (?, ?, 'B1', NOW())")->execute([$u2, $en]);
$mapa = RutaModel::mapa($c, $en, $u2);
P::ver("ubicado en B1: A2 abierto y saltado", $nivel($mapa, "A2")["estado"] === "abierto" && $nivel($mapa, "A2")["saltado"] === true);
P::ver("ubicado en B1: cualquier lección del A2 disponible", $estadoDe($mapa, (int) end($a2)["id"]) === "disponible");
P::ver("ubicado en B1: B1 abierto, no saltado", $nivel($mapa, "B1")["estado"] === "abierto" && $nivel($mapa, "B1")["saltado"] === false);
P::ver("ubicado en B1: B2 bloqueado", $nivel($mapa, "B2")["estado"] === "bloqueado");
$sig = RutaModel::siguiente($c, $en, $u2);
P::ver("ubicado en B1: «Continúa tu ruta» va al B1", $sig && $sig["nivel"] === "B1");
P::ver("ubicado en B1: no se marcó nada como completado", (int) $c->query("SELECT COUNT(*) FROM ruta_progreso WHERE usuario_id = $u2")->fetchColumn() === 0);
