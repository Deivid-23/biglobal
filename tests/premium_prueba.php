<?php
/**
 * Prueba gratis de Premium (Plan::puedeProbar / empezarPrueba) y sus avisos
 * en la tarea diaria. La tarea diaria revisa TODAS las suscripciones, así que
 * corre dentro de una transacción que se deshace.
 */
$u = P::usuario($c, "estudiante");
$yaTuvo = P::usuario($c, "estudiante");
Plan::otorgarManual($c, $yaTuvo, date("Y-m-d H:i:s", strtotime("-1 day")), 0, "Cortesía vieja");
$docente = P::usuario($c, "docente");

P::config("premium_prueba_activa", "0");
P::ver("apagada: no puede", !Plan::puedeProbar($c, $u) && Plan::empezarPrueba($c, $u) === null);

P::config("premium_prueba_activa", "1");
P::config("premium_prueba_dias", "7");
P::ver("encendida: el estudiante nuevo puede", Plan::puedeProbar($c, $u));
P::ver("quien ya tuvo Premium no puede", !Plan::puedeProbar($c, $yaTuvo));
P::ver("un docente no puede", !Plan::puedeProbar($c, $docente));

$fin = Plan::empezarPrueba($c, $u);
P::ver("empieza: 7 días", $fin !== null && (int) round((strtotime($fin) - time()) / 86400) === 7);
P::ver("queda Premium (prueba gratis)", Plan::esPremium($c, $u) && Plan::nombre(Plan::codigoActual($c, $u)) === "Premium (prueba gratis)");
P::ver("una sola vez", !Plan::puedeProbar($c, $u) && Plan::empezarPrueba($c, $u) === null
    && (int) $c->query("SELECT COUNT(*) FROM suscripciones WHERE usuario_id = $u")->fetchColumn() === 1);
P::ver("con la prueba entra a clases de pago", Plan::puedeUnirseAClase($c, $u, false));

P::config("premium_prueba_dias", "99");
P::ver("los días se limitan a 30", Plan::diasPrueba() === 30);

// Avisos de la tarea diaria
$aviso = fn(string $tipo) => (string) $c->query("SELECT titulo FROM notificaciones WHERE usuario_id = $u AND tipo = '$tipo' ORDER BY id DESC LIMIT 1")->fetchColumn();
$c->beginTransaction();
$c->exec("UPDATE suscripciones SET fin = NOW() + INTERVAL 2 DAY WHERE usuario_id = $u");
PagoModel::tareaDiaria($c);
P::ver("faltando pocos días: «Tu prueba de Premium termina pronto»", $aviso("premium_por_vencer") === "Tu prueba de Premium termina pronto");
$c->exec("UPDATE suscripciones SET fin = NOW() - INTERVAL 1 MINUTE WHERE usuario_id = $u");
PagoModel::tareaDiaria($c);
P::ver("al terminar: «Tu prueba de Premium terminó» y vuelve a Gratuito", $aviso("premium_terminado") === "Tu prueba de Premium terminó" && !Plan::esPremium($c, $u));
P::ver("y no puede repetirla", !Plan::puedeProbar($c, $u));
$c->rollBack();
