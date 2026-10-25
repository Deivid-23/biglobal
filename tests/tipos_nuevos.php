<?php
/**
 * Tipos de ejercicio de la fase 1 de juegos: «elegir» (imagen o seña),
 * «memoria» y «deletreo», y el dibujo (emoji) en opción múltiple y V/F.
 * Validación del formulario del admin, calificación y vista pública.
 */
$video = "https://youtu.be/dQw4w9WgXcQ";
$video2 = "https://www.youtube.com/watch?v=9bZkp7q19f0";
$form = fn(array $extra) => RutaModel::datosEjercicio($extra + ["enunciado" => "Prueba", "orden" => 1, "activo" => 1]);
$ej = fn(array $d) => ["id" => 1, "tipo" => $d["tipo"], "enunciado" => $d["enunciado"], "datos" => $d["datos"], "video_url" => $d["video_url"]];

// ---------- Elegir ----------
$d = $form(["tipo" => "elegir", "opciones" => "🍎\n🐶\n🚗", "correcta" => 1]);
P::ver("elegir con emojis: válido", is_array($d));
$e = $ej($d);
P::ver("elegir: la correcta (🍎)", RutaModel::calificar($e, "🍎")["correcto"] === true);
P::ver("elegir: otra (🐶)", RutaModel::calificar($e, "🐶")["correcto"] === false);
P::ver("elegir: avisa cuál era (correctaValor)", RutaModel::calificar($e, "🐶")["correctaValor"] === "🍎");
$pub = RutaModel::vistaPublica($e, "ES");
P::ver("elegir: la vista no trae la correcta", !array_key_exists("correcta", $pub) && count($pub["opciones"]) === 3);

$d = $form(["tipo" => "elegir", "opciones" => "$video\n$video2", "correcta" => 2]);
P::ver("elegir con videos: válido y normalizado", is_array($d) && str_contains($d["datos"], "watch?v=9bZkp7q19f0"));
$pub = RutaModel::vistaPublica($ej($d), "LSC");
P::ver("elegir con videos: cada opción trae su reproductor", count(array_filter($pub["opciones"], fn($o) => str_contains((string) ($o["video"] ?? ""), "youtube-nocookie.com/embed/"))) === 2);
P::ver("elegir con videos: la correcta por su enlace", RutaModel::calificar($ej($d), "https://www.youtube.com/watch?v=9bZkp7q19f0")["correcto"] === true);
P::ver("elegir: rechaza un enlace que no es video", is_string($form(["tipo" => "elegir", "opciones" => "https://ejemplo.com/x\n🍎", "correcta" => 1])));
P::ver("elegir: rechaza opciones repetidas", is_string($form(["tipo" => "elegir", "opciones" => "🍎\n🍎", "correcta" => 1])));
P::ver("elegir: pide la correcta dentro del rango", is_string($form(["tipo" => "elegir", "opciones" => "🍎\n🐶", "correcta" => 3])));

// ---------- Memoria ----------
$d = $form(["tipo" => "memoria", "pares" => "🍎 = manzana\n🐶 = perro\n$video = hola"]);
P::ver("memoria con emojis y video: válida", is_array($d));
$e = $ej($d);
$pub = RutaModel::vistaPublica($e, "LSC");
P::ver("memoria: 6 cartas (3 parejas)", count($pub["cartas"]) === 6);
P::ver("memoria: cada pareja tiene sus 2 cartas", array_count_values(array_column($pub["cartas"], "par")) === [0 => 2, 1 => 2, 2 => 2] || count(array_filter(array_count_values(array_column($pub["cartas"], "par")), fn($n) => $n === 2)) === 3);
P::ver("memoria: la carta de video trae su reproductor", count(array_filter($pub["cartas"], fn($c) => !empty($c["video"]))) === 1);
P::ver("memoria: todas las parejas = correcto", RutaModel::calificar($e, [2, 0, 1])["correcto"] === true);
P::ver("memoria: falta una = incorrecto", RutaModel::calificar($e, [0, 1])["correcto"] === false);
P::ver("memoria: números inventados = incorrecto", RutaModel::calificar($e, [0, 1, 7])["correcto"] === false);
P::ver("memoria: la solución dice «seña» en vez del enlace", str_contains(RutaModel::calificar($e, [0])["solucion"], "seña = hola"));
P::ver("memoria: rechaza cartas repetidas", is_string($form(["tipo" => "memoria", "pares" => "perro = dog\ngato = perro"])));
P::ver("memoria: pide de 2 a 6 parejas", is_string($form(["tipo" => "memoria", "pares" => "perro = dog"])));

