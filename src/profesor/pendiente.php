<?php

require_once __DIR__ . "/../../app/bootstrap.php";

/* =========================================================
   VERIFICAR SESIÓN
========================================================= */
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../auth/login.html");
    exit;
}

$rol = strtolower($_SESSION["rol"] ?? "");

/* Solo docentes pueden ver esta página */
if ($rol !== "docente") {

    if ($rol === "admin" || $rol === "administrador") {
        header("Location: ../admin/index.php");
        exit;
    }

    if ($rol === "estudiante" || $rol === "usuario") {
        header("Location: ../estudiante/index.php");
        exit;
    }

    header("Location: ../auth/login.html");
    exit;
}

/* =========================================================
   VERIFICAR ESTADO DEL DOCENTE
========================================================= */
$conexion = Conexion::conectar();

$stmt = $conexion->prepare("SELECT activo FROM usuarios WHERE id = ? LIMIT 1");
$stmt->execute([(int) $_SESSION["usuario_id"]]);
$activo = (int) $stmt->fetchColumn();

/* Si ya fue aprobado → mandarlo a su panel */
if ($activo === 1) {
    header("Location: index.php");
    exit;
}

/* Si fue rechazado/bloqueado → cerrar sesión */
if ($activo === 0) {
    header("Location: ../auth/logout.php");
    exit;
}

/* activo === 2 → pendiente, seguir mostrando la pantalla */

$nombre = $_SESSION["nombre"] ?? "Docente";
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BiGlobal | Solicitud pendiente</title>

<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Manrope', system-ui, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #eef2ff 0%, #f5f7fb 50%, #ede9fe 100%);
    padding: 20px;
    color: #0f172a;
  }

  .card {
    background: #fff;
    border-radius: 20px;
    padding: 48px 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
    border: 1px solid #e2e8f0;
  }

  .logo {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 32px;
    font-size: 20px;
    font-weight: 800;
  }

  .logo-mark {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 16px;
  }

  .logo-text { color: #0f172a; }
  .logo-text span { color: #2563eb; }

  .icon-circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dbeafe, #ede9fe);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 40px;
    color: #2563eb;
    animation: pulse 2.5s ease-in-out infinite;
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.9; }
  }

  h1 {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 12px;
    color: #0f172a;
  }

  p {
    font-size: 14px;
    line-height: 1.6;
    color: #64748b;
    margin-bottom: 8px;
  }

  .greeting {
    font-size: 13px;
    color: #2563eb;
    font-weight: 600;
    margin-bottom: 20px;
  }

  .info-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin: 24px 0;
    text-align: left;
    font-size: 13px;
    color: #475569;
  }

  .info-box strong { color: #0f172a; }

  .info-box i {
    color: #2563eb;
    margin-right: 8px;
  }

  .buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 24px;
  }

  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all .18s ease;
    border: none;
    cursor: pointer;
    font-family: inherit;
  }

  .btn-primary {
    background: linear-gradient(90deg, #2563eb, #7c3aed);
    color: #fff;
  }
  .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
  }

  .btn-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid #e2e8f0;
  }
  .btn-secondary:hover {
    background: #e2e8f0;
  }
</style>
</head>
<body>

<div class="card">

  <div class="logo">
    <div class="logo-mark">B</div>
    <div class="logo-text">Bi<span>Global</span></div>
  </div>

  <div class="icon-circle">
    <i class="fa-solid fa-hourglass-half"></i>
  </div>

  <h1>Tu solicitud está en revisión</h1>
  <p class="greeting">Hola, <?= htmlspecialchars($nombre) ?> 👋</p>

  <p>
    Tu cuenta como <strong>docente</strong> fue creada correctamente,
    pero aún está pendiente de aprobación por un administrador.
  </p>

  <div class="info-box">
    <p><i class="fa-solid fa-info-circle"></i> <strong>¿Qué sigue?</strong></p>
    <p style="margin-top: 6px; font-size: 12.5px;">
      Un administrador revisará tu solicitud y activará tu cuenta.
      Podrás iniciar sesión normalmente cuando sea aprobada.
    </p>
  </div>

  <div class="buttons">
    <a href="../auth/logout.php" class="btn btn-primary">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>
      Cerrar sesión
    </a>
    <a href="../index.html" class="btn btn-secondary">
      <i class="fa-solid fa-arrow-left"></i>
      Volver al inicio
    </a>
  </div>

</div>

</body>
</html>