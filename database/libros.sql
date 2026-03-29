-- ─────────────────────────────────────────────────────────────────────────────
-- MÓDULO 5: LIBROS OFICIALES (HONDURAS)
-- Libro Diario, Libro Mayor y Libro de Inventarios y Balances
-- ─────────────────────────────────────────────────────────────────────────────

USE `contafc`;

-- 1. Control de Folios de Libros Autorizados
CREATE TABLE IF NOT EXISTS `libros_folios` (
    `id`                    INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`            SMALLINT UNSIGNED NOT NULL,
    `libro_tipo`            ENUM('DIARIO','MAYOR','INVENTARIOS') NOT NULL,
    `folio_inicial`         INT UNSIGNED      NOT NULL DEFAULT 1,
    `ultimo_folio_usado`    INT UNSIGNED      NOT NULL DEFAULT 0,
    `autorizacion_sar`      VARCHAR(50)       DEFAULT NULL COMMENT 'N° de Resolución si aplica',
    `updated_at`            DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_libro_empresa` (`empresa_id`, `libro_tipo`),
    CONSTRAINT `fk_libro_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Registro de Cierres de Libros por Período
CREATE TABLE IF NOT EXISTS `libros_periodos` (
    `id`             INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    `empresa_id`     SMALLINT UNSIGNED NOT NULL,
    `periodo_id`     INT UNSIGNED       NOT NULL,
    `libro_tipo`     ENUM('DIARIO','MAYOR','INVENTARIOS') NOT NULL,
    `folio_desde`    INT UNSIGNED       NOT NULL,
    `folio_hasta`    INT UNSIGNED       NOT NULL,
    `fecha_impresion` DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_libro_periodo` (`empresa_id`, `periodo_id`, `libro_tipo`),
    CONSTRAINT `fk_lp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
    CONSTRAINT `fk_lp_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Semilla básica para una empresa (opcional, se creará al configurar por primera vez)
