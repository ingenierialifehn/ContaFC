-- ─────────────────────────────────────────────────────────────────────────────
-- MÓDULO 4: CERTIFICADOS DE RETENCIÓN (HONDURAS)
-- SAR - Retenciones de ISV (1% / 15%) y Retención en la Fuente
-- ─────────────────────────────────────────────────────────────────────────────

USE `contafc`;

-- 1. Catálogo de Tipos de Retención (Honduras)
CREATE TABLE IF NOT EXISTS `tipos_retencion` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `codigo`         VARCHAR(10)       NOT NULL COMMENT 'Codificación SAR',
    `nombre`         VARCHAR(100)      NOT NULL,
    `porcentaje`     DECIMAL(5,2)      NOT NULL DEFAULT 0.00,
    `tipo`           ENUM('ISV','FUENTE') NOT NULL DEFAULT 'FUENTE',
    `cuenta_id`      INT UNSIGNED      DEFAULT NULL COMMENT 'Cuenta contable relacionada en el PUC',
    `activa`         TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_ret_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `puc_cuentas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Maestro de Certificados Emitidos
CREATE TABLE IF NOT EXISTS `certificados_retencion` (
    `id`                BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `empresa_id`        SMALLINT UNSIGNED  NOT NULL,
    `tercero_id`        INT UNSIGNED       NOT NULL,
    `comprobante_id`    BIGINT UNSIGNED    DEFAULT NULL COMMENT 'Vínculo al asiento de diario donde se originó',
    `tipo_retencion_id` INT UNSIGNED       NOT NULL,
    `correlativo`       VARCHAR(30)        NOT NULL COMMENT 'Número de certificado emitido',
    `fecha`             DATE               NOT NULL,
    `base_imponible`    DECIMAL(20,4)      NOT NULL DEFAULT 0.0000,
    `porcentaje`        DECIMAL(5,2)       NOT NULL,
    `monto_retencion`   DECIMAL(20,4)      NOT NULL,
    -- Datos adicionales del emisor/receptor para reporte
    `comentarios`       TEXT               DEFAULT NULL,
    `created_at`        DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cert_emp_corr` (`empresa_id`, `correlativo`),
    CONSTRAINT `fk_cert_emp`    FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_cert_ter`    FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`),
    CONSTRAINT `fk_cert_tipo`   FOREIGN KEY (`tipo_retencion_id`) REFERENCES `tipos_retencion` (`id`),
    CONSTRAINT `fk_cert_asiento` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Semilla de tipos de retención comunes en Honduras
INSERT INTO `tipos_retencion` (`codigo`, `nombre`, `porcentaje`, `tipo`) VALUES 
('R15', 'Retención de ISV (15%) - Régimen de Pagos', 15.00, 'ISV'),
('R01', 'Retención de ISV (1%) - Proveedores del Estado', 1.00, 'ISV'),
('RF1', 'Retención en la Fuente - Honorarios Profesionales', 10.00, 'FUENTE'),
('RF2', 'Retención en la Fuente - Comisiones', 12.50, 'FUENTE'),
('RF3', 'Retención en la Fuente - Alquileres', 10.00, 'FUENTE');