// ---------- Deletreo ----------
P::ver("deletreo sin video: no se puede guardar", is_string($form(["tipo" => "deletreo", "respuestas" => "ana"])));
$d = $form(["tipo" => "deletreo", "respuestas" => "Ana", "video_url" => $video]);
P::ver("deletreo con video: válido", is_array($d));
$e = $ej($d);
P::ver("deletreo: ignora mayúsculas y tildes", RutaModel::calificar($e, "ÁNA")["correcto"] === true);
P::ver("deletreo: otra palabra", RutaModel::calificar($e, "eva")["correcto"] === false);
P::ver("deletreo: la vista no trae la respuesta", !preg_match('/"respuestas"|Ana/u', json_encode(RutaModel::vistaPublica($e, "LSC"), JSON_UNESCAPED_UNICODE)));

// ---------- Dibujo en opción múltiple y V/F ----------
$d = $form(["tipo" => "opcion", "opciones" => "apple\nbanana", "correcta" => 1, "imagen" => "🍎"]);
P::ver("opción con dibujo: lo muestra la vista", RutaModel::vistaPublica($ej($d), "EN")["imagen"] === "🍎");
$d = $form(["tipo" => "vf", "correcta_vf" => "1", "imagen" => "🐶"]);
P::ver("V/F con dibujo: lo muestra la vista", RutaModel::vistaPublica($ej($d), "EN")["imagen"] === "🐶");
P::ver("dibujo demasiado largo: rechazado", is_string($form(["tipo" => "opcion", "opciones" => "a\nb", "correcta" => 1, "imagen" => str_repeat("x", 20)])));
P::ver("dibujo con < >: rechazado", is_string($form(["tipo" => "opcion", "opciones" => "a\nb", "correcta" => 1, "imagen" => "<b>"])));

// ---------- Jugable: el deletreo sin video no se muestra ----------
$en = P::idioma($c, "EN");
$leccion = P::lecciones($c, $en, "A1")[0];
$c->beginTransaction();
$c->prepare("INSERT INTO ruta_ejercicios (leccion_id, clave, tipo, enunciado, datos, video_url, orden, activo) VALUES (?, ?, 'deletreo', 'x', '{\"respuestas\":[\"a\"]}', NULL, 99, 1)")
  ->execute([(int) $leccion["id"], "prueba-auto-" . bin2hex(random_bytes(4))]);
$sinVideo = (int) $c->lastInsertId();
$ids = array_map(fn($x) => (int) $x["id"], RutaModel::ejercicios($c, (int) $leccion["id"], true));
P::ver("deletreo sin video: no sale en la lección", !in_array($sinVideo, $ids, true));
$c->prepare("UPDATE ruta_ejercicios SET video_url = ? WHERE id = ?")->execute([$video, $sinVideo]);
$ids = array_map(fn($x) => (int) $x["id"], RutaModel::ejercicios($c, (int) $leccion["id"], true));
P::ver("deletreo con video: sí sale", in_array($sinVideo, $ids, true));
$c->rollBack();

// El formulario de editar recupera los datos
$d = $form(["tipo" => "memoria", "pares" => "🍎 = manzana\n🐶 = perro"]);
P::ver("editar memoria: recupera las parejas", RutaModel::formularioDe($ej($d) + ["orden" => 1, "activo" => 1])["pares"] === "🍎 = manzana\n🐶 = perro");
$d = $form(["tipo" => "opcion", "opciones" => "a\nb", "correcta" => 2, "imagen" => "🍎"]);
P::ver("editar opción: recupera el dibujo", RutaModel::formularioDe($ej($d) + ["orden" => 1, "activo" => 1])["imagen"] === "🍎");
