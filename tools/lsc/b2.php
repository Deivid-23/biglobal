<?php
/**
 * Contenido LSC B2 (fase 2): el nivel estaba vacío; aquí van sus 4 unidades
 * y 16 lecciones. Lo convierte en SQL tools/generar_lsc.php.
 *
 * El nivel B2 todavía está oculto, así que todo entra publicado: nadie lo
 * ve hasta que se publique el nivel.
 *
 * Cada ejercicio: [tipo, enunciado, campos del formulario de Admin > Ruta].
 * En "opcion", "sena" y "elegir" la primera opción es la correcta, salvo
 * que se diga "correcta". Los de tipo "sena" y "deletreo" quedan sin video
 * (no se muestran) hasta que se pegue el video del INSOR.
 *
 * Nada de esto describe cómo se hace una seña: eso lo muestra el video.
 * La unidad 3 explica ideas de gramática en general; el revisor confirma.
 */
return [
    "idioma"   => "LSC",
    "nivel"    => "B2",
    "activo"   => 1,
    "publicar" => 1,

    "unidades" => [
        ["clave" => "lsc-b2-u1", "titulo" => "Opiniones y debates", "descripcion" => "Opinar, estar de acuerdo, argumentar y debatir con respeto.", "icono" => "fa-comments", "orden" => 1, "lecciones" => [
            ["titulo" => "Dar tu opinión", "descripcion" => "Creo, pienso y me parece.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Yo creo\nYo sé\nYo quiero"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Me parece bien\nMe da igual\nNo estoy seguro"]],
                ["clasificar", "¿Es una opinión o un hecho?", ["grupos" => "Opinión\nHecho", "items" => "La LSC es el idioma más bonito = Opinión\nEl mejor deporte es el fútbol = Opinión\nBogotá tiene el clima más agradable = Opinión\nLa Ley 324 de 1996 reconoce la LSC = Hecho\nColombia tiene costas en dos océanos = Hecho\nEl INSOR es una entidad pública = Hecho"]],
                ["huecos", "Completa la opinión.", ["texto_huecos" => "___ que las clases deberían tener intérprete, ___ así todos entienden.", "respuestas" => "Creo\nporque", "extra" => "aunque"]],
                ["opcion", "Para dejar claro que algo es solo tu opinión, en LSC ayuda…", ["opciones" => "Usar una seña como «creo» o «pienso» y un rostro que lo acompañe\nDeletrear toda la frase\nSeñar más rápido", "imagen" => "🤔"]],
                ["vf", "Dar tu opinión con respeto también es dejar que los demás terminen.", ["correcta_vf" => "1", "imagen" => "🙌"]],
            ]],
            ["titulo" => "De acuerdo o en desacuerdo", "descripcion" => "Apoyar una idea, rechazarla o poner condiciones.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Estoy de acuerdo\nNo estoy de acuerdo\nTal vez"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Tienes razón\nNo es cierto\nDepende"]],
                ["emparejar", "Une cada frase con su intención.", ["pares" => "Estoy de acuerdo = Apoyar una idea\nNo estoy de acuerdo = Rechazar una idea\nDepende = Poner una condición\nTal vez = Mostrar duda"]],
                ["clasificar", "¿Acuerdo o desacuerdo?", ["grupos" => "Acuerdo\nDesacuerdo", "items" => "Tienes razón = Acuerdo\nPienso lo mismo = Acuerdo\nClaro que sí = Acuerdo\nYo lo veo distinto = Desacuerdo\nNo es así = Desacuerdo\nNo comparto esa idea = Desacuerdo"]],
                ["elegir", "¿Qué cara va con «no estoy seguro»?", ["opciones" => "🤔\n😄\n😡"]],
                ["vf", "En LSC, mover la cabeza para negar puede acompañar un «no estoy de acuerdo».", ["correcta_vf" => "1"]],
            ]],
            ["titulo" => "Causas y consecuencias", "descripcion" => "Porque, por eso y entonces.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Porque\nPor eso\nEntonces"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "¿Por qué?\n¿Para qué?\n¿Cómo?"]],
                ["emparejar", "Une cada causa con su consecuencia.", ["pares" => "Llovió mucho = Se inundó la calle\nNo había intérprete = La persona sorda no entendió la reunión\nEstudió todos los días = Aprobó el examen\nSe acabó la batería = No pudo hacer la videollamada"]],
                ["huecos", "Completa el párrafo.", ["texto_huecos" => "No pusieron subtítulos en el video, ___ muchas personas sordas no lo entendieron. Se quejaron ___ el video era importante.", "respuestas" => "por eso\nporque", "extra" => "aunque"]],
                ["ordenar", "Ordena la frase.", ["frase" => "No fui a la reunión porque no había intérprete"]],
                ["opcion", "«No fui porque estaba enfermo». ¿Cuál es la causa?", ["opciones" => "Estaba enfermo\nNo fui\nLa reunión"]],
            ]],
            ["titulo" => "Un debate con respeto", "descripcion" => "Turnos, papeles y cómo sentarse en un grupo grande.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Pedir la palabra\nTerminar\nEsperar"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "debate"]],
                ["clasificar", "¿Ayuda o daña un debate en LSC?", ["grupos" => "Ayuda\nDaña", "items" => "Pedir la palabra y esperar el turno = Ayuda\nEsperar a que el otro termine = Ayuda\nResumir lo que dijo el otro antes de responder = Ayuda\nSeñar encima de otra persona = Daña\nDarle la espalda al grupo = Daña\nBurlarse de la opinión del otro = Daña"]],
                ["opcion", "En un debate grande en LSC, conviene que las personas se sienten…", ["opciones" => "En círculo o en U, para que todos se vean\nEn filas, mirando la nuca del de adelante\nCada una en un salón distinto", "imagen" => "👥"]],
                ["vf", "Si alguien está a contraluz, frente a una ventana, a los demás les cuesta ver bien sus señas.", ["correcta_vf" => "1", "imagen" => "🪟"]],
                ["emparejar", "Une cada papel con lo que hace en un debate.", ["pares" => "Moderador = Da los turnos\nIntérprete = Interpreta entre LSC y español\nParticipante = Da su opinión\nPúblico = Observa y pregunta al final"]],
            ]],
        ]],
        ["clave" => "lsc-b2-u2", "titulo" => "Noticias y medios", "descripcion" => "Las noticias, las redes, contar un hecho y revisar la fuente.", "icono" => "fa-newspaper", "orden" => 2, "lecciones" => [
            ["titulo" => "Las noticias en LSC", "descripcion" => "Noticieros con intérprete y subtítulos.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Noticia\nPeriódico\nTelevisión"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Noticiero\nPelícula\nNovela"]],
                ["vf", "Muchos noticieros en Colombia incluyen intérprete de LSC en un recuadro o subtítulos.", ["correcta_vf" => "1", "imagen" => "📺"]],
                ["opcion", "El recuadro del intérprete en la pantalla debe ser…", ["opciones" => "Lo bastante grande para ver bien las manos y el rostro\nMuy pequeño para que no moleste\nEn blanco y negro", "imagen" => "🖥️"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "📰 = Periódico\n📺 = Televisión\n📻 = Radio\n📱 = Celular"]],
                ["completar", "Completa la frase.", ["frase" => "Una persona sorda no puede seguir las noticias de la ___ sin otro apoyo.", "respuestas" => "radio"]],
            ]],
            ["titulo" => "Medios y redes sociales", "descripcion" => "Publicar, compartir y cuidarte en internet.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Redes sociales\nInternet\nCelular"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Publicar\nCompartir\nBorrar"]],
                ["vf", "En redes sociales, muchas personas sordas comparten videos en LSC y no solo texto.", ["correcta_vf" => "1", "imagen" => "📱"]],
                ["emparejar", "Une cada acción con lo que significa.", ["pares" => "Publicar = Subir algo para que otros lo vean\nCompartir = Pasar a otros lo que publicó alguien\nComentar = Escribir o grabar una respuesta\nBloquear = Impedir que alguien te contacte"]],
                ["clasificar", "¿Es seguro o arriesgado en las redes?", ["grupos" => "Seguro\nArriesgado", "items" => "Usar una contraseña fuerte = Seguro\nRevisar quién ve tus publicaciones = Seguro\nVerificar antes de compartir = Seguro\nPublicar tu dirección = Arriesgado\nAceptar a cualquier desconocido = Arriesgado\nCompartir tu contraseña = Arriesgado"]],
                ["ordenar", "Ordena la frase.", ["frase" => "Subí un video en LSC a mis redes"]],
            ]],
            ["titulo" => "Contar una noticia", "descripcion" => "Qué, quién, dónde y cuándo.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "¿Qué pasó?\n¿A quién le pasó?\n¿Qué hora es?"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Accidente\nFiesta\nElecciones"]],
                ["emparejar", "Une cada pregunta de la noticia con su respuesta.", ["pares" => "¿Qué? = Un incendio\n¿Dónde? = En el centro de Cali\n¿Cuándo? = Anoche\n¿Quién? = Los bomberos lo apagaron"]],
                ["huecos", "Completa la noticia.", ["texto_huecos" => "___ hubo un incendio en el centro de Cali. Los ___ llegaron rápido y no hubo ___.", "respuestas" => "Anoche\nbomberos\nheridos", "extra" => "mañana"]],
                ["opcion", "Al contar una noticia en LSC, es útil empezar por…", ["opciones" => "Ubicar el tiempo y el lugar\nEl final de la historia\nDeletrear el título completo", "imagen" => "📰"]],
                ["vf", "Al narrar en LSC, los lugares de la noticia se pueden ubicar en el espacio y luego señalar.", ["correcta_vf" => "1"]],
            ]],
            ["titulo" => "Revisar la fuente", "descripcion" => "Noticias falsas y cómo reconocerlas.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Verdad\nMentira\nRumor"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "fuente"]],
                ["clasificar", "¿Señal de noticia confiable o sospechosa?", ["grupos" => "Confiable\nSospechosa", "items" => "Dice quién la publicó = Confiable\nOtros medios dicen lo mismo = Confiable\nTiene fecha = Confiable\nNo tiene autor = Sospechosa\nPide compartirla con urgencia = Sospechosa\nEl título viene lleno de insultos = Sospechosa"]],
                ["opcion", "Antes de compartir una noticia que te sorprende, lo mejor es…", ["opciones" => "Revisar si otros medios confiables la publicaron\nCompartirla rápido\nCambiarle el título", "imagen" => "🔍"]],
                ["vf", "Un video con muchas visitas siempre dice la verdad.", ["correcta_vf" => "0", "imagen" => "🎥"]],
                ["completar", "Completa la frase.", ["frase" => "Una noticia ___ es la que se inventa para engañar.", "respuestas" => "falsa"]],
            ]],
        ]],
        ["clave" => "lsc-b2-u3", "titulo" => "Expresarse con más precisión", "descripcion" => "Intensidad, condiciones, comparaciones y acciones que se repiten.", "icono" => "fa-hands-asl-interpreting", "orden" => 3, "lecciones" => [
            ["titulo" => "La intensidad y el rostro", "descripcion" => "Muy, poco y nada.", "ej" => [
                ["opcion", "En LSC, para decir «muy» o «poco» (por ejemplo, muy cansado), se usa mucho…", ["opciones" => "La expresión del rostro y la fuerza del movimiento\nDeletrear M-U-Y\nUna seña distinta para cada grado", "imagen" => "😫"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Muy cansado\nUn poco cansado\nNada cansado"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Muy lejos\nCerca\nAl lado"]],
                ["clasificar", "¿Más o menos intenso?", ["grupos" => "Más intenso\nMenos intenso", "items" => "Furioso = Más intenso\nAgotado = Más intenso\nHelado = Más intenso\nMolesto = Menos intenso\nCansado = Menos intenso\nFresco = Menos intenso"]],
                ["emparejar", "Une cada palabra con su versión más intensa.", ["pares" => "Bonito = Hermoso\nGrande = Enorme\nFrío = Helado\nBueno = Excelente"]],
                ["vf", "En LSC, el rostro puede cambiar el grado de una seña, por ejemplo de «grande» a «enorme».", ["correcta_vf" => "1", "imagen" => "😮"]],
            ]],
            ["titulo" => "Si…, entonces…", "descripcion" => "Poner condiciones.", "ej" => [
                ["opcion", "En muchas lenguas de señas, la parte del «si…» de una condición suele marcarse con…", ["opciones" => "Las cejas levantadas\nLos ojos cerrados\nLa boca abierta"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Si llueve, no voy\nSi voy, llueve\nLlueve y no voy"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Si tienes tiempo, ven\nVen a tiempo\nNo tengo tiempo"]],
                ["emparejar", "Une cada condición con su resultado.", ["pares" => "Si llueve = Llevo paraguas\nSi tengo hambre = Como algo\nSi no entiendo = Pido que repitan\nSi hay intérprete = Voy a la charla"]],
                ["huecos", "Completa la frase.", ["texto_huecos" => "___ mañana hace sol, ___ vamos al parque.", "respuestas" => "Si\nentonces", "extra" => "porque"]],
                ["ordenar", "Ordena la frase.", ["frase" => "Si tienes dudas me escribes un mensaje"]],
            ]],
            ["titulo" => "Comparar", "descripcion" => "Más que, menos que e igual que.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Más que\nMenos que\nIgual que"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Mejor\nPeor\nIgual"]],
                ["completar", "Completa la comparación.", ["frase" => "Un elefante es ___ grande que un perro.", "respuestas" => "más\nmas"]],
                ["emparejar", "Une cada dato con su conclusión.", ["pares" => "Ana tiene 20 años y Luis 15 = Ana es mayor\nEl bus cuesta 3.000 y el taxi 15.000 = El bus es más barato\nCali está a 30 °C y Bogotá a 14 °C = Bogotá es más fría"]],
                ["elegir", "¿Qué animal es más grande que un caballo?", ["opciones" => "🐘\n🐱\n🐔\n🐰"]],
                ["opcion", "Para comparar dos cosas en LSC, una estrategia común es…", ["opciones" => "Ubicar cada cosa a un lado del espacio y luego compararlas\nDeletrear las dos\nHacer las dos señas a la vez sin mirar", "imagen" => "⚖️"]],
            ]],
            ["titulo" => "Acciones que se repiten o que duran", "descripcion" => "Siempre, a veces, nunca y mucho tiempo.", "ej" => [
                ["opcion", "En LSC, repetir el movimiento de un verbo puede mostrar que la acción…", ["opciones" => "Se repite o dura mucho tiempo\nYa terminó\nEs una pregunta", "imagen" => "🔁"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Estudiar mucho tiempo\nEstudiar una vez\nNo estudiar"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Siempre\nA veces\nNunca"]],
                ["clasificar", "¿Pasa una vez o se repite?", ["grupos" => "Una vez\nSe repite", "items" => "Ayer me caí = Una vez\nEl lunes llegó una carta = Una vez\nHoy conocí a Juan = Una vez\nTodos los días camino al trabajo = Se repite\nCada semana voy a la asociación = Se repite\nSiempre desayuno arepa = Se repite"]],
                ["emparejar", "Une cada palabra con su frecuencia.", ["pares" => "Siempre = Todos los días\nCasi siempre = Muchas veces\nA veces = De vez en cuando\nNunca = Ninguna vez"]],
                ["completar", "Completa la frase.", ["frase" => "Voy a la asociación de sordos ___ los sábados.", "respuestas" => "todos"]],
            ]],
        ]],
        ["clave" => "lsc-b2-u4", "titulo" => "Derechos y cultura sorda", "descripcion" => "Leyes, educación bilingüe, arte sordo y el repaso del nivel.", "icono" => "fa-scale-balanced", "orden" => 4, "lecciones" => [
            ["titulo" => "Los derechos", "descripcion" => "La Convención de la ONU y las leyes de Colombia.", "ej" => [
                ["emparejar", "Une cada norma con lo que dice.", ["pares" => "Ley 324 de 1996 = Reconoce la LSC\nLey 982 de 2005 = Igualdad de oportunidades para sordos y sordociegos\nLey 1346 de 2009 = Aprueba la Convención de la ONU sobre discapacidad\nLey 1618 de 2013 = Garantiza los derechos de las personas con discapacidad"]],
                ["opcion", "La Convención de la ONU sobre los derechos de las personas con discapacidad pide a los países…", ["opciones" => "Reconocer y promover el uso de las lenguas de señas\nProhibir las lenguas de señas\nUsar solo la lectura de labios", "imagen" => "🌐"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Derecho\nLey\nJusticia"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Igualdad\nDiscriminación\nInclusión"]],
                ["vf", "Negarle a una persona sorda un intérprete en un trámite público puede ser una forma de discriminación.", ["correcta_vf" => "1", "imagen" => "⚖️"]],
                ["clasificar", "¿Derecho respetado o vulnerado?", ["grupos" => "Respetado\nVulnerado", "items" => "Juicio con intérprete de LSC = Respetado\nNoticiero con intérprete = Respetado\nColegio bilingüe = Respetado\nHospital sin forma de comunicarse = Vulnerado\nTrabajo negado por ser sordo = Vulnerado\nTrámite solo por teléfono = Vulnerado"]],
            ]],
            ["titulo" => "Educación bilingüe e inclusión", "descripcion" => "LSC y español escrito en el aula.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Colegio bilingüe\nUniversidad\nGuardería"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "inclusión\ninclusion"]],
                ["opcion", "En la educación bilingüe para sordos en Colombia, las dos lenguas son…", ["opciones" => "La LSC y el español escrito\nEl inglés y el español\nSolo la lectura de labios", "imagen" => "🏫"]],
                ["vf", "En la educación bilingüe, la LSC es la primera lengua de muchos estudiantes sordos.", ["correcta_vf" => "1", "imagen" => "🧏"]],
                ["emparejar", "Une cada persona con su papel en el aula.", ["pares" => "Docente = Enseña los contenidos\nModelo lingüístico = Enseña la LSC y la cultura sorda\nIntérprete = Interpreta entre LSC y español\nEstudiante = Aprende y participa"]],
                ["huecos", "Completa el texto.", ["texto_huecos" => "En un aula ___, el estudiante sordo aprende en LSC y lee y escribe en ___.", "respuestas" => "bilingüe\nespañol", "extra" => "inglés"]],
            ]],
            ["titulo" => "Arte sordo", "descripcion" => "Poesía en señas, teatro y humor visual.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Poesía\nTeatro\nCuento"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Artista\nPúblico\nAplauso"]],
                ["opcion", "La poesía en lengua de señas juega sobre todo con…", ["opciones" => "El ritmo, las formas de las manos y el espacio\nLas rimas de las palabras habladas\nLa música de fondo", "imagen" => "🎭"]],
                ["vf", "El Visual Vernacular es un arte de la comunidad sorda que mezcla señas, gestos y técnicas de cine.", ["correcta_vf" => "1", "imagen" => "🎬"]],
                ["clasificar", "¿Es parte del arte sordo?", ["grupos" => "Arte sordo\nNo tiene que ver", "items" => "Poesía en señas = Arte sordo\nTeatro en LSC = Arte sordo\nHumor visual = Arte sordo\nRimas habladas = No tiene que ver\nPrograma de radio = No tiene que ver\nLlamada telefónica = No tiene que ver"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "🎭 = Teatro\n📖 = Cuento\n🎬 = Cine\n🖼️ = Pintura"]],
            ]],
            ["titulo" => "Repaso del B2 completo", "descripcion" => "Todo lo del nivel, mezclado.", "ej" => [
                ["clasificar", "Clasifica las palabras.", ["grupos" => "Debate\nNoticias\nDerechos", "items" => "Opinión = Debate\nTurno = Debate\nFuente = Noticias\nNoticiero = Noticias\nLey = Derechos\nIgualdad = Derechos"]],
                ["emparejar", "Une cada idea con su ejemplo.", ["pares" => "Condición = Si llueve, no voy\nComparación = Ana es más alta que Luis\nCausa = No fui porque estaba enfermo\nOpinión = Creo que es una buena idea"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "No estoy de acuerdo porque…\nEstoy de acuerdo\nNo sé"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "derechos"]],
                ["huecos", "Completa el párrafo.", ["texto_huecos" => "___ que todos los noticieros deberían tener intérprete, ___ las personas sordas también tienen derecho a ___.", "respuestas" => "Creo\nporque\ninformarse", "extra" => "aunque"]],
                ["vf", "En un debate en LSC, respetar los turnos es tan importante como los argumentos.", ["correcta_vf" => "1"]],
            ]],
        ]],
    ],
];
