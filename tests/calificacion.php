<?php
/**
 * Calificación de los 11 tipos de ejercicio (RutaModel::calificar) y que la
 * vista pública no lleve la respuesta. No usa la base de datos.
 */
$ej = fn(string $tipo, array $datos) => ["id" => 1, "tipo" => $tipo, "enunciado" => "x", "datos" => json_encode($datos), "video_url" => null];
$bien = fn(array $e, $r) => RutaModel::calificar($e, $r)["correcto"] === true;
$mal = fn(array $e, $r) => RutaModel::calificar($e, $r)["correcto"] === false;

// Opción múltiple (y "sena" y "escuchar", que califican igual)
foreach (["opcion", "sena", "escuchar"] as $tipo) {
    $e = $ej($tipo, ["opciones" => ["Hello", "Bye", "Thanks"], "correcta" => 0]);
    P::ver("$tipo: la correcta", $bien($e, "Hello"));
    P::ver("$tipo: otra opción", $mal($e, "Bye"));
    P::ver("$tipo: no acepta una lista", $mal($e, ["Hello"]));
}

// Verdadero o falso
$e = $ej("vf", ["correcta" => false]);
P::ver("vf: falso es correcto", $bien($e, "0"));
P::ver("vf: verdadero es incorrecto", $mal($e, "1"));

// Completar: ignora mayúsculas, tildes, signos y espacios de más
$e = $ej("completar", ["frase" => "I ___ a student.", "respuestas" => ["am", "'m"]]);
P::ver("completar: respuesta exacta", $bien($e, "am"));
P::ver("completar: con mayúsculas y espacios", $bien($e, "  AM "));
P::ver("completar: otra respuesta aceptada", $bien($e, "'m"));
P::ver("completar: vacía", $mal($e, ""));
P::ver("completar: incorrecta", $mal($e, "is"));
$e = $ej("completar", ["frase" => "Mi ___ es Ana.", "respuestas" => ["mamá"]]);
P::ver("completar: sin tilde también vale", $bien($e, "MAMA"));

// Ordenar
$e = $ej("ordenar", ["frase" => "My name is Ana.", "aceptadas" => ["Ana is my name."]]);
P::ver("ordenar: el orden correcto (sin el punto)", $bien($e, "my name is ana"));
P::ver("ordenar: el orden aceptado alternativo", $bien($e, "Ana is my name"));
P::ver("ordenar: otro orden", $mal($e, "name my is Ana"));

// Emparejar: el orden de las parejas no importa
$e = $ej("emparejar", ["pares" => [["dog", "perro"], ["cat", "gato"]]]);
P::ver("emparejar: todas bien en otro orden", $bien($e, [["cat", "gato"], ["dog", "perro"]]));
P::ver("emparejar: una cruzada", $mal($e, [["dog", "gato"], ["cat", "perro"]]));
P::ver("emparejar: incompleta", $mal($e, [["dog", "perro"]]));
P::ver("emparejar: texto en vez de lista", $mal($e, "dog perro"));

// Dictado
$e = $ej("dictado", ["texto" => "Good morning!", "aceptadas" => ["Good mornin"]]);
P::ver("dictado: igual sin signos", $bien($e, "good morning"));
P::ver("dictado: aceptada alternativa", $bien($e, "Good mornin"));
P::ver("dictado: distinta", $mal($e, "good night"));

// Traducir
$e = $ej("traducir", ["respuestas" => ["Tengo hambre", "Estoy hambriento"]]);
P::ver("traducir: primera respuesta", $bien($e, "tengo hambre"));
P::ver("traducir: segunda respuesta", $bien($e, "Estoy hambriento."));
P::ver("traducir: incorrecta", $mal($e, "Tengo sed"));

// Encontrar el error: se responde con la posición de la palabra
$e = $ej("error", ["palabras" => ["She", "go", "to", "school."], "error" => 1, "correccion" => "goes"]);
$r = RutaModel::calificar($e, "1");
P::ver("error: la palabra equivocada", $r["correcto"] === true);
P::ver("error: la solución muestra la frase corregida", str_contains($r["solucion"], "She goes to school."));
P::ver("error: otra palabra", $mal($e, "0"));
P::ver("error: texto que no es número", $mal($e, "go"));

// Clasificar
$e = $ej("clasificar", ["grupos" => ["Animales", "Frutas"], "items" => [["dog", 0], ["apple", 1], ["cat", 0]]]);
P::ver("clasificar: todo bien", $bien($e, [["apple", "Frutas"], ["dog", "Animales"], ["cat", "Animales"]]));
P::ver("clasificar: uno en el grupo equivocado", $mal($e, [["apple", "Animales"], ["dog", "Animales"], ["cat", "Animales"]]));
P::ver("clasificar: falta uno", $mal($e, [["apple", "Frutas"], ["dog", "Animales"]]));

// Varios espacios: en orden
$e = $ej("huecos", ["texto" => "I ___ up at ___.", "respuestas" => ["get", "seven"], "extra" => ["gets"]]);
P::ver("huecos: todos bien", $bien($e, ["get", "Seven"]));
P::ver("huecos: en otro orden", $mal($e, ["seven", "get"]));
P::ver("huecos: uno de más", $mal($e, ["get", "seven", "x"]));
P::ver("huecos: la ficha distractora", $mal($e, ["gets", "seven"]));

// Un tipo que no existe nunca es correcto
P::ver("tipo desconocido: incorrecto", $mal($ej("otro", ["respuestas" => ["x"]]), "x"));

// La vista pública no lleva la respuesta (salvo escuchar y dictado, que la necesitan para la voz)
$muestras = [
    $ej("opcion", ["opciones" => ["a", "b"], "correcta" => 1]),
    $ej("vf", ["correcta" => true]),
    $ej("completar", ["frase" => "x ___", "respuestas" => ["secreto"]]),
    $ej("ordenar", ["frase" => "uno dos", "aceptadas" => ["dos uno"]]),
    $ej("emparejar", ["pares" => [["a", "b"]]]),
    $ej("traducir", ["respuestas" => ["secreto"]]),
    $ej("error", ["palabras" => ["a", "b"], "error" => 1, "correccion" => "secreto"]),
    $ej("clasificar", ["grupos" => ["G"], "items" => [["a", 0]]]),
    $ej("huecos", ["texto" => "x ___", "respuestas" => ["secreto"]]),
];
foreach ($muestras as $m) {
    $publica = RutaModel::vistaPublica($m, "EN");
    $vista = json_encode($publica, JSON_UNESCAPED_UNICODE);
    // En "huecos" las fichas SÍ traen las palabras (mezcladas con las de relleno): es el ejercicio
    $patron = $m["tipo"] === "huecos" ? '/"(correcta|respuestas|aceptadas|correccion|error|pares)"\s*:/' : '/"(correcta|respuestas|aceptadas|correccion|error|pares)"\s*:|secreto/';
    $filtra = preg_match($patron, $vista) === 1;
    // En clasificar, "items" solo puede traer las palabras, sin el grupo de cada una
    $filtra = $filtra || array_filter($publica["items"] ?? [], fn($i) => !is_string($i));
    P::ver("vista pública de {$m['tipo']}: sin la respuesta", !$filtra);
}
