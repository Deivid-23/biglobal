/**
 * BiGlobal · Reproductor de lecciones de la ruta
 *
 * - Muestra un ejercicio a la vez. Los datos llegan SIN la respuesta.
 * - "Comprobar" envía la respuesta a ruta_responder.php, que califica en el
 *   servidor y dice cuál ejercicio sigue (los fallados vuelven al final).
 * - Todo el texto se pone con textContent: nada de HTML desde los datos.
 * - data-modo="ubicacion": examen de ubicación. Envía a data-endpoint con
 *   idioma_id y al final muestra el nivel donde quedó.
 * - data-modo="repaso": repaso de errores. Igual, y al final muestra cuántos
 *   resolvió y cuántos le quedan.
 * - data-modo="contrarreloj": práctica rápida. El reloj (data-reloj) cuenta
 *   hacia atrás; cada respuesta pasa sola a la siguiente; al llegar a 0 se
 *   pide "terminar" al servidor, que es quien lleva el tiempo de verdad.
 * - Combo: con 3, 5, 10… aciertos seguidos aparece «¡3 seguidas! 🔥». Al final,
 *   confeti (más si fue perfecta o si cumplió la meta del día). Son solo
 *   visuales: no cambian el XP. Con "reducir movimiento" no hay confeti.
 * - Accesibilidad: los resultados y los cambios (palabra movida, carta
 *   volteada, pareja hecha) se anuncian en una región oculta (anunciar);
 *   el foco nunca se pierde al mover fichas; los grupos de opciones se
 *   recorren con las flechas.
 * - «No puedo escuchar ahora» (escuchar y dictado): salta el ejercicio sin
 *   contarlo como error (lo valida el servidor con saltar=1).
 */
