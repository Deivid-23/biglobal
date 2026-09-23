<?php

/**
 * ErroresController (admin)
 *
 * "Reportes y errores" se consolidó dentro de Reportes; este
 * modulo solo redirige (antes errores.php).
 */
class ErroresController
{
    public function __construct()
    {
        protegerRol("administrador");
    }

    public function index(): void
    {
        header("Location: reportes.php");
        exit;
    }
}