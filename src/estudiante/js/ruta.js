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
    const endpoint = raiz.dataset.endpoint || "ruta_responder.php";

    let actual = ejercicios.length ? ejercicios[0].id : null;
    let obtenerRespuesta = null;   // función que devuelve la respuesta o null si falta
    let modo = "comprobar";        // "comprobar" | "continuar" | "enviando"
    let siguiente = null;
    let resultado = null;

    /* ---------- Utilidades ---------- */
    function crear(etiqueta, clase, texto) {
        const el = document.createElement(etiqueta);
        if (clase) el.className = clase;
        if (texto !== undefined) el.textContent = texto;
        return el;
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
        let elegido = null;
        lista.forEach((texto, i) => {
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
        });
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

    /** Caja para escribir una frase completa. */
    function campoDeTexto(cuerpo, etiqueta) {
        const entrada = crear("textarea", "entrada ruta-escribir");
        entrada.rows = 2;
        entrada.maxLength = 300;
        entrada.autocomplete = "off";
        entrada.spellcheck = false;
        entrada.setAttribute("autocapitalize", "off");
        entrada.setAttribute("aria-label", etiqueta);
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

    /* ---------- Cada tipo de ejercicio ---------- */
    const tipos = {
        opcion(e, cuerpo) {
            const o = opciones(e.opciones, () => {});
            cuerpo.appendChild(o.el);
            return o.valor;
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
            cuerpo.appendChild(crear("p", "ruta-traducir__original", e.original));
            return campoDeTexto(cuerpo, "Escribe la traducción");
        },

        error(e, cuerpo) {
            const frase = crear("div", "ruta-error");
            frase.setAttribute("role", "radiogroup");
            frase.setAttribute("aria-label", "Toca la palabra equivocada");
            let elegida = null;
            e.palabras.forEach((palabra, i) => {
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
            });
            cuerpo.appendChild(frase);
            return () => elegida;
        },

        clasificar(e, cuerpo) {
            // Tocar una palabra la pasa al siguiente grupo; vuelve al banco después del último
            const banco = crear("div", "ruta-ordenar__banco ruta-clasificar__banco");
            banco.setAttribute("aria-label", "Palabras por clasificar");
            const grupos = crear("div", "ruta-clasificar");
            const cajas = e.grupos.map((nombre, g) => {
                const caja = crear("div", "ruta-clasificar__grupo");
                caja.dataset.grupo = String(g);
                const titulo = crear("p", "ruta-clasificar__titulo", nombre);
                const lista = crear("div", "ruta-clasificar__lista");
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
                    } else {
                        lugar.set(ficha, g);
                        cajas[g].appendChild(ficha);
                        ficha.setAttribute("aria-label", palabra + ": " + e.grupos[g]);
                    }
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
            banco.setAttribute("aria-label", "Palabras");
            const espacios = [];
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
                    libre.ficha = ficha;
                    libre.textContent = palabra;
                    libre.setAttribute("aria-label", "Espacio " + (espacios.indexOf(libre) + 1) + ": " + palabra);
                    ficha.hidden = true;
                    listo();
                });
                banco.appendChild(ficha);
            });
            cuerpo.append(texto, banco);
            return () => (espacios.every((h) => h.ficha) ? espacios.map((h) => h.ficha.textContent) : null);
        },

        vf(e, cuerpo) {
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
            entrada.addEventListener("input", listo);
            frase.append(document.createTextNode(e.antes), entrada, document.createTextNode(e.despues));
            cuerpo.appendChild(frase);
            setTimeout(() => entrada.focus(), 50);
            return () => (entrada.value.trim() === "" ? null : entrada.value);
        },

        ordenar(e, cuerpo) {
            const respuesta = crear("div", "ruta-ordenar__respuesta");
            respuesta.setAttribute("aria-label", "Tu frase");
            const banco = crear("div", "ruta-ordenar__banco");
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
            const pares = new Map();   // botón izquierdo -> botón derecho
            let marcado = null;
            let color = 0;

            const soltar = (bIzq) => {
                const bDer = pares.get(bIzq);
                pares.delete(bIzq);
                [bIzq, bDer].forEach((b) => { b.removeAttribute("data-par"); b.setAttribute("aria-pressed", "false"); });
            };
            const hacer = (lista, lado) => lista.forEach((texto) => {
                const b = crear("button", "ruta-ficha ruta-ficha--par", texto);
                b.type = "button";
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
                        color = (color % 6) + 1;
                        [bIzq, bDer].forEach((x) => { x.dataset.par = String(color); x.setAttribute("aria-pressed", "true"); x.classList.remove("es-marcada"); });
                        pares.set(bIzq, bDer);
                        marcado = null;
                    } else {
                        if (marcado) marcado.b.classList.remove("es-marcada");
                        marcado = { b, lado };
                        b.classList.add("es-marcada");
                    }
                    listo();
                });
                (lado === "izq" ? izq : der).appendChild(b);
            });
            hacer(e.izquierda, "izq");
            hacer(e.derecha, "der");
            tablero.append(izq, der);
            cuerpo.appendChild(tablero);
            return () => (pares.size === e.izquierda.length ? [...pares].map(([a, b]) => [a.textContent, b.textContent]) : null);
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
        }[e.tipo] || ""));
        const enunciado = crear("h2", "ruta-ejercicio__enunciado", e.enunciado);
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
        caja.hidden = false;
        pie.hidden = false;
        listo();
        if (!["completar", "dictado", "traducir"].includes(e.tipo)) enunciado.focus({ preventScroll: true });
    }

    /* ---------- Comprobar en el servidor ---------- */
    async function comprobar() {
        const respuesta = obtenerRespuesta ? obtenerRespuesta() : null;
        if (respuesta === null) return;
        modo = "enviando";
        boton.disabled = true;
        boton.dataset.cargando = "true";

        const datos = new FormData();
        datos.append("csrf_token", csrf);
        if (esExamen || esRepaso) datos.append("idioma_id", raiz.dataset.idioma);
        else datos.append("leccion_id", raiz.dataset.leccion);
        datos.append("ejercicio_id", String(actual));
        if (Array.isArray(respuesta)) datos.append("respuesta_json", JSON.stringify(respuesta));
        else datos.append("respuesta", respuesta);

        let r;
        try {
            const res = await fetch(endpoint, { method: "POST", body: datos, credentials: "same-origin" });
            r = await res.json();
        } catch (err) {
            r = { error: "Se perdió la conexión. Revisa tu internet e inténtalo de nuevo." };
        }
        delete boton.dataset.cargando;

        if (r.error) {
            retro.className = "ruta-retro ruta-retro--error";
            $("[data-retro-titulo]").textContent = r.error;
            $("[data-retro-solucion]").textContent = "";
            retro.hidden = false;
            modo = "comprobar";
            listo();
            if (r.siguiente) { actual = r.siguiente; mostrar(actual); }
            return;
        }

        caja.querySelectorAll("button, input, textarea").forEach((x) => { x.disabled = true; });
        retro.className = "ruta-retro " + (r.correcto ? "ruta-retro--bien" : "ruta-retro--mal");
        $("[data-retro-titulo]").textContent = r.correcto ? "¡Correcto!" : "Respuesta correcta:";
        // En "encontrar el error" la frase corregida se muestra siempre: es lo que se aprende
        $("[data-retro-solucion]").textContent = r.correcto && porId[actual].tipo !== "error" ? "" : r.solucion;
        retro.hidden = false;
        avance(r.avance || 0);

        siguiente = r.siguiente;
        resultado = r.resultado || null;
        modo = "continuar";
        boton.textContent = resultado ? "Ver resultado" : (r.correcto ? "Continuar" : "Entendido");
        boton.disabled = false;
        boton.focus();
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
        const ir = $("[data-fin-ir]");
        if (typeof r.url === "string" && r.url.indexOf("index.php?page=ruta") === 0) ir.setAttribute("href", r.url);
        ir.textContent = "Ir al " + r.nivel;
        const fin = $("[data-fin]");
        fin.hidden = false;
        ir.focus();
        avance(100);
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
        const fin = $("[data-fin]");
        fin.hidden = false;
        fin.querySelector("a").focus();
        avance(100);
    }

    function terminar(r) {
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
        if (r.insignias && r.insignias.length) {
            const avisoInsignia = $("[data-fin-insignia]");
            avisoInsignia.querySelector("span").textContent = (r.insignias.length === 1 ? "¡Nueva insignia: " : "¡Nuevas insignias: ") + r.insignias.join(", ") + "!";
            avisoInsignia.hidden = false;
        }
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

    $("[data-empezar]").addEventListener("click", () => {
        $("[data-intro]").hidden = true;
        if (actual !== null) mostrar(actual);
    });

    // Salir a mitad de la lección: pide confirmación
    $("[data-salir]").addEventListener("click", (ev) => {
        const texto = raiz.dataset.salirTexto || "¿Salir de la lección? Perderás el avance de este intento.";
        if (!caja.hidden && !window.confirm(texto)) ev.preventDefault();
    });
})();
