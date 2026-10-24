<?php
/**
 * Regla de acceso: la ruta es gratis; clases en vivo y grupos con docentes son
 * de Premium, salvo los que el admin marca como gratuitos.
 */
$gratis = P::usuario($c, "estudiante");
$premium = P::usuario($c, "estudiante");
Plan::otorgarManual($c, $premium, date("Y-m-d H:i:s", strtotime("+10 days")), 0, "Prueba automática");
$docente = P::usuario($c, "docente");

P::ver("el estudiante gratis no es Premium", !Plan::esPremium($c, $gratis));
P::ver("el de cortesía sí es Premium", Plan::esPremium($c, $premium));

// Clases en vivo
P::ver("clase de pago: el gratis no entra", !Plan::puedeUnirseAClase($c, $gratis, false));
P::ver("clase gratuita para todos: el gratis entra", Plan::puedeUnirseAClase($c, $gratis, true));
P::ver("clase de pago: Premium entra", Plan::puedeUnirseAClase($c, $premium, false));
$datos = ClaseModel::datosClase($c, ["titulo" => "Prueba", "inicio" => date("Y-m-d\TH:i", time() + 86400 * 3), "duracion_min" => 60,
    "enlace" => "https://meet.google.com/abc-defg-hij", "cupo" => 10, "abierta_para_todos" => "1"], 0);
P::ver("el docente no puede marcar la clase como gratuita", is_array($datos) && !array_key_exists("abierta", $datos));

// Grupo nuevo: nace de pago
$c->prepare("INSERT INTO grupos (docente_id, nombre, codigo) VALUES (?, 'Grupo de prueba automática', ?)")->execute([$docente, GrupoModel::generarCodigo($c)]);
$grupoId = (int) $c->lastInsertId();
$grupo = GrupoModel::porId($c, $grupoId);
P::ver("un grupo nuevo nace de pago", (int) $grupo["gratuito"] === 0);

$r = GrupoModel::unirse($c, $grupo["codigo"], $gratis);
P::ver("grupo de pago: el gratis no se une", !$r["ok"] && !empty($r["premium"]) && !GrupoModel::esMiembro($c, $grupoId, $gratis));
$r = GrupoModel::unirse($c, $grupo["codigo"], $premium);
P::ver("grupo de pago: Premium se une", $r["ok"] && GrupoModel::paraEstudiante($c, $grupoId, $premium)["permitido"] === true);

GrupoModel::marcarGratuito($c, $grupoId, true);
$r = GrupoModel::unirse($c, $grupo["codigo"], $gratis);
P::ver("grupo gratuito: el gratis se une", $r["ok"] && GrupoModel::paraEstudiante($c, $grupoId, $gratis)["permitido"] === true);

GrupoModel::marcarGratuito($c, $grupoId, false);
P::ver("vuelve a pago: sigue de miembro pero bloqueado", GrupoModel::esMiembro($c, $grupoId, $gratis) && GrupoModel::paraEstudiante($c, $grupoId, $gratis)["permitido"] === false);
$lista = array_values(array_filter(GrupoModel::gruposEstudiante($c, $gratis), fn($g) => (int) $g["id"] === $grupoId));
P::ver("en Mis grupos aparece bloqueado", $lista && $lista[0]["permitido"] === false);
P::ver("puede salir del grupo bloqueado", GrupoModel::salir($c, $grupoId, $gratis) && !GrupoModel::esMiembro($c, $grupoId, $gratis));

// Material del grupo: el archivo solo lo baja quien tiene acceso
$c->prepare("INSERT INTO archivos (dueno_id, contexto, contexto_id, nombre, mime, tamano, sha256, contenido) VALUES (?, 'material', 0, 'guia.pdf', 'application/pdf', 5, ?, '%PDF-')")
  ->execute([$docente, hash("sha256", "guia")]);
$archivoId = (int) $c->lastInsertId();
$c->prepare("INSERT INTO grupo_material (grupo_id, titulo, tipo, archivo_id) VALUES (?, 'Guía', 'archivo', ?)")->execute([$grupoId, $archivoId]);
$c->prepare("UPDATE archivos SET contexto_id = ? WHERE id = ?")->execute([(int) $c->lastInsertId(), $archivoId]);
GrupoModel::unirse($c, $grupo["codigo"], $gratis);   // no entra: es de pago
P::ver("material: Premium lo baja", GrupoModel::archivoConPermiso($c, $archivoId, $premium, "estudiante") !== null);
P::ver("material: el gratis no", GrupoModel::archivoConPermiso($c, $archivoId, $gratis, "estudiante") === null);
P::ver("material: el docente del grupo sí", GrupoModel::archivoConPermiso($c, $archivoId, $docente, "docente") !== null);
$otroDocente = P::usuario($c, "docente");
P::ver("material: otro docente no", GrupoModel::archivoConPermiso($c, $archivoId, $otroDocente, "docente") === null);
