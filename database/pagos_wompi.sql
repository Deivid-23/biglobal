-- =============================================================
-- BiGlobal · Pagos con Wompi (plan Premium)
-- =============================================================
-- pagos          : un intento de cobro (primer pago o renovación).
-- wompi_eventos  : cada aviso (webhook) que manda Wompi, para no
--                  procesar dos veces el mismo y poder revisar qué pasó.
-- suscripciones  : 1 columna nueva para no repetir el recordatorio de
--                  renovación de los pagos únicos (PSE, Nequi, Bancolombia).
--
-- Seguridad: aquí NUNCA hay datos de tarjeta. Solo el id de la fuente de
-- pago tokenizada de Wompi, la marca y los últimos 4 dígitos.
-- El Premium se activa SOLO cuando llega el webhook firmado de Wompi.
--
-- Solo crea tablas y una columna NUEVAS: no toca datos existentes.
-- Si se corre dos veces, el ALTER da "Duplicate column": se puede ignorar.
-- =============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS pagos (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id             INT NOT NULL,
    suscripcion_id         INT NULL,                 -- se llena al aprobarse (o desde el inicio en una renovación)
    plan                   VARCHAR(30) NOT NULL,     -- 'premium_mensual' | 'premium_anual'
    tipo                   VARCHAR(20) NOT NULL,     -- 'inicial' | 'renovacion'
    canal                  VARCHAR(20) NOT NULL,     -- 'tarjeta' (renovación automática) | 'checkout' (PSE, Nequi, Bancolombia)
    metodo                 VARCHAR(30) NULL,         -- lo que diga Wompi: CARD, PSE, NEQUI, BANCOLOMBIA_TRANSFER...
    referencia             VARCHAR(60) NOT NULL,     -- única; la mandamos a Wompi
    centavos               INT NOT NULL,             -- 1990000 = $19.900
    moneda                 CHAR(3) NOT NULL DEFAULT 'COP',
    estado                 VARCHAR(20) NOT NULL DEFAULT 'PENDING',   -- PENDING | APPROVED | DECLINED | VOIDED | ERROR
    wompi_transaccion_id   VARCHAR(60) NULL,
    fuente_pago_id         VARCHAR(40) NULL,
    tarjeta_marca          VARCHAR(30) NULL,
    tarjeta_ultimos4       CHAR(4) NULL,
    mensaje                VARCHAR(255) NULL,        -- motivo que da Wompi si se rechaza
    -- Aceptación de los términos de Wompi (obligatoria)
    token_aceptacion       TEXT NULL,
    token_datos_personales TEXT NULL,
    aceptado_en            DATETIME NULL,
    aceptado_ip            VARCHAR(45) NULL,
    aplicado               TINYINT(1) NOT NULL DEFAULT 0,   -- 1 = ya se activó/extendió el Premium por este pago
    creado_en              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pago_referencia (referencia),
    KEY idx_pago_usuario (usuario_id, creado_en),
    KEY idx_pago_suscripcion (suscripcion_id, estado),
    CONSTRAINT fk_pago_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wompi_eventos (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    evento        VARCHAR(60) NOT NULL,              -- 'transaction.updated'
    checksum      CHAR(64) NOT NULL,                 -- firma del evento: también evita procesarlo dos veces
    referencia    VARCHAR(60) NULL,
    estado        VARCHAR(20) NULL,
    valido        TINYINT(1) NOT NULL DEFAULT 0,     -- 1 = la firma era correcta
    resultado     VARCHAR(255) NULL,                 -- qué hizo BiGlobal con el evento
    cuerpo        MEDIUMTEXT NULL,                   -- el JSON tal como llegó (Wompi no manda datos de tarjeta)
    recibido_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_checksum (checksum),
    KEY idx_evento_referencia (referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE suscripciones ADD COLUMN recordatorio_enviado_en DATETIME NULL DEFAULT NULL AFTER proximo_intento;
