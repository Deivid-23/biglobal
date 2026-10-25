<?php
/**
 * Importador de videos del INSOR (VideosLscModel): el CSV que se descarga,
 * la vista previa (que no guarda nada), lo que se rechaza, guardar, volver
 * a importar sin cambios y reemplazar. Al final deja los videos como estaban.
 */
require_once __DIR__ . "/../app/Models/VideosLscModel.php";

$sena = "lsc-b1-u1-l1-e2";       // «Ver la seña» (activo, nivel oculto)
$deletreo = "lsc-b1-u1-l4-e2";   // «Deletreo»
$tocados = $c->query("SELECT id, clave, video_url FROM ruta_ejercicios WHERE clave IN ('$sena', '$deletreo')")->fetchAll(PDO::FETCH_ASSOC);
$enClave = (string) $c->query("SELECT e.clave FROM ruta_ejercicios e INNER JOIN ruta_lecciones l ON l.id = e.leccion_id INNER JOIN ruta_unidades u ON u.id = l.unidad_id INNER JOIN ruta_niveles n ON n.id = u.nivel_id INNER JOIN idiomas i ON i.id = n.idioma_id WHERE i.codigo = 'EN' LIMIT 1")->fetchColumn();
$enId = (int) $c->query("SELECT id FROM ruta_ejercicios WHERE clave = " . $c->quote($enClave))->fetchColumn();
$enVideo = $c->query("SELECT video_url FROM ruta_ejercicios WHERE id = $enId")->fetchColumn();
$videoDe = fn(string $clave) => $c->query("SELECT video_url FROM ruta_ejercicios WHERE clave = " . $c->quote($clave))->fetchColumn();
// Fila de cada clave (la primera, si está repetida)
$porClave = fn(array $r) => array_reduce($r["filas"], fn($a, $x) => $a + [$x["clave"] => $x], []);

