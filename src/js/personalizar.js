/**
 * BiGlobal · Tarjeta "Personalización" de Mi perfil
 *
 * Al elegir un color:
 *   1. se aplica al instante en toda la página (BiGlobalTema.aplicarAcento),
 *   2. se guarda en la cuenta (BiGlobalTema.guardarAcento).
 * El selector "Otro color" guarda cuando la persona deja de moverlo
 * (para no enviar un pedido por cada movimiento del mouse).
 */
document.addEventListener("DOMContentLoaded", () => {
    const tarjeta = document.querySelector("[data-personalizar]");
    if (!tarjeta || !window.BiGlobalTema) return;

    const estado = tarjeta.querySelector("[data-personalizar-estado]");
    const nota = tarjeta.querySelector("[data-personalizar-nota]");
    const selector = tarjeta.querySelector(".color-opcion__selector");
    const radioLibre = tarjeta.querySelector('input[value="libre"]');
    let espera = null;

    function mostrar(texto) {
        estado.textContent = texto;
        clearTimeout(mostrar.t);
        mostrar.t = setTimeout(() => { estado.textContent = ""; }, 2500);
    }

    /* Avisa si el color se tuvo que oscurecer para que el texto blanco se lea */
    function revisarContraste(hex) {
        nota.hidden = !hex || window.BiGlobalTema.contraste(hex, "#ffffff") >= 4.5;
    }

    function aplicarYGuardar(hex, conEspera) {
        window.BiGlobalTema.aplicarAcento(hex);
        revisarContraste(hex);

        clearTimeout(espera);
        espera = setTimeout(() => {
            window.BiGlobalTema.guardarAcento(hex).then((ok) => {
                mostrar(ok ? "Color guardado." : "No pudimos guardar el color. Inténtalo de nuevo.");
            });
        }, conEspera ? 450 : 0);
    }

    tarjeta.addEventListener("change", (e) => {
        if (e.target.name !== "acento") return;
        const valor = e.target.value;
        aplicarYGuardar(valor === "libre" ? selector.value : (valor || null), false);
    });

    // El selector libre: vista previa mientras se mueve, guardar al soltar
    selector.addEventListener("input", () => {
        radioLibre.checked = true;
        selector.parentElement.style.setProperty("--muestra", selector.value);
        aplicarYGuardar(selector.value, true);
    });

    const marcado = tarjeta.querySelector('input[name="acento"]:checked');
    revisarContraste(marcado && marcado.value === "libre" ? selector.value : (marcado ? marcado.value : ""));
});
