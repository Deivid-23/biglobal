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

    window.BiGlobalTema = { alternar: alternar, guardarLocal: guardarLocal };
})();
