-- =============================================================
-- BiGlobal · Racha en la zona horaria del usuario y protectores (req. 12)
-- =============================================================
-- usuarios.zona_horaria      : zona del estudiante (la detecta el navegador
--                              o la elige en Mi perfil). Por defecto Colombia.
-- usuarios.zona_cambiada     : día del último cambio (solo 1 cambio al día).
-- estudiante_stats.racha_hasta     : último día cubierto (estudió o usó protector).
-- estudiante_stats.protectores     : protectores que le quedan este mes (0–2).
-- estudiante_stats.protectores_mes : mes de esos protectores ("2026-10").
--                                    El día 1 vuelven a ser 2 (no se acumulan).
-- racha_dias  : un día por fila: 'estudio' o 'protector' (para la semana del inicio).
--
-- No borra datos. Se puede ejecutar más de una vez.
-- =============================================================

SET NAMES utf8mb4;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS zona_horaria VARCHAR(40) NOT NULL DEFAULT 'America/Bogota';
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS zona_cambiada DATE NULL;

ALTER TABLE estudiante_stats ADD COLUMN IF NOT EXISTS racha_hasta DATE NULL;
ALTER TABLE estudiante_stats ADD COLUMN IF NOT EXISTS protectores TINYINT NOT NULL DEFAULT 2;
ALTER TABLE estudiante_stats ADD COLUMN IF NOT EXISTS protectores_mes CHAR(7) NULL;

-- Nadie pierde su racha: el día cubierto es el último en que estudió
UPDATE estudiante_stats SET racha_hasta = ultima_actividad WHERE racha_hasta IS NULL AND ultima_actividad IS NOT NULL;
-- Todos empiezan con 2 protectores este mes
UPDATE estudiante_stats SET protectores = 2, protectores_mes = DATE_FORMAT(NOW(), '%Y-%m') WHERE protectores_mes IS NULL;

CREATE TABLE IF NOT EXISTS racha_dias (
    usuario_id  INT NOT NULL,
    fecha       DATE NOT NULL,
    tipo        VARCHAR(10) NOT NULL,          -- 'estudio' | 'protector'
    PRIMARY KEY (usuario_id, fecha),
    CONSTRAINT fk_racha_dia_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Los días de estudio de las últimas dos semanas (para que la semana no salga vacía)
INSERT IGNORE INTO racha_dias (usuario_id, fecha, tipo)
SELECT DISTINCT usuario_id, DATE(fecha), 'estudio'
FROM xp_movimientos
WHERE fecha >= NOW() - INTERVAL 14 DAY AND puntos > 0;
