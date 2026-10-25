<?php
/**
 * Contenido LSC A1 (fase 2): juegos para las lecciones que ya existen y dos
 * unidades nuevas. Lo convierte en SQL tools/generar_lsc.php.
 *
 * Cada ejercicio: [tipo, enunciado, campos del formulario de Admin > Ruta].
 * En "opcion", "sena" y "elegir" la primera opción es la correcta, salvo
 * que se diga "correcta". Los de tipo "sena" y "deletreo" quedan sin video
 * (no se muestran) hasta que se pegue el video del INSOR.
 *
 * Nada de esto describe cómo se hace una seña: eso lo muestra el video.
 */
return [
    "idioma" => "LSC",
    "nivel"  => "A1",

    // Ejercicios que se suman a lecciones ya publicadas. Entran inactivos:
    // el revisor los activa (o se activan todos juntos cuando apruebe).
    "agregar" => [
        "lsc-a1-u1-l1" => ["desde" => 7, "ej" => [
            ["clasificar", "¿Es una lengua de señas o una lengua oral?", ["grupos" => "Lengua de señas\nLengua oral", "items" => "LSC (Colombia) = Lengua de señas\nASL (Estados Unidos) = Lengua de señas\nLSE (España) = Lengua de señas\nEspañol = Lengua oral\nInglés = Lengua oral\nWayuunaiki = Lengua oral"]],
            ["opcion", "El Día Internacional de las Lenguas de Señas se celebra el…", ["opciones" => "23 de septiembre\n1 de enero\n25 de diciembre", "imagen" => "🌎"]],
        ]],
        "lsc-a1-u1-l2" => ["desde" => 7, "ej" => [
            ["vf", "Encender y apagar la luz es una forma aceptada de llamar la atención de un grupo de personas sordas.", ["correcta_vf" => "1", "imagen" => "💡"]],
            ["memoria", "Encuentra las parejas.", ["pares" => "🧏 = Persona sorda\n👀 = Contacto visual\n💡 = Encender la luz\n🙋 = Pedir el turno"]],
        ]],
        "lsc-a1-u1-l3" => ["desde" => 6, "ej" => [
            ["clasificar", "¿Es parte de la seña o no?", ["grupos" => "Parte de la seña\nNo es parte de la seña", "items" => "Forma de la mano = Parte de la seña\nMovimiento = Parte de la seña\nLugar donde se hace = Parte de la seña\nExpresión del rostro = Parte de la seña\nColor de la ropa = No es parte de la seña\nTono de voz = No es parte de la seña"]],
            ["opcion", "La expresión del rostro puede convertir una afirmación en…", ["opciones" => "Una pregunta\nUn número\nUna letra", "imagen" => "🤔"]],
        ]],
        "lsc-a1-u2-l1" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "👋 = Hola\n🌅 = Buenos días\n🌇 = Buenas tardes\n🌙 = Buenas noches"]],
            ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Buenas tardes\nBuenas noches\nBuenos días"]],
        ]],
        "lsc-a1-u2-l2" => ["desde" => 6, "ej" => [
            ["elegir", "¿Qué cara va con «estoy bien»?", ["opciones" => "😊\n😢\n😠\n😴"]],
            ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Cansado\nFeliz\nTriste"]],
        ]],
        "lsc-a1-u2-l3" => ["desde" => 6, "ej" => [
            ["emparejar", "Une cada situación con la palabra amable.", ["pares" => "Alguien te ayuda = Gracias\nPides algo = Por favor\nTropiezas con alguien = Perdón\nConoces a alguien = Mucho gusto"]],
            ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Mucho gusto\nGracias\nAdiós"]],
        ]],
        "lsc-a1-u3-l1" => ["desde" => 6, "ej" => [
            ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "café\ncafe"]],
            ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "día\ndia"]],
        ]],
        "lsc-a1-u3-l2" => ["desde" => 6, "ej" => [
            ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "mamá\nmama"]],
            ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "pelo"]],
        ]],
        "lsc-a1-u3-l3" => ["desde" => 6, "ej" => [
            ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "sol"]],
            ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "vaso"]],
        ]],
        "lsc-a1-u4-l1" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "☝️ = Uno\n✌️ = Dos\n🖐️ = Cinco\n🔟 = Diez"]],
            ["sena", "Mira la seña. ¿Qué número es?", ["opciones" => "8\n3\n6"]],
            ["completar", "Completa la serie.", ["frase" => "2, 4, 6, ___", "respuestas" => "8\nocho"]],
        ]],
        "lsc-a1-u4-l2" => ["desde" => 6, "ej" => [
            ["memoria", "Encuentra las parejas.", ["pares" => "👩 = Mamá\n👨 = Papá\n👵 = Abuela\n👶 = Bebé"]],
            ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Abuela\nMamá\nHija"]],
        ]],
        "lsc-a1-u4-l3" => ["desde" => 6, "ej" => [
            ["clasificar", "Clasifica las palabras.", ["grupos" => "Saludos\nFamilia\nNúmeros", "items" => "Hola = Saludos\nBuenas noches = Saludos\nMamá = Familia\nHermano = Familia\nTres = Números\nDiez = Números"]],
            ["elegir", "¿Qué dibujo representa a una persona sorda?", ["opciones" => "🧏\n🗣️\n🎧"]],
        ]],
    ],

    // Unidades nuevas: quedan sin publicar hasta que el revisor las apruebe.
    "unidades" => [
        ["clave" => "lsc-a1-u5", "titulo" => "Yo y los demás", "descripcion" => "Señalar a las personas, presentarte, las emociones y los turnos.", "icono" => "fa-user-group", "orden" => 5, "lecciones" => [
            ["titulo" => "Yo, tú, él y ella", "descripcion" => "En LSC se señala a las personas.", "ej" => [
                ["opcion", "En LSC, para referirte a la persona que tienes enfrente («tú»), normalmente…", ["opciones" => "La señalas con el dedo índice\nDeletreas T-Ú\nTe tocas la cabeza", "imagen" => "👉"]],
                ["vf", "Para hablar de alguien que no está, se le puede ubicar en un punto del espacio y luego señalar ese punto.", ["correcta_vf" => "1"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Yo\nTú\nElla"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Nosotros\nUstedes\nEllos"]],
                ["emparejar", "Une cada palabra con lo que se hace.", ["pares" => "Yo = Me señalo a mí\nTú = Señalo a quien tengo enfrente\nÉl o ella = Señalo a otra persona o a su lugar"]],
                ["vf", "En LSC señalar a las personas es de mala educación.", ["correcta_vf" => "0", "imagen" => "👉"]],
            ]],
            ["titulo" => "¿Cómo te llamas?", "descripcion" => "Preséntate y conoce la seña personal.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "¿Cómo te llamas?\n¿Cuántos años tienes?\n¿Dónde vives?"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Nombre\nCasa\nAmigo"]],
                ["deletreo", "Mira el deletreo. ¿Qué nombre es?", ["respuestas" => "Ana"]],
                ["opcion", "La seña personal de alguien suele inspirarse en…", ["opciones" => "Un rasgo físico o de su personalidad\nSu número de cédula\nSu contraseña", "imagen" => "🏷️"]],
                ["vf", "Lo mejor es inventarte tu propia seña personal antes de conocer a la comunidad sorda.", ["correcta_vf" => "0"]],
                ["emparejar", "Une cada pregunta con su respuesta.", ["pares" => "¿Cómo te llamas? = Me llamo Ana\n¿Cómo estás? = Bien, gracias\n¿Cuántos años tienes? = Tengo diez años"]],
            ]],
            ["titulo" => "Emociones en el rostro", "descripcion" => "Feliz, triste, enojado, sorprendido.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "😊 = Feliz\n😢 = Triste\n😠 = Enojado\n😲 = Sorprendido"]],
                ["elegir", "Elige la cara de «enojado».", ["opciones" => "😠\n😊\n😢\n😴"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Feliz\nTriste\nEnojado"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Sorprendido\nAsustado\nCansado"]],
                ["vf", "En LSC el rostro debe acompañar la emoción que se expresa.", ["correcta_vf" => "1", "imagen" => "🙂"]],
                ["opcion", "Si haces la seña de «triste» mientras sonríes…", ["opciones" => "El mensaje se vuelve confuso\nSe entiende mejor\nSignifica «feliz»"]],
            ]],
            ["titulo" => "Turnos en la conversación", "descripcion" => "Mirar, esperar y pedir la palabra.", "ej" => [
                ["opcion", "En un grupo que conversa en LSC, para pedir la palabra puedes…", ["opciones" => "Levantar o mover la mano donde te vean\nHablar más fuerte\nDar la espalda", "imagen" => "🙋"]],
                ["vf", "Si dejas de mirar a quien te está hablando en señas, puede pensar que no le prestas atención.", ["correcta_vf" => "1", "imagen" => "👀"]],
                ["vf", "Caminar entre dos personas que conversan en LSC no interrumpe nada.", ["correcta_vf" => "0"]],
                ["clasificar", "¿Ayuda o interrumpe la conversación?", ["grupos" => "Ayuda\nInterrumpe", "items" => "Mirar a los ojos = Ayuda\nAsentir con la cabeza = Ayuda\nPedir que repitan = Ayuda\nMirar el celular = Interrumpe\nDar la espalda = Interrumpe\nTaparse la boca al hablar = Interrumpe"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Repite, por favor\nMás despacio\nNo entiendo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "No entiendo\nSí\nYa sé"]],
            ]],
        ]],
        ["clave" => "lsc-a1-u6", "titulo" => "Animales y el salón", "descripcion" => "Animales de la casa y de la granja, y las cosas del colegio.", "icono" => "fa-paw", "orden" => 6, "lecciones" => [
            ["titulo" => "Animales de la casa", "descripcion" => "Perro, gato, pájaro y pez.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "🐶 = Perro\n🐱 = Gato\n🐦 = Pájaro\n🐟 = Pez"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Perro\nGato\nConejo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Gato\nPez\nPájaro"]],
                ["elegir", "¿Cuál es el «conejo»?", ["opciones" => "🐰\n🐶\n🐱\n🐭"]],
                ["deletreo", "Mira el deletreo. ¿Qué animal es?", ["respuestas" => "gato"]],
                ["vf", "Hay perros entrenados para avisar a las personas sordas cuando suena el timbre o una alarma.", ["correcta_vf" => "1", "imagen" => "🐕"]],
            ]],
            ["titulo" => "Animales de la granja", "descripcion" => "Vaca, caballo, gallina y cerdo.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "🐄 = Vaca\n🐴 = Caballo\n🐔 = Gallina\n🐷 = Cerdo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Vaca\nCaballo\nOveja"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Gallina\nPato\nCerdo"]],
                ["elegir", "¿Cuál es la «oveja»?", ["opciones" => "🐑\n🐐\n🐄\n🐷"]],
                ["clasificar", "¿Vive en la casa o en la granja?", ["grupos" => "En la casa\nEn la granja", "items" => "Perro = En la casa\nGato = En la casa\nPez = En la casa\nVaca = En la granja\nCaballo = En la granja\nGallina = En la granja"]],
                ["opcion", "Muchas señas de animales se basan en…", ["opciones" => "Un rasgo del animal, como sus orejas o cómo se mueve\nEl sonido que hace el animal\nLa primera letra de su nombre, siempre", "imagen" => "🐴"]],
            ]],
            ["titulo" => "En el salón de clase", "descripcion" => "Libro, lápiz, profesor y compañeros.", "ej" => [
                ["memoria", "Encuentra las parejas.", ["pares" => "📚 = Libro\n✏️ = Lápiz\n📓 = Cuaderno\n🎒 = Morral"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Profesor\nEstudiante\nAmigo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Colegio\nCasa\nIglesia"]],
                ["elegir", "¿Cuál es el «lápiz»?", ["opciones" => "✏️\n📚\n✂️\n📏"]],
                ["vf", "En Colombia hay colegios con educación bilingüe para estudiantes sordos: LSC y español escrito.", ["correcta_vf" => "1", "imagen" => "🏫"]],
                ["opcion", "En un salón con estudiantes sordos, el intérprete se ubica…", ["opciones" => "Donde los estudiantes lo vean bien, cerca del profesor\nDetrás de los estudiantes\nFuera del salón"]],
            ]],
            ["titulo" => "Repaso del A1 completo", "descripcion" => "Todo lo del nivel, mezclado.", "ej" => [
                ["clasificar", "Clasifica las palabras.", ["grupos" => "Animales\nEmociones\nFamilia", "items" => "Perro = Animales\nVaca = Animales\nFeliz = Emociones\nTriste = Emociones\nMamá = Familia\nAbuelo = Familia"]],
                ["memoria", "Encuentra las parejas.", ["pares" => "👋 = Hola\n😊 = Feliz\n🐱 = Gato\n👵 = Abuela\n✏️ = Lápiz"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Gracias\nPor favor\nAdiós"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "luna"]],
                ["opcion", "¿Qué entidad pública colombiana trabaja por la educación de las personas sordas?", ["opciones" => "INSOR (Instituto Nacional para Sordos)\nICFES\nDANE", "imagen" => "🏛️"]],
                ["vf", "La Ley 982 de 2005 también protege los derechos de las personas sordas y sordociegas en Colombia.", ["correcta_vf" => "1"]],
            ]],
        ]],
    ],
];
