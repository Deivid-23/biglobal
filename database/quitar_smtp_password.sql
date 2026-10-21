-- =============================================================
-- BiGlobal · Quita la contraseña SMTP de la tabla configuracion
-- =============================================================
-- La contraseña SMTP es un secreto: desde ahora solo se lee de la
-- variable de entorno SMTP_PASS (igual que BREVO_API_KEY) y el panel
-- ya no la pide ni la guarda. En producción el valor estaba vacío.
-- =============================================================

DELETE FROM configuracion WHERE clave = 'smtp_password';
