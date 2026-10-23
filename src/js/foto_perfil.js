/**
 * BiGlobal · Foto de perfil
 * Al elegir una imagen: la recorta en un cuadrado al centro, la deja en
 * 512 × 512 px como JPG y la pone en el campo del formulario (así sube
 * liviana). Muestra la vista previa. Si el navegador no puede, se sube la
 * original y el servidor la revisa igual (JPG o PNG, máx. 2 MB).
 *
 * Usa direcciones data: (no blob:) porque la CSP solo permite img-src 'self' data:.
 */
(function () {
    "use strict";
    var form = document.querySelector("[data-foto-form]");
    if (!form) return;
    var entrada = form.querySelector("[data-foto-archivo]");
    var vista = document.querySelector("[data-foto-vista]");
    var estado = form.querySelector("[data-foto-estado]");
    var LADO = 512;

    function mostrar(url) {
        var caja = document.createElement("span");
        caja.className = "foto-perfil__avatar con-foto";
        var img = document.createElement("img");
        img.src = url;
        img.alt = "";
        caja.appendChild(img);
        vista.replaceChildren(caja);
    }

    entrada.addEventListener("change", function () {
        var archivo = entrada.files && entrada.files[0];
        if (!archivo) return;
        if (!/^image\/(jpeg|png)$/.test(archivo.type)) {
            estado.textContent = "Elige una foto JPG o PNG.";
            return;
        }
        var lector = new FileReader();
        lector.onload = function () {
            var img = new Image();
            img.onload = function () {
                var lado = Math.min(img.naturalWidth, img.naturalHeight);
                var lienzo = document.createElement("canvas");
                lienzo.width = LADO;
                lienzo.height = LADO;
                var ctx = lienzo.getContext("2d");
                ctx.fillStyle = "#FFFFFF";                 // fondo blanco para los PNG transparentes
                ctx.fillRect(0, 0, LADO, LADO);
                ctx.imageSmoothingQuality = "high";
                ctx.drawImage(img, (img.naturalWidth - lado) / 2, (img.naturalHeight - lado) / 2, lado, lado, 0, 0, LADO, LADO);
                lienzo.toBlob(function (blob) {
                    if (blob && typeof DataTransfer !== "undefined") {
                        try {
                            var dt = new DataTransfer();
                            dt.items.add(new File([blob], "foto.jpg", { type: "image/jpeg" }));
                            entrada.files = dt.files;
                        } catch (e) { /* se sube la original */ }
                    }
                    mostrar(lienzo.toDataURL("image/jpeg", 0.88));
                    estado.textContent = "Así se verá. Toca «Guardar foto» para usarla.";
                }, "image/jpeg", 0.88);
            };
            img.onerror = function () { estado.textContent = "No pudimos abrir esa imagen. Prueba con otra."; };
            img.src = lector.result;
        };
        lector.readAsDataURL(archivo);
    });
})();