(function () {
    "use strict";

    const raiz = document.querySelector("[data-ruta]");
    if (!raiz) return;

    const ejercicios = JSON.parse(raiz.dataset.ejercicios || "[]");
    const porId = {};
    ejercicios.forEach((e) => { porId[e.id] = e; });

    const $ = (sel) => raiz.querySelector(sel);
    const caja = $("[data-ejercicio]");
    const pie = $("[data-pie]");
    const boton = $("[data-comprobar]");
    const retro = $("[data-retro]");
    const barra = $("[data-progreso]");
    const csrf = document.documentElement.dataset.csrf || "";
    const esExamen = raiz.dataset.modo === "ubicacion";
    const esRepaso = raiz.dataset.modo === "repaso";
    const esContrarreloj = raiz.dataset.modo === "contrarreloj";
    let reloj = null;              // intervalo del contrarreloj
    let terminado = false;         // contrarreloj: ya se mostró el final
    const endpoint = raiz.dataset.endpoint || "ruta_responder.php";

    let actual = ejercicios.length ? ejercicios[0].id : null;
    let obtenerRespuesta = null;   // función que devuelve la respuesta o null si falta
    let modo = "comprobar";        // "comprobar" | "continuar" | "enviando"
    let siguiente = null;
    let resultado = null;
    let seguidas = 0;              // aciertos seguidos (combo)
    const SIN_AUDIO = ["escuchar", "dictado"];   // se pueden saltar con «No puedo escuchar ahora»
    const sinMovimiento = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* ---------- Utilidades ---------- */
    function crear(etiqueta, clase, texto) {
        const el = document.createElement(etiqueta);
        if (clase) el.className = clase;
        if (texto !== undefined) el.textContent = texto;
        return el;
    }

    // Región oculta para los lectores de pantalla (lo que solo se ve o cambia de lugar)
    const anuncios = crear("p", "sr-only");
    anuncios.setAttribute("role", "status");
    anuncios.setAttribute("aria-live", "polite");
    raiz.appendChild(anuncios);
    function anunciar(texto) {
        anuncios.textContent = "";
        setTimeout(() => { anuncios.textContent = texto; }, 60);   // vaciar primero: así se repite si es igual
    }

    /**
     * Grupo de radios accesible: solo el elegido (o el primero) entra con Tab
     * y las flechas, Inicio y Fin pasan de uno a otro y lo eligen.
     */
    function radios(grupo, botones) {
        const marcar = (b) => botones.forEach((x) => { x.tabIndex = x === b ? 0 : -1; });
        marcar(botones[0]);
        botones.forEach((b, i) => {
            b.addEventListener("click", () => marcar(b));
            b.addEventListener("keydown", (ev) => {
                const ir = { ArrowRight: i + 1, ArrowDown: i + 1, ArrowLeft: i - 1, ArrowUp: i - 1, Home: 0, End: botones.length - 1 }[ev.key];
                if (ir === undefined) return;
                ev.preventDefault();
                const destino = botones[(ir + botones.length) % botones.length];
                destino.focus();
                destino.click();
            });
        });
    }

    function listo() {
        boton.disabled = modo === "comprobar" && (!obtenerRespuesta || obtenerRespuesta() === null);
    }

    function avance(valor) {
        const relleno = barra.querySelector(".progreso__relleno");
        relleno.style.width = valor + "%";
        relleno.dataset.valor = String(valor);
        barra.setAttribute("aria-valuenow", String(valor));
    }

    /** Botones de opción (uno elegido a la vez). */
    function opciones(lista, alElegir) {
        const grupo = crear("div", "ruta-opciones");
        grupo.setAttribute("role", "radiogroup");
        grupo.setAttribute("aria-labelledby", "ruta-enunciado");
        let elegido = null;
        const botones = lista.map((texto, i) => {
            const b = crear("button", "ruta-opcion", texto);
            b.type = "button";
            b.setAttribute("role", "radio");
            b.setAttribute("aria-checked", "false");
            const numero = crear("span", "ruta-opcion__tecla", String(i + 1));
            numero.setAttribute("aria-hidden", "true");
            b.prepend(numero);
            b.addEventListener("click", () => {
                grupo.querySelectorAll(".ruta-opcion").forEach((x) => x.setAttribute("aria-checked", "false"));
                b.setAttribute("aria-checked", "true");
                elegido = texto;
                alElegir(texto);
                listo();
            });
            grupo.appendChild(b);
            return b;
        });
        radios(grupo, botones);
        return { el: grupo, valor: () => elegido };
    }

    /** Botón para oír la frase (y uno para oírla más despacio) con la voz del navegador. */
    function botonesDeVoz(e) {
        const fila = crear("div", "ruta-escuchar");
        const hayVoz = "speechSynthesis" in window && typeof SpeechSynthesisUtterance !== "undefined";
        const decir = (lento) => {
            window.speechSynthesis.cancel();
            const u = new SpeechSynthesisUtterance(e.texto);
            u.lang = e.voz || "es-CO";
            u.rate = lento ? 0.6 : 0.9;
            window.speechSynthesis.speak(u);
        };
        if (hayVoz) {
            const play = crear("button", "boton boton--primario ruta-escuchar__boton");
            play.type = "button";
            play.setAttribute("aria-label", "Escuchar");
            play.appendChild(crear("i", "fa-solid fa-volume-high"));
            play.addEventListener("click", () => decir(false));
            const lento = crear("button", "boton boton--secundario boton--sm", " Más lento");
            lento.type = "button";
            lento.prepend(crear("i", "fa-solid fa-person-walking"));
            lento.addEventListener("click", () => decir(true));
            fila.append(play, lento);
            setTimeout(() => decir(false), 300);
        } else {
            // Sin voz en el navegador: se muestra el texto
            fila.appendChild(crear("p", "ruta-escuchar__texto", "«" + e.texto + "»"));
        }
        return fila;
    }

    /**
     * Caja para escribir una frase completa. Recibe el foco, así que lleva
     * como descripción el enunciado (y lo que se traduce, si hay).
     */
    function campoDeTexto(cuerpo, etiqueta, describe = []) {
        const entrada = crear("textarea", "entrada ruta-escribir");
        entrada.rows = 2;
        entrada.maxLength = 300;
        entrada.autocomplete = "off";
        entrada.spellcheck = false;
        entrada.setAttribute("autocapitalize", "off");
        entrada.setAttribute("aria-label", etiqueta);
        entrada.setAttribute("aria-describedby", ["ruta-enunciado", ...describe].join(" "));
        entrada.placeholder = etiqueta + "…";
        entrada.addEventListener("input", listo);
        // Enter comprueba (sin saltos de línea)
        entrada.addEventListener("keydown", (ev) => {
            if (ev.key === "Enter") {
                ev.preventDefault();
                ev.stopPropagation();
                if (!boton.disabled) boton.click();
            }
        });
        cuerpo.appendChild(entrada);
        setTimeout(() => entrada.focus(), 50);
        return () => (entrada.value.trim() === "" ? null : entrada.value.replace(/\s+/g, " "));
    }

    /** Dibujo grande (un emoji) arriba de las opciones. */
    function dibujo(e, cuerpo) {
        if (!e.imagen) return;
        const d = crear("p", "ruta-dibujo", e.imagen);
        d.setAttribute("role", "img");
        d.setAttribute("aria-label", "Dibujo: " + e.imagen);
        cuerpo.appendChild(d);
    }

    /** Video pequeño (la seña) para una opción o una carta. */
    function videoMini(src, titulo) {
        const marco = crear("span", "ruta-video-mini");
        const iframe = document.createElement("iframe");
        iframe.src = src;
        iframe.title = titulo;
        iframe.loading = "lazy";
        iframe.referrerPolicy = "strict-origin-when-cross-origin";
        iframe.allow = "encrypted-media; picture-in-picture; fullscreen";
        iframe.allowFullscreen = true;
        marco.appendChild(iframe);
        return marco;
    }

    /* ---------- Cada tipo de ejercicio ---------- */
    const tipos = {
        opcion(e, cuerpo) {
            dibujo(e, cuerpo);
            const o = opciones(e.opciones, () => {});
            cuerpo.appendChild(o.el);
            return o.valor;
        },

        /** Elige la imagen o la seña: tarjetas con emoji, palabra o video. */
        elegir(e, cuerpo) {
            const grupo = crear("div", "ruta-elegir");
            grupo.setAttribute("role", "radiogroup");
            grupo.setAttribute("aria-labelledby", "ruta-enunciado");
            let elegido = null;
            const botones = e.opciones.map((o, i) => {
                const tarjeta = crear("div", "ruta-elegir__opcion");
                tarjeta.dataset.valor = o.valor;
                if (o.video) {
                    tarjeta.appendChild(videoMini(o.video, "Seña " + (i + 1)));
                } else {
                    tarjeta.appendChild(crear("span", "ruta-elegir__dibujo", o.valor));
                }
                const b = crear("button", "boton boton--secundario boton--sm ruta-elegir__boton", o.video ? "Elegir la seña " + (i + 1) : "Elegir");
                b.type = "button";
                b.setAttribute("role", "radio");
                b.setAttribute("aria-checked", "false");
                if (!o.video) b.setAttribute("aria-label", "Elegir " + o.valor);
                b.addEventListener("click", () => {
                    grupo.querySelectorAll(".ruta-elegir__opcion").forEach((x) => x.classList.remove("es-elegida"));
                    grupo.querySelectorAll(".ruta-elegir__boton").forEach((x) => x.setAttribute("aria-checked", "false"));
                    tarjeta.classList.add("es-elegida");
                    b.setAttribute("aria-checked", "true");
                    elegido = o.valor;
                    listo();
                });
                // Tocar el dibujo también elige (en los videos no: tocarlos los reproduce)
                if (!o.video) tarjeta.addEventListener("click", (ev) => { if (ev.target !== b) b.click(); });
                tarjeta.appendChild(b);
                grupo.appendChild(tarjeta);
                return b;
            });
            radios(grupo, botones);
            cuerpo.appendChild(grupo);
            return () => elegido;
        },

        /** Memoria: voltear dos cartas; si son pareja se quedan boca arriba. */
        memoria(e, cuerpo) {
            const tablero = crear("div", "ruta-memoria");
            tablero.setAttribute("role", "group");
            tablero.setAttribute("aria-labelledby", "ruta-enunciado");
            const estado = crear("p", "ruta-memoria__estado", "");
            const encontradas = [];
            let abiertas = [];
            let intentos = 0;
            let ocupado = false;
            const total = e.cartas.length / 2;
            const contar = () => { estado.textContent = "Parejas: " + encontradas.length + " de " + total + " · Intentos: " + intentos; };
            const nombre = (c, i) => "Carta " + (i + 1) + ": " + (c.video ? "una seña en video" : c.valor);
            const mostrarCara = (carta, c, i) => {
                const cara = carta.querySelector(".ruta-memoria__cara");
                cara.replaceChildren(
                    c.video ? videoMini(c.video, "Seña de la carta " + (i + 1)) : crear("span", "ruta-memoria__texto" + (/\p{Extended_Pictographic}/u.test(c.valor) && Array.from(c.valor).length <= 4 ? " ruta-memoria__texto--dibujo" : ""), c.valor)
                );
                cara.setAttribute("aria-label", nombre(c, i));
            };
            e.cartas.forEach((c, i) => {
                const carta = crear("div", "ruta-memoria__carta");
                const dorso = crear("button", "ruta-memoria__dorso");
                dorso.type = "button";
                dorso.setAttribute("aria-label", "Carta " + (i + 1) + ", boca abajo");
                dorso.appendChild(crear("i", "fa-solid fa-question"));
                // La cara recibe el foco al voltear (el dorso se oculta): así el teclado no se pierde
                const cara = crear("div", "ruta-memoria__cara");
                cara.setAttribute("role", "group");
                cara.tabIndex = -1;
                carta.append(dorso, cara);
                dorso.addEventListener("click", () => {
                    if (ocupado || carta.classList.contains("es-abierta")) return;
                    carta.classList.add("es-abierta");
                    mostrarCara(carta, c, i);
                    cara.focus();
                    abiertas.push({ carta, c, i });
                    if (abiertas.length < 2) {
                        anunciar(nombre(c, i) + ".");
                        return;
                    }
                    intentos++;
                    const [a, b] = abiertas;
                    abiertas = [];
                    if (a.c.par === b.c.par) {
                        a.carta.classList.add("es-pareja");
                        b.carta.classList.add("es-pareja");
                        [a, b].forEach((x) => x.carta.querySelector(".ruta-memoria__cara").setAttribute("aria-label", nombre(x.c, x.i) + ", pareja encontrada"));
                        encontradas.push(a.c.par);
                        contar();
                        anunciar(nombre(c, i) + ". ¡Pareja! Llevas " + encontradas.length + " de " + total + ".");
                        listo();
                        return;
                    }
                    ocupado = true;
                    contar();
                    anunciar(nombre(c, i) + ". No son pareja: se voltean otra vez.");
                    // Si no son pareja, se voltean otra vez (más tiempo si hay videos para verlos)
                    setTimeout(() => {
                        [a, b].forEach((x) => {
                            const caraX = x.carta.querySelector(".ruta-memoria__cara");
                            const teniaFoco = document.activeElement === caraX;
                            x.carta.classList.remove("es-abierta");
                            caraX.replaceChildren();
                            caraX.removeAttribute("aria-label");
                            if (teniaFoco) x.carta.querySelector(".ruta-memoria__dorso").focus();
                        });
                        ocupado = false;
                    }, a.c.video || b.c.video ? 2500 : 1000);
                });
                tablero.appendChild(carta);
            });
            contar();
            cuerpo.append(tablero, estado);
            return () => (encontradas.length === total ? encontradas.slice() : null);
        },

        /** Deletreo (LSC): ver el video y escribir la palabra. */
        deletreo(e, cuerpo) {
            return campoDeTexto(cuerpo, "Escribe la palabra deletreada");
        },
        sena(e, cuerpo) { return tipos.opcion(e, cuerpo); },

        escuchar(e, cuerpo) {
            cuerpo.appendChild(botonesDeVoz(e));
            return tipos.opcion(e, cuerpo);
        },

        dictado(e, cuerpo) {
            cuerpo.appendChild(botonesDeVoz(e));
            return campoDeTexto(cuerpo, "Escribe lo que oíste");
        },

        traducir(e, cuerpo) {
            const original = crear("p", "ruta-traducir__original", e.original);
            original.id = "ruta-original";
            cuerpo.appendChild(original);
            return campoDeTexto(cuerpo, "Escribe la traducción", ["ruta-original"]);
        },

        error(e, cuerpo) {
            const frase = crear("div", "ruta-error");
            frase.setAttribute("role", "radiogroup");
            frase.setAttribute("aria-label", "Toca la palabra equivocada");
            let elegida = null;
            const botones = e.palabras.map((palabra, i) => {
                const b = crear("button", "ruta-ficha ruta-error__palabra", palabra);
                b.type = "button";
                b.setAttribute("role", "radio");
                b.setAttribute("aria-checked", "false");
                b.addEventListener("click", () => {
                    frase.querySelectorAll("button").forEach((x) => x.setAttribute("aria-checked", "false"));
                    b.setAttribute("aria-checked", "true");
                    elegida = String(i);
                    listo();
                });
                frase.appendChild(b);
                return b;
            });
            radios(frase, botones);
            cuerpo.appendChild(frase);
            return () => elegida;
        },

        clasificar(e, cuerpo) {
            // Tocar una palabra la pasa al siguiente grupo; vuelve al banco después del último
            const banco = crear("div", "ruta-ordenar__banco ruta-clasificar__banco");
            banco.setAttribute("role", "group");
            banco.setAttribute("aria-label", "Palabras por clasificar");
            const grupos = crear("div", "ruta-clasificar");
            const cajas = e.grupos.map((nombre, g) => {
                const caja = crear("div", "ruta-clasificar__grupo");
                caja.dataset.grupo = String(g);
                const titulo = crear("p", "ruta-clasificar__titulo", nombre);
                const lista = crear("div", "ruta-clasificar__lista");
                lista.setAttribute("role", "group");
                lista.setAttribute("aria-label", nombre);
                caja.append(titulo, lista);
                grupos.appendChild(caja);
                return lista;
            });
            const lugar = new Map();   // ficha -> índice del grupo (o -1 en el banco)
            e.items.forEach((palabra) => {
                const ficha = crear("button", "ruta-ficha", palabra);
                ficha.type = "button";
                lugar.set(ficha, -1);
                ficha.addEventListener("click", () => {
                    const g = lugar.get(ficha) + 1;
                    if (g >= cajas.length) {
                        lugar.set(ficha, -1);
                        banco.appendChild(ficha);
                        ficha.setAttribute("aria-label", palabra + ": sin grupo");
                        anunciar(palabra + ": sin grupo.");
                    } else {
                        lugar.set(ficha, g);
                        cajas[g].appendChild(ficha);
                        ficha.setAttribute("aria-label", palabra + ": " + e.grupos[g]);
                        anunciar(palabra + ": " + e.grupos[g] + ".");
                    }
                    ficha.focus();   // moverla en la página le quita el foco
                    listo();
                });
                banco.appendChild(ficha);
            });
            cuerpo.append(crear("p", "ruta-clasificar__ayuda", "Toca cada palabra para pasarla de grupo."), grupos, banco);
            return () => ([...lugar.values()].some((g) => g < 0) ? null : [...lugar].map(([f, g]) => [f.textContent, e.grupos[g]]));
        },

        huecos(e, cuerpo) {
            // Tocar una ficha llena el primer espacio libre; tocar un espacio lleno lo vacía
            const texto = crear("p", "ruta-huecos");
            const banco = crear("div", "ruta-ordenar__banco");
            banco.setAttribute("role", "group");
            banco.setAttribute("aria-label", "Palabras");
            const espacios = [];
            const fichas = [];
            e.partes.forEach((parte, i) => {
                texto.appendChild(document.createTextNode(parte));
                if (i < e.partes.length - 1) {
                    const hueco = crear("button", "ruta-huecos__espacio");
                    hueco.type = "button";
                    hueco.setAttribute("aria-label", "Espacio " + (i + 1) + ": vacío");
                    hueco.addEventListener("click", () => {
                        if (!hueco.ficha) return;
                        hueco.ficha.hidden = false;
                        hueco.ficha = null;
                        hueco.textContent = "";
                        hueco.setAttribute("aria-label", "Espacio " + (i + 1) + ": vacío");
                        anunciar("Espacio " + (i + 1) + " vacío. La palabra volvió al banco.");
                        listo();
                    });
                    espacios.push(hueco);
                    texto.appendChild(hueco);
                }
            });
            e.fichas.forEach((palabra) => {
                const ficha = crear("button", "ruta-ficha", palabra);
                ficha.type = "button";
                ficha.addEventListener("click", () => {
                    const libre = espacios.find((h) => !h.ficha);
                    if (!libre) return;
                    const n = espacios.indexOf(libre) + 1;
                    libre.ficha = ficha;
                    libre.textContent = palabra;
                    libre.setAttribute("aria-label", "Espacio " + n + ": " + palabra);
                    ficha.hidden = true;
                    // La ficha se oculta: el foco pasa a la siguiente que quede, o al espacio recién llenado
                    const otra = fichas.slice(fichas.indexOf(ficha) + 1).concat(fichas).find((f) => !f.hidden);
                    (otra || libre).focus();
                    anunciar(palabra + " en el espacio " + n + ".");
                    listo();
                });
                fichas.push(ficha);
                banco.appendChild(ficha);
            });
            cuerpo.append(texto, banco);
            return () => (espacios.every((h) => h.ficha) ? espacios.map((h) => h.ficha.textContent) : null);
        },

        vf(e, cuerpo) {
            dibujo(e, cuerpo);
            const grupo = crear("div", "ruta-opciones ruta-opciones--dos");
            let valor = null;
            [["1", "Verdadero"], ["0", "Falso"]].forEach(([v, texto]) => {
                const b = crear("button", "ruta-opcion", texto);
                b.type = "button";
                b.setAttribute("aria-pressed", "false");
                b.addEventListener("click", () => {
                    grupo.querySelectorAll(".ruta-opcion").forEach((x) => x.setAttribute("aria-pressed", "false"));
                    b.setAttribute("aria-pressed", "true");
                    valor = v;
                    listo();
                });
                grupo.appendChild(b);
            });
            cuerpo.appendChild(grupo);
            return () => valor;
        },

        completar(e, cuerpo) {
            const frase = crear("p", "ruta-completar");
            const entrada = crear("input", "entrada ruta-completar__entrada");
            entrada.type = "text";
            entrada.autocomplete = "off";
            entrada.spellcheck = false;
            entrada.maxLength = 100;
            entrada.setAttribute("aria-label", "Escribe la palabra que falta");
            entrada.setAttribute("aria-describedby", "ruta-enunciado ruta-frase");
            entrada.addEventListener("input", listo);
            frase.append(document.createTextNode(e.antes), entrada, document.createTextNode(e.despues));
            // La frase completa para el lector de pantalla (el campo va en medio y recibe el foco)
            const leida = crear("span", "sr-only", "Frase: " + e.antes + " … " + e.despues);
            leida.id = "ruta-frase";
            cuerpo.append(frase, leida);
            setTimeout(() => entrada.focus(), 50);
            return () => (entrada.value.trim() === "" ? null : entrada.value);
        },

        ordenar(e, cuerpo) {
            const respuesta = crear("div", "ruta-ordenar__respuesta");
            respuesta.setAttribute("role", "group");
            respuesta.setAttribute("aria-label", "Tu frase");
            const banco = crear("div", "ruta-ordenar__banco");
            banco.setAttribute("role", "group");
            banco.setAttribute("aria-label", "Palabras");
            const elegidas = [];
            e.fichas.forEach((palabra) => {
                const ficha = crear("button", "ruta-ficha", palabra);
                ficha.type = "button";
                ficha.addEventListener("click", () => {
                    const enRespuesta = ficha.parentNode === respuesta;
                    (enRespuesta ? banco : respuesta).appendChild(ficha);
                    if (enRespuesta) elegidas.splice(elegidas.indexOf(ficha), 1);
                    else elegidas.push(ficha);
                    ficha.focus();   // moverla en la página le quita el foco
                    anunciar(enRespuesta
                        ? palabra + " volvió a las palabras."
                        : "Tu frase: " + elegidas.map((f) => f.textContent).join(" ") + ".");
                    listo();
                });
                banco.appendChild(ficha);
            });
            cuerpo.append(respuesta, banco);
            return () => (elegidas.length ? elegidas.map((f) => f.textContent).join(" ") : null);
        },

        emparejar(e, cuerpo) {
            const tablero = crear("div", "ruta-emparejar");
            const izq = crear("div", "ruta-emparejar__columna");
            const der = crear("div", "ruta-emparejar__columna");
            izq.setAttribute("role", "group");
            izq.setAttribute("aria-label", "Columna 1");
            der.setAttribute("role", "group");
            der.setAttribute("aria-label", "Columna 2");
            const pares = new Map();   // botón izquierdo -> botón derecho
            let marcado = null;        // aria-pressed = la palabra elegida que espera su pareja

            // Cada pareja lleva un número visible (además del color) y lo dice su etiqueta
            const numeroLibre = () => {
                const usados = new Set([...pares.keys()].map((k) => k.dataset.par));
                for (let n = 1; n <= 6; n++) if (!usados.has(String(n))) return n;
                return 1;
            };
            const marcarPar = (b, otro, n) => {
                const viejo = b.querySelector(".ruta-ficha__par");
                if (viejo) viejo.remove();
                if (!otro) {
                    b.removeAttribute("data-par");
                    b.removeAttribute("aria-label");
                    return;
                }
                b.dataset.par = String(n);
                const numero = crear("span", "ruta-ficha__par", String(n));
                numero.setAttribute("aria-hidden", "true");
                b.prepend(numero);
                b.setAttribute("aria-label", b.dataset.texto + ", unida con " + otro.dataset.texto + " (pareja " + n + ")");
            };
            const soltar = (bIzq) => {
                const bDer = pares.get(bIzq);
                pares.delete(bIzq);
                [bIzq, bDer].forEach((b) => marcarPar(b, null));
                anunciar("Pareja deshecha: " + bIzq.dataset.texto + " y " + bDer.dataset.texto + ".");
            };
            const hacer = (lista, lado) => lista.forEach((texto) => {
                const b = crear("button", "ruta-ficha ruta-ficha--par", texto);
                b.type = "button";
                b.dataset.texto = texto;
                b.setAttribute("aria-pressed", "false");
                b.addEventListener("click", () => {
                    if (b.dataset.par) {   // tocar una pareja hecha la deshace
                        const bIzq = lado === "izq" ? b : [...pares.keys()].find((k) => pares.get(k) === b);
                        soltar(bIzq);
                        listo();
                        return;
                    }
                    if (marcado && marcado.lado !== lado) {
                        const bIzq = lado === "izq" ? b : marcado.b;
                        const bDer = lado === "izq" ? marcado.b : b;
                        const n = numeroLibre();
                        marcarPar(bIzq, bDer, n);
                        marcarPar(bDer, bIzq, n);
                        [bIzq, bDer].forEach((x) => { x.setAttribute("aria-pressed", "false"); x.classList.remove("es-marcada"); });
                        pares.set(bIzq, bDer);
                        marcado = null;
                        anunciar("Unidas: " + bIzq.dataset.texto + " y " + bDer.dataset.texto + ". Pareja " + n + ".");
                    } else {
                        const otraVez = marcado && marcado.b === b;
                        if (marcado) {
                            marcado.b.classList.remove("es-marcada");
                            marcado.b.setAttribute("aria-pressed", "false");
                        }
                        marcado = otraVez ? null : { b, lado };
                        if (!otraVez) {
                            b.classList.add("es-marcada");
                            b.setAttribute("aria-pressed", "true");
                            anunciar(texto + " elegida. Ahora elige su pareja en la otra columna.");
                        }
                    }
                    listo();
                });
                (lado === "izq" ? izq : der).appendChild(b);
            });
            hacer(e.izquierda, "izq");
            hacer(e.derecha, "der");
            tablero.append(izq, der);
            cuerpo.appendChild(tablero);
            return () => (pares.size === e.izquierda.length ? [...pares].map(([a, b]) => [a.dataset.texto, b.dataset.texto]) : null);
        },
    };

    /* ---------- Mostrar un ejercicio ---------- */
    function mostrar(id) {
        const e = porId[id];
        caja.replaceChildren();
        retro.hidden = true;
        retro.className = "ruta-retro";
        modo = "comprobar";
        boton.textContent = "Comprobar";

        caja.appendChild(crear("p", "ruta-ejercicio__tipo", {
            opcion: "Elige la respuesta", sena: "Mira la seña", escuchar: "Escucha y elige", vf: "¿Verdadero o falso?",
            completar: "Completa la frase", ordenar: "Ordena las palabras", emparejar: "Une las parejas",
            dictado: "Escucha y escribe", traducir: "Traduce", error: "Encuentra el error",
            clasificar: "Clasifica", huecos: "Completa el texto",
            memoria: "Encuentra las parejas", elegir: "Elige la correcta", deletreo: "Mira el deletreo",
        }[e.tipo] || ""));
        const enunciado = crear("h2", "ruta-ejercicio__enunciado", e.enunciado);
        enunciado.id = "ruta-enunciado";   // describe los campos y grupos de opciones
        enunciado.tabIndex = -1;   // recibe el foco al cambiar de ejercicio (lectores de pantalla)
        caja.appendChild(enunciado);

        if (e.video) {
            const marco = crear("div", "leccion__video-marco ruta-ejercicio__video");
            const iframe = document.createElement("iframe");
            iframe.src = e.video;
            iframe.title = "Video del ejercicio";
            iframe.loading = "lazy";
            iframe.referrerPolicy = "strict-origin-when-cross-origin";
            iframe.allow = "encrypted-media; picture-in-picture; fullscreen";
            iframe.allowFullscreen = true;
            marco.appendChild(iframe);
            caja.appendChild(marco);
        }

        const cuerpo = crear("div", "ruta-ejercicio__cuerpo");
        caja.appendChild(cuerpo);
        obtenerRespuesta = (tipos[e.tipo] || tipos.opcion)(e, cuerpo);
        if (SIN_AUDIO.includes(e.tipo) && !esExamen) {
            const saltar = crear("button", "boton boton--fantasma boton--sm ruta-saltar", " No puedo escuchar ahora");
            saltar.type = "button";
            saltar.prepend(crear("i", "fa-solid fa-ear-deaf"));
            saltar.firstChild.setAttribute("aria-hidden", "true");
            saltar.addEventListener("click", () => { if (modo === "comprobar") comprobar(true); });
            caja.appendChild(saltar);
        }
        caja.hidden = false;
        pie.hidden = false;
        listo();
        if (!["completar", "dictado", "traducir", "deletreo"].includes(e.tipo)) enunciado.focus({ preventScroll: true });
    }

    /* ---------- Comprobar en el servidor ---------- */
    async function comprobar(saltar = false) {
        const respuesta = saltar ? "" : (obtenerRespuesta ? obtenerRespuesta() : null);
        if (respuesta === null) return;
        modo = "enviando";
        boton.disabled = true;
        boton.dataset.cargando = "true";

        const datos = new FormData();
        datos.append("csrf_token", csrf);
        if (esExamen || esRepaso || esContrarreloj) datos.append("idioma_id", raiz.dataset.idioma);
        else datos.append("leccion_id", raiz.dataset.leccion);
        datos.append("ejercicio_id", String(actual));
        if (saltar) datos.append("saltar", "1");
        else if (Array.isArray(respuesta)) datos.append("respuesta_json", JSON.stringify(respuesta));
        else datos.append("respuesta", respuesta);

        let r;
        try {
            const res = await fetch(endpoint, { method: "POST", body: datos, credentials: "same-origin" });
            r = await res.json();
        } catch (err) {
            r = { error: "Se perdió la conexión. Revisa tu internet e inténtalo de nuevo." };
        }
        delete boton.dataset.cargando;
        if (terminado) return;
        // Contrarreloj: la respuesta llegó tarde, el servidor ya cerró la partida
        if (esContrarreloj && r.tiempo && r.resultado) return terminar(r.resultado);

        if (r.error) {
            retro.className = "ruta-retro ruta-retro--error";
            tituloRetro("fa-triangle-exclamation", r.error);
            $("[data-retro-solucion]").textContent = "";
            retro.hidden = false;
            anunciar(r.error);
            modo = "comprobar";
            listo();
            if (r.siguiente) { actual = r.siguiente; mostrar(actual); }
            return;
        }

        caja.querySelectorAll("button, input, textarea").forEach((x) => { x.disabled = true; });
        // Elige la imagen o la seña: marcar en verde la correcta (los videos no se leen como texto)
        if (r.correctaValor !== undefined) {
            caja.querySelectorAll(".ruta-elegir__opcion").forEach((x) => {
                if (x.dataset.valor === r.correctaValor) x.classList.add("es-correcta");
            });
        }
        // El resultado se dice con palabras y un ícono, no solo con el color
        let titulo;
        if (r.saltado) {
            retro.className = "ruta-retro ruta-retro--saltado";
            titulo = tituloRetro("fa-ear-deaf", r.solucion ? "Saltado: no cuenta como error. Era:" : "Saltado: no cuenta como error.");
        } else if (r.correcto) {
            retro.className = "ruta-retro ruta-retro--bien";
            titulo = tituloRetro("fa-circle-check", "¡Correcto!");
        } else {
            retro.className = "ruta-retro ruta-retro--mal";
            titulo = tituloRetro("fa-circle-xmark", "Incorrecto. La respuesta correcta es:");
        }
        // En "encontrar el error" la frase corregida se muestra siempre: es lo que se aprende
        const solucion = r.correcto && porId[actual].tipo !== "error" ? "" : (r.solucion || "");
        $("[data-retro-solucion]").textContent = solucion;
        retro.hidden = false;
        if (!esContrarreloj) avance(r.avance || 0);
        if (!r.saltado) combo(r.correcto);
        const comboTexto = retro.querySelector(".ruta-combo");
        anunciar([titulo, solucion, comboTexto ? comboTexto.textContent : ""].filter(Boolean).join(" "));

        siguiente = r.siguiente;
        resultado = r.resultado || null;
        if (esContrarreloj) {
            // Sin botón "Continuar": la siguiente aparece sola (es contra el reloj)
            const marcador = $("[data-aciertos]");
            if (marcador && r.aciertos !== undefined) marcador.textContent = aciertos(r.aciertos);
            modo = "enviando";
            setTimeout(() => { if (!terminado) continuar(); }, resultado ? 0 : (r.correcto || r.saltado ? 450 : 1100));
            return;
        }
        modo = "continuar";
        boton.textContent = resultado ? "Ver resultado" : (r.correcto || r.saltado ? "Continuar" : "Entendido");
        boton.disabled = false;
        boton.focus();
    }

    /** Título del aviso tras comprobar: ícono (decorativo) y texto. Devuelve el texto. */
    function tituloRetro(icono, texto) {
        const i = crear("i", "fa-solid " + icono);
        i.setAttribute("aria-hidden", "true");
        $("[data-retro-titulo]").replaceChildren(i, document.createTextNode(" " + texto));
        return texto;
    }

    /** Combo de aciertos seguidos: avisa en 3, 5, 10, 15… (solo visual). */
    function combo(acerto) {
        seguidas = acerto ? seguidas + 1 : 0;
        const viejo = retro.querySelector(".ruta-combo");
        if (viejo) viejo.remove();
        if (seguidas < 3 || !(seguidas === 3 || seguidas % 5 === 0)) return;
        const aviso = crear("p", "ruta-combo", "¡" + seguidas + " seguidas! 🔥");
        retro.prepend(aviso);
    }

    /** Confeti al terminar (más piezas si fue perfecta o cumplió la meta). Se borra solo. */
    function confeti(fuerte) {
        if (sinMovimiento) return;
        const capa = crear("div", "confeti");
        capa.setAttribute("aria-hidden", "true");
        const colores = ["#2563EB", "#60A5FA", "#22C55E", "#F59E0B", "#F97316", "#A855F7"];
        const piezas = fuerte ? 90 : 45;
        for (let i = 0; i < piezas; i++) {
            const p = crear("span", "confeti__pieza");
            p.style.left = Math.random() * 100 + "%";
            p.style.background = colores[i % colores.length];
            p.style.animationDelay = Math.random() * 0.6 + "s";
            p.style.animationDuration = 1.6 + Math.random() * 1.4 + "s";
            p.style.setProperty("--giro", Math.round(Math.random() * 720 - 360) + "deg");
            p.style.setProperty("--desvio", Math.round(Math.random() * 160 - 80) + "px");
            capa.appendChild(p);
        }
        document.body.appendChild(capa);
        setTimeout(() => capa.remove(), 3600);
    }

    function continuar() {
        if (resultado) return terminar(resultado);
        if (siguiente !== null && porId[siguiente]) {
            actual = siguiente;
            mostrar(actual);
        }
    }

    /** Final del examen de ubicación: el nivel donde quedó. */
    function terminarExamen(r) {
        caja.hidden = true;
        pie.hidden = true;
        retro.hidden = true;
        $("[data-fin-titulo]").textContent = "Empiezas en el " + r.nivel + (r.nombre ? " · " + r.nombre : "");
        let mensaje = r.aprobados > 0
            ? "Aprobaste " + r.aprobados + (r.aprobados === 1 ? " nivel" : " niveles") + ". Los anteriores quedan abiertos para repasar cuando quieras."
            : "Tu ruta empieza desde el principio. ¡Vamos paso a paso!";
        if (!r.mejoro && r.antes) mensaje = "Esta vez no superaste tu resultado anterior: sigues en el " + r.antes + ".";
        $("[data-fin-mensaje]").textContent = mensaje;
        $("[data-fin-detalle]").textContent = r.detalle || "";
        if (r.aprobados > 0) confeti(r.aprobados >= 3);
        const ir = $("[data-fin-ir]");
        if (typeof r.url === "string" && r.url.indexOf("index.php?page=ruta") === 0) ir.setAttribute("href", r.url);
        ir.textContent = "Ir al " + r.nivel;
        const fin = $("[data-fin]");
        fin.hidden = false;
        ir.focus();
        avance(100);
    }

    /** Si el XP de recién cumplió la meta diaria, lo dice debajo del mensaje final. */
    function avisarMeta(r) {
        if (!r.meta || !r.meta.recienCumplida) return;
        const mensaje = $("[data-fin-mensaje]");
        const aviso = crear("p", "ruta-fin__meta", "¡Cumpliste tu meta del día! (" + r.meta.xpHoy + " de " + r.meta.meta + " XP)");
        mensaje.insertAdjacentElement("afterend", aviso);
    }

    /** Final del repaso de errores. */
    function terminarRepaso(r) {
        caja.hidden = true;
        pie.hidden = true;
        retro.hidden = true;
        $("[data-fin-porcentaje]").textContent = r.resueltos + " de " + r.total;
        $("[data-fin-xp]").textContent = "+" + r.xp;
        if (r.racha) {
            $("[data-fin-racha-caja]").hidden = false;
            $("[data-fin-racha]").textContent = r.racha + (r.racha === 1 ? " día" : " días");
        }
        $("[data-fin-mensaje]").textContent = r.quedan > 0
            ? "Te " + (r.quedan === 1 ? "queda 1 ejercicio" : "quedan " + r.quedan + " ejercicios") + " por repasar. Los que fallaste hoy siguen en tu lista."
            : "¡Resolviste todos tus errores de este idioma!";
        const otro = $("[data-fin-otro]");
        if (otro) otro.hidden = r.quedan === 0;
        avisarMeta(r);
        if (r.resueltos > 0) confeti(r.quedan === 0 || !!(r.meta && r.meta.recienCumplida));
        const fin = $("[data-fin]");
        fin.hidden = false;
        fin.querySelector("a").focus();
        avance(100);
    }

    /** Contrarreloj: avisa al servidor (empezar / terminar). */
    async function pedirContrarreloj(accion) {
        const datos = new FormData();
        datos.append("csrf_token", csrf);
        datos.append("idioma_id", raiz.dataset.idioma);
        datos.append("accion", accion);
        try {
            return await (await fetch(endpoint, { method: "POST", body: datos, credentials: "same-origin" })).json();
        } catch (err) {
            return { error: "Se perdió la conexión." };
        }
    }

    function arrancarReloj(segundos) {
        const caja = $("[data-reloj]");
        let quedan = segundos;
        caja.textContent = String(quedan);
        reloj = setInterval(async () => {
            quedan--;
            caja.textContent = String(Math.max(0, quedan));
            avance(Math.min(100, Math.round(((segundos - quedan) / segundos) * 100)));
            if (quedan <= 10) caja.classList.add("es-urgente");
            // El número cambia cada segundo sin anunciarse; estos avisos sí se oyen
            if (quedan === 30 || quedan === 10) anunciar("Quedan " + quedan + " segundos.");
            if (quedan <= 0) {
                clearInterval(reloj);
                if (terminado) return;
                const r = await pedirContrarreloj("terminar");
                if (!terminado && r.resultado && !r.resultado.error) terminar(r.resultado);
            }
        }, 1000);
    }

    const aciertos = (n) => n + (n === 1 ? " acierto" : " aciertos");

    /** Final del contrarreloj: aciertos, récord y XP. */
    function terminarContrarreloj(r) {
        if (terminado) return;
        terminado = true;
        if (reloj) clearInterval(reloj);
        caja.hidden = true;
        pie.hidden = true;
        retro.hidden = true;
        $("[data-fin-porcentaje]").textContent = r.aciertos + " de " + r.respondidas;
        $("[data-fin-xp]").textContent = "+" + r.xp;
        $("[data-fin-mejor]").textContent = String(r.mejor);
        if (r.racha) {
            $("[data-fin-racha-caja]").hidden = false;
            $("[data-fin-racha]").textContent = r.racha + (r.racha === 1 ? " día" : " días");
        }
        $("[data-fin-mensaje]").textContent = r.record
            ? "¡Nuevo récord! " + aciertos(r.aciertos) + " en " + raiz.dataset.segundos + " segundos."
            : (r.aciertos > 0 ? "¡Bien! Tu récord es " + r.mejor + ". Inténtalo otra vez para superarlo." : "Se acabó el tiempo. ¡Inténtalo otra vez!");
        avisarMeta(r);
        if (r.record) confeti(true);
        const fin = $("[data-fin]");
        fin.hidden = false;
        fin.querySelector("a").focus();
        avance(100);
    }

    function terminar(r) {
        if (esContrarreloj) return terminarContrarreloj(r);
        if (esExamen) return terminarExamen(r);
        if (esRepaso) return terminarRepaso(r);
        caja.hidden = true;
        pie.hidden = true;
        retro.hidden = true;
        $("[data-fin-porcentaje]").textContent = r.porcentaje + "%";
        $("[data-fin-xp]").textContent = "+" + r.xp;
        if (r.racha) {
            $("[data-fin-racha-caja]").hidden = false;
            $("[data-fin-racha]").textContent = r.racha + (r.racha === 1 ? " día" : " días");
        }
        $("[data-fin-mensaje]").textContent = r.porcentaje === 100
            ? "¡Perfecta! No fallaste ninguna."
            : (r.primera ? "Se abrió la siguiente lección de tu ruta." : "Buen repaso.");
        avisarMeta(r);
        if (r.insignias && r.insignias.length) {
            const avisoInsignia = $("[data-fin-insignia]");
            avisoInsignia.querySelector("span").textContent = (r.insignias.length === 1 ? "¡Nueva insignia: " : "¡Nuevas insignias: ") + r.insignias.join(", ") + "!";
            avisoInsignia.hidden = false;
        }
        confeti(r.porcentaje === 100 || !!(r.meta && r.meta.recienCumplida));
        const fin = $("[data-fin]");
        fin.hidden = false;
        fin.querySelector("a").focus();
        avance(100);
    }

    /* ---------- Eventos ---------- */
    boton.addEventListener("click", () => {
        if (modo === "comprobar") comprobar();
        else if (modo === "continuar") continuar();
    });

    // Enter comprueba o continúa; los números 1–4 eligen opción
    document.addEventListener("keydown", (ev) => {
        if (caja.hidden) return;
        if (ev.key === "Enter" && !boton.disabled && ev.target.tagName !== "BUTTON") {
            ev.preventDefault();
            boton.click();
        } else if (modo === "comprobar" && /^[1-4]$/.test(ev.key) && ev.target.tagName !== "INPUT") {
            const op = caja.querySelectorAll(".ruta-opcion")[Number(ev.key) - 1];
            if (op) op.click();
        }
    });

    $("[data-empezar]").addEventListener("click", async () => {
        if (esContrarreloj) {
            const r = await pedirContrarreloj("empezar");
            if (r.error) { window.location.reload(); return; }
            $("[data-marcador]").hidden = false;
            arrancarReloj(r.segundos || Number(raiz.dataset.segundos) || 60);
        }
        const intro = $("[data-intro]");
        // El <h1> está en la portada: al ocultarla, queda uno solo para lectores de pantalla
        const h1 = intro.querySelector("h1");
        if (h1) raiz.prepend(crear("h1", "sr-only", h1.textContent));
        intro.hidden = true;
        if (actual !== null) mostrar(actual);
    });

    // Salir a mitad de la lección: pide confirmación
    $("[data-salir]").addEventListener("click", (ev) => {
        const texto = raiz.dataset.salirTexto || "¿Salir de la lección? Perderás el avance de este intento.";
        if (!caja.hidden && !window.confirm(texto)) ev.preventDefault();
    });
})();
