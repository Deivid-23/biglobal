<?php

session_start();


/* =========================================================
   VERIFICAR SI HAY SESIÓN
========================================================= */

function protegerSesion()
{
    if (!isset($_SESSION["usuario_id"])) {

        header("Location: ../auth/login.html");
        exit;
    }
}


/* =========================================================
   VERIFICAR ROL
========================================================= */

function protegerRol($rolPermitido)
{
    protegerSesion();

    if (!isset($_SESSION["rol"])) {

        session_destroy();

        header("Location: ../auth/login.html");
        exit;
    }


    /* =====================================================
       COMPARAR ROL
    ===================================================== */

    if (strtolower($_SESSION["rol"]) !== strtolower($rolPermitido)) {

        http_response_code(403);

        echo "
        <!DOCTYPE html>

        <html lang='es'>

        <head>

            <meta charset='UTF-8'>

            <meta name='viewport' content='width=device-width, initial-scale=1.0'>

            <title>Acceso denegado | BiGlobal</title>

            <style>

                body {

                    margin: 0;

                    min-height: 100vh;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    font-family: Arial, sans-serif;

                    background: #f8fafc;

                    color: #1e293b;

                }

                .error-card {

                    width: 90%;

                    max-width: 450px;

                    background: white;

                    padding: 40px;

                    border-radius: 18px;

                    text-align: center;

                    box-shadow:
                        0 10px 30px rgba(15, 23, 42, 0.08);

                }

                .error-icon {

                    width: 70px;

                    height: 70px;

                    margin: 0 auto 20px;

                    border-radius: 50%;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    background: #fee2e2;

                    color: #dc2626;

                    font-size: 32px;

                }

                h1 {

                    margin-bottom: 10px;

                }

                p {

                    color: #64748b;

                    line-height: 1.6;

                }

                a {

                    display: inline-block;

                    margin-top: 20px;

                    padding: 11px 18px;

                    background: #2563eb;

                    color: white;

                    text-decoration: none;

                    border-radius: 9px;

                    font-weight: bold;

                }

                a:hover {

                    background: #1d4ed8;

                }

            </style>

        </head>

        <body>

            <div class='error-card'>

                <div class='error-icon'>
                    🔒
                </div>

                <h1>
                    Acceso denegado
                </h1>

                <p>
                    No tienes permisos para acceder a esta sección.
                </p>

                <a href='../auth/login.html'>
                    Volver al inicio
                </a>

            </div>

        </body>

        </html>
        ";

        exit;
    }
}