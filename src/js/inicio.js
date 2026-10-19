/**
 * BiGlobal · Página principal
 *
 * Tres cosas:
 *   1. La lección de ejemplo del hero (preguntas de muestra; no guarda nada).
 *   2. El boletín del footer (todavía no guarda correos: lo decimos claro).
 *   3. Cerrar el menú del celular al tocar un enlace.
 *
 * El progreso real vive en la base de datos y se ve en el panel del
 * estudiante. Aquí todo es de muestra y se pierde al recargar.
 */

/* ---------- Preguntas de la lección de ejemplo ----------
   Para cambiar una pregunta, edita este objeto. "correcta" es la posición
   (empezando en 0) de la opción buena dentro de "opciones".
   "lang" marca el idioma del texto para los lectores de pantalla. */
const LECCIONES = {
    en: {
        tema: "Saludos",
        preguntas: [
            { pregunta: "¿Cómo se dice «Buenos días» en inglés?", lang: "es",
              opciones: ["Good night", "Good morning", "Goodbye"], langOpciones: "en", correcta: 1 },
            { pregunta: "¿Qué significa «Thank you»?", lang: "es",
              opciones: ["Por favor", "De nada", "Gracias"], langOpciones: "es", correcta: 2 },
            { pregunta: "Completa la frase: «Nice to ___ you»", lang: "es",
              opciones: ["meet", "eat", "sit"], langOpciones: "en", correcta: 0 },
        ],
    },
    es: {
        tema: "Greetings",
        preguntas: [
            { pregunta: "How do you say “Thank you” in Spanish?", lang: "en",
              opciones: ["Hola", "Gracias", "Adiós"], langOpciones: "es", correcta: 1 },
            { pregunta: "What does «¿Cómo estás?» mean?", lang: "en",
              opciones: ["Where are you?", "Who are you?", "How are you?"], langOpciones: "en", correcta: 2 },
            { pregunta: "Complete: «Me ___ Ana»", lang: "en",
              opciones: ["llamo", "llama", "llamas"], langOpciones: "es", correcta: 0 },
        ],
    },
    lsc: null, // el curso de LSC todavía no existe
};

const XP_POR_ACIERTO = 10;

document.addEventListener("DOMContentLoaded", () => {

    /* ---------- 1. Lección de ejemplo ---------- */
    const leccion = document.querySelector("[data-leccion]");

    if (leccion) {
        const pestanas = [...leccion.querySelectorAll("[role='tab']")];
        const panel = leccion.querySelector("[role='tabpanel']");
        const cuerpo = leccion.querySelector("[data-cuerpo]");
        const filaEstado = leccion.querySelector(".leccion__estado");
        const barra = leccion.querySelector("[role='progressbar']");
        const relleno = leccion.querySelector("[data-relleno]");
        const xpEtiqueta = leccion.querySelector("[data-xp]");
        const rachaEtiqueta = leccion.querySelector("[data-racha]");
        const mensaje = leccion.querySelector("[data-mensaje]");
        const botonSiguiente = leccion.querySelector("[data-siguiente]");

        const RACHA_INICIAL = 3;
        const estado = { idioma: "en", indice: 0, xp: 0, aciertos: 0 };

        // Crea un elemento con clase y texto (sin innerHTML: más seguro)
        const crear = (etiqueta, clase, texto) => {
            const el = document.createElement(etiqueta);
            if (clase) el.className = clase;
            if (texto) el.textContent = texto;
            return el;
        };

        const crearIcono = (clases) => {
            const i = document.createElement("i");
            i.className = clases;
            i.setAttribute("aria-hidden", "true");
            return i;
        };

        const ponerAvance = (hechas, total) => {
            // Un pedacito mínimo para que la barra no se vea vacía
            relleno.style.transform = `scaleX(${Math.max(hechas / total, 0.04)})`;
            barra.setAttribute("aria-valuenow", String(hechas));
            barra.setAttribute("aria-valuemax", String(total));
        };

        const ponerXp = (animar) => {
            xpEtiqueta.textContent = `${estado.xp} XP`;
            if (animar) {
                xpEtiqueta.classList.remove("sube");
                void xpEtiqueta.offsetWidth; // reinicia la animación
                xpEtiqueta.classList.add("sube");
            }
        };

        const animarEntrada = () => {
            cuerpo.classList.remove("entra");
            void cuerpo.offsetWidth;
            cuerpo.classList.add("entra");
        };

        const limpiarPie = () => {
            mensaje.textContent = "";
            mensaje.className = "leccion__mensaje";
            botonSiguiente.hidden = true;
        };

        const mostrarPregunta = () => {
            const datos = LECCIONES[estado.idioma];
            const actual = datos.preguntas[estado.indice];
            const total = datos.preguntas.length;

            cuerpo.replaceChildren();
            cuerpo.append(crear("p", "leccion__tema", `${datos.tema} · Pregunta ${estado.indice + 1} de ${total}`));

            const pregunta = crear("p", "leccion__pregunta", actual.pregunta);
            pregunta.lang = actual.lang;
            pregunta.tabIndex = -1;
            cuerpo.append(pregunta);

            const opciones = crear("div", "leccion__opciones");
            actual.opciones.forEach((texto, posicion) => {
                const boton = crear("button", "opcion");
                boton.type = "button";
                boton.lang = actual.langOpciones;
                boton.append(crear("span", "", texto));
                boton.addEventListener("click", () => responder(posicion));
                opciones.append(boton);
            });
            cuerpo.append(opciones);

            limpiarPie();
            ponerAvance(estado.indice, total);
            animarEntrada();
        };

        const responder = (elegida) => {
            const datos = LECCIONES[estado.idioma];
            const actual = datos.preguntas[estado.indice];
            const botones = [...cuerpo.querySelectorAll(".opcion")];
            const acerto = elegida === actual.correcta;

            botones.forEach((boton, posicion) => {
                boton.disabled = true;
                if (posicion === actual.correcta) {
                    boton.classList.add("es-correcta");
                    boton.append(crearIcono("fa-solid fa-circle-check opcion__icono"));
                } else if (posicion === elegida) {
                    boton.classList.add("es-incorrecta");
                    boton.append(crearIcono("fa-solid fa-circle-xmark opcion__icono"));
                } else {
                    boton.classList.add("es-apagada");
                }
            });

            if (acerto) {
                estado.xp += XP_POR_ACIERTO;
                estado.aciertos += 1;
                ponerXp(true);
                mensaje.textContent = `¡Correcto! +${XP_POR_ACIERTO} XP`;
                mensaje.className = "leccion__mensaje es-bien";
            } else {
                mensaje.textContent = `Casi. La respuesta es «${actual.opciones[actual.correcta]}».`;
                mensaje.className = "leccion__mensaje es-mal";
            }

            ponerAvance(estado.indice + 1, datos.preguntas.length);

            const esUltima = estado.indice === datos.preguntas.length - 1;
            botonSiguiente.firstChild.textContent = esUltima ? "Ver resultado " : "Siguiente ";
            botonSiguiente.hidden = false;
            botonSiguiente.focus();
        };

        const mostrarFinal = () => {
            const total = LECCIONES[estado.idioma].preguntas.length;

            // Terminar la lección mantiene viva la racha (igual que en el panel real)
            rachaEtiqueta.textContent = String(RACHA_INICIAL + 1);

            const final = crear("div", "leccion__final");
            const icono = crear("span", "leccion__final-icono leccion__final-icono--logro");
            icono.append(crearIcono("fa-solid fa-trophy"));
            const titulo = crear("p", "leccion__pregunta", "¡Lección completada!");
            titulo.tabIndex = -1;
            const resumen = crear("p", "",
                `Acertaste ${estado.aciertos} de ${total} y ganaste ${estado.xp} XP. ` +
                "Crea tu cuenta para guardar tu avance y seguir con la lección 2.");

            const acciones = crear("div", "hero__acciones");
            acciones.style.marginTop = "0.5rem";
            const registro = crear("a", "btn-principal", "Crear cuenta gratis ");
            registro.href = "auth/login.html#registro";
            registro.append(crearIcono("fa-solid fa-arrow-right"));
            const repetir = crear("button", "btn-secundario", "Repetir");
            repetir.type = "button";
            repetir.addEventListener("click", () => reiniciar(estado.idioma));
            acciones.append(registro, repetir);

            final.append(icono, titulo, resumen, acciones);
            cuerpo.replaceChildren(final);
            limpiarPie();
            animarEntrada();
            titulo.focus();
        };

        const mostrarLsc = () => {
            const final = crear("div", "leccion__final");
            const icono = crear("span", "leccion__final-icono");
            icono.append(crearIcono("fa-solid fa-hands-asl-interpreting"));
            const titulo = crear("p", "leccion__pregunta", "El curso de LSC viene en camino");
            const texto = crear("p", "",
                "Estamos preparando las lecciones de Lengua de Señas Colombiana. " +
                "Crea tu cuenta gratis y te avisamos cuando abra.");
            const registro = crear("a", "btn-principal", "Crear cuenta ");
            registro.href = "auth/login.html#registro";
            registro.append(crearIcono("fa-solid fa-arrow-right"));

            final.append(icono, titulo, texto, registro);
            cuerpo.replaceChildren(final);
            limpiarPie();
            animarEntrada();
        };

        const reiniciar = (idioma) => {
            estado.idioma = idioma;
            estado.indice = 0;
            estado.xp = 0;
            estado.aciertos = 0;
            ponerXp(false);
            rachaEtiqueta.textContent = String(RACHA_INICIAL);

            // Sin lección de LSC no hay avance, racha ni XP que mostrar
            filaEstado.hidden = !LECCIONES[idioma];

            if (LECCIONES[idioma]) {
                mostrarPregunta();
            } else {
                mostrarLsc();
            }
        };

        botonSiguiente.addEventListener("click", () => {
            const total = LECCIONES[estado.idioma].preguntas.length;
            if (estado.indice < total - 1) {
                estado.indice += 1;
                mostrarPregunta();
                cuerpo.querySelector(".leccion__pregunta").focus();
            } else {
                mostrarFinal();
            }
        });

        /* Pestañas: clic o flechas del teclado (patrón ARIA de pestañas) */
        const elegirPestana = (pestana) => {
            pestanas.forEach((p) => {
                const activa = p === pestana;
                p.setAttribute("aria-selected", String(activa));
                p.tabIndex = activa ? 0 : -1;
            });
            panel.setAttribute("aria-labelledby", pestana.id);
            reiniciar(pestana.dataset.idioma);
        };

        pestanas.forEach((pestana, posicion) => {
            pestana.addEventListener("click", () => {
                if (pestana.getAttribute("aria-selected") !== "true") elegirPestana(pestana);
            });

            pestana.addEventListener("keydown", (evento) => {
                let destino = null;
                if (evento.key === "ArrowRight") destino = pestanas[(posicion + 1) % pestanas.length];
                if (evento.key === "ArrowLeft")  destino = pestanas[(posicion - 1 + pestanas.length) % pestanas.length];
                if (evento.key === "Home") destino = pestanas[0];
                if (evento.key === "End")  destino = pestanas[pestanas.length - 1];
                if (!destino) return;
                evento.preventDefault();
                destino.focus();
                elegirPestana(destino);
            });
        });

        // Arranca con la primera pregunta de inglés (reemplaza la versión sin JS)
        reiniciar("en");
    }

    /* ---------- 2. Boletín ---------- */
    const formBoletin = document.getElementById("form-boletin");
    const campoBoletin = document.getElementById("campo-boletin");
    const mensajeBoletin = document.getElementById("mensaje-boletin");

    if (formBoletin && campoBoletin && mensajeBoletin) {
        formBoletin.addEventListener("submit", (evento) => {
            evento.preventDefault();

            if (!campoBoletin.checkValidity() || campoBoletin.value.trim() === "") {
                mensajeBoletin.className = "mensaje-boletin es-error";
                mensajeBoletin.textContent = "Escribe un correo válido, por ejemplo nombre@correo.com.";
                campoBoletin.setAttribute("aria-invalid", "true");
                campoBoletin.focus();
                return;
            }

            // TODO (siguiente versión): enviar a un endpoint PHP que guarde
            // el correo en una tabla "boletin_suscriptores".
            campoBoletin.removeAttribute("aria-invalid");
            mensajeBoletin.className = "mensaje-boletin es-info";
            mensajeBoletin.textContent = "¡Gracias! El boletín abre muy pronto. Mientras tanto, crea tu cuenta gratis para empezar a aprender.";
        });
    }

    /* ---------- 3. Menú de celular ---------- */
    const menu = document.getElementById("menuPrincipal");

    if (menu && window.bootstrap) {
        menu.querySelectorAll(".nav-link").forEach((enlace) => {
            enlace.addEventListener("click", () => {
                if (menu.classList.contains("show")) {
                    window.bootstrap.Collapse.getOrCreateInstance(menu).hide();
                }
            });
        });
    }
});
