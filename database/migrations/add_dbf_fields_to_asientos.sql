-- ═══════════════════════════════════════════════════════════════════════════
-- ContaFC – Migración de campos nuevos de "Resumen VF asientos.DBF"
-- Campos que existen en el DBF pero NO en la tabla `asientos`
-- Ejecutar UNA SOLA VEZ antes de correr el script de migración 2025
-- ═══════════════════════════════════════════════════════════════════════════
USE `contafc`;

-- -------------------------------------------------------------------
-- 1. Campos nuevos identificados en el DBF
-- -------------------------------------------------------------------

-- Saldo anterior de la cuenta antes de este movimiento (snapshot del DBF)
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `saldo_anterior`    DECIMAL(20,4) DEFAULT NULL
        COMMENT 'SALDANT del DBF – saldo previo al movimiento'
    AFTER `base_retencion`;

-- Saldo corriente después del movimiento
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `saldo`             DECIMAL(20,4) DEFAULT NULL
        COMMENT 'SALDO del DBF – saldo acumulado corriente'
    AFTER `saldo_anterior`;

-- Saldo final del periodo para la cuenta
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `saldo_final`       DECIMAL(20,4) DEFAULT NULL
        COMMENT 'SALDOFINAL del DBF – saldo al cierre del periodo'
    AFTER `saldo`;

-- Número de documento externo / factura relacionada (INVC en el DBF)
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `invc`              VARCHAR(8)    DEFAULT NULL
        COMMENT 'INVC del DBF – referencia de documento externo'
    AFTER `doc_cruce_num`;

-- Razón o nota adicional del asiento (campo RAZON, 150 chars en DBF)
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `razon`             VARCHAR(150)  DEFAULT NULL
        COMMENT 'RAZON del DBF – nota/razón del movimiento'
    AFTER `descripcion`;

-- Segunda descripción (DESCRIPCION2 en el DBF, 30 chars)
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `descripcion2`      VARCHAR(30)   DEFAULT NULL
        COMMENT 'DESCRIPCION2 del DBF'
    AFTER `razon`;

-- RTN/NIT del tercero al momento del asiento (snapshot, no FK)
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `nit_tercero`       VARCHAR(14)   DEFAULT NULL
        COMMENT 'NIT del DBF – RTN del tercero como snapshot'
    AFTER `descripcion2`;

-- Dirección del tercero al momento del asiento (snapshot, no FK)
ALTER TABLE `asientos`
    ADD COLUMN IF NOT EXISTS `direccion_tercero` VARCHAR(33)   DEFAULT NULL
        COMMENT 'DIRECCION1 del DBF – dirección snapshot del tercero'
    AFTER `nit_tercero`;

-- -------------------------------------------------------------------
-- 2. Índice útil para filtrar por año (útil para migración y reportes)
-- -------------------------------------------------------------------
ALTER TABLE `asientos`
    ADD INDEX IF NOT EXISTS `idx_asiento_fecha` (`empresa_id`, `fecha`);

SELECT 'Campos nuevos agregados correctamente a la tabla asientos.' AS resultado;