try {
    // ---------- Resumen y CSV para el revisor ----------
    $resumen = VideosLscModel::resumen($c);
    $pendientes = VideosLscModel::pendientes($c);
    P::ver("el resumen trae los 5 niveles de LSC", array_column($resumen, "codigo") === ["A1", "A2", "B1", "B2", "C1"]);
    P::ver("lo que falta en el resumen coincide con la lista del CSV", array_sum(array_column($resumen, "faltan")) === count($pendientes) && count($pendientes) > 0);
    $csv = VideosLscModel::csv($pendientes);
    P::ver("el CSV sale con BOM, «;» y la columna del enlace", str_starts_with($csv, "\xEF\xBB\xBF") && str_contains(strtok($csv, "\n"), ";\"enlace del video (INSOR)\""));
    $r = VideosLscModel::analizar($c, $csv);
    P::ver("el CSV descargado sin llenar no cambia nada", is_array($r) && !$r["cambios"] && $r["cuenta"]["vacia"] === count($pendientes) && $r["cuenta"]["error"] === 0);

    // ---------- Vista previa: válidos, rechazados y nada guardado ----------
    $filas = [
        ["clave", "tipo", "enlace del video (INSOR)"],
        [$sena, "Ver la seña", "https://youtu.be/dQw4w9WgXcQ?t=3"],
        [$deletreo, "Deletreo", "vimeo.com/123456789"],
        ["lsc-b1-u1-l1-e1", "memoria", "https://youtu.be/dQw4w9WgXcQ"],
        [$enClave, "inglés", "https://youtu.be/dQw4w9WgXcQ"],
        ["lsc-b1-u1-l1-e3", "Ver la seña", "javascript:alert(1)"],
        ["lsc-b1-u1-l2-e1", "Ver la seña", "https://evil.example/video"],
        [$sena, "repetida", "https://youtu.be/aaaaaaaaaaa"],
        ["lsc-b1-u1-l2-e2", "Ver la seña", ""],
        ["", "", ""],
    ];
    $conComas = "\xEF\xBB\xBF" . implode("\r\n", array_map(fn($f) => implode(",", array_map(fn($v) => '"' . $v . '"', $f)), $filas));
    $r = VideosLscModel::analizar($c, $conComas);
    $f = is_array($r) ? $porClave($r) : [];
    P::ver("lee el CSV con comas, BOM y fin de línea de Windows", is_array($r) && count($r["filas"]) === 8);
    P::ver("seña y deletreo de LSC con enlace válido quedan como nuevos", ($f[$sena]["estado"] ?? "") === "nuevo" && ($f[$deletreo]["estado"] ?? "") === "nuevo");
    P::ver("el enlace se guarda limpio (sin ?t= y con https)", ($f[$sena]["url"] ?? "") === "https://www.youtube.com/watch?v=dQw4w9WgXcQ" && ($f[$deletreo]["url"] ?? "") === "https://vimeo.com/123456789");
    P::ver("se rechaza un ejercicio de LSC que no es de seña ni deletreo", ($f["lsc-b1-u1-l1-e1"]["estado"] ?? "") === "error");
    P::ver("se rechaza un ejercicio de otro idioma", ($f[$enClave]["estado"] ?? "") === "error");
    P::ver("se rechazan los enlaces que no son de YouTube ni Vimeo", ($f["lsc-b1-u1-l1-e3"]["estado"] ?? "") === "error" && ($f["lsc-b1-u1-l2-e1"]["estado"] ?? "") === "error");
    P::ver("una clave repetida en el archivo se rechaza", $r["cuenta"]["error"] === 5 && count(array_filter($r["filas"], fn($x) => $x["clave"] === $sena && $x["estado"] === "error")) === 1);
    P::ver("una fila sin enlace se ignora", ($f["lsc-b1-u1-l2-e2"]["estado"] ?? "") === "vacia");
    P::ver("la vista previa solo propone los 2 válidos", array_column($r["cambios"], 2) === [$sena, $deletreo]);
    P::ver("la vista previa no guarda nada", $videoDe($sena) === null && $videoDe($deletreo) === null);

    $ansi = mb_convert_encoding("clave;el video debe mostrar;enlace del video (INSOR)\n$sena;La seña de: Profesor;https://youtu.be/dQw4w9WgXcQ\n", "Windows-1252", "UTF-8");
    $r2 = VideosLscModel::analizar($c, $ansi);
    P::ver("lee un CSV guardado por Excel sin UTF-8", is_array($r2) && ($r2["filas"][0]["estado"] ?? "") === "nuevo");
    P::ver("un CSV sin la columna del enlace se rechaza con un mensaje", is_string(VideosLscModel::analizar($c, "clave;otra\n$sena;x\n")));

    // ---------- Guardar ----------
    $jugable = fn(string $clave) => (int) $c->query("SELECT COUNT(*) FROM ruta_ejercicios e WHERE e.clave = " . $c->quote($clave) . " AND " . RutaModel::condicionJugable("e"))->fetchColumn() === 1;
    P::ver("sin video, la seña no se puede jugar", !$jugable($sena));
    $hechos = VideosLscModel::aplicar($c, $r["cambios"]);
    P::ver("guardar cambia los 2 ejercicios", $hechos === 2 && $videoDe($sena) === "https://www.youtube.com/watch?v=dQw4w9WgXcQ" && $videoDe($deletreo) === "https://vimeo.com/123456789");
    P::ver("con video, la seña ya se puede jugar", $jugable($sena));

    // ---------- Importar otra vez: sin cambios; con otro enlace: reemplaza ----------
    $r3 = VideosLscModel::analizar($c, $conComas);
    $f3 = $porClave($r3);
    P::ver("importar el mismo archivo otra vez no cambia nada", !$r3["cambios"] && ($f3[$sena]["estado"] ?? "") === "igual");
    $r4 = VideosLscModel::analizar($c, "clave;enlace del video (INSOR)\n$sena;https://www.youtube.com/watch?v=bbbbbbbbbbb\n");
    P::ver("otro enlace para el mismo ejercicio aparece como reemplazo, con el anterior", ($r4["filas"][0]["estado"] ?? "") === "reemplaza" && $r4["filas"][0]["anterior"] === "https://www.youtube.com/watch?v=dQw4w9WgXcQ");
    VideosLscModel::aplicar($c, $r4["cambios"]);
    P::ver("el reemplazo se guarda", $videoDe($sena) === "https://www.youtube.com/watch?v=bbbbbbbbbbb");

    // ---------- Aunque alguien arme el cambio a mano, solo toca LSC y enlaces válidos ----------
    $otros = VideosLscModel::aplicar($c, [[$enId, "https://youtu.be/ccccccccccc", $enClave], [(int) $tocados[0]["id"], "https://evil.example/x", $sena]]);
    P::ver("guardar ignora ejercicios de otros idiomas y enlaces malos", $otros === 0
        && $c->query("SELECT video_url FROM ruta_ejercicios WHERE id = $enId")->fetchColumn() === $enVideo
        && $videoDe($sena) === "https://www.youtube.com/watch?v=bbbbbbbbbbb");
} finally {
    $restaurar = $c->prepare("UPDATE ruta_ejercicios SET video_url = ? WHERE id = ?");
    foreach ($tocados as $t) {
        $restaurar->execute([$t["video_url"], (int) $t["id"]]);
    }
}
