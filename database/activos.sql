-- ─────────────────────────────────────────────────────────────────────────────
-- MÓDULO 1: ACTIVOS FIJOS (ContaFC)
-- Honduras - NIIF para PYMES
-- ─────────────────────────────────────────────────────────────────────────────

USE `contafc`;

-- 1. Tabla de Activos Fijos
CREATE TABLE IF NOT EXISTS `activos_fijos` (
    `id`                    INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`            SMALLINT UNSIGNED NOT NULL,
    `sucursal_id`           SMALLINT UNSIGNED DEFAULT NULL,
    `codigo`                VARCHAR(20)       NOT NULL,
    `nombre`                VARCHAR(120)      NOT NULL,
    `descripcion`           TEXT              DEFAULT NULL,
    `fecha_compra`          DATE              NOT NULL,
    `costo_adquisicion`     DECIMAL(20,4)     NOT NULL DEFAULT 0.0000,
    `valor_salvamento`      DECIMAL(20,4)     NOT NULL DEFAULT 0.0000,
    `vida_util_meses`       SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    -- Contabilidad
    `cuenta_activo_id`      INT UNSIGNED      NOT NULL COMMENT 'Cuenta 15xx Activo Fijo',
    `cuenta_deprec_acum_id` INT UNSIGNED      NOT NULL COMMENT 'Cuenta 159x Depreciación Acumulada',
    `cuenta_gasto_deprec_id` INT UNSIGNED     NOT NULL COMMENT 'Cuenta 510x o 520x Gasto Depreciación',
    `ceco_id`               INT UNSIGNED      DEFAULT NULL,
    -- Estado
    `depreciacion_mensual`  DECIMAL(20,4)     GENERATED ALWAYS AS ((`costo_adquisicion` - `valor_salvamento`) / NULLIF(`vida_util_meses`, 0)) STORED,
    `depreciacion_acumulada` DECIMAL(20,4)    NOT NULL DEFAULT 0.0000,
    `estado`                ENUM('activo','total_depreciado','retirado','vendido') NOT NULL DEFAULT 'activo',
    `created_at`            DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_activo_empresa_codigo` (`empresa_id`, `codigo`),
    CONSTRAINT `fk_activo_empresa`   FOREIGN KEY (`empresa_id`)   REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_activo_sucursal`  FOREIGN KEY (`sucursal_id`)  REFERENCES `sucursales` (`id`),
    CONSTRAINT `fk_activo_cta_act`   FOREIGN KEY (`cuenta_activo_id`)      REFERENCES `puc_cuentas` (`id`),
    CONSTRAINT `fk_activo_cta_dep`   FOREIGN KEY (`cuenta_deprec_acum_id`) REFERENCES `puc_cuentas` (`id`),
    CONSTRAINT `fk_activo_cta_gas`   FOREIGN KEY (`cuenta_gasto_deprec_id`) REFERENCES `puc_cuentas` (`id`),
    CONSTRAINT `fk_activo_ceco`      FOREIGN KEY (`ceco_id`)      REFERENCES `centros_costo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Historial de Depreciaciones Mensuales
CREATE TABLE IF NOT EXISTS `activos_depreciaciones` (
    `id`             BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `activo_id`      INT UNSIGNED       NOT NULL,
    `periodo_id`     INT UNSIGNED       NOT NULL,
    `comprobante_id` BIGINT UNSIGNED    DEFAULT NULL COMMENT 'Vínculo al asiento contable generado',
    `valor`          DECIMAL(20,4)      NOT NULL,
    `fecha_proceso`  DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_deprec_periodo` (`activo_id`, `periodo_id`),
    CONSTRAINT `fk_deprec_activo`  FOREIGN KEY (`activo_id`)  REFERENCES `activos_fijos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_deprec_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
    CONSTRAINT `fk_deprec_comp`    FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
