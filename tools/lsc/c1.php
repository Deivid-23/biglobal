<?php
/**
 * Contenido LSC C1 (fase 2): el nivel estaba vacío; aquí van sus 4 unidades
 * y 16 lecciones. Lo convierte en SQL tools/generar_lsc.php.
 *
 * El nivel C1 todavía está oculto, así que todo entra publicado: nadie lo
 * ve hasta que se publique el nivel.
 *
 * Cada ejercicio: [tipo, enunciado, campos del formulario de Admin > Ruta].
 * En "opcion", "sena" y "elegir" la primera opción es la correcta, salvo
 * que se diga "correcta". Los de tipo "sena" y "deletreo" quedan sin video
 * (no se muestran) hasta que se pegue el video del INSOR.
 *
 * Nada de esto describe cómo se hace una seña: eso lo muestra el video.
 * Historia, ética y variación van para que el revisor las confirme.
 */
return [
    "idioma"   => "LSC",
    "nivel"    => "C1",
    "activo"   => 1,
    "publicar" => 1,

    "unidades" => [
        ["clave" => "lsc-c1-u1", "titulo" => "LSC académica y profesional", "descripcion" => "Exponer, términos técnicos, registro y resúmenes en español escrito.", "icono" => "fa-graduation-cap", "orden" => 1, "lecciones" => [
            ["titulo" => "Exponer un tema", "descripcion" => "Organizar una presentación formal en LSC.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Presentación\nConferencia\nClase"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "En primer lugar\nPor último\nEn resumen"]],
                ["ordenar", "Ordena la frase.", ["frase" => "Hoy les voy a presentar los resultados de la investigación"]],
                ["emparejar", "Une cada parte de la exposición con su función.", ["pares" => "Introducción = Presenta el tema y el objetivo\nDesarrollo = Explica las ideas con ejemplos\nConclusión = Resume y cierra\nPreguntas = Aclara las dudas del público"]],
                ["clasificar", "¿Ayuda o perjudica una exposición en LSC?", ["grupos" => "Ayuda\nPerjudica", "items" => "Ubicar cada idea en un lugar del espacio = Ayuda\nMirar al público = Ayuda\nTener un fondo liso detrás = Ayuda\nVestir ropa con muchos estampados = Perjudica\nPararse frente a una ventana con luz = Perjudica\nTapar las manos con el atril = Perjudica"]],
                ["vf", "Para exponer en LSC, la ropa de un solo color que contraste con la piel ayuda a ver mejor las señas.", ["correcta_vf" => "1", "imagen" => "👕"]],
            ]],
            ["titulo" => "Términos técnicos y señas nuevas", "descripcion" => "Qué hacer cuando un término no tiene seña.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Investigación\nTecnología\nCiencia"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "algoritmo"]],
                ["opcion", "Cuando aparece un término técnico que todavía no tiene seña en LSC, es común…", ["opciones" => "Deletrearlo y acordar una seña con la comunidad sorda\nInventar una seña propia y usarla en público\nSaltárselo", "imagen" => "💡"]],
                ["vf", "Las señas nuevas de la LSC se deben crear y validar con la comunidad sorda.", ["correcta_vf" => "1", "imagen" => "🧏"]],
                ["clasificar", "¿De qué área es cada término?", ["grupos" => "Ciencia\nTecnología\nSalud", "items" => "Célula = Ciencia\nMolécula = Ciencia\nSoftware = Tecnología\nContraseña = Tecnología\nVacuna = Salud\nDiagnóstico = Salud"]],
                ["huecos", "Completa el texto.", ["texto_huecos" => "Si un término no tiene seña, primero se ___ y luego la comunidad sorda puede acordar una ___ para usarla.", "respuestas" => "deletrea\nseña", "extra" => "inventa"]],
            ]],
            ["titulo" => "Registro formal e informal", "descripcion" => "Hablar distinto según la persona y la situación.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Señor\nAmigo\nCompañero"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Con mucho gusto\nQué más\nChao"]],
                ["clasificar", "¿Formal o informal?", ["grupos" => "Formal\nInformal", "items" => "Buenos días, señora directora = Formal\nLe agradezco su atención = Formal\nCon todo respeto = Formal\nQuiubo, parce = Informal\nNos pillamos = Informal\nQué más, ¿todo bien? = Informal"]],
                ["opcion", "En una reunión formal en LSC, las señas suelen ser…", ["opciones" => "Más cuidadas y completas, con un ritmo pausado\nMás rápidas y abreviadas\nIguales que entre amigos", "imagen" => "👔"]],
                ["emparejar", "Une la frase informal con su versión formal.", ["pares" => "¿Qué más? = ¿Cómo está usted?\nChao = Hasta luego, que esté bien\nVen acá = ¿Podría acercarse, por favor?\nNi idea = No tengo esa información"]],
                ["vf", "En LSC, igual que en español, la forma de expresarse cambia según la persona y la situación.", ["correcta_vf" => "1"]],
            ]],
            ["titulo" => "Resumir en español escrito", "descripcion" => "Pasar una exposición en LSC a un texto corto.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Resumen\nTítulo\nPárrafo"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Idea principal\nEjemplo\nDetalle"]],
                ["opcion", "Al pasar una exposición en LSC a un resumen en español escrito, lo más importante es…", ["opciones" => "Mantener el sentido, aunque cambie el orden de las palabras\nCopiar seña por seña en el mismo orden\nEscribir solo los nombres propios", "imagen" => "📝"]],
                ["vf", "La LSC y el español tienen gramáticas distintas; por eso no se traduce palabra por palabra.", ["correcta_vf" => "1"]],
                ["huecos", "Completa el resumen.", ["texto_huecos" => "La expositora explicó que la LSC es una ___ con gramática propia y que fue reconocida en Colombia en ___.", "respuestas" => "lengua\n1996", "extra" => "2005"]],
                ["clasificar", "¿Va en el resumen o sobra?", ["grupos" => "Va en el resumen\nSobra", "items" => "La idea principal = Va en el resumen\nLas conclusiones = Va en el resumen\nLos datos más importantes = Va en el resumen\nEl color de la ropa de la expositora = Sobra\nCada saludo al público = Sobra\nLas pausas para tomar agua = Sobra"]],
            ]],
        ]],
        ["clave" => "lsc-c1-u2", "titulo" => "Interpretación y traducción", "descripcion" => "Modalidades, ética y contextos difíciles.", "icono" => "fa-language", "orden" => 2, "lecciones" => [
            ["titulo" => "Interpretar o traducir", "descripcion" => "Dos trabajos distintos entre la LSC y el español.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Interpretar\nTraducir\nTranscribir"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Mensaje\nIdea\nPalabra"]],
                ["emparejar", "Une cada término con su definición.", ["pares" => "Interpretar = Pasar un mensaje de una lengua a otra en el momento\nTraducir = Pasar un texto o video a otra lengua con tiempo para revisarlo\nGlosa = Escribir las señas con palabras en mayúscula"]],
                ["clasificar", "¿Es interpretación o traducción?", ["grupos" => "Interpretación\nTraducción", "items" => "Una cita médica en vivo = Interpretación\nUna clase en la universidad = Interpretación\nUna rueda de prensa = Interpretación\nUn libro grabado en video en LSC = Traducción\nLos subtítulos de una película = Traducción\nUn documento legal pasado a LSC en video = Traducción"]],
                ["vf", "Saber LSC no basta para ser intérprete: también hace falta formación profesional.", ["correcta_vf" => "1", "imagen" => "🎓"]],
                ["opcion", "En Colombia, para trabajar como intérprete de LSC se recomienda…", ["opciones" => "Formarse y certificarse como intérprete\nHaber visto videos en internet\nTener un familiar sordo, y nada más", "imagen" => "📜"]],
            ]],
            ["titulo" => "Simultánea y consecutiva", "descripcion" => "Interpretar al tiempo o por turnos.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Simultánea\nConsecutiva\nEscrita"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Pausa\nContinuar\nRepetir"]],
                ["emparejar", "Une cada modalidad con su descripción.", ["pares" => "Simultánea = Se interpreta mientras la persona habla\nConsecutiva = Se interpreta cuando la persona hace una pausa\nA la vista = Se interpreta un texto escrito mientras se lee"]],
                ["clasificar", "¿Simultánea o consecutiva?", ["grupos" => "Simultánea\nConsecutiva", "items" => "Un noticiero en vivo = Simultánea\nUna conferencia larga = Simultánea\nUna clase = Simultánea\nUn testimonio en un juicio, frase por frase = Consecutiva\nUna consulta médica con pausas = Consecutiva\nUna declaración corta por turnos = Consecutiva"]],
                ["vf", "En interpretaciones largas, los intérpretes suelen turnarse para no cansarse ni cometer errores.", ["correcta_vf" => "1", "imagen" => "⏱️"]],
                ["completar", "Completa la frase.", ["frase" => "En la interpretación ___, el intérprete trabaja al mismo tiempo que habla la persona.", "respuestas" => "simultánea\nsimultanea"]],
            ]],
            ["titulo" => "Ética del intérprete", "descripcion" => "Fidelidad, imparcialidad y reserva.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Confidencial\nPúblico\nAbierto"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Imparcial\nAmable\nPuntual"]],
                ["emparejar", "Une cada principio con lo que significa.", ["pares" => "Fidelidad = Transmitir el mensaje completo y sin cambios\nImparcialidad = No tomar partido ni opinar\nConfidencialidad = No contar lo que se interpretó\nProfesionalismo = Prepararse y saber cuándo no aceptar un servicio"]],
                ["clasificar", "¿Cumple o no la ética del intérprete?", ["grupos" => "Cumple\nNo cumple", "items" => "Avisar que no conoce un término = Cumple\nRechazar un servicio con conflicto de interés = Cumple\nPrepararse antes con los documentos = Cumple\nContar en redes lo que oyó en una cita = No cumple\nSuavizar un insulto sin avisar = No cumple\nAconsejar a la persona sorda qué decidir = No cumple"]],
                ["opcion", "Si el intérprete es familiar de una de las partes en un juicio, lo correcto es…", ["opciones" => "Que no interprete y se busque otro intérprete\nQue interprete y ayude a su familiar\nQue interprete solo lo que le convenga", "imagen" => "⚖️"]],
                ["vf", "Un buen intérprete puede omitir lo que le parece ofensivo para proteger a las personas.", ["correcta_vf" => "0"]],
            ]],
            ["titulo" => "Contextos difíciles", "descripcion" => "Salud, justicia, educación y emergencias.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Juzgado\nHospital\nUniversidad"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "diagnóstico\ndiagnostico"]],
                ["emparejar", "Une cada contexto con un reto típico.", ["pares" => "Salud = Términos médicos y noticias delicadas\nJusticia = Lenguaje legal y consecuencias graves\nEducación = Muchas horas y temas especializados\nEmergencias = Poco tiempo y mucha tensión"]],
                ["opcion", "Antes de interpretar en un hospital, conviene que el intérprete…", ["opciones" => "Pregunte el motivo de la cita y repase el vocabulario\nLlegue justo a tiempo, sin prepararse\nDecida qué noticias contar", "imagen" => "🏥"]],
                ["vf", "En una audiencia judicial, la persona sorda tiene derecho a un intérprete que no dependa de ninguna de las partes.", ["correcta_vf" => "1", "imagen" => "⚖️"]],
                ["huecos", "Completa el texto.", ["texto_huecos" => "En salud, el intérprete debe ser ___ a lo que dice el médico y guardar ___ sobre la información del paciente.", "respuestas" => "fiel\nreserva", "extra" => "opinión"]],
            ]],
        ]],
        ["clave" => "lsc-c1-u3", "titulo" => "Variación y riqueza de la LSC", "descripcion" => "Regiones, generaciones, contacto con el español y otras lenguas de señas.", "icono" => "fa-earth-americas", "orden" => 3, "lecciones" => [
            ["titulo" => "Variación regional", "descripcion" => "Una misma idea, señas distintas según la región.", "ej" => [
                ["sena", "Mira esta variante regional. ¿Qué significa?", ["opciones" => "Arepa\nPan\nTamal"]],
                ["sena", "Mira esta variante regional. ¿Qué significa?", ["opciones" => "Mamá\nTía\nAbuela"]],
                ["vf", "En la LSC hay señas que cambian según la región del país, como pasa con las palabras del español.", ["correcta_vf" => "1", "imagen" => "🗺️"]],
                ["opcion", "Si una persona sorda de otra región usa una seña que no conoces, lo mejor es…", ["opciones" => "Preguntarle qué significa y aprender la variante\nDecirle que su seña está mal\nCambiar de tema", "imagen" => "🤝"]],
                ["huecos", "Completa el texto.", ["texto_huecos" => "Como el español, la LSC tiene ___ regionales: una misma idea puede tener señas distintas en Bogotá y en la ___.", "respuestas" => "variantes\ncosta", "extra" => "errores"]],
                ["completar", "Completa la frase.", ["frase" => "Las diferencias de una lengua según el lugar se llaman variación ___.", "respuestas" => "regional\ngeográfica\ngeografica\ndiatópica\ndiatopica"]],
            ]],
            ["titulo" => "Generaciones y lenguaje juvenil", "descripcion" => "Señas nuevas, jerga y señas que se dejan de usar.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Chévere\nAburrido\nRaro"]],
                ["sena", "Mira esta seña de los jóvenes. ¿Qué significa?", ["opciones" => "Genial\nTriste\nCansado"]],
                ["vf", "Los jóvenes sordos también crean señas y expresiones propias, como en cualquier lengua viva.", ["correcta_vf" => "1", "imagen" => "🧑"]],
                ["clasificar", "¿Influye en que las señas cambien con el tiempo?", ["grupos" => "Influye\nNo influye", "items" => "Las redes sociales = Influye\nLa tecnología nueva = Influye\nEl contacto entre colegios = Influye\nEl color de los ojos = No influye\nLa estatura = No influye\nEl signo zodiacal = No influye"]],
                ["opcion", "Que personas mayores y jóvenes usen señas distintas para lo mismo muestra que la LSC…", ["opciones" => "Es una lengua viva que cambia\nEstá mal aprendida\nNo tiene reglas", "imagen" => "🔄"]],
                ["emparejar", "Une cada término con su significado.", ["pares" => "Variación generacional = Diferencias entre personas de distintas edades\nNeologismo = Seña o palabra nueva\nArcaísmo = Seña o palabra que ya casi no se usa\nJerga = Lenguaje propio de un grupo"]],
            ]],
            ["titulo" => "Contacto con el español", "descripcion" => "Deletreo, inicialización y préstamos.", "ej" => [
                ["deletreo", "Mira el deletreo rápido. ¿Qué palabra es?", ["respuestas" => "bus"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Familia\nGrupo\nEquipo"]],
                ["opcion", "La «inicialización» es…", ["opciones" => "Usar en la seña la letra inicial de la palabra en español\nDeletrear todas las letras despacio\nEscribir la palabra en un papel"]],
                ["vf", "El deletreo manual es una forma de contacto entre la LSC y el español escrito.", ["correcta_vf" => "1", "imagen" => "🔤"]],
                ["emparejar", "Une cada fenómeno con su ejemplo.", ["pares" => "Deletreo = Escribir un nombre propio con el alfabeto manual\nInicialización = Usar la letra inicial en la seña\nLabialización = Mover los labios como en español mientras se seña\nPréstamo = Tomar una palabra de otra lengua"]],
                ["huecos", "Completa el texto.", ["texto_huecos" => "Cuando una lengua toma palabras de otra hace un ___. En la LSC, el ___ manual permite usar nombres del español.", "respuestas" => "préstamo\ndeletreo", "extra" => "error"]],
            ]],
            ["titulo" => "Lenguas de señas del mundo", "descripcion" => "Cada país tiene la suya, y existen las señas internacionales.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Señas internacionales\nLengua de señas americana\nLengua de señas colombiana"]],
                ["deletreo", "Mira el deletreo. ¿Qué sigla es?", ["respuestas" => "asl"]],
                ["emparejar", "Une cada lengua de señas con su país.", ["pares" => "LSC = Colombia\nASL = Estados Unidos\nLSE = España\nLSM = México\nLibras = Brasil"]],
                ["vf", "Hay una sola lengua de señas universal que entienden todas las personas sordas del mundo.", ["correcta_vf" => "0", "imagen" => "🌍"]],
                ["opcion", "Las «señas internacionales» se usan sobre todo…", ["opciones" => "En encuentros internacionales de personas sordas, como congresos o competencias deportivas\nEn los colegios de Colombia\nPara escribir en el celular", "imagen" => "🌐"]],
                ["completar", "Completa la frase.", ["frase" => "La lengua de señas de Brasil se llama ___.", "respuestas" => "Libras"]],
            ]],
        ]],
        ["clave" => "lsc-c1-u4", "titulo" => "Identidad, historia y futuro", "descripcion" => "La identidad sorda, el Congreso de Milán, el movimiento asociativo y el repaso del nivel.", "icono" => "fa-landmark", "orden" => 4, "lecciones" => [
            ["titulo" => "La identidad sorda", "descripcion" => "La visión cultural y la visión médica.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Identidad\nOrgullo\nComunidad"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Sordo\nOyente\nHipoacúsico"]],
                ["clasificar", "¿Visión cultural o visión médica de la sordera?", ["grupos" => "Visión cultural\nVisión médica", "items" => "La comunidad sorda tiene lengua y cultura propias = Visión cultural\nLa LSC es la lengua natural de muchas personas sordas = Visión cultural\nSer sordo es parte de la identidad = Visión cultural\nLa sordera es solo algo que hay que curar = Visión médica\nLo único importante es que la persona oiga y hable = Visión médica\nLa persona sorda es ante todo un paciente = Visión médica"]],
                ["vf", "Muchas personas sordas prefieren que se les diga «sordo» y no «sordomudo».", ["correcta_vf" => "1", "imagen" => "🧏"]],
                ["opcion", "La palabra «sordomudo» se evita porque…", ["opciones" => "Las personas sordas se comunican y muchas también hablan: no son mudas\nEs muy larga\nEs una palabra en inglés", "imagen" => "🚫"]],
                ["emparejar", "Une cada término con su significado.", ["pares" => "CODA = Hijo oyente de padres sordos\nOyente = Persona que oye\nSordociego = Persona con pérdida de audición y de visión\nUsuario de LSC = Persona que se comunica en LSC"]],
            ]],
            ["titulo" => "El Congreso de Milán", "descripcion" => "1880, el oralismo y sus consecuencias.", "ej" => [
                ["opcion", "En el Congreso de Milán de 1880 se decidió…", ["opciones" => "Imponer el oralismo y sacar las lenguas de señas de las escuelas\nCrear la primera lengua de señas\nReconocer la LSC", "imagen" => "📜"]],
                ["vf", "Después del Congreso de Milán, en muchos países se prohibió la lengua de señas en las escuelas.", ["correcta_vf" => "1"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Historia\nFuturo\nPresente"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Prohibir\nPermitir\nObligar"]],
                ["emparejar", "Une cada enfoque educativo con lo que propone.", ["pares" => "Oralismo = Enseñar solo con habla y lectura de labios\nBilingüismo = Enseñar en lengua de señas y lengua escrita\nComunicación total = Usar todos los medios: señas, habla y escritura"]],
                ["completar", "Completa la frase.", ["frase" => "El Congreso de Milán se realizó en el año ___.", "respuestas" => "1880"]],
            ]],
            ["titulo" => "El movimiento asociativo en Colombia", "descripcion" => "Asociaciones, FENASCOL y sus logros.", "ej" => [
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Asociación\nFederación\nClub"]],
                ["deletreo", "Mira el deletreo. ¿Qué sigla es?", ["respuestas" => "fenascol"]],
                ["vf", "En Colombia hay asociaciones de personas sordas en muchas ciudades, agrupadas en FENASCOL.", ["correcta_vf" => "1", "imagen" => "🧏"]],
                ["opcion", "Una de las funciones de las asociaciones de sordos es…", ["opciones" => "Defender los derechos y promover la LSC y la cultura sorda\nVender audífonos\nReemplazar a los colegios", "imagen" => "🤝"]],
                ["clasificar", "¿Es un logro del movimiento sordo en Colombia?", ["grupos" => "Logro\nNo tiene relación", "items" => "El reconocimiento legal de la LSC = Logro\nLos intérpretes en la televisión = Logro\nEl Centro de Relevo = Logro\nLa invención del teléfono = No tiene relación\nEl horario de los bancos = No tiene relación\nEl precio del café = No tiene relación"]],
                ["huecos", "Completa el texto.", ["texto_huecos" => "Las asociaciones de sordos ___ los derechos de la comunidad y promueven la ___ como lengua.", "respuestas" => "defienden\nLSC", "extra" => "prohíben"]],
            ]],
            ["titulo" => "Repaso del C1 completo", "descripcion" => "Todo lo del nivel, mezclado.", "ej" => [
                ["clasificar", "Clasifica los términos.", ["grupos" => "Interpretación\nVariación\nHistoria", "items" => "Fidelidad = Interpretación\nConsecutiva = Interpretación\nVariante regional = Variación\nNeologismo = Variación\nCongreso de Milán = Historia\nOralismo = Historia"]],
                ["emparejar", "Une cada idea con su nombre.", ["pares" => "Interpretar mientras se habla = Interpretación simultánea\nUsar la letra inicial en la seña = Inicialización\nEnseñar solo con habla = Oralismo\nNo contar lo interpretado = Confidencialidad"]],
                ["sena", "Mira la seña. ¿Qué significa?", ["opciones" => "Lengua viva\nLengua muerta\nLengua escrita"]],
                ["deletreo", "Mira el deletreo. ¿Qué palabra es?", ["respuestas" => "identidad"]],
                ["huecos", "Completa el párrafo.", ["texto_huecos" => "La LSC es una lengua ___ que cambia con el tiempo y varía según la ___. Por eso las señas nuevas se acuerdan con la ___ sorda.", "respuestas" => "viva\nregión\ncomunidad", "extra" => "muerta"]],
                ["vf", "Un intérprete profesional transmite todo el mensaje, sin agregar ni quitar nada.", ["correcta_vf" => "1"]],
            ]],
        ]],
    ],
];
