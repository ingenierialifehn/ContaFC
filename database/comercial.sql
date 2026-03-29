-- ═══════════════════════════════════════════════════════════════════════════
-- ContaFC – Ecosistema Comercial (Honduras SAR Compliant)
-- Extension de Base de Datos
-- ═══════════════════════════════════════════════════════════════════════════
USE `contafc`;

SET foreign_key_checks = 0;

-- 1. CATEGORIAS DE PRODUCTOS
CREATE TABLE IF NOT EXISTS `com_categorias` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `nombre`         VARCHAR(100)      NOT NULL,
    `descripcion`    VARCHAR(250)      DEFAULT NULL,
    `activa`         TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cat_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PRODUCTOS Y SERVICIOS
CREATE TABLE IF NOT EXISTS `com_productos` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `categoria_id`   INT UNSIGNED      DEFAULT NULL,
    `codigo`         VARCHAR(50)       NOT NULL COMMENT 'SKU / Código Barras',
    `nombre`         VARCHAR(200)      NOT NULL,
    `descripcion`    TEXT              DEFAULT NULL,
    `tipo`           ENUM('producto','servicio','combo') NOT NULL DEFAULT 'producto',
    `unidad_medida`  VARCHAR(20)       NOT NULL DEFAULT 'und',
    `precio_venta`   DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `costo_promedio` DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `tasa_isv`       DECIMAL(5,2)      NOT NULL DEFAULT 15.00 COMMENT '15.00 o 18.00',
    `maneja_inventario` TINYINT(1)     NOT NULL DEFAULT 1,
    `maneja_lotes`   TINYINT(1)        NOT NULL DEFAULT 0,
    `maneja_seriales`TINYINT(1)        NOT NULL DEFAULT 0,
    
    -- CUENTAS CONTABLES PARA INTEGRACION AUTOMATICA
    `cuenta_ingreso_id` INT UNSIGNED  DEFAULT NULL,
    `cuenta_inventario_id` INT UNSIGNED DEFAULT NULL,
    `cuenta_costo_id`      INT UNSIGNED DEFAULT NULL,
    
    `stock_minimo`   DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `activo`         TINYINT(1)        NOT NULL DEFAULT 1,
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_prod_empresa_codigo` (`empresa_id`, `codigo`),
    CONSTRAINT `fk_prod_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_prod_cat`     FOREIGN KEY (`categoria_id`) REFERENCES `com_categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. RESOLUCIONES CAI (S.A.R. HONDURAS)
CREATE TABLE IF NOT EXISTS `com_cai` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `punto_emision`  VARCHAR(3)        NOT NULL COMMENT 'Ej: 001',
    `establecimiento` VARCHAR(3)       NOT NULL COMMENT 'Ej: 000',
    `tipo_documento` VARCHAR(10)       NOT NULL DEFAULT '01' COMMENT '01=Factura, 02=Recibo, etc',
    `cai`            VARCHAR(40)       NOT NULL COMMENT 'Código de Autorización de Impresión',
    `rango_desde`    INT UNSIGNED      NOT NULL,
    `rango_hasta`    INT UNSIGNED      NOT NULL,
    `consecutivo_actual` INT UNSIGNED  NOT NULL,
    `fecha_limite`   DATE              NOT NULL COMMENT 'Fecha límite de emisión',
    `activo`         TINYINT(1)        NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cai_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
  COMMENT='Configuración fiscal de facturación hondureña';

-- 4. CABECERA DE FACTURAS
CREATE TABLE IF NOT EXISTS `com_facturas` (
    `id`             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `sucursal_id`    SMALLINT UNSIGNED DEFAULT NULL,
    `cai_id`         INT UNSIGNED      NOT NULL,
    `tercero_id`     INT UNSIGNED      NOT NULL COMMENT 'Cliente',
    `tipo_pago`      ENUM('contado','credito') NOT NULL DEFAULT 'contado',
    `fecha`          DATE              NOT NULL,
    `fecha_vence`    DATE              DEFAULT NULL,
    `numero_factura` VARCHAR(20)       NOT NULL COMMENT 'Formato: 000-001-01-00000001',
    
    `subtotal_0`     DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `subtotal_15`    DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `subtotal_18`    DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `descuento`      DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `isv_15`         DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `isv_18`         DECIMAL(20,4)     NOT NULL DEFAULT 0,
    `total`          DECIMAL(20,4)     NOT NULL DEFAULT 0,
    
    `estado`         ENUM('pendiente','pagada','anulada','vencida') NOT NULL DEFAULT 'pendiente',
    `comprobante_id` BIGINT UNSIGNED   DEFAULT NULL COMMENT 'Vínculo al asiento contable',
    
    `vendedor_id`    INT UNSIGNED      DEFAULT NULL,
    `observaciones`  TEXT              DEFAULT NULL,
    `created_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_fact_empresa_num` (`empresa_id`, `numero_factura`),
    CONSTRAINT `fk_fact_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_fact_cai`     FOREIGN KEY (`cai_id`)     REFERENCES `com_cai` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. DETALLE DE FACTURAS
CREATE TABLE IF NOT EXISTS `com_facturas_detalle` (
    `id`             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `factura_id`     BIGINT UNSIGNED   NOT NULL,
    `producto_id`    INT UNSIGNED      NOT NULL,
    `cantidad`       DECIMAL(20,4)     NOT NULL,
    `precio_unitario`DECIMAL(20,4)     NOT NULL,
    `tasa_isv`       DECIMAL(5,2)      NOT NULL,
    `total_isv`      DECIMAL(20,4)     NOT NULL,
    `total_linea`    DECIMAL(20,4)     NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_det_factura` FOREIGN KEY (`factura_id`) REFERENCES `com_facturas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_det_prod`    FOREIGN KEY (`producto_id`) REFERENCES `com_productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. LOTES Y SERIALES (TRAZABILIDAD)
CREATE TABLE IF NOT EXISTS `com_trazabilidad` (
    `id`             BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `producto_id`    INT UNSIGNED      NOT NULL,
    `tipo`           ENUM('lote','serial') NOT NULL,
    `valor`          VARCHAR(100)      NOT NULL COMMENT 'Número de lote o número de serie',
    `fecha_vence`    DATE              DEFAULT NULL,
    `stock_actual`   DECIMAL(20,4)     NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_traza_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_traza_prod`    FOREIGN KEY (`producto_id`) REFERENCES `com_productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
