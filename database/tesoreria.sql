-- ─────────────────────────────────────────────────────────────────────────────
-- MÓDULO 2: TESORERÍA (ContaFC)
-- Honduras - Bancos, Conciliaciones y Recurrencia
-- ─────────────────────────────────────────────────────────────────────────────

USE `contafc`;

-- 1. Cuentas Bancarias
CREATE TABLE IF NOT EXISTS `bancos_cuentas` (
    `id`            INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`    SMALLINT UNSIGNED NOT NULL,
    `nombre`        VARCHAR(100)      NOT NULL COMMENT 'Ej: Banco Atlántida - Cuenta Principal',
    `numero_cuenta` VARCHAR(30)       NOT NULL,
    `moneda`        CHAR(3)           NOT NULL DEFAULT 'HNL',
    `cuenta_id`     INT UNSIGNED      NOT NULL COMMENT 'Relación al PUC (la cuenta de Mayor)',
    `saldo_inicial` DECIMAL(20,4)     NOT NULL DEFAULT 0.0000,
    `activa`        TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`    DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_banco_empresa`   FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_banco_cta_puc`   FOREIGN KEY (`cuenta_id`)  REFERENCES `puc_cuentas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Conciliaciones Bancarias
CREATE TABLE IF NOT EXISTS `conciliaciones` (
    `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `banco_cuenta_id` INT UNSIGNED      NOT NULL,
    `periodo_id`      INT UNSIGNED      NOT NULL,
    `fecha_corte`     DATE              NOT NULL,
    `saldo_banco`     DECIMAL(20,4)     NOT NULL DEFAULT 0.0000 COMMENT 'Saldo según extracto',
    `saldo_libros`    DECIMAL(20,4)     NOT NULL DEFAULT 0.0000 COMMENT 'Saldo según contabilidad',
    `estado`          ENUM('borrador','cerrada') NOT NULL DEFAULT 'borrador',
    `usuario_id`      INT UNSIGNED      NOT NULL,
    `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_conciliacion_banco_periodo` (`banco_cuenta_id`, `periodo_id`),
    CONSTRAINT `fk_conc_banco`   FOREIGN KEY (`banco_cuenta_id`) REFERENCES `bancos_cuentas` (`id`),
    CONSTRAINT `fk_conc_periodo` FOREIGN KEY (`periodo_id`)      REFERENCES `periodos` (`id`),
    CONSTRAINT `fk_conc_usuario` FOREIGN KEY (`usuario_id`)      REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Detalle de Conciliación (Marcación de movimientos)
CREATE TABLE IF NOT EXISTS `conciliaciones_det` (
    `id`              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `conciliacion_id` INT UNSIGNED       NOT NULL,
    `asiento_id`      BIGINT UNSIGNED   NOT NULL,
    `conciliado`      TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_conc_det_asiento` (`conciliacion_id`, `asiento_id`),
    CONSTRAINT `fk_cdet_conc`    FOREIGN KEY (`conciliacion_id`) REFERENCES `conciliaciones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cdet_asiento` FOREIGN KEY (`asiento_id`)      REFERENCES `asientos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Comprobantes Recurrentes (Plantillas)
CREATE TABLE IF NOT EXISTS `comprobantes_recurrentes` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `nombre`         VARCHAR(100)      NOT NULL COMMENT 'Ej: Alquiler Mensual Tegucigalpa',
    `frecuencia`     ENUM('mensual','quincenal','semanal') NOT NULL DEFAULT 'mensual',
    `dia_ejecucion`  TINYINT UNSIGNED  NOT NULL DEFAULT 1 COMMENT 'Día del mes/semana para sugerir',
    -- Data en JSON para clonar el comprobante (cabecera y líneas)
    `json_data`      JSON              NOT NULL,
    `activa`         TINYINT(1)        NOT NULL DEFAULT 1,
    `ultimo_procesado` DATE            DEFAULT NULL,
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_recu_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
