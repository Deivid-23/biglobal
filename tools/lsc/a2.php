<?php
/**
 * Contenido LSC A2 (fase 2): juegos para las 12 lecciones que ya existen y
 * dos unidades nuevas. Lo convierte en SQL tools/generar_lsc.php.
 *
 * El nivel A2 todavía está oculto, así que todo entra activo y publicado:
 * nadie lo ve hasta que se publique el nivel.
 *
 * Cada ejercicio: [tipo, enunciado, campos del formulario de Admin > Ruta].
 * En "opcion", "sena" y "elegir" la primera opción es la correcta, salvo
 * que se diga "correcta". Los de tipo "sena" y "deletreo" quedan sin video
 * (no se muestran) hasta que se pegue el video del INSOR.
 *
 * Nada de esto describe cómo se hace una seña: eso lo muestra el video.
 */
return [
    "idioma"   => "LSC",
    "nivel"    => "A2",
    "activo"   => 1,
    "publicar" => 1,

    // El repaso de la unidad 4 ya no es el último del nivel
    "renombrar" => [
        "lsc-a2-u4-l3" => ["Repaso: unidades 1 a 4", "Tiempo, lugares, comida y preguntas, mezclados."],
    ],

    // Ejercicios que se suman a las lecciones que ya existen
    "agregar" => [
        "lsc-a2-u1-l1" => ["desde" => 6, "ej" => [
            ["ordenar", "Ordena los días de la semana.", ["frase" => "lunes martes miércoles jueves viernes sábado domingo"]],
            ["completar", "Completa la frase.", ["frase" => "Después del viernes viene el ___.", "respuestas" => "sábado\nsabado"]],
            ["clasificar", "¿Entre semana o fin de semana?", ["grupos" => "Entre semana\nFin de semana", "items" => "Lunes = Entre semana\nMartes = Entre semana\nMiércoles = Entre semana\nJueves = Entre semana\nSábado = Fin de semana\nDomingo = Fin de semana"]],
        ]],
        "lsc-a2-u1-l2" => ["desde" => 6, "ej" => [
            ["clasificar", "¿Pasado o futuro?", ["grupos" => "Pasado\nFuturo", "items" => "Ayer = Pasado\nAnteayer = Pasado\nLa semana pasada = Pasado\nMañana = Futuro\nPasado mañana = Futuro\nEl próximo año = Futuro"]],
            ["completar", "Completa la frase.", ["frase" => "Si hoy es martes, mañana es ___.", "respuestas" => "miércoles\nmiercoles"]],
            ["memoria", "Encuentra las parejas.", ["pares" => "⏪ = Ayer\n⏺️ = Hoy\n⏩ = Mañana"]],
        ]],
        "lsc-a2-u1-l3" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "🌄 = En la mañana\n☀️ = Al mediodía\n🌇 = En la tarde\n🌙 = En la noche"]],
            ["clasificar", "¿Qué haces a esa hora?", ["grupos" => "En la mañana\nEn la noche", "items" => "Despertarse = En la mañana\nDesayunar = En la mañana\nIr al colegio = En la mañana\nCenar = En la noche\nPonerse la piyama = En la noche\nDormir = En la noche"]],
            ["elegir", "¿Qué dibujo va con «en la noche»?", ["opciones" => "🌙\n☀️\n🌄"]],
        ]],
        "lsc-a2-u2-l1" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "🏠 = Casa\n🏫 = Colegio\n🏥 = Hospital\n🏦 = Banco\n🌳 = Parque"]],
            ["elegir", "¿Cuál es el «hospital»?", ["opciones" => "🏥\n🏫\n🏦\n🏠"]],
            ["sena", "Mira la seña. ¿Qué lugar es?", ["opciones" => "Biblioteca\nRestaurante\nSupermercado"]],
        ]],
        "lsc-a2-u2-l2" => ["desde" => 6, "ej" => [
            ["elegir", "¿Qué flecha indica «izquierda»?", ["opciones" => "⬅️\n➡️\n⬆️\n⬇️"]],
            ["emparejar", "Une cada palabra con su contrario.", ["pares" => "Izquierda = Derecha\nCerca = Lejos\nArriba = Abajo\nAdelante = Atrás"]],
            ["vf", "Un mapa o un dibujo también puede ayudar a explicarle un camino a una persona sorda.", ["correcta_vf" => "1", "imagen" => "🗺️"]],
        ]],
        "lsc-a2-u2-l3" => ["desde" => 6, "ej" => [
            ["ordenar", "Ordena la pregunta.", ["frase" => "¿Dónde está el baño?"]],
            ["emparejar", "Une cada pregunta con su respuesta.", ["pares" => "¿Dónde está el baño? = Al fondo, a la derecha\n¿Está lejos? = No, está cerca\n¿Cómo llego al parque? = Sigue derecho dos cuadras"]],
            ["deletreo", "Mira el deletreo. ¿Qué lugar es?", ["respuestas" => "parque"]],
        ]],
        "lsc-a2-u3-l1" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "🍎 = Manzana\n🍞 = Pan\n🥛 = Leche\n🍚 = Arroz"]],
            ["clasificar", "¿Se come o se bebe?", ["grupos" => "Se come\nSe bebe", "items" => "Pan = Se come\nArroz = Se come\nManzana = Se come\nAgua = Se bebe\nLeche = Se bebe\nJugo = Se bebe"]],
            ["elegir", "¿Cuál es el «huevo»?", ["opciones" => "🥚\n🍞\n🍎\n🧀"]],
        ]],
        "lsc-a2-u3-l2" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "🔴 = Rojo\n🔵 = Azul\n🟢 = Verde\n🟡 = Amarillo"]],
            ["elegir", "Elige el color «morado».", ["opciones" => "🟣\n🟠\n🟤\n⚫"]],
            ["completar", "Completa la frase.", ["frase" => "La bandera de Colombia es amarilla, azul y ___.", "respuestas" => "roja\nrojo"]],
        ]],
        "lsc-a2-u3-l3" => ["desde" => 6, "ej" => [
            ["clasificar", "¿Qué cara dice «me gusta» y cuál «no me gusta»?", ["grupos" => "Me gusta\nNo me gusta", "items" => "😋 = Me gusta\n😍 = Me gusta\n👍 = Me gusta\n🤢 = No me gusta\n👎 = No me gusta\n😖 = No me gusta"]],
            ["emparejar", "Une cada frase con lo que quieres hacer.", ["pares" => "Tengo hambre = Comer\nTengo sed = Beber\nTengo sueño = Dormir"]],
            ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Dulce\nSalado\nPicante"]],
        ]],
        "lsc-a2-u4-l1" => ["desde" => 6, "ej" => [
            ["emparejar", "Une cada pregunta con lo que se responde.", ["pares" => "¿Quién? = Una persona\n¿Dónde? = Un lugar\n¿Cuándo? = Un momento\n¿Cuánto? = Una cantidad\n¿Por qué? = Una razón"]],
            ["clasificar", "¿Las cejas se levantan o se fruncen?", ["grupos" => "Se levantan\nSe fruncen", "items" => "¿Vienes mañana? = Se levantan\n¿Te gusta el café? = Se levantan\n¿Tienes hermanos? = Se levantan\n¿Dónde vives? = Se fruncen\n¿Qué hora es? = Se fruncen\n¿Por qué lloras? = Se fruncen"]],
        ]],
        "lsc-a2-u4-l2" => ["desde" => 6, "ej" => [
            ["ordenar", "Ordena la frase.", ["frase" => "Más despacio, por favor"]],
            ["vf", "Si no entiendes una seña, puedes pedirle a la persona que deletree la palabra.", ["correcta_vf" => "1", "imagen" => "🔤"]],
            ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Otra vez\nDespués\nAntes"]],
        ]],
        "lsc-a2-u4-l3" => ["desde" => 6, "ej" => [
            ["clasificar", "Clasifica las palabras.", ["grupos" => "Tiempo\nLugares\nComida", "items" => "Ayer = Tiempo\nLunes = Tiempo\nParque = Lugares\nHospital = Lugares\nPan = Comida\nArroz = Comida"]],
            ["memoria", "Encuentra las parejas.", ["pares" => "🟢 = Verde\n🏦 = Banco\n🌙 = Noche\n🍞 = Pan"]],
        ]],
    ],

    // Unidades nuevas
    "unidades" => [
        ["clave" => "lsc-a2-u5", "titulo" => "Mi casa y mi día", "descripcion" => "Las partes de la casa, la rutina, la ropa y el clima.", "icono" => "fa-house", "orden" => 5, "lecciones" => [
            ["titulo" => "Las partes de la casa", "descripcion" => "Cocina, baño, cuarto y sala.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "🛏️ = Cuarto\n🛁 = Baño\n🍳 = Cocina\n🛋️ = Sala"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Cocina\nBaño\nCuarto"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Ventana\nPuerta\nMesa"]],
                ["elegir", "¿Cuál es la «puerta»?", ["opciones" => "🚪\n🛏️\n🛁\n📺"]],
                ["clasificar", "¿En qué parte de la casa está?", ["grupos" => "Cocina\nBaño", "items" => "Estufa = Cocina\nNevera = Cocina\nOlla = Cocina\nDucha = Baño\nInodoro = Baño\nCepillo de dientes = Baño"]],
                ["vf", "En muchas casas de personas sordas, el timbre enciende una luz en lugar de solo sonar.", ["correcta_vf" => "1", "imagen" => "💡"]],
            ]],
            ["titulo" => "Mi rutina diaria", "descripcion" => "Despertarse, bañarse, comer y dormir.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "⏰ = Despertarse\n🚿 = Bañarse\n🍽️ = Almorzar\n😴 = Dormir"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Despertarse\nDormir\nBañarse"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Desayunar\nAlmorzar\nCenar"]],
                ["ordenar", "Ordena la frase.", ["frase" => "Me levanto a las seis de la mañana"]],
                ["emparejar", "Une cada comida con su momento del día.", ["pares" => "Desayuno = En la mañana\nAlmuerzo = Al mediodía\nCena = En la noche"]],
                ["vf", "Muchas personas sordas usan despertadores que vibran o encienden una luz.", ["correcta_vf" => "1", "imagen" => "⏰"]],
            ]],
            ["titulo" => "La ropa", "descripcion" => "Camisa, pantalón, zapatos y chaqueta.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "👕 = Camisa\n👖 = Pantalón\n👟 = Zapatos\n🧥 = Chaqueta"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Camisa\nPantalón\nVestido"]],
                ["deletreo", "Mira el deletreo. ¿Qué prenda es?", ["respuestas" => "gorra"]],
                ["elegir", "¿Cuál es el «vestido»?", ["opciones" => "👗\n👕\n👖\n🧢"]],
                ["clasificar", "¿Para el frío o para el calor?", ["grupos" => "Para el frío\nPara el calor", "items" => "Chaqueta = Para el frío\nBufanda = Para el frío\nGuantes = Para el frío\nPantaloneta = Para el calor\nSandalias = Para el calor\nCamiseta = Para el calor"]],
                ["completar", "Completa la frase.", ["frase" => "Cuando llueve me pongo las botas y la ___ de lluvia.", "respuestas" => "chaqueta\ncapa\nruana"]],
            ]],
            ["titulo" => "El clima", "descripcion" => "Sol, lluvia, frío y calor.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "☀️ = Sol\n🌧️ = Lluvia\n🥶 = Frío\n🥵 = Calor"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Llueve\nHace sol\nHace viento"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Hace frío\nHace calor\nEstá nublado"]],
                ["elegir", "Elige el dibujo de «está nublado».", ["opciones" => "☁️\n☀️\n🌧️\n❄️"]],
                ["emparejar", "Une cada clima con lo que necesitas.", ["pares" => "Llueve = Paraguas\nHace sol = Gorra\nHace frío = Chaqueta"]],
                ["opcion", "Para decir en LSC que hace «mucho» frío, ayuda mucho…", ["opciones" => "La expresión del rostro\nDeletrear M-U-C-H-O\nHablar en voz alta", "imagen" => "🥶"]],
            ]],
        ]],
        ["clave" => "lsc-a2-u6", "titulo" => "Salud, compras y transporte", "descripcion" => "El cuerpo y el médico, ir de compras y moverse por la ciudad.", "icono" => "fa-bus", "orden" => 6, "lecciones" => [
            ["titulo" => "El cuerpo y la salud", "descripcion" => "Partes del cuerpo, «me duele» y el médico.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "👁️ = Ojo\n👂 = Oreja\n👃 = Nariz\n✋ = Mano"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Me duele\nEstoy bien\nTengo frío"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Médico\nEnfermera\nFarmacia"]],
                ["elegir", "¿Cuál es el «pie»?", ["opciones" => "🦶\n✋\n👂\n👃"]],
                ["completar", "Completa la frase.", ["frase" => "Cuando estoy enfermo voy al ___.", "respuestas" => "médico\nmedico\ndoctor\nhospital"]],
                ["vf", "En Colombia, una persona sorda tiene derecho a un intérprete de LSC para entender al médico.", ["correcta_vf" => "1", "imagen" => "🏥"]],
            ]],
            ["titulo" => "De compras", "descripcion" => "Precios, dinero y la tienda.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "💵 = Dinero\n🛒 = Carrito\n🏪 = Tienda\n🧾 = Recibo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "¿Cuánto cuesta?\n¿Dónde queda?\n¿Qué hora es?"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Dinero\nTienda\nCaro"]],
                ["emparejar", "Une cada cosa con dónde se compra.", ["pares" => "Pan = Panadería\nRemedios = Farmacia\nZapatos = Zapatería\nFrutas = Plaza de mercado"]],
                ["completar", "Completa la cuenta.", ["frase" => "Si un pan cuesta 1.000 pesos, dos panes cuestan ___ pesos.", "respuestas" => "2.000\n2000\ndos mil"]],
                ["opcion", "Si no entiendes el precio que te dicen en una tienda, una buena idea es…", ["opciones" => "Pedir que lo escriban o lo muestren\nPagar cualquier cosa\nIrte sin comprar", "imagen" => "🏪"]],
            ]],
            ["titulo" => "Los medios de transporte", "descripcion" => "Bus, carro, bicicleta y avión.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "🚌 = Bus\n🚗 = Carro\n🚲 = Bicicleta\n✈️ = Avión"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Bus\nCarro\nMoto"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Avión\nBarco\nTren"]],
                ["elegir", "¿Cuál es la «moto»?", ["opciones" => "🏍️\n🚲\n🚗\n🚌"]],
                ["clasificar", "¿Por tierra, por agua o por aire?", ["grupos" => "Por tierra\nPor agua\nPor aire", "items" => "Bus = Por tierra\nBicicleta = Por tierra\nBarco = Por agua\nLancha = Por agua\nAvión = Por aire\nHelicóptero = Por aire"]],
                ["vf", "En Colombia, las personas sordas pueden sacar la licencia de conducción.", ["correcta_vf" => "1", "imagen" => "🚗"]],
            ]],
            ["titulo" => "Repaso del A2 completo", "descripcion" => "Todo lo del nivel, mezclado.", "ej" => [
                ["clasificar", "Clasifica las palabras.", ["grupos" => "Casa\nRopa\nTransporte", "items" => "Cocina = Casa\nBaño = Casa\nCamisa = Ropa\nZapatos = Ropa\nBus = Transporte\nAvión = Transporte"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "🌧️ = Lluvia\n🍎 = Manzana\n🏥 = Hospital\n🚲 = Bicicleta\n🟡 = Amarillo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Mañana\nAyer\nHoy"]],
                ["deletreo", "Mira el deletreo. ¿Qué día es?", ["respuestas" => "lunes"]],
                ["emparejar", "Une cada pregunta con su respuesta.", ["pares" => "¿Qué día es hoy? = Hoy es lunes\n¿Dónde está el banco? = Cerca del parque\n¿Te gusta el arroz? = Sí, me gusta\n¿Cuánto cuesta? = Dos mil pesos"]],
                ["vf", "En LSC, el tiempo, los lugares y las personas se pueden ubicar en el espacio.", ["correcta_vf" => "1"]],
            ]],
        ]],
    ],
];
