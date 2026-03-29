-- ============================================================
-- ContaFC – Migración: Módulo Cartera y Recaudos
-- Ejecutar en la base de datos: contafc
-- Fecha: 2026-03-27
-- ============================================================

CREATE TABLE IF NOT EXISTS `cartera_creditos` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `empresa_id`     INT NOT NULL,
    `tercero_id`     INT NOT NULL,
    `referencia_doc` VARCHAR(80)  DEFAULT NULL COMMENT 'Nro. Lote, Factura, Exp.',
    `descripcion`    TEXT         DEFAULT NULL,
    `valor_total`    DECIMAL(18,2) NOT NULL,
    `saldo_actual`   DECIMAL(18,2) NOT NULL,
    `tasa_interes`   DECIMAL(5,2)  NOT NULL DEFAULT 0.00 COMMENT 'Tasa anual %',
    `cuotas_totales` SMALLINT      NOT NULL,
    `frecuencia`     ENUM('mensual','quincenal','semanal') NOT NULL DEFAULT 'mensual',
    `fecha_inicio`   DATE          NOT NULL,
    `estado`         ENUM('activo','liquidado','anulado')  NOT NULL DEFAULT 'activo',
    `usuario_id`     INT           DEFAULT NULL,
    `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_empresa`  (`empresa_id`),
    INDEX `idx_tercero`  (`tercero_id`),
    INDEX `idx_estado`   (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Créditos y planes de amortización';

-- ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `cartera_cuotas` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `credito_id`        INT NOT NULL,
    `num_cuota`         SMALLINT    NOT NULL,
    `fecha_vencimiento` DATE        NOT NULL,
    `valor_capital`     DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `valor_interes`     DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `valor_pagado`      DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    `estado`            ENUM('pendiente','parcial','pagado','mora') NOT NULL DEFAULT 'pendiente',
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_credito`    (`credito_id`),
    INDEX `idx_vencimiento`(`fecha_vencimiento`),
    CONSTRAINT `fk_cuota_credito`
        FOREIGN KEY (`credito_id`) REFERENCES `cartera_creditos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla de amortización por crédito';

-- ──────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `cartera_recaudos` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `empresa_id`     INT NOT NULL,
    `tercero_id`     INT NOT NULL,
    `credito_id`     INT DEFAULT NULL COMMENT 'Si aplica a un crédito específico',
    `fecha`          DATE          NOT NULL,
    `valor_total`    DECIMAL(18,2) NOT NULL,
    `glosa`          VARCHAR(255)  DEFAULT NULL,
    `metodo_pago`    ENUM('efectivo','transferencia','cheque','tarjeta') NOT NULL DEFAULT 'efectivo',
    `comprobante_id` INT DEFAULT NULL,
    `usuario_id`     INT DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_empresa`  (`empresa_id`),
    INDEX `idx_tercero`  (`tercero_id`),
    INDEX `idx_credito`  (`credito_id`),
    INDEX `idx_fecha`    (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Recaudos y abonos de cartera';

-- ──────────────────────────────────────────────────────────────
-- Agregar permiso 'cartera' a todos los administradores existentes
-- ──────────────────────────────────────────────────────────────
UPDATE `usuarios`
SET `permisos` = JSON_SET(
    COALESCE(`permisos`, '{}'),
    '$.cartera', TRUE
)
WHERE `rol` = 'admin'
  AND (JSON_EXTRACT(`permisos`, '$.cartera') IS NULL);
