/**
 * BiGlobal · Modo claro / oscuro
 *
 * Se carga en el <head> ANTES del CSS (sin "defer") para aplicar el tema
 * antes de que el navegador pinte la página: así no hay parpadeo.
 *
 * ¿Qué tema se usa? (en este orden)
 *   1. El que el usuario guardó en su cuenta. PHP lo imprime en
 *      <html data-tema-usuario="oscuro"> cuando hay sesión.
 *   2. El último que eligió en este navegador (localStorage).
 *   3. El del sistema operativo (prefers-color-scheme).
 *
 * El botón: cualquier elemento con el atributo data-alternar-tema.
 * Si la página tiene <html data-tema-url="..." data-csrf="...">, el cambio
 * también se guarda en la cuenta del usuario.
 */
(function () {
    var raiz = document.documentElement;
    var CLAVE = "biglobal-tema";

    function valido(t) { return t === "claro" || t === "oscuro"; }

    function leerLocal() {
        try {
            var t = localStorage.getItem(CLAVE);
            if (valido(t)) return t;
            // Clave antigua del panel admin ("dark"/"light")
            var viejo = localStorage.getItem("biglobal-theme");
            if (viejo === "dark") return "oscuro";
            if (viejo === "light") return "claro";
        } catch (e) { /* navegador sin localStorage */ }
        return null;
    }

    function guardarLocal(t) {
        try { localStorage.setItem(CLAVE, t); } catch (e) { /* sin localStorage: solo dura esta página */ }
    }

    var consultaSistema = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
    function temaSistema() { return consultaSistema && consultaSistema.matches ? "oscuro" : "claro"; }

    function aplicar(t) {
        raiz.setAttribute("data-tema", t);
        raiz.classList.toggle("dark", t === "oscuro");                    // panel admin (Tailwind)
        raiz.setAttribute("data-bs-theme", t === "oscuro" ? "dark" : "light"); // página principal (Bootstrap)
        actualizarBotones(t);
    }

    function actualizarBotones(t) {
        var botones = document.querySelectorAll("[data-alternar-tema]");
        for (var i = 0; i < botones.length; i++) {
            botones[i].setAttribute("aria-pressed", t === "oscuro" ? "true" : "false");
            botones[i].setAttribute("aria-label", t === "oscuro" ? "Cambiar a modo claro" : "Cambiar a modo oscuro");
            botones[i].setAttribute("title", t === "oscuro" ? "Modo claro" : "Modo oscuro");
        }
        var meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.setAttribute("content", t === "oscuro" ? "#0B1220" : "#EFF6FF");
    }

    /* 1-2-3: decidir el tema inicial */
    var deCuenta = raiz.getAttribute("data-tema-usuario");
    var elegido = valido(deCuenta) ? deCuenta : leerLocal();
    if (valido(deCuenta)) guardarLocal(deCuenta);

    aplicar(elegido || temaSistema());

    /* Si nunca eligió, seguimos al sistema en vivo (ej. el celular pasa a modo noche) */
    if (!elegido && consultaSistema && consultaSistema.addEventListener) {
        consultaSistema.addEventListener("change", function () {
            if (!leerLocal()) aplicar(temaSistema());
        });
    }

    /* Guardar en la cuenta (solo si la página tiene sesión) */
    function guardarEnCuenta(t) {
        var url = raiz.getAttribute("data-tema-url");
        var token = raiz.getAttribute("data-csrf");
        if (!url || !token || !window.fetch) return;

        var datos = new FormData();
        datos.append("tema", t);
        datos.append("csrf_token", token);
        fetch(url, { method: "POST", body: datos, credentials: "same-origin" }).catch(function () {
            /* sin conexión: queda guardado en este navegador */
        });
    }

    function alternar() {
        var nuevo = raiz.getAttribute("data-tema") === "oscuro" ? "claro" : "oscuro";

        // Transición de colores solo durante el cambio (no al cargar la página)
        raiz.setAttribute("data-tema-cambiando", "");
        aplicar(nuevo);
        guardarLocal(nuevo);
        guardarEnCuenta(nuevo);
        setTimeout(function () { raiz.removeAttribute("data-tema-cambiando"); }, 250);
    }

    document.addEventListener("click", function (e) {
        var boton = e.target.closest ? e.target.closest("[data-alternar-tema]") : null;
        if (boton) alternar();
    });

    /* Los botones todavía no existen cuando este script corre en el <head> */
    document.addEventListener("DOMContentLoaded", function () {
        actualizarBotones(raiz.getAttribute("data-tema"));
    });

    /* =====================================================
       COLOR DE ACENTO PERSONAL
       A partir de UN color (#RRGGBB) se calculan todos los tonos
       que usan los paneles (--azul-50 ... --azul-900), para modo
       claro y oscuro. Si el color no tiene buen contraste, se
       oscurece o aclara lo justo para cumplir WCAG AA (4.5:1).

       <html data-acento-usuario="#E11D48">  -> el que eligió la persona
       <html data-acento-sitio="#15803D">    -> el del sitio (super admin)
       Si ninguno existe, se queda el azul de la marca (tokens.css).
    ===================================================== */
    var AZUL_MARCA = "#2563eb";

    function aRgb(hex) {
        var n = parseInt(hex.slice(1), 16);
        return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }

    function aHex(rgb) {
        return "#" + rgb.map(function (v) {
            return Math.round(Math.max(0, Math.min(255, v))).toString(16).padStart(2, "0");
        }).join("");
    }

    /* Mezcla el color a con el color b (t = 0 → a, t = 1 → b) */
    function mezclar(a, b, t) {
        var x = aRgb(a), y = aRgb(b);
        return aHex([x[0] + (y[0] - x[0]) * t, x[1] + (y[1] - x[1]) * t, x[2] + (y[2] - x[2]) * t]);
    }

    function luminancia(hex) {
        var c = aRgb(hex).map(function (v) {
            v /= 255;
            return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
        });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }

    function contraste(a, b) {
        var x = luminancia(a), y = luminancia(b);
        return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
    }

    /* Acerca "color" hacia "destino" hasta que contraste con "fondo" >= minimo */
    function hastaContraste(color, destino, fondo, minimo) {
        for (var t = 0; t <= 1.001; t += 0.04) {
            var prueba = mezclar(color, destino, t);
            if (contraste(prueba, fondo) >= minimo) return prueba;
        }
        return destino;
    }

    function esHexValido(h) { return /^#[0-9a-f]{6}$/i.test(h || ""); }

    function cssAcento(base) {
        base = base.toLowerCase();
        var BLANCO = "#ffffff", NEGRO = "#000000", FONDO_OSCURO = "#0b1220", SUP_OSCURA = "#111a2e";

        // Botones: el texto es blanco, así que el fondo debe contrastar con blanco
        var boton = hastaContraste(base, NEGRO, BLANCO, 4.5);
        var hover = mezclar(boton, NEGRO, 0.14);
        var sombra = mezclar(boton, NEGRO, 0.32);

        // Modo claro: tintes suaves de fondo y tonos oscuros para texto
        var c50 = mezclar(base, BLANCO, 0.92), c100 = mezclar(base, BLANCO, 0.82);
        var c200 = mezclar(base, BLANCO, 0.66), c300 = mezclar(base, BLANCO, 0.45);
        var c700 = hastaContraste(boton, NEGRO, c50, 4.5);
        var c800 = hastaContraste(mezclar(c700, NEGRO, 0.2), NEGRO, c100, 6);
        var c900 = mezclar(c800, NEGRO, 0.3);

        // Modo oscuro: tintes oscuros de fondo y tonos claros para texto
        var o50 = mezclar(base, FONDO_OSCURO, 0.82), o100 = mezclar(base, FONDO_OSCURO, 0.68);
        var o200 = mezclar(base, FONDO_OSCURO, 0.56), o300 = mezclar(base, FONDO_OSCURO, 0.38);
        var o700 = hastaContraste(base, BLANCO, o100, 4.5);
        var o800 = hastaContraste(mezclar(o700, BLANCO, 0.35), BLANCO, o100, 7);
        var o900 = mezclar(o800, BLANCO, 0.5);
        var oMarca = hastaContraste(base, BLANCO, SUP_OSCURA, 4.5);

        var rgb = aRgb(boton).join(", "), rgbO = aRgb(oMarca).join(", ");

        // "html:root" gana a ":root" de tokens.css aunque esta etiqueta <style> esté antes
        return "html:root{" +
            "--azul-50:" + c50 + ";--azul-100:" + c100 + ";--azul-200:" + c200 + ";--azul-300:" + c300 + ";" +
            "--azul-500:" + boton + ";--azul-600:" + boton + ";--azul-700:" + c700 + ";--azul-800:" + c800 + ";--azul-900:" + c900 + ";" +
            "--azul-hover:" + hover + ";--azul-sombra:" + sombra + ";--marca-texto:" + boton + ";" +
            "--celeste-50:" + c50 + ";--foco:0 0 0 3px rgba(" + rgb + ",0.35);}" +
            "html:root[data-tema=\"oscuro\"]{" +
            "--azul-50:" + o50 + ";--azul-100:" + o100 + ";--azul-200:" + o200 + ";--azul-300:" + o300 + ";" +
            "--azul-700:" + o700 + ";--azul-800:" + o800 + ";--azul-900:" + o900 + ";" +
            "--marca-texto:" + oMarca + ";--celeste-50:" + o50 + ";--foco:0 0 0 3px rgba(" + rgbO + ",0.45);}";
    }

    /* Aplica un color (o el del sitio si es null). Devuelve el color usado. */
    function aplicarAcento(hex) {
        var color = esHexValido(hex) ? hex : raiz.getAttribute("data-acento-sitio");
        var estilo = document.getElementById("estilo-acento");

        if (!esHexValido(color) || color.toLowerCase() === AZUL_MARCA) {
            if (estilo) estilo.remove();
            return AZUL_MARCA;
        }

        if (!estilo) {
            estilo = document.createElement("style");
            estilo.id = "estilo-acento";
            document.head.appendChild(estilo);
        }
        estilo.textContent = cssAcento(color);
        return color;
    }

    /* Guarda el color en la cuenta (null = volver al color del sitio) */
    function guardarAcento(hex) {
        var url = raiz.getAttribute("data-acento-url");
        var token = raiz.getAttribute("data-csrf");
        if (!url || !token || !window.fetch) return Promise.resolve(false);

        var datos = new FormData();
        datos.append("color", esHexValido(hex) ? hex : "");
        datos.append("csrf_token", token);
        return fetch(url, { method: "POST", body: datos, credentials: "same-origin" })
            .then(function (r) { return r.json(); })
            .then(function (j) { return !!j.ok; })
            .catch(function () { return false; });
    }

    aplicarAcento(raiz.getAttribute("data-acento-usuario"));

    window.BiGlobalTema = {
        alternar: alternar,
        guardarLocal: guardarLocal,
        aplicarAcento: aplicarAcento,
        guardarAcento: guardarAcento,
        contraste: contraste
    };
})();
