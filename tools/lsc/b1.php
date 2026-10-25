<?php
/**
 * Contenido LSC B1 (fase 2): el nivel estaba vacío; aquí van sus 4 unidades
 * y 16 lecciones. Lo convierte en SQL tools/generar_lsc.php.
 *
 * El nivel B1 todavía está oculto, así que todo entra publicado: nadie lo
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
    "nivel"    => "B1",
    "activo"   => 1,
    "publicar" => 1,

    "unidades" => [
        ["clave" => "lsc-b1-u1", "titulo" => "Trabajo y profesiones", "descripcion" => "Las profesiones, el trabajo, la hora y una entrevista.", "icono" => "fa-briefcase", "orden" => 1, "lecciones" => [
            ["titulo" => "Las profesiones", "descripcion" => "Médico, profesor, cocinero y más.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "👩‍⚕️ = Médica\n👨‍🏫 = Profesor\n👨‍🍳 = Cocinero\n👮 = Policía\n👩‍🌾 = Agricultora"]],
                ["sena", "Mira la seña. ¿Qué profesión es?", ["opciones" => "Profesor\nMédico\nAbogado"]],
                ["sena", "Mira la seña. ¿Qué profesión es?", ["opciones" => "Cocinero\nPolicía\nBombero"]],
                ["emparejar", "Une cada profesión con su lugar de trabajo.", ["pares" => "Médico = Hospital\nProfesor = Colegio\nCocinero = Restaurante\nAgricultor = Finca"]],
                ["elegir", "¿Quién apaga los incendios?", ["opciones" => "🧑‍🚒\n👨‍🍳\n👩‍⚕️\n👮"]],
                ["vf", "En Colombia hay personas sordas que enseñan LSC en los colegios; se les llama modelos lingüísticos.", ["correcta_vf" => "1", "imagen" => "🧏"]],
            ]],
            ["titulo" => "El lugar de trabajo", "descripcion" => "La oficina, los compañeros y el jefe.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Jefe\nCompañero\nCliente"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Trabajar\nDescansar\nEstudiar"]],
                ["clasificar", "¿Ayuda a que una persona sorda participe en una reunión de trabajo?", ["grupos" => "Ayuda\nNo ayuda", "items" => "Contratar un intérprete = Ayuda\nCompartir la agenda por escrito = Ayuda\nPoner subtítulos en las videollamadas = Ayuda\nHablar todos a la vez = No ayuda\nApagar la cámara al hablar = No ayuda\nDar las instrucciones solo por altavoz = No ayuda"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "💻 = Computador\n📅 = Agenda\n☕ = Descanso\n📧 = Correo"]],
                ["opcion", "En una reunión con intérprete de LSC, ¿a quién le hablas?", ["opciones" => "A la persona sorda, mirándola a ella\nAl intérprete, diciéndole «dile que…»\nA nadie en particular", "imagen" => "🤝"]],
                ["vf", "Una empresa puede negarse a contratar a alguien solo porque es sordo.", ["correcta_vf" => "0", "imagen" => "⚖️"]],
            ]],
            ["titulo" => "La hora y los horarios", "descripcion" => "¿Qué hora es? y la jornada de trabajo.", "ej" => [
                ["sena", "Mira la seña. ¿Qué hora es?", ["opciones" => "Son las tres\nSon las seis\nSon las nueve"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Temprano\nTarde\nA tiempo"]],
                ["elegir", "Elige el reloj de las tres en punto.", ["opciones" => "🕒\n🕘\n🕛\n🕕"]],
                ["completar", "Completa la cuenta.", ["frase" => "Entro a trabajar a las 8 y salgo a las 5: trabajo ___ horas.", "respuestas" => "9\nnueve"]],
                ["ordenar", "Ordena la frase.", ["frase" => "Mi jornada empieza a las ocho de la mañana"]],
                ["emparejar", "Une cada hora con su momento del día.", ["pares" => "7:00 a. m. = En la mañana\n12:00 m. = Al mediodía\n4:00 p. m. = En la tarde\n10:00 p. m. = En la noche"]],
            ]],
            ["titulo" => "Una entrevista de trabajo", "descripcion" => "Preguntar y responder sobre tu experiencia.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Experiencia\nSalario\nContrato"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "empresa"]],
                ["emparejar", "Une cada pregunta con su respuesta.", ["pares" => "¿En qué trabajas? = Soy diseñadora\n¿Dónde trabajas? = En una empresa de software\n¿Desde cuándo? = Desde hace dos años\n¿Te gusta tu trabajo? = Sí, mucho"]],
                ["huecos", "Completa la presentación.", ["texto_huecos" => "Me llamo Laura, ___ contadora y trabajo ___ hace tres años en un ___.", "respuestas" => "soy\ndesde\nbanco", "extra" => "estoy"]],
                ["opcion", "Si una persona sorda va a una entrevista de trabajo, lo mejor es…", ["opciones" => "Preguntarle cómo prefiere comunicarse y conseguir intérprete si lo necesita\nHablarle más fuerte\nHacerle la entrevista solo por teléfono", "imagen" => "💼"]],
                ["vf", "En una entrevista en LSC, mantener el contacto visual muestra interés y respeto.", ["correcta_vf" => "1", "imagen" => "👀"]],
            ]],
        ]],
        ["clave" => "lsc-b1-u2", "titulo" => "De viaje por Colombia", "descripcion" => "Ciudades, planear un viaje y contar lo que viviste.", "icono" => "fa-map-location-dot", "orden" => 2, "lecciones" => [
            ["titulo" => "Ciudades y regiones", "descripcion" => "Bogotá, Medellín, Cali y las regiones del país.", "ej" => [
                ["sena", "Mira la seña. ¿Qué ciudad es?", ["opciones" => "Bogotá\nMedellín\nCali"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "🏖️ = Playa\n⛰️ = Montaña\n🌴 = Selva\n🏙️ = Ciudad"]],
                ["deletreo", "Mira el deletreo. ¿Qué ciudad es?", ["respuestas" => "Pasto"]],
                ["emparejar", "Une cada ciudad con su región.", ["pares" => "Cartagena = Caribe\nMedellín = Andina\nLeticia = Amazonía\nQuibdó = Pacífica"]],
                ["vf", "Muchas ciudades tienen una seña propia en LSC y no hace falta deletrear su nombre.", ["correcta_vf" => "1", "imagen" => "🏙️"]],
                ["opcion", "Si no conoces la seña de una ciudad, puedes…", ["opciones" => "Deletrear su nombre con el alfabeto manual\nInventar una seña nueva\nNo mencionarla", "imagen" => "🔤"]],
            ]],
            ["titulo" => "Planear un viaje", "descripcion" => "La maleta, los pasajes y el presupuesto.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Viajar\nVolver\nQuedarse"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Maleta\nPasaje\nPasaporte"]],
                ["clasificar", "¿Qué llevas a tierra caliente y qué a tierra fría?", ["grupos" => "Tierra caliente\nTierra fría", "items" => "Vestido de baño = Tierra caliente\nBloqueador = Tierra caliente\nSandalias = Tierra caliente\nRuana = Tierra fría\nBufanda = Tierra fría\nGuantes = Tierra fría"]],
                ["ordenar", "Ordena la frase.", ["frase" => "El próximo mes voy a viajar a Cartagena"]],
                ["completar", "Completa la cuenta.", ["frase" => "Si el pasaje cuesta 80.000 pesos de ida y lo mismo de vuelta, en total pago ___ pesos.", "respuestas" => "160.000\n160000\nciento sesenta mil"]],
                ["elegir", "¿Cuál es la «maleta»?", ["opciones" => "🧳\n🎒\n👜\n📦"]],
            ]],
            ["titulo" => "En el terminal y el hotel", "descripcion" => "Pasajes, pantallas y la habitación.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Habitación\nLlave\nReserva"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "¿A qué hora sale?\n¿Cuánto vale?\n¿Dónde es la entrada?"]],
                ["vf", "En terminales y aeropuertos, los anuncios en pantalla ayudan a las personas sordas a enterarse de los cambios.", ["correcta_vf" => "1", "imagen" => "🖥️"]],
                ["opcion", "Si en el aeropuerto cambian la puerta de embarque y lo anuncian solo por altavoz, una persona sorda puede…", ["opciones" => "Pedir ayuda al personal y revisar las pantallas\nEsperar sin preguntar\nAdivinar la puerta", "imagen" => "✈️"]],
                ["emparejar", "Une cada palabra con su significado.", ["pares" => "Embarque = Subir al avión o al bus\nEquipaje = Las maletas que llevas\nReserva = Apartar la habitación\nTaquilla = Donde se compra el pasaje"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "🛏️ = Habitación\n🔑 = Llave\n🧳 = Equipaje\n🎫 = Pasaje"]],
            ]],
            ["titulo" => "Contar un viaje", "descripcion" => "El pasado, el orden de los hechos y la línea del tiempo.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Ya fui\nVoy a ir\nNunca he ido"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Primero\nDespués\nAl final"]],
                ["ordenar", "Ordena la frase.", ["frase" => "El año pasado viajé con mi familia a Santa Marta"]],
                ["clasificar", "¿Ya pasó o va a pasar?", ["grupos" => "Ya pasó\nVa a pasar", "items" => "El año pasado fui al mar = Ya pasó\nAyer llegué de Cali = Ya pasó\nHace un mes conocí Leticia = Ya pasó\nMañana viajo a Pasto = Va a pasar\nEl próximo año iré a Cartagena = Va a pasar\nEn diciembre voy a ir a la finca = Va a pasar"]],
                ["vf", "Al contar una historia en LSC, el tiempo se puede marcar una vez al inicio, sin repetirlo en cada frase.", ["correcta_vf" => "1", "imagen" => "⏪"]],
                ["huecos", "Completa el relato.", ["texto_huecos" => "El año ___ fuimos a Santa Marta. ___ llegamos al hotel y al final fuimos a la playa.", "respuestas" => "pasado\nPrimero", "extra" => "próximo"]],
            ]],
        ]],
        ["clave" => "lsc-b1-u3", "titulo" => "La gramática del espacio", "descripcion" => "Verbos con dirección, clasificadores, números grandes y el cambio de rol.", "icono" => "fa-hands", "orden" => 3, "lecciones" => [
            ["titulo" => "Verbos que cambian de dirección", "descripcion" => "Yo te ayudo, tú me ayudas.", "ej" => [
                ["opcion", "En LSC, en verbos como «ayudar» o «preguntar», la dirección del movimiento muestra…", ["opciones" => "Quién hace la acción y quién la recibe\nSi la frase está en pasado\nCuántas personas hay", "imagen" => "↔️"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Yo te ayudo\nTú me ayudas\nÉl me ayuda"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Yo te pregunto\nTú me preguntas\nElla te pregunta"]],
                ["vf", "Con un verbo de dirección, «yo te doy» y «tú me das» se diferencian por el sentido del movimiento.", ["correcta_vf" => "1"]],
                ["emparejar", "Une cada frase con quién recibe la acción.", ["pares" => "Yo te ayudo = Tú\nTú me ayudas = Yo\nElla les ayuda = Ustedes o ellos\nYo le ayudo = Él o ella"]],
                ["clasificar", "¿Quién hace la acción?", ["grupos" => "Yo\nTú", "items" => "Yo te doy = Yo\nYo te aviso = Yo\nYo te pregunto = Yo\nTú me das = Tú\nTú me avisas = Tú\nTú me preguntas = Tú"]],
            ]],
            ["titulo" => "Los clasificadores", "descripcion" => "Mostrar personas, carros y objetos en el espacio.", "ej" => [
                ["opcion", "En LSC, un clasificador es…", ["opciones" => "Una forma de la mano que representa un tipo de objeto y muestra dónde está o cómo se mueve\nUna letra del alfabeto manual\nUna seña para saludar", "imagen" => "🚗"]],
                ["vf", "Con un clasificador se puede mostrar, por ejemplo, que un carro sube por una montaña.", ["correcta_vf" => "1", "imagen" => "⛰️"]],
                ["clasificar", "¿Es plano, redondo o una persona?", ["grupos" => "Plano\nRedondo\nPersona", "items" => "Hoja = Plano\nMesa = Plano\nPelota = Redondo\nNaranja = Redondo\nPeatón = Persona\nNiño = Persona"]],
                ["sena", "Mira la seña. ¿Qué muestra?", ["opciones" => "Un carro que pasa rápido\nUna persona que camina\nUn libro sobre la mesa"]],
                ["sena", "Mira la seña. ¿Qué muestra?", ["opciones" => "Dos personas se encuentran\nUna persona se cae\nUn carro se estrella"]],
                ["opcion", "Antes de usar un clasificador para un carro, normalmente…", ["opciones" => "Se hace primero la seña de «carro»\nSe deletrea su color\nSe señala al cielo"]],
            ]],
            ["titulo" => "Números grandes y cantidades", "descripcion" => "Del 20 al 1.000, precios y edades.", "ej" => [
                ["sena", "Mira la seña. ¿Qué número es?", ["opciones" => "25\n52\n15"]],
                ["sena", "Mira la seña. ¿Qué número es?", ["opciones" => "100\n1.000\n10"]],
                ["completar", "Completa la serie.", ["frase" => "100, 200, 300, ___", "respuestas" => "400\ncuatrocientos"]],
                ["emparejar", "Une cada número con su nombre.", ["pares" => "20 = Veinte\n50 = Cincuenta\n100 = Cien\n1.000 = Mil"]],
                ["vf", "En LSC un número se puede unir a otra seña, por ejemplo para decir «hace dos semanas».", ["correcta_vf" => "1", "imagen" => "📅"]],
                ["completar", "Completa la cuenta.", ["frase" => "Si en el salón hay 12 estudiantes sordos y 18 oyentes, en total hay ___ estudiantes.", "respuestas" => "30\ntreinta"]],
            ]],
            ["titulo" => "Narrar: el cambio de rol", "descripcion" => "Contar lo que dijo o hizo otra persona.", "ej" => [
                ["opcion", "En LSC, el «cambio de rol» sirve para…", ["opciones" => "Contar lo que dijo o hizo otra persona, como si fueras ella\nCambiar de idioma\nTerminar la conversación", "imagen" => "🎭"]],
                ["vf", "En el cambio de rol, la mirada y la posición del cuerpo ayudan a mostrar quién está hablando.", ["correcta_vf" => "1", "imagen" => "👀"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Me dijo\nLe dije\nNos dijeron"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Contar una historia\nHacer una pregunta\nDar una orden"]],
                ["clasificar", "¿Ayuda a contar una historia clara en LSC?", ["grupos" => "Ayuda\nConfunde", "items" => "Ubicar a cada personaje en un lugar del espacio = Ayuda\nMarcar el tiempo al inicio = Ayuda\nUsar la expresión del rostro = Ayuda\nCambiar de lugar a los personajes sin avisar = Confunde\nNarrar sin mirar a nadie = Confunde\nNo dejar claro quién habla = Confunde"]],
                ["ordenar", "Ordena la frase.", ["frase" => "Mi hermano me dijo que mañana viene"]],
            ]],
        ]],
        ["clave" => "lsc-b1-u4", "titulo" => "Comunidad sorda y accesibilidad", "descripcion" => "Historia, organizaciones, tecnología, el intérprete y el repaso del nivel.", "icono" => "fa-universal-access", "orden" => 4, "lecciones" => [
            ["titulo" => "Historia y organizaciones", "descripcion" => "FENASCOL, INSOR y las leyes de la LSC.", "ej" => [
                ["emparejar", "Une cada nombre con lo que es.", ["pares" => "FENASCOL = Federación Nacional de Sordos de Colombia\nINSOR = Instituto Nacional para Sordos\nLey 324 de 1996 = Reconoce la LSC como idioma de la comunidad sorda\nLey 982 de 2005 = Igualdad de oportunidades para sordos y sordociegos"]],
                ["opcion", "La ley colombiana que reconoce la LSC como idioma propio de la comunidad sorda es…", ["opciones" => "La Ley 324 de 1996\nLa Ley 100 de 1993\nLa Ley 30 de 1992", "imagen" => "📜"]],
                ["vf", "FENASCOL es una organización de la propia comunidad sorda, dirigida por personas sordas.", ["correcta_vf" => "1", "imagen" => "🧏"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Comunidad sorda\nOyente\nIntérprete"]],
                ["deletreo", "Mira el deletreo. ¿Qué sigla es?", ["respuestas" => "insor"]],
                ["vf", "La LSC es igual en todos los países de habla hispana.", ["correcta_vf" => "0", "imagen" => "🌎"]],
            ]],
            ["titulo" => "Tecnología y accesibilidad", "descripcion" => "Videollamadas, subtítulos y el Centro de Relevo.", "ej" => [
                ["opcion", "El Centro de Relevo sirve para…", ["opciones" => "Que una persona sorda se comunique con oyentes por medio de un intérprete en línea\nAprender a leer los labios\nReparar audífonos", "imagen" => "📱"]],
                ["clasificar", "¿Es accesible para una persona sorda?", ["grupos" => "Accesible\nNo accesible", "items" => "Video con subtítulos = Accesible\nVideo con intérprete en un recuadro = Accesible\nAlarma con luz = Accesible\nAnuncio solo por altavoz = No accesible\nVideo sin subtítulos = No accesible\nTrámite solo por teléfono = No accesible"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Videollamada\nMensaje de texto\nCorreo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Subtítulos\nIntérprete\nPantalla"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "📹 = Videollamada\n💬 = Mensaje\n🔔 = Alarma\n📺 = Televisión"]],
                ["vf", "Las videollamadas facilitaron mucho conversar en lengua de señas a distancia.", ["correcta_vf" => "1", "imagen" => "📹"]],
            ]],
            ["titulo" => "El intérprete y su papel", "descripcion" => "Qué hace un intérprete y cómo trabajar con uno.", "ej" => [
                ["opcion", "Un intérprete de LSC debe…", ["opciones" => "Transmitir el mensaje completo sin dar su opinión\nResumir solo lo que le parece importante\nResponder por la persona sorda", "imagen" => "🤟"]],
                ["vf", "El intérprete debe guardar en reserva lo que se dice en una cita médica o legal.", ["correcta_vf" => "1", "imagen" => "🤐"]],
                ["clasificar", "¿Correcto o incorrecto al trabajar con un intérprete?", ["grupos" => "Correcto\nIncorrecto", "items" => "Mirar a la persona sorda al hablar = Correcto\nHablar a un ritmo normal = Correcto\nCompartir antes los documentos = Correcto\nDecir «dile que…» = Incorrecto\nPedirle su opinión al intérprete = Incorrecto\nTaparle la vista al intérprete = Incorrecto"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Interpretar\nTraducir\nExplicar"]],
                ["emparejar", "Une cada persona con el apoyo que suele necesitar.", ["pares" => "Persona sorda usuaria de LSC = Intérprete de LSC\nPersona sordociega = Guía-intérprete\nPersona sorda que no sabe LSC = Subtítulos o texto escrito"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Más despacio, por favor\nNo te entiendo\nRepite, por favor"]],
            ]],
            ["titulo" => "Repaso del B1 completo", "descripcion" => "Todo lo del nivel, mezclado.", "ej" => [
                ["clasificar", "Clasifica las palabras.", ["grupos" => "Trabajo\nViajes\nComunidad sorda", "items" => "Jefe = Trabajo\nContrato = Trabajo\nMaleta = Viajes\nPasaje = Viajes\nFENASCOL = Comunidad sorda\nIntérprete = Comunidad sorda"]],
                ["emparejar", "Une cada idea con su nombre en la gramática de la LSC.", ["pares" => "Mostrar con la dirección quién hace la acción = Verbo de dirección\nRepresentar un carro con la mano = Clasificador\nContar lo que dijo otra persona = Cambio de rol"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Viajé a Cali el año pasado\nVoy a viajar a Cali\nNunca he ido a Cali"]],
                ["deletreo", "Mira el deletreo. ¿Qué ciudad es?", ["respuestas" => "Neiva"]],
                ["completar", "Completa la frase.", ["frase" => "La ley que reconoce la LSC en Colombia es la Ley ___ de 1996.", "respuestas" => "324"]],
                ["vf", "En la LSC, el rostro, la mirada y el espacio son parte de la gramática.", ["correcta_vf" => "1"]],
            ]],
        ]],
    ],
];
