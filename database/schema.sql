-- ═══════════════════════════════════════════════════════════════════════════
-- ContaFC – Schema MySQL 8.0
-- Migrado desde WX-Manager / Firebird 1.5
-- Empresa: Ingeniería LIFE
-- Fecha:   2026-03-26
-- ═══════════════════════════════════════════════════════════════════════════
SET NAMES utf8mb4;
SET time_zone = '-06:00';
SET foreign_key_checks = 0;
SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS `contafc`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `contafc`;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. EMPRESAS
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `empresas` (
    `id`           SMALLINT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `codigo`       VARCHAR(20)        NOT NULL,
    `nombre`       VARCHAR(120)       NOT NULL,
    `nit`          VARCHAR(20)        NOT NULL DEFAULT '',
    `direccion`    VARCHAR(200)       DEFAULT NULL,
    `telefono`     VARCHAR(40)        DEFAULT NULL,
    `ciudad`       VARCHAR(80)        DEFAULT NULL,
    `departamento` VARCHAR(80)        DEFAULT NULL,
    `pais`         CHAR(3)            NOT NULL DEFAULT 'HND',
    `logo_path`    VARCHAR(255)       DEFAULT NULL,
    `moneda_base`  CHAR(3)            NOT NULL DEFAULT 'HNL',
    `activa`       TINYINT(1)         NOT NULL DEFAULT 1,
    `created_at`   DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_empresas_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Multi-empresa: datos de cada compañía registrada';

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. SUCURSALES
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sucursales` (
    `id`         SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `empresa_id` SMALLINT UNSIGNED NOT NULL,
    `codigo`     VARCHAR(10)       NOT NULL,
    `nombre`     VARCHAR(100)      NOT NULL,
    `activa`     TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_suc_empresa_codigo` (`empresa_id`, `codigo`),
    CONSTRAINT `fk_suc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. USUARIOS
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`   SMALLINT UNSIGNED NOT NULL,
    `username`     VARCHAR(50)       NOT NULL,
    `password_hash`VARCHAR(255)      NOT NULL,
    `nombre`       VARCHAR(100)      NOT NULL,
    `email`        VARCHAR(150)      DEFAULT NULL,
    `rol`          ENUM('admin','contador','auditor','consulta') NOT NULL DEFAULT 'consulta',
    `activo`       TINYINT(1)        NOT NULL DEFAULT 1,
    `permisos`     JSON              DEFAULT NULL COMMENT 'Módulos: dashboard,asiento,comprobantes,reportes,puc,terceros,usuarios',
    `ultimo_login` DATETIME          DEFAULT NULL,
    `created_at`   DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_usuario_global` (`username`),
    CONSTRAINT `fk_usu_emp_def` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mappings de usuarios a múltiples empresas
CREATE TABLE IF NOT EXISTS `usuarios_empresas` (
    `usuario_id` INT UNSIGNED NOT NULL,
    `empresa_id` SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (`usuario_id`, `empresa_id`),
    CONSTRAINT `fk_ue_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ue_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mappings de usuarios a proyectos
CREATE TABLE IF NOT EXISTS `usuarios_proyectos` (
    `usuario_id`  INT UNSIGNED NOT NULL,
    `proyecto_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`usuario_id`, `proyecto_id`),
    CONSTRAINT `fk_up_usuario`  FOREIGN KEY (`usuario_id`)  REFERENCES `usuarios`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_up_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. PLAN ÚNICO DE CUENTAS (PUC)
-- Estructura jerárquica: Clase > Grupo > Cuenta > Subcuenta > Auxiliar
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `puc_cuentas` (
    `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`      SMALLINT UNSIGNED NOT NULL,
    `codigo`          VARCHAR(20)       NOT NULL,
    `nombre`          VARCHAR(200)      NOT NULL,
    `nivel`           TINYINT UNSIGNED  NOT NULL DEFAULT 1
                      COMMENT '1=Clase,2=Grupo,3=Cuenta,4=Subcuenta,5=Auxiliar',
    `codigo_padre`    VARCHAR(20)       DEFAULT NULL,
    `naturaleza`      ENUM('D','C')     NOT NULL DEFAULT 'D'
                      COMMENT 'D=Débito, C=Crédito',
    `tipo_cuenta`     ENUM('A','P','R','G','O')  NOT NULL DEFAULT 'A'
                      COMMENT 'A=Activo,P=Pasivo,R=Resultado/Patrimonio,G=Gasto,O=Orden',
    `acepta_movimiento` TINYINT(1)      NOT NULL DEFAULT 0
                      COMMENT '1=Solo cuentas de nivel 4 o 5 aceptan asientos',
    `maneja_tercero`  TINYINT(1)        NOT NULL DEFAULT 0,
    `maneja_ceco`     TINYINT(1)        NOT NULL DEFAULT 0
                      COMMENT 'Centro de costos',
    `activa`          TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_puc_empresa_codigo` (`empresa_id`, `codigo`),
    KEY `idx_puc_nivel` (`empresa_id`, `nivel`),
    KEY `idx_puc_padre` (`empresa_id`, `codigo_padre`),
    KEY `idx_puc_tipo` (`tipo_cuenta`),
    CONSTRAINT `fk_puc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Plan Único de Cuentas con estructura jerárquica';

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. TERCEROS (Clientes, Proveedores, Empleados, etc.)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `terceros` (
    `id`              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`      SMALLINT UNSIGNED NOT NULL,
    `codigo`          VARCHAR(20)       NOT NULL,
    `tipo_persona`    ENUM('N','J')     NOT NULL DEFAULT 'J'
                      COMMENT 'N=Natural, J=Jurídica',
    `tipo_documento`  ENUM('RTN','DNI','PAS','CE') NOT NULL DEFAULT 'RTN',
    `nit_cc`          VARCHAR(20)       NOT NULL,
    `digito_verif`    CHAR(1)           DEFAULT NULL,
    `razon_social`    VARCHAR(200)      NOT NULL,
    `nombre1`         VARCHAR(80)       DEFAULT NULL,
    `nombre2`         VARCHAR(80)       DEFAULT NULL,
    `apellido1`       VARCHAR(80)       DEFAULT NULL,
    `apellido2`       VARCHAR(80)       DEFAULT NULL,
    `direccion`       VARCHAR(200)      DEFAULT NULL,
    `ciudad`          VARCHAR(80)       DEFAULT NULL,
    `telefono`        VARCHAR(50)       DEFAULT NULL,
    `email`           VARCHAR(150)      DEFAULT NULL,
    `tipo_tercero`    SET('cliente','proveedor','empleado','accionista','otro')
                                        NOT NULL DEFAULT 'cliente',
    `activo`          TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tercero_empresa_codigo` (`empresa_id`, `codigo`),
    KEY `idx_tercero_nit` (`empresa_id`, `nit_cc`),
    KEY `idx_tercero_nombre` (`empresa_id`, `razon_social`),
    CONSTRAINT `fk_tercero_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 6. TIPOS DE COMPROBANTE (configurables por empresa)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tipos_comprobante` (
    `id`             SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `codigo`         VARCHAR(5)        NOT NULL
                     COMMENT 'Ej: NA,CE,NC,ND,RC,CP',
    `nombre`         VARCHAR(80)       NOT NULL,
    `descripcion`    VARCHAR(200)      DEFAULT NULL,
    `consecutivo_actual` INT UNSIGNED  NOT NULL DEFAULT 0,
    `activo`         TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_tipo_comp_empresa` (`empresa_id`, `codigo`),
    CONSTRAINT `fk_tc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tipos de documento contable: Nota de Ajuste, Comprobante de Egreso, etc.';

-- ─────────────────────────────────────────────────────────────────────────────
-- 7. PERIODOS CONTABLES
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `periodos` (
    `id`          INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    `empresa_id`  SMALLINT UNSIGNED  NOT NULL,
    `anio`        YEAR               NOT NULL,
    `mes`         TINYINT UNSIGNED   NOT NULL COMMENT '1-12',
    `estado`      ENUM('abierto','cerrado','bloqueado') NOT NULL DEFAULT 'abierto',
    `fecha_cierre` DATE              DEFAULT NULL,
    `usuario_cierre_id` INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_periodo_empresa` (`empresa_id`, `anio`, `mes`),
    KEY `idx_periodo_estado` (`empresa_id`, `estado`),
    CONSTRAINT `fk_periodo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 8. CENTROS DE COSTOS
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `centros_costo` (
    `id`          INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    `empresa_id`  SMALLINT UNSIGNED  NOT NULL,
    `codigo`      VARCHAR(10)        NOT NULL,
    `nombre`      VARCHAR(100)       NOT NULL,
    `activo`      TINYINT(1)         NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ceco_empresa` (`empresa_id`, `codigo`),
    CONSTRAINT `fk_ceco_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 9. PROYECTOS
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `proyectos` (
    `id`          INT UNSIGNED       NOT NULL AUTO_INCREMENT,
    `empresa_id`  SMALLINT UNSIGNED  NOT NULL,
    `codigo`      VARCHAR(10)        NOT NULL,
    `nombre`      VARCHAR(100)       NOT NULL,
    `logo_path`   VARCHAR(255)       DEFAULT NULL,
    `activo`      TINYINT(1)         NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_proyecto_empresa` (`empresa_id`, `codigo`),
    CONSTRAINT `fk_proyecto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 10. COMPROBANTES (CABECERA)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `comprobantes` (
    `id`              BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `empresa_id`      SMALLINT UNSIGNED  NOT NULL,
    `sucursal_id`     SMALLINT UNSIGNED  DEFAULT NULL,
    `tipo_comp_id`    SMALLINT UNSIGNED  NOT NULL,
    `numero`          INT UNSIGNED       NOT NULL,
    `fecha`           DATE               NOT NULL,
    `periodo_id`      INT UNSIGNED       NOT NULL,
    `tercero_id`      INT UNSIGNED       DEFAULT NULL
                      COMMENT 'Tercero principal del comprobante',
    `total_debitos`   DECIMAL(20,4)      NOT NULL DEFAULT 0.0000,
    `total_creditos`  DECIMAL(20,4)      NOT NULL DEFAULT 0.0000,
    `diferencia`      DECIMAL(20,4)      GENERATED ALWAYS AS (`total_debitos` - `total_creditos`) STORED,
    `observaciones`   TEXT               DEFAULT NULL,
    `estado`          ENUM('borrador','registrado','anulado') NOT NULL DEFAULT 'borrador',
    `revisado`        TINYINT(1)         NOT NULL DEFAULT 0,
    `usuario_id`      INT UNSIGNED       NOT NULL,
    `usuario_anula_id` INT UNSIGNED      DEFAULT NULL,
    `fecha_anulacion` DATETIME           DEFAULT NULL,
    `moneda`          CHAR(3)            NOT NULL DEFAULT 'HNL',
    `tasa_cambio`     DECIMAL(12,6)      NOT NULL DEFAULT 1.000000,
    `created_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_comp_empresa_tipo_num` (`empresa_id`, `tipo_comp_id`, `numero`),
    KEY `idx_comp_fecha` (`empresa_id`, `fecha`),
    KEY `idx_comp_periodo` (`periodo_id`),
    KEY `idx_comp_tercero` (`empresa_id`, `tercero_id`),
    KEY `idx_comp_estado` (`empresa_id`, `estado`),
    CONSTRAINT `fk_comp_empresa`    FOREIGN KEY (`empresa_id`)   REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_comp_sucursal`   FOREIGN KEY (`sucursal_id`)  REFERENCES `sucursales` (`id`),
    CONSTRAINT `fk_comp_tipo`       FOREIGN KEY (`tipo_comp_id`) REFERENCES `tipos_comprobante` (`id`),
    CONSTRAINT `fk_comp_periodo`    FOREIGN KEY (`periodo_id`)   REFERENCES `periodos` (`id`),
    CONSTRAINT `fk_comp_tercero`    FOREIGN KEY (`tercero_id`)   REFERENCES `terceros` (`id`),
    CONSTRAINT `fk_comp_usuario`    FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cabecera del comprobante contable (journal entry header)';

-- ─────────────────────────────────────────────────────────────────────────────
-- 11. ASIENTOS CONTABLES (DETALLE / PARTIDA DOBLE)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `asientos` (
    `id`              BIGINT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `comprobante_id`  BIGINT UNSIGNED    NOT NULL,
    `empresa_id`      SMALLINT UNSIGNED  NOT NULL,
    `linea`           SMALLINT UNSIGNED  NOT NULL COMMENT 'Orden dentro del comprobante',
    `fecha`           DATE               DEFAULT NULL,
    `cuenta_id`       INT UNSIGNED       NOT NULL,
    `tercero_id`      INT UNSIGNED       DEFAULT NULL,
    `ceco_id`         INT UNSIGNED       DEFAULT NULL,
    `proyecto_id`     INT UNSIGNED       DEFAULT NULL,
    `debito`          DECIMAL(20,4)      NOT NULL DEFAULT 0.0000,
    `credito`         DECIMAL(20,4)      NOT NULL DEFAULT 0.0000,
    `descripcion`     VARCHAR(500)       DEFAULT NULL,
    -- Documento cruce (ej: factura relacionada)
    `doc_cruce_tipo`  VARCHAR(5)         DEFAULT NULL,
    `doc_cruce_num`   VARCHAR(20)        DEFAULT NULL,
    `doc_cruce_cuota` SMALLINT UNSIGNED  DEFAULT NULL,
    `vencimiento`     DATE               DEFAULT NULL,
    `base_retencion`  DECIMAL(20,4)      NOT NULL DEFAULT 0.0000,
    `conteo`          INT                DEFAULT NULL,
    `created_at`      DATETIME           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_asiento_comp_linea` (`comprobante_id`, `linea`),
    KEY `idx_asiento_cuenta` (`empresa_id`, `cuenta_id`),
    KEY `idx_asiento_tercero` (`empresa_id`, `tercero_id`),
    KEY `idx_asiento_fecha_cuenta` (`empresa_id`, `cuenta_id`),
    CONSTRAINT `fk_asiento_comp`     FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_asiento_empresa`  FOREIGN KEY (`empresa_id`)     REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_asiento_cuenta`   FOREIGN KEY (`cuenta_id`)      REFERENCES `puc_cuentas` (`id`),
    CONSTRAINT `fk_asiento_tercero`  FOREIGN KEY (`tercero_id`)     REFERENCES `terceros` (`id`),
    CONSTRAINT `fk_asiento_ceco`     FOREIGN KEY (`ceco_id`)        REFERENCES `centros_costo` (`id`),
    CONSTRAINT `fk_asiento_proyecto` FOREIGN KEY (`proyecto_id`)    REFERENCES `proyectos` (`id`),
    CONSTRAINT `chk_asiento_pd`      CHECK (`debito` >= 0 AND `credito` >= 0),
    CONSTRAINT `chk_asiento_pd2`     CHECK (NOT (`debito` > 0 AND `credito` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Líneas de la partida doble de cada comprobante';

-- ─────────────────────────────────────────────────────────────────────────────
-- 12. SALDOS (Tabla de saldos por cuenta/periodo – optimización reporting)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `saldos_periodo` (
    `id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `empresa_id`  SMALLINT UNSIGNED NOT NULL,
    `periodo_id`  INT UNSIGNED      NOT NULL,
    `cuenta_id`   INT UNSIGNED      NOT NULL,
    `total_debito`  DECIMAL(20,4)   NOT NULL DEFAULT 0.0000,
    `total_credito` DECIMAL(20,4)   NOT NULL DEFAULT 0.0000,
    `saldo`         DECIMAL(20,4)   GENERATED ALWAYS AS (`total_debito` - `total_credito`) STORED,
    `updated_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_saldo_empresa_periodo_cuenta` (`empresa_id`, `periodo_id`, `cuenta_id`),
    KEY `idx_saldo_cuenta` (`empresa_id`, `cuenta_id`),
    CONSTRAINT `fk_saldo_empresa`  FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_saldo_periodo`  FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
    CONSTRAINT `fk_saldo_cuenta`   FOREIGN KEY (`cuenta_id`)  REFERENCES `puc_cuentas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pre-cálculo de saldos por periodo para reportes de Balance';

-- ─────────────────────────────────────────────────────────────────────────────
-- 13. AUDIT LOG
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id`          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `empresa_id`  SMALLINT UNSIGNED NOT NULL,
    `usuario_id`  INT UNSIGNED      DEFAULT NULL,
    `tabla`       VARCHAR(60)       NOT NULL,
    `registro_id` BIGINT            NOT NULL,
    `accion`      ENUM('INSERT','UPDATE','DELETE','ANULAR','LOGIN','LOGOUT') NOT NULL,
    `datos_antes` JSON              DEFAULT NULL,
    `datos_des`   JSON              DEFAULT NULL,
    `ip`          VARCHAR(45)       DEFAULT NULL,
    `created_at`  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_empresa_tabla` (`empresa_id`, `tabla`),
    KEY `idx_audit_usuario` (`usuario_id`),
    KEY `idx_audit_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- 14. TRIGGER: Actualizar total_debitos / total_creditos en comprobante
-- ─────────────────────────────────────────────────────────────────────────────
DELIMITER //

CREATE TRIGGER `trg_asiento_after_insert`
AFTER INSERT ON `asientos`
FOR EACH ROW
BEGIN
    UPDATE `comprobantes`
    SET `total_debitos`  = (SELECT COALESCE(SUM(debito), 0)  FROM asientos WHERE comprobante_id = NEW.comprobante_id),
        `total_creditos` = (SELECT COALESCE(SUM(credito), 0) FROM asientos WHERE comprobante_id = NEW.comprobante_id)
    WHERE id = NEW.comprobante_id;
END//

CREATE TRIGGER `trg_asiento_after_update`
AFTER UPDATE ON `asientos`
FOR EACH ROW
BEGIN
    UPDATE `comprobantes`
    SET `total_debitos`  = (SELECT COALESCE(SUM(debito), 0)  FROM asientos WHERE comprobante_id = NEW.comprobante_id),
        `total_creditos` = (SELECT COALESCE(SUM(credito), 0) FROM asientos WHERE comprobante_id = NEW.comprobante_id)
    WHERE id = NEW.comprobante_id;
END//

CREATE TRIGGER `trg_asiento_after_delete`
AFTER DELETE ON `asientos`
FOR EACH ROW
BEGIN
    UPDATE `comprobantes`
    SET `total_debitos`  = (SELECT COALESCE(SUM(debito), 0)  FROM asientos WHERE comprobante_id = OLD.comprobante_id),
        `total_creditos` = (SELECT COALESCE(SUM(credito), 0) FROM asientos WHERE comprobante_id = OLD.comprobante_id)
    WHERE id = OLD.comprobante_id;
END//

DELIMITER ;

SET foreign_key_checks = 1;
