<?php
/**
 * «No puedo escuchar ahora» (escuchar y dictado): en la lección sale de la
 * cola sin contar como error ni ir al repaso; en el repaso se queda para otro
 * día; en el contrarreloj pasa al siguiente sin sumar. Los demás tipos no se
 * pueden saltar.
 */
$en = P::idioma($c, "EN");
$u = P::usuario($c, "estudiante");

// Una lección publicada del A1 con un ejercicio de escuchar o dictado
$leccion = null;
$ejs = [];
foreach (P::lecciones($c, $en, "A1") as $l) {
    $ejs = RutaModel::ejercicios($c, (int) $l["id"], true);
    if (array_filter($ejs, fn($e) => in_array($e["tipo"], RutaModel::SIN_AUDIO, true))) {
        $leccion = $l;
        break;
    }
}
P::ver("hay una lección con escuchar o dictado para probar", $leccion !== null);
$porId = array_column($ejs, null, "id");
$lid = (int) $leccion["id"];
$esAudio = fn(int $id) => in_array($porId[$id]["tipo"], RutaModel::SIN_AUDIO, true);
$enRepaso = fn(int $id) => (int) $c->query("SELECT COUNT(*) FROM ruta_repaso WHERE usuario_id = $u AND ejercicio_id = $id AND resuelto_en IS NULL")->fetchColumn() === 1;

// ---------- Lección: saltar el audio y acertar lo demás = 100 % ----------
RutaModel::iniciarIntento($lid, $ejs);
$saltados = [];
$noAudioRechazado = null;
for ($i = 0, $res = null; $i < 60 && !$res; $i++) {
    $id = (int) $_SESSION["ruta_intento"]["cola"][0];
    if ($esAudio($id)) {
        $r = RutaModel::responder($c, $u, $lid, $id, "", true);
        $saltados[] = $r;
    } else {
        if ($noAudioRechazado === null) {
            $noAudioRechazado = isset(RutaModel::responder($c, $u, $lid, $id, "", true)["error"]) && (int) $_SESSION["ruta_intento"]["cola"][0] === $id;
        }
        $r = RutaModel::responder($c, $u, $lid, $id, P::respuestaCorrecta($porId[$id]));
    }
    $res = $r["resultado"] ?? null;
}
P::ver("saltar un ejercicio que no es de audio da error y no cambia la cola", $noAudioRechazado === true);
P::ver("el salto avisa que fue saltado y muestra la solución", $saltados && !array_filter($saltados, fn($r) => empty($r["saltado"]) || (string) $r["solucion"] === ""));
P::ver("saltar el audio y acertar lo demás da 100 %", ($res["porcentaje"] ?? null) === 100);
$idsAudio = array_keys(array_filter($porId, fn($e) => in_array($e["tipo"], RutaModel::SIN_AUDIO, true)));
P::ver("lo saltado no entra al repaso de errores", !array_filter($idsAudio, fn($id) => $enRepaso((int) $id)));

// ---------- Si ya lo había fallado, saltarlo después no borra ese fallo ----------
RutaModel::iniciarIntento($lid, $ejs);
$falloAntes = false;
for ($i = 0, $res = null; $i < 80 && !$res; $i++) {
    $id = (int) $_SESSION["ruta_intento"]["cola"][0];
    if ($esAudio($id) && !$falloAntes) {
        $r = RutaModel::responder($c, $u, $lid, $id, "__mal__");
        $falloAntes = true;
    } elseif ($esAudio($id)) {
        $r = RutaModel::responder($c, $u, $lid, $id, "", true);
    } else {
        $r = RutaModel::responder($c, $u, $lid, $id, P::respuestaCorrecta($porId[$id]));
    }
    $res = $r["resultado"] ?? null;
}
P::ver("fallar y luego saltar deja la lección por debajo de 100 %", $falloAntes && ($res["porcentaje"] ?? 100) < 100);

// ---------- Repaso: el saltado sigue en la lista ----------
$audio = (int) $idsAudio[0];
RepasoModel::anotarFallo($c, $u, $audio);
$lista = RepasoModel::iniciar($c, $u, $en);
$_SESSION["ruta_repaso"]["cola"] = array_values(array_unique(array_merge([$audio], array_map("intval", $_SESSION["ruta_repaso"]["cola"]))));
$r = RepasoModel::responder($c, $u, $en, $audio, "", true);
P::ver("en el repaso el audio se puede saltar", !empty($r["saltado"]));
P::ver("en el repaso lo saltado sigue pendiente para otro día", $enRepaso($audio));

// ---------- Contrarreloj: saltar no suma ni resta ----------
P::completar($c, $u, [$leccion]);
$juego = ContrarrelojModel::iniciar($c, $u, $en, 5000);
if ($juego) {
    ContrarrelojModel::arrancar($en, 5000);
    $otro = (int) $_SESSION["ruta_contrarreloj"]["cola"][0];
    $_SESSION["ruta_contrarreloj"]["cola"] = array_values(array_unique(array_merge([$audio], array_map("intval", $_SESSION["ruta_contrarreloj"]["cola"]))));
    $antes = $_SESSION["ruta_contrarreloj"];
    $r = ContrarrelojModel::responder($c, $u, $en, $audio, "", 5001, true);
    $x = $_SESSION["ruta_contrarreloj"];
    P::ver("en el contrarreloj saltar pasa al siguiente sin sumar respondidas ni aciertos",
        !empty($r["saltado"]) && $x["respondidas"] === $antes["respondidas"] && $x["aciertos"] === $antes["aciertos"] && (int) ($r["siguiente"] ?? 0) === (int) $x["cola"][0]);
    $noAudio = (int) $x["cola"][0];
    if (!in_array(RutaModel::ejercicio($c, $noAudio)["tipo"], RutaModel::SIN_AUDIO, true)) {
        P::ver("en el contrarreloj no se salta lo que no es audio", isset(ContrarrelojModel::responder($c, $u, $en, $noAudio, "", 5002, true)["error"]));
    }
} else {
    P::ver("hay contrarreloj para probar el salto", false);
}
unset($_SESSION["ruta_contrarreloj"], $_SESSION["ruta_repaso"], $_SESSION["ruta_intento"]);
