<?php
/**
 * Tabla de posiciones (PosicionesModel): XP de la semana, orden, empates,
 * privacidad, quién nunca aparece, grupos y la posición propia.
 * Suma el XP de TODOS los estudiantes, así que corre en una transacción
 * sin el XP de los demás y se deshace al final.
 */
$c->beginTransaction();
$c->exec("DELETE FROM xp_movimientos");   // solo dentro de la transacción

$ana = P::usuario($c, "estudiante");
$beto = P::usuario($c, "estudiante");
$caro = P::usuario($c, "estudiante");
$dani = P::usuario($c, "estudiante");
$docente = P::usuario($c, "docente");
$c->prepare("UPDATE usuarios SET nombre = 'Ana María', apellido = 'Gómez' WHERE id = ?")->execute([$ana]);
$c->prepare("UPDATE usuarios SET nombre = 'Beto', apellido = 'No especificado' WHERE id = ?")->execute([$beto]);
// Las pruebas usan correos prueba-auto-…: para esta prueba se les pone uno normal (se deshace al final)
foreach ([$ana, $beto, $caro, $dani, $docente] as $id) {
    $c->prepare("UPDATE usuarios SET correo = CONCAT('posiciones-', id, '@ejemplo.test') WHERE id = ?")->execute([$id]);
}
$xp = function (int $u, int $puntos, string $cuando = "NOW()") use ($c) {
    $c->prepare("INSERT INTO xp_movimientos (usuario_id, puntos, motivo, fecha) VALUES (?, ?, 'prueba', $cuando)")->execute([$u, $puntos]);
};
$xp($ana, 30, "NOW() - INTERVAL 1 MINUTE");
$xp($beto, 30);                     // empata con Ana, pero llegó después
$xp($caro, 50);
$xp($dani, 10);
$xp($docente, 99);                  // un docente nunca aparece
$xp($dani, 500, "CONCAT(CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY, ' 00:00:00') - INTERVAL 1 SECOND");   // la semana pasada no cuenta

$t = PosicionesModel::general($c, $ana);
$ids = array_column($t["filas"], "id");
P::ver("orden por XP de la semana: Caro, Ana, Beto, Dani", $ids === [$caro, $ana, $beto, $dani]);
P::ver("puestos con empate: 1, 2, 2, 4", array_column($t["filas"], "puesto") === [1, 2, 2, 4]);
P::ver("el XP de la semana pasada no cuenta (Dani: 10)", $t["filas"][3]["xp"] === 10);
P::ver("el docente no aparece", !in_array($docente, $ids, true));
P::ver("nombre corto: «Ana G.»", $t["filas"][1]["nombre"] === "Ana G.");
P::ver("sin apellido real: solo «Beto»", $t["filas"][2]["nombre"] === "Beto");
P::ver("no trae el correo", !preg_match('/@/', json_encode($t)));
P::ver("mi fila (Ana) viene marcada", $t["yo"] && $t["yo"]["id"] === $ana && $t["filas"][1]["soyYo"] === true);

// Privacidad: Caro se oculta
PosicionesModel::guardarVisible($c, $caro, false);
P::ver("oculto: los demás no la ven", !in_array($caro, array_column(PosicionesModel::general($c, $ana)["filas"], "id"), true));
$suya = PosicionesModel::general($c, $caro);
P::ver("oculto: ella sí ve su puesto (1)", $suya["yo"] && $suya["yo"]["puesto"] === 1);
P::ver("guardar la casilla queda en la cuenta", PosicionesModel::visible($c, $caro) === false);
PosicionesModel::guardarVisible($c, $caro, true);

// Las cuentas de las pruebas automáticas nunca aparecen
$auto = P::usuario($c, "estudiante");
$xp($auto, 1000);
P::ver("las cuentas prueba-auto no aparecen", !in_array($auto, array_column(PosicionesModel::general($c, $ana)["filas"], "id"), true));

// Cuenta inactiva
$c->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?")->execute([$dani]);
P::ver("una cuenta inactiva no aparece", !in_array($dani, array_column(PosicionesModel::general($c, $ana)["filas"], "id"), true));
$c->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?")->execute([$dani]);

// Grupo: solo sus miembros
$c->prepare("INSERT INTO grupos (docente_id, nombre, codigo, gratuito) VALUES (?, 'Grupo posiciones', ?, 1)")->execute([$docente, GrupoModel::generarCodigo($c)]);
$g = (int) $c->lastInsertId();
foreach ([$ana, $dani] as $m) {
    $c->prepare("INSERT INTO grupo_miembros (grupo_id, usuario_id) VALUES (?, ?)")->execute([$g, $m]);
}
$tg = PosicionesModel::grupo($c, $ana, $g);
P::ver("grupo: solo Ana y Dani, Ana primera", array_column($tg["filas"], "id") === [$ana, $dani] && $tg["filas"][0]["puesto"] === 1);

// Más de 20: los primeros 20 y mi puesto aparte
for ($k = 0; $k < 22; $k++) {
    $otro = P::usuario($c, "estudiante");
    $c->prepare("UPDATE usuarios SET correo = CONCAT('posiciones-', id, '@ejemplo.test') WHERE id = ?")->execute([$otro]);
    $xp($otro, 200 + $k);
}
$t = PosicionesModel::general($c, $dani);
P::ver("muestra solo " . PosicionesModel::LIMITE . " filas", count($t["filas"]) === PosicionesModel::LIMITE);
P::ver("mi puesto fuera del top viene aparte (Dani: 26 de 26)", $t["yo"] && $t["yo"]["puesto"] === 26 && $t["total"] === 26);

$c->rollBack();
