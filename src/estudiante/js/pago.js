/* =========================================================
   Pago con tarjeta (Mi plan).
   Los datos de la tarjeta van DIRECTO del navegador a Wompi
   (POST /tokens/cards con la llave pública). A BiGlobal solo llega
   el token "tok_..."; los campos de la tarjeta no tienen "name",
   así que el formulario nunca los envía al servidor.
========================================================= */
(function () {
    "use strict";

    var form = document.querySelector("[data-pago-wompi]");
    if (!form) return;

    var error = form.querySelector("[data-pago-error]");
    var campoToken = form.querySelector("input[name=token_tarjeta]");
    var enviando = false;

    function mostrarError(texto) {
        error.textContent = texto;
        error.hidden = false;
    }

    function valor(nombre) {
        var campo = form.querySelector('[data-tarjeta="' + nombre + '"]');
        return campo ? campo.value.trim() : "";
    }

    form.addEventListener("submit", function (evento) {
        // Solo el botón "Pagar con tarjeta" necesita tokenizar; el checkout de Wompi sigue normal
        var boton = evento.submitter;
        if (!boton || boton.value !== "tarjeta") return;

        evento.preventDefault();
        if (enviando) return;
        error.hidden = true;

        if (!form.reportValidity()) return;   // casillas de los términos

        var datos = {
            number: valor("number").replace(/\D/g, ""),
            exp_month: valor("exp_month").padStart(2, "0"),
            exp_year: valor("exp_year"),
            cvc: valor("cvc"),
            card_holder: valor("card_holder")
        };

        if (datos.number.length < 13 || datos.number.length > 19) return mostrarError("Revisa el número de la tarjeta.");
        if (!/^(0[1-9]|1[0-2])$/.test(datos.exp_month)) return mostrarError("Escribe el mes de vencimiento (01 a 12).");
        if (!/^\d{2}$/.test(datos.exp_year)) return mostrarError("Escribe el año de vencimiento con 2 dígitos (ej. 29).");
        if (!/^\d{3,4}$/.test(datos.cvc)) return mostrarError("El CVC tiene 3 o 4 números (atrás de la tarjeta).");
        if (datos.card_holder.length < 5) return mostrarError("Escribe el nombre como aparece en la tarjeta.");

        enviando = true;
        boton.disabled = true;
        boton.dataset.cargando = "true";

        fetch(form.dataset.wompiApi + "/tokens/cards", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Authorization": "Bearer " + form.dataset.wompiPublica
            },
            body: JSON.stringify(datos)
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
            .then(function (r) {
                var token = r.ok && r.json && r.json.data && r.json.data.id;
                if (!token) throw new Error("rechazada");

                // Se limpian los campos para que no queden en la página
                form.querySelectorAll("[data-tarjeta]").forEach(function (c) { c.value = ""; });
                campoToken.value = token;

                var accion = document.createElement("input");
                accion.type = "hidden";
                accion.name = "accion";
                accion.value = "tarjeta";
                form.appendChild(accion);
                form.submit();   // submit() no vuelve a pasar por este evento
            })
            .catch(function () {
                enviando = false;
                boton.disabled = false;
                delete boton.dataset.cargando;
                mostrarError("Wompi no aceptó los datos de la tarjeta. Revísalos o usa otro medio de pago.");
            });
    });
})();
