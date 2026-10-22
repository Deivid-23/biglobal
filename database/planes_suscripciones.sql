-- =============================================================
-- BiGlobal · Planes Gratuito y Premium (suscripciones)
-- =============================================================
-- Un usuario es PREMIUM si tiene una suscripción cuya fecha "fin" todavía
-- no pasa (ver app/Helpers/Plan.php). Si no, es GRATUITO.
-- Al vencer o cancelarse no se borra nada: el progreso, la XP y las
-- medallas están en otras tablas, así que el usuario no pierde nada.
--
-- Una fila por suscripción (no por pago). Los pagos van en la tabla
-- "pagos" del requerimiento 2 (Wompi).
--
-- Los datos de la tarjeta NUNCA se guardan: solo el id de la fuente de
-- pago tokenizada de Wompi, la marca y los últimos 4 dígitos.
--
-- Solo crea una tabla NUEVA. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS suscripciones (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id             INT NOT NULL,
    plan                   VARCHAR(30) NOT NULL,            -- 'premium_mensual' | 'premium_anual' | 'premium_cortesia'
    estado                 VARCHAR(20) NOT NULL DEFAULT 'activa',
        -- 'activa'     : vigente y se renueva sola (si renovacion_automatica = 1)
        -- 'cancelada'  : el usuario canceló la renovación; sigue Premium hasta "fin"
        -- 'vencida'    : ya pasó "fin" y no se renovó
        -- 'manual'     : la dio el super admin (sin pago, sin renovación)
    inicio                 DATETIME NOT NULL,
    fin                    DATETIME NOT NULL,               -- hasta cuándo es Premium
    renovacion_automatica  TINYINT(1) NOT NULL DEFAULT 0,
    -- Cobro recurrente con Wompi (requerimiento 2). Nunca el número de la tarjeta.
    fuente_pago_id         VARCHAR(40) NULL,                -- payment_source id de Wompi
    tarjeta_marca          VARCHAR(30) NULL,
    tarjeta_ultimos4       CHAR(4) NULL,
    intentos_fallidos      INT NOT NULL DEFAULT 0,
    proximo_intento        DATETIME NULL,
    otorgada_por           INT NULL,                        -- super admin que la dio (estado 'manual')
    nota                   VARCHAR(255) NULL,
    creada_en              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizada_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_suscripcion_usuario (usuario_id, fin),
    KEY idx_suscripcion_renovacion (estado, renovacion_automatica, fin),
    CONSTRAINT fk_suscripcion_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
