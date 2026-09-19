/* =========================================================
   TOKEN CSRF
========================================================= */

let csrfToken = "";

async function cargarCsrfToken() {

    try {

        const respuesta = await fetch("../auth/csrf_token.php");
        const datos = await respuesta.json();

        csrfToken = datos.csrf_token || "";

    } catch (error) {

        console.error("No se pudo obtener el token CSRF:", error);
    }
}

document.addEventListener("DOMContentLoaded", cargarCsrfToken);


/* =========================================================
   CAMBIO ENTRE PANTALLAS
========================================================= */

function goToSection(target) {

    const screenChoice = document.getElementById("screen-choice");
    const screenForm = document.getElementById("screen-form");

    const formLogin = document.getElementById("form-login");
    const formRegister = document.getElementById("form-register");


    if (target === "choice") {

        screenChoice.classList.add("active");
        screenForm.classList.remove("active");

        formLogin.classList.remove("active");
        formRegister.classList.remove("active");


        // Limpiar formularios
        formLogin.reset();
        formRegister.reset();


        // Reiniciar validación de contraseña
        const strengthText =
            document.getElementById("strength-text");

        if (strengthText) {
            strengthText.textContent = "Débil";
        }


        const matchError =
            document.getElementById("match-error");

        if (matchError) {
            matchError.style.display = "none";
        }


        const confirmInput =
            document.getElementById("reg-confirm");

        if (confirmInput) {
            confirmInput.classList.remove("error");
        }


        document
            .querySelectorAll(".strength-bar")
            .forEach(bar => {
                bar.style.background = "#e2e8f0";
            });
    }
}


/* =========================================================
   ELEGIR LOGIN O REGISTRO
========================================================= */

function goToForm(type) {

    const screenChoice =
        document.getElementById("screen-choice");

    const screenForm =
        document.getElementById("screen-form");

    const formLogin =
        document.getElementById("form-login");

    const formRegister =
        document.getElementById("form-register");


    screenChoice.classList.remove("active");
    screenForm.classList.add("active");


    if (type === "login") {

        formLogin.classList.add("active");
        formRegister.classList.remove("active");


        setTimeout(() => {

            const input =
                document.getElementById("login-email");

            if (input) {
                input.focus();
            }

        }, 50);

    }


    if (type === "register") {

        formRegister.classList.add("active");
        formLogin.classList.remove("active");


        setTimeout(() => {

            const input =
                document.getElementById("reg-name");

            if (input) {
                input.focus();
            }

        }, 50);
    }
}


/* =========================================================
   MOSTRAR / OCULTAR CONTRASEÑA
========================================================= */

function togglePassword(id, btn) {

    const input =
        document.getElementById(id);

    const svg =
        btn.querySelector("svg");


    if (!input || !svg) {
        return;
    }


    svg.style.transform =
        "scale(0.7) rotate(20deg)";

    svg.style.opacity = "0.4";


    setTimeout(() => {

        if (input.type === "password") {

            input.type = "text";

            svg.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20
                c-7 0-11-8-11-8
                a18.45 18.45 0 0 1 5.06-5.94
                M9.9 4.24A9.12 9.12 0 0 1 12 4
                c7 0 11 8 11 8
                a18.5 18.5 0 0 1-2.16 3.19
                m-6.72-1.07
                a3 3 0 1 1-4.24-4.24">
                </path>

                <line
                    x1="1"
                    y1="1"
                    x2="23"
                    y2="23">
                </line>
            `;

        } else {

            input.type = "password";

            svg.innerHTML = `
                <path d="M1 12s4-8 11-8
                11 8 11 8-4 8-11 8-11-8-11-8z">
                </path>

                <circle
                    cx="12"
                    cy="12"
                    r="3">
                </circle>
            `;
        }


        svg.style.transform =
            "scale(1) rotate(0deg)";

        svg.style.opacity = "1";

    }, 120);
}


/* =========================================================
   VALIDAR FUERZA DE CONTRASEÑA
========================================================= */

function validatePasswordStrength(password) {

    const bars = [

        document.getElementById("s-bar-1"),
        document.getElementById("s-bar-2"),
        document.getElementById("s-bar-3"),
        document.getElementById("s-bar-4")

    ];


    const text =
        document.getElementById("strength-text");


    let score = 0;


    if (password.length > 0) {
        score++;
    }

    if (password.length >= 6) {
        score++;
    }

    if (
        /[A-Z]/.test(password) &&
        /[0-9]/.test(password)
    ) {
        score++;
    }

    if (
        password.length >= 8 &&
        /[^A-Za-z0-9]/.test(password)
    ) {
        score++;
    }


    const colors = [
        "#ef4444",
        "#f59e0b",
        "#3b82f6",
        "#10b981"
    ];


    const labels = [
        "Débil",
        "Regular",
        "Buena",
        "Fuerte"
    ];


    bars.forEach((bar, index) => {

        if (
            bar &&
            index < score &&
            password.length > 0
        ) {

            bar.style.background =
                colors[score - 1];

        } else if (bar) {

            bar.style.background =
                "#e2e8f0";
        }

    });


    if (text) {

        text.textContent =
            password.length > 0
                ? labels[score - 1] || "Débil"
                : "Débil";
    }


    validatePasswordMatch();
}


/* =========================================================
   VALIDAR QUE LAS CONTRASEÑAS COINCIDAN
========================================================= */

function validatePasswordMatch() {

    const passwordInput =
        document.getElementById("reg-password");

    const confirmInput =
        document.getElementById("reg-confirm");

    const errorDiv =
        document.getElementById("match-error");


    if (
        !passwordInput ||
        !confirmInput ||
        !errorDiv
    ) {
        return;
    }


    const password =
        passwordInput.value;

    const confirm =
        confirmInput.value;


    if (
        confirm.length > 0 &&
        password !== confirm
    ) {

        confirmInput.classList.add("error");

        errorDiv.style.display =
            "block";

    } else {

        confirmInput.classList.remove("error");

        errorDiv.style.display =
            "none";
    }
}


/* =========================================================
   LOGIN Y REGISTRO
========================================================= */

async function handleAuth(event, type) {

    event.preventDefault();


    let url = "";

    const data =
        new FormData();


    /* =====================================================
       REGISTRO
    ===================================================== */

    if (type === "registro") {

        url =
            "../auth/registro.php";


        const nombreInput =
            document.getElementById("reg-name");

        const correoInput =
            document.getElementById("reg-email");

        const passwordInput =
            document.getElementById("reg-password");

        const confirmInput =
            document.getElementById("reg-confirm");


        if (
            !nombreInput ||
            !correoInput ||
            !passwordInput ||
            !confirmInput
        ) {

            console.error(
                "No se encontraron los campos del registro."
            );

            return;
        }


        const nombre =
            nombreInput.value.trim();

        const correo =
            correoInput.value.trim();

        const password =
            passwordInput.value;

        const confirmPassword =
            confirmInput.value;


        if (
            !nombre ||
            !correo ||
            !password ||
            !confirmPassword
        ) {

            alert(
                "Completa todos los campos."
            );

            return;
        }


        if (password !== confirmPassword) {

            alert(
                "Las contraseñas no coinciden."
            );

            return;
        }


        data.append(
            "nombre",
            nombre
        );

        data.append(
            "correo",
            correo
        );

        data.append(
            "password",
            password
        );

        data.append(
            "confirm_password",
            confirmPassword
        );
    }


    /* =====================================================
       LOGIN
    ===================================================== */

    if (type === "login") {

        url =
            "../auth/login.php";


        const correoInput =
            document.getElementById("login-email");

        const passwordInput =
            document.getElementById("login-password");


        if (
            !correoInput ||
            !passwordInput
        ) {

            console.error(
                "No se encontraron los campos del login."
            );

            return;
        }


        const correo =
            correoInput.value.trim();

        const password =
            passwordInput.value;


        if (
            !correo ||
            !password
        ) {

            alert(
                "Completa el correo y la contraseña."
            );

            return;
        }


        data.append(
            "correo",
            correo
        );

        data.append(
            "password",
            password
        );
    }


    data.append("csrf_token", csrfToken);


    /* =====================================================
       ENVIAR INFORMACIÓN A PHP
    ===================================================== */

    try {

        const response =
            await fetch(
                url,
                {
                    method: "POST",
                    body: data
                }
            );


        const texto =
            await response.text();


        console.log(
            "Respuesta del servidor:",
            texto
        );


        let resultado;


        try {

            resultado =
                JSON.parse(texto);

        } catch (error) {

            console.error(
                "PHP no devolvió JSON válido:",
                texto
            );

            alert(
                "El servidor devolvió una respuesta inesperada."
            );

            return;
        }


        /* =================================================
           RESPUESTA NEGATIVA
        ================================================= */

        if (!resultado.success) {

            alert(
                resultado.message ||
                "No fue posible completar la operación."
            );

            return;
        }


        /* =================================================
           REDIRECCIÓN
        ================================================= */

        if (resultado.redirect) {

            window.location.href =
                resultado.redirect;

        } else {

            alert(
                resultado.message ||
                "Operación realizada correctamente."
            );
        }


    } catch (error) {

        console.error(
            "Error al conectar con el servidor:",
            error
        );


        alert(
            "No se pudo conectar con el servidor."
        );
    }
}


/* =========================================================
   LOGIN CON GOOGLE
========================================================= */

function handleGoogleLogin() {

    alert(
        "La autenticación con Google todavía no está configurada."
    );
}