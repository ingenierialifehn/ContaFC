-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Mar 27, 2026 at 06:05 PM
-- Server version: 10.6.24-MariaDB-ubu2204
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `contafc`
--

-- --------------------------------------------------------

--
-- Table structure for table `activos_depreciaciones`
--

CREATE TABLE `activos_depreciaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activo_id` int(10) UNSIGNED NOT NULL,
  `periodo_id` int(10) UNSIGNED NOT NULL,
  `comprobante_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Vínculo al asiento contable generado',
  `valor` decimal(20,4) NOT NULL,
  `fecha_proceso` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activos_fijos`
--

CREATE TABLE `activos_fijos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `sucursal_id` smallint(5) UNSIGNED DEFAULT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_compra` date NOT NULL,
  `costo_adquisicion` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `valor_salvamento` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `vida_util_meses` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `cuenta_activo_id` int(10) UNSIGNED NOT NULL COMMENT 'Cuenta 15xx Activo Fijo',
  `cuenta_deprec_acum_id` int(10) UNSIGNED NOT NULL COMMENT 'Cuenta 159x Depreciación Acumulada',
  `cuenta_gasto_deprec_id` int(10) UNSIGNED NOT NULL COMMENT 'Cuenta 510x o 520x Gasto Depreciación',
  `ceco_id` int(10) UNSIGNED DEFAULT NULL,
  `depreciacion_mensual` decimal(20,4) GENERATED ALWAYS AS ((`costo_adquisicion` - `valor_salvamento`) / nullif(`vida_util_meses`,0)) STORED,
  `depreciacion_acumulada` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `estado` enum('activo','total_depreciado','retirado','vendido') NOT NULL DEFAULT 'activo',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asientos`
--

CREATE TABLE `asientos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `comprobante_id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `linea` smallint(5) UNSIGNED NOT NULL COMMENT 'Orden dentro del comprobante',
  `cuenta_id` int(10) UNSIGNED NOT NULL,
  `tercero_id` int(10) UNSIGNED DEFAULT NULL,
  `ceco_id` int(10) UNSIGNED DEFAULT NULL,
  `proyecto_id` int(10) UNSIGNED DEFAULT NULL,
  `debito` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `credito` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `descripcion` varchar(500) DEFAULT NULL,
  `doc_cruce_tipo` varchar(5) DEFAULT NULL,
  `doc_cruce_num` varchar(20) DEFAULT NULL,
  `doc_cruce_cuota` smallint(5) UNSIGNED DEFAULT NULL,
  `vencimiento` date DEFAULT NULL,
  `base_retencion` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Triggers `asientos`
--
DELIMITER $$
CREATE TRIGGER `trg_asiento_after_delete` AFTER DELETE ON `asientos` FOR EACH ROW BEGIN
    UPDATE `comprobantes`
    SET `total_debitos`  = (SELECT COALESCE(SUM(debito), 0)  FROM asientos WHERE comprobante_id = OLD.comprobante_id),
        `total_creditos` = (SELECT COALESCE(SUM(credito), 0) FROM asientos WHERE comprobante_id = OLD.comprobante_id)
    WHERE id = OLD.comprobante_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_asiento_after_insert` AFTER INSERT ON `asientos` FOR EACH ROW BEGIN
    UPDATE `comprobantes`
    SET `total_debitos`  = (SELECT COALESCE(SUM(debito), 0)  FROM asientos WHERE comprobante_id = NEW.comprobante_id),
        `total_creditos` = (SELECT COALESCE(SUM(credito), 0) FROM asientos WHERE comprobante_id = NEW.comprobante_id)
    WHERE id = NEW.comprobante_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_asiento_after_update` AFTER UPDATE ON `asientos` FOR EACH ROW BEGIN
    UPDATE `comprobantes`
    SET `total_debitos`  = (SELECT COALESCE(SUM(debito), 0)  FROM asientos WHERE comprobante_id = NEW.comprobante_id),
        `total_creditos` = (SELECT COALESCE(SUM(credito), 0) FROM asientos WHERE comprobante_id = NEW.comprobante_id)
    WHERE id = NEW.comprobante_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `tabla` varchar(60) NOT NULL,
  `registro_id` bigint(20) NOT NULL,
  `accion` enum('INSERT','UPDATE','DELETE','ANULAR','LOGIN','LOGOUT') NOT NULL,
  `datos_antes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_antes`)),
  `datos_des` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_des`)),
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bancos_cuentas`
--

CREATE TABLE `bancos_cuentas` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Ej: Banco Atlántida - Cuenta Principal',
  `numero_cuenta` varchar(30) NOT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'HNL',
  `cuenta_id` int(10) UNSIGNED NOT NULL COMMENT 'Relación al PUC (la cuenta de Mayor)',
  `saldo_inicial` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cartera_creditos`
--

CREATE TABLE `cartera_creditos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tercero_id` int(11) NOT NULL,
  `referencia_doc` varchar(80) DEFAULT NULL COMMENT 'Nro. Lote, Factura, Exp.',
  `descripcion` text DEFAULT NULL,
  `valor_total` decimal(18,2) NOT NULL,
  `saldo_actual` decimal(18,2) NOT NULL,
  `tasa_interes` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Tasa anual %',
  `cuotas_totales` smallint(6) NOT NULL,
  `frecuencia` enum('mensual','quincenal','semanal') NOT NULL DEFAULT 'mensual',
  `fecha_inicio` date NOT NULL,
  `estado` enum('activo','liquidado','anulado') NOT NULL DEFAULT 'activo',
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Créditos y planes de amortización';

-- --------------------------------------------------------

--
-- Table structure for table `cartera_cuotas`
--

CREATE TABLE `cartera_cuotas` (
  `id` int(11) NOT NULL,
  `credito_id` int(11) NOT NULL,
  `num_cuota` smallint(6) NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `valor_capital` decimal(18,2) NOT NULL DEFAULT 0.00,
  `valor_interes` decimal(18,2) NOT NULL DEFAULT 0.00,
  `valor_pagado` decimal(18,2) NOT NULL DEFAULT 0.00,
  `estado` enum('pendiente','parcial','pagado','mora') NOT NULL DEFAULT 'pendiente',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla de amortización por crédito';

-- --------------------------------------------------------

--
-- Table structure for table `cartera_recaudos`
--

CREATE TABLE `cartera_recaudos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tercero_id` int(11) NOT NULL,
  `credito_id` int(11) DEFAULT NULL COMMENT 'Si aplica a un crédito específico',
  `fecha` date NOT NULL,
  `valor_total` decimal(18,2) NOT NULL,
  `glosa` varchar(255) DEFAULT NULL,
  `metodo_pago` enum('efectivo','transferencia','cheque','tarjeta') NOT NULL DEFAULT 'efectivo',
  `comprobante_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Recaudos y abonos de cartera';

-- --------------------------------------------------------

--
-- Table structure for table `centros_costo`
--

CREATE TABLE `centros_costo` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `centros_costo`
--

INSERT INTO `centros_costo` (`id`, `empresa_id`, `codigo`, `nombre`, `activo`) VALUES
(1, 1, 'GEN', 'General', 1),
(2, 1, 'ADM', 'Administración', 1),
(3, 1, 'VEN', 'Ventas', 1),
(4, 1, 'OPE', 'Operaciones', 1);

-- --------------------------------------------------------

--
-- Table structure for table `certificados_retencion`
--

CREATE TABLE `certificados_retencion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `tercero_id` int(10) UNSIGNED NOT NULL,
  `comprobante_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Vínculo al asiento de diario donde se originó',
  `tipo_retencion_id` int(10) UNSIGNED NOT NULL,
  `correlativo` varchar(30) NOT NULL COMMENT 'Número de certificado emitido',
  `fecha` date NOT NULL,
  `base_imponible` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `porcentaje` decimal(5,2) NOT NULL,
  `monto_retencion` decimal(20,4) NOT NULL,
  `comentarios` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comprobantes`
--

CREATE TABLE `comprobantes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `sucursal_id` smallint(5) UNSIGNED DEFAULT NULL,
  `tipo_comp_id` smallint(5) UNSIGNED NOT NULL,
  `numero` int(10) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `periodo_id` int(10) UNSIGNED NOT NULL,
  `tercero_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Tercero principal del comprobante',
  `total_debitos` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_creditos` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `diferencia` decimal(20,4) GENERATED ALWAYS AS (`total_debitos` - `total_creditos`) STORED,
  `observaciones` text DEFAULT NULL,
  `estado` enum('borrador','registrado','anulado') NOT NULL DEFAULT 'borrador',
  `revisado` tinyint(1) NOT NULL DEFAULT 0,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `usuario_anula_id` int(10) UNSIGNED DEFAULT NULL,
  `fecha_anulacion` datetime DEFAULT NULL,
  `moneda` char(3) NOT NULL DEFAULT 'COP',
  `tasa_cambio` decimal(12,6) NOT NULL DEFAULT 1.000000,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cabecera del comprobante contable (journal entry header)';

-- --------------------------------------------------------

--
-- Table structure for table `comprobantes_recurrentes`
--

CREATE TABLE `comprobantes_recurrentes` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL COMMENT 'Ej: Alquiler Mensual Tegucigalpa',
  `frecuencia` enum('mensual','quincenal','semanal') NOT NULL DEFAULT 'mensual',
  `dia_ejecucion` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Día del mes/semana para sugerir',
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json_data`)),
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_procesado` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_cai`
--

CREATE TABLE `com_cai` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `punto_emision` varchar(3) NOT NULL COMMENT 'Ej: 001',
  `establecimiento` varchar(3) NOT NULL COMMENT 'Ej: 000',
  `tipo_documento` varchar(10) NOT NULL DEFAULT '01' COMMENT '01=Factura, 02=Recibo, etc',
  `cai` varchar(40) NOT NULL COMMENT 'Código de Autorización de Impresión',
  `rango_desde` int(10) UNSIGNED NOT NULL,
  `rango_hasta` int(10) UNSIGNED NOT NULL,
  `consecutivo_actual` int(10) UNSIGNED NOT NULL,
  `fecha_limite` date NOT NULL COMMENT 'Fecha límite de emisión',
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuración fiscal de facturación hondureña';

-- --------------------------------------------------------

--
-- Table structure for table `com_categorias`
--

CREATE TABLE `com_categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_facturas`
--

CREATE TABLE `com_facturas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `sucursal_id` smallint(5) UNSIGNED DEFAULT NULL,
  `cai_id` int(10) UNSIGNED NOT NULL,
  `tercero_id` int(10) UNSIGNED NOT NULL COMMENT 'Cliente',
  `tipo_pago` enum('contado','credito') NOT NULL DEFAULT 'contado',
  `fecha` date NOT NULL,
  `fecha_vence` date DEFAULT NULL,
  `numero_factura` varchar(20) NOT NULL COMMENT 'Formato: 000-001-01-00000001',
  `subtotal_0` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subtotal_15` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `subtotal_18` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `descuento` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `isv_15` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `isv_18` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `estado` enum('pendiente','pagada','anulada','vencida') NOT NULL DEFAULT 'pendiente',
  `comprobante_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Vínculo al asiento contable',
  `vendedor_id` int(10) UNSIGNED DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_facturas_detalle`
--

CREATE TABLE `com_facturas_detalle` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `factura_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `cantidad` decimal(20,4) NOT NULL,
  `precio_unitario` decimal(20,4) NOT NULL,
  `tasa_isv` decimal(5,2) NOT NULL,
  `total_isv` decimal(20,4) NOT NULL,
  `total_linea` decimal(20,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_productos`
--

CREATE TABLE `com_productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `codigo` varchar(50) NOT NULL COMMENT 'SKU / Código Barras',
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('producto','servicio','combo') NOT NULL DEFAULT 'producto',
  `unidad_medida` varchar(20) NOT NULL DEFAULT 'und',
  `precio_venta` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `costo_promedio` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `tasa_isv` decimal(5,2) NOT NULL DEFAULT 15.00 COMMENT '15.00 o 18.00',
  `maneja_inventario` tinyint(1) NOT NULL DEFAULT 1,
  `maneja_lotes` tinyint(1) NOT NULL DEFAULT 0,
  `maneja_seriales` tinyint(1) NOT NULL DEFAULT 0,
  `cuenta_ingreso_id` int(10) UNSIGNED DEFAULT NULL,
  `cuenta_inventario_id` int(10) UNSIGNED DEFAULT NULL,
  `cuenta_costo_id` int(10) UNSIGNED DEFAULT NULL,
  `stock_minimo` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_trazabilidad`
--

CREATE TABLE `com_trazabilidad` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('lote','serial') NOT NULL,
  `valor` varchar(100) NOT NULL COMMENT 'Número de lote o número de serie',
  `fecha_vence` date DEFAULT NULL,
  `stock_actual` decimal(20,4) NOT NULL DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conciliaciones`
--

CREATE TABLE `conciliaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `banco_cuenta_id` int(10) UNSIGNED NOT NULL,
  `periodo_id` int(10) UNSIGNED NOT NULL,
  `fecha_corte` date NOT NULL,
  `saldo_banco` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'Saldo según extracto',
  `saldo_libros` decimal(20,4) NOT NULL DEFAULT 0.0000 COMMENT 'Saldo según contabilidad',
  `estado` enum('borrador','cerrada') NOT NULL DEFAULT 'borrador',
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conciliaciones_det`
--

CREATE TABLE `conciliaciones_det` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conciliacion_id` int(10) UNSIGNED NOT NULL,
  `asiento_id` bigint(20) UNSIGNED NOT NULL,
  `conciliado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `empresas`
--

CREATE TABLE `empresas` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `nit` varchar(20) NOT NULL DEFAULT '',
  `direccion` varchar(200) DEFAULT NULL,
  `telefono` varchar(40) DEFAULT NULL,
  `ciudad` varchar(80) DEFAULT NULL,
  `departamento` varchar(80) DEFAULT NULL,
  `pais` char(3) NOT NULL DEFAULT 'COL',
  `logo_path` varchar(255) DEFAULT NULL,
  `moneda_base` char(3) NOT NULL DEFAULT 'COP',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Multi-empresa: datos de cada compañía registrada';

--
-- Dumping data for table `empresas`
--

INSERT INTO `empresas` (`id`, `codigo`, `nombre`, `nit`, `direccion`, `telefono`, `ciudad`, `departamento`, `pais`, `logo_path`, `moneda_base`, `activa`, `created_at`, `updated_at`) VALUES
(1, 'INGLIFE', 'Ingeniería LIFE S.A.S.', '900123456-7', 'Barrio Cabañas', '87787554', 'Comayagua', 'Comayagua', 'COL', NULL, 'HNL', 1, '2026-03-26 21:36:05', '2026-03-26 23:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `libros_folios`
--

CREATE TABLE `libros_folios` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `libro_tipo` enum('DIARIO','MAYOR','INVENTARIOS') NOT NULL,
  `folio_inicial` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `ultimo_folio_usado` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `autorizacion_sar` varchar(50) DEFAULT NULL COMMENT 'N° de Resolución si aplica',
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `libros_periodos`
--

CREATE TABLE `libros_periodos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `periodo_id` int(10) UNSIGNED NOT NULL,
  `libro_tipo` enum('DIARIO','MAYOR','INVENTARIOS') NOT NULL,
  `folio_desde` int(10) UNSIGNED NOT NULL,
  `folio_hasta` int(10) UNSIGNED NOT NULL,
  `fecha_impresion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `periodos`
--

CREATE TABLE `periodos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `anio` year(4) NOT NULL,
  `mes` tinyint(3) UNSIGNED NOT NULL COMMENT '1-12',
  `estado` enum('abierto','cerrado','bloqueado') NOT NULL DEFAULT 'abierto',
  `fecha_cierre` date DEFAULT NULL,
  `usuario_cierre_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `periodos`
--

INSERT INTO `periodos` (`id`, `empresa_id`, `anio`, `mes`, `estado`, `fecha_cierre`, `usuario_cierre_id`) VALUES
(1, 1, '2026', 1, 'cerrado', NULL, NULL),
(2, 1, '2026', 2, 'cerrado', NULL, NULL),
(3, 1, '2026', 3, 'abierto', NULL, NULL),
(4, 1, '2026', 4, 'abierto', NULL, NULL),
(5, 1, '2026', 5, 'abierto', NULL, NULL),
(6, 1, '2026', 6, 'abierto', NULL, NULL),
(7, 1, '2026', 7, 'abierto', NULL, NULL),
(8, 1, '2026', 8, 'abierto', NULL, NULL),
(9, 1, '2026', 9, 'abierto', NULL, NULL),
(10, 1, '2026', 10, 'abierto', NULL, NULL),
(11, 1, '2026', 11, 'abierto', NULL, NULL),
(12, 1, '2026', 12, 'abierto', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `proyectos`
--

CREATE TABLE `proyectos` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proyectos`
--

INSERT INTO `proyectos` (`id`, `empresa_id`, `codigo`, `nombre`, `activo`) VALUES
(1, 1, 'GEN', 'General', 1);

-- --------------------------------------------------------

--
-- Table structure for table `puc_cuentas`
--

CREATE TABLE `puc_cuentas` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `nivel` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Clase,2=Grupo,3=Cuenta,4=Subcuenta,5=Auxiliar',
  `codigo_padre` varchar(20) DEFAULT NULL,
  `naturaleza` enum('D','C') NOT NULL DEFAULT 'D' COMMENT 'D=Débito, C=Crédito',
  `tipo_cuenta` enum('A','P','R','G','O') NOT NULL DEFAULT 'A' COMMENT 'A=Activo,P=Pasivo,R=Resultado/Patrimonio,G=Gasto,O=Orden',
  `acepta_movimiento` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Solo cuentas de nivel 4 o 5 aceptan asientos',
  `maneja_tercero` tinyint(1) NOT NULL DEFAULT 0,
  `maneja_ceco` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Centro de costos',
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Plan Único de Cuentas con estructura jerárquica';

--
-- Dumping data for table `puc_cuentas`
--

INSERT INTO `puc_cuentas` (`id`, `empresa_id`, `codigo`, `nombre`, `nivel`, `codigo_padre`, `naturaleza`, `tipo_cuenta`, `acepta_movimiento`, `maneja_tercero`, `maneja_ceco`, `activa`, `created_at`, `updated_at`) VALUES
(1, 1, '1', 'ACTIVO', 1, NULL, 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(2, 1, '11', 'EFECTIVO Y EQUIVALENTES', 2, '1', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(3, 1, '1105', 'CAJA', 3, '11', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(4, 1, '110505', 'CAJA GENERAL', 4, '1105', 'D', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(5, 1, '1110', 'BANCOS', 3, '11', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(6, 1, '111005', 'BANCO DE BOGOTÁ CTA CTE', 4, '1110', 'D', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(7, 1, '111010', 'BANCO DAVIVIENDA CTA CTE', 4, '1110', 'D', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(8, 1, '13', 'DEUDORES', 2, '1', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(9, 1, '1305', 'CLIENTES', 3, '13', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(10, 1, '130505', 'CLIENTES NACIONALES', 4, '1305', 'D', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(11, 1, '1330', 'ANTICIPOS Y AVANCES', 3, '13', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(12, 1, '133005', 'ANTICIPOS A PROVEEDORES', 4, '1330', 'D', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(13, 1, '15', 'PROPIEDADES, PLANTA Y EQUIPO', 2, '1', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(14, 1, '1504', 'EQUIPO DE OFICINA', 3, '15', 'D', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(15, 1, '150405', 'MUEBLES Y ENSERES', 4, '1504', 'D', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(16, 1, '1592', 'DEPRECIACIÓN ACUMULADA PPE', 3, '15', 'C', 'A', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(17, 1, '159205', 'DEPRECIACIÓN ACUMULADA MUEBLES', 4, '1592', 'C', 'A', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(18, 1, '2', 'PASIVO', 1, NULL, 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(19, 1, '21', 'OBLIGACIONES FINANCIERAS', 2, '2', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(20, 1, '2105', 'BANCOS NACIONALES', 3, '21', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(21, 1, '210505', 'BANCO DE BOGOTÁ PRÉSTAMO', 4, '2105', 'C', 'P', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(22, 1, '22', 'PROVEEDORES', 2, '2', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(23, 1, '2205', 'PROVEEDORES NACIONALES', 3, '22', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(24, 1, '220505', 'PROVEEDORES BIENES', 4, '2205', 'C', 'P', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(25, 1, '23', 'CUENTAS POR PAGAR', 2, '2', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(26, 1, '2335', 'COSTAS Y GASTOS POR PAGAR', 3, '23', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(27, 1, '233505', 'GASTOS ACUMULADOS POR PAGAR', 4, '2335', 'C', 'P', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(28, 1, '24', 'IMPUESTOS, GRAVÁMENES Y TASAS', 2, '2', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(29, 1, '2404', 'DE RENTA Y COMPLEMENTARIOS', 3, '24', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(30, 1, '240405', 'IMPUESTO DE RENTA CORRIENTE', 4, '2404', 'C', 'P', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(31, 1, '2408', 'IMPUESTO SOBRE LAS VENTAS IVA', 3, '24', 'C', 'P', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(32, 1, '240805', 'IVA POR PAGAR', 4, '2408', 'C', 'P', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(33, 1, '3', 'PATRIMONIO', 1, NULL, 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(34, 1, '31', 'CAPITAL SOCIAL', 2, '3', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(35, 1, '3105', 'CAPITAL SUSCRITO Y PAGADO', 3, '31', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(36, 1, '310505', 'CAPITAL DE SOCIOS', 4, '3105', 'C', 'R', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(37, 1, '36', 'RESULTADO DEL EJERCICIO', 2, '3', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(38, 1, '3605', 'UTILIDAD DEL EJERCICIO', 3, '36', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(39, 1, '360505', 'UTILIDAD DEL PERÍODO', 4, '3605', 'C', 'R', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(40, 1, '3610', 'PÉRDIDA DEL EJERCICIO', 3, '36', 'D', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(41, 1, '361005', 'PÉRDIDA DEL PERÍODO', 4, '3610', 'D', 'R', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(42, 1, '4', 'INGRESOS', 1, NULL, 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(43, 1, '41', 'OPERACIONALES', 2, '4', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(44, 1, '4135', 'SERVICIOS', 3, '41', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(45, 1, '413505', 'INGRESOS POR SERVICIOS DE INGENIERÍA', 4, '4135', 'C', 'R', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(46, 1, '4175', 'DEVOLUCIONES EN VENTAS (CR)', 3, '41', 'D', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(47, 1, '417505', 'DEVOLUCIONES SERVICIOS', 4, '4175', 'D', 'R', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(48, 1, '42', 'NO OPERACIONALES', 2, '4', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(49, 1, '4210', 'FINANCIEROS', 3, '42', 'C', 'R', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(50, 1, '421005', 'INTERESES RECIBIDOS', 4, '4210', 'C', 'R', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(51, 1, '5', 'GASTOS', 1, NULL, 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(52, 1, '51', 'OPERACIONALES DE ADMINISTRACIÓN', 2, '5', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(53, 1, '5105', 'GASTOS DE PERSONAL', 3, '51', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(54, 1, '510506', 'SALARIOS', 4, '5105', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(55, 1, '510527', 'AUXILIO DE TRANSPORTE', 4, '5105', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(56, 1, '5110', 'HONORARIOS', 3, '51', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(57, 1, '511005', 'HONORARIOS A CONTADORES', 4, '5110', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(58, 1, '5120', 'ARRENDAMIENTOS', 3, '51', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(59, 1, '512005', 'ARRENDAMIENTO DE LOCAL', 4, '5120', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(60, 1, '5145', 'MANTENIMIENTO Y REPARACIONES', 3, '51', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(61, 1, '514505', 'MANTENIMIENTO DE EQUIPOS', 4, '5145', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(62, 1, '5195', 'DIVERSOS', 3, '51', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(63, 1, '519505', 'GASTOS VARIOS', 4, '5195', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(64, 1, '52', 'OPERACIONALES DE VENTAS', 2, '5', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(65, 1, '5205', 'GASTOS DE PERSONAL VENTAS', 3, '52', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(66, 1, '520506', 'SALARIOS FUERZA DE VENTAS', 4, '5205', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(67, 1, '53', 'NO OPERACIONALES', 2, '5', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(68, 1, '5305', 'FINANCIEROS', 3, '53', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(69, 1, '530505', 'INTERESES BANCARIOS', 4, '5305', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(70, 1, '6', 'COSTOS DE VENTAS', 1, NULL, 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(71, 1, '61', 'COSTOS DE SERVICIOS', 2, '6', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(72, 1, '6135', 'COSTOS SERVICIOS PRESTADOS', 3, '61', 'D', 'G', 0, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(73, 1, '613505', 'COSTO DE INGENIERÍA Y CONSULTORÍA', 4, '6135', 'D', 'G', 1, 0, 0, 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `saldos_periodo`
--

CREATE TABLE `saldos_periodo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `periodo_id` int(10) UNSIGNED NOT NULL,
  `cuenta_id` int(10) UNSIGNED NOT NULL,
  `total_debito` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `total_credito` decimal(20,4) NOT NULL DEFAULT 0.0000,
  `saldo` decimal(20,4) GENERATED ALWAYS AS (`total_debito` - `total_credito`) STORED,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pre-cálculo de saldos por periodo para reportes de Balance';

-- --------------------------------------------------------

--
-- Table structure for table `sucursales`
--

CREATE TABLE `sucursales` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sucursales`
--

INSERT INTO `sucursales` (`id`, `empresa_id`, `codigo`, `nombre`, `activa`) VALUES
(1, 1, '01', 'Principal', 1);

-- --------------------------------------------------------

--
-- Table structure for table `terceros`
--

CREATE TABLE `terceros` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `tipo_persona` enum('N','J') NOT NULL DEFAULT 'J' COMMENT 'N=Natural, J=Jurídica',
  `tipo_documento` enum('NIT','CC','CE','PP','TI','NE') NOT NULL DEFAULT 'NIT',
  `nit_cc` varchar(20) NOT NULL,
  `digito_verif` char(1) DEFAULT NULL,
  `razon_social` varchar(200) NOT NULL,
  `nombre1` varchar(80) DEFAULT NULL,
  `nombre2` varchar(80) DEFAULT NULL,
  `apellido1` varchar(80) DEFAULT NULL,
  `apellido2` varchar(80) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL,
  `ciudad` varchar(80) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `tipo_tercero` set('cliente','proveedor','empleado','accionista','otro') NOT NULL DEFAULT 'cliente',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terceros`
--

INSERT INTO `terceros` (`id`, `empresa_id`, `codigo`, `tipo_persona`, `tipo_documento`, `nit_cc`, `digito_verif`, `razon_social`, `nombre1`, `nombre2`, `apellido1`, `apellido2`, `direccion`, `ciudad`, `telefono`, `email`, `tipo_tercero`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'CLI001', 'J', 'NIT', '800111222-3', NULL, 'INMOBILIARIA WINEROQUI S.A.S', NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, 'cliente', 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(2, 1, 'CLI002', 'J', 'NIT', '900456789-1', NULL, 'CONSTRUCTORA ANDINA LTDA', NULL, NULL, NULL, NULL, NULL, 'Medellín', NULL, NULL, 'cliente', 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(3, 1, 'PRO001', 'J', 'NIT', '860001922-2', NULL, 'FERRETERÍA NACIONAL S.A.', NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, 'proveedor', 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05'),
(4, 1, 'EMP001', 'J', 'CC', '52456789', NULL, 'GÓMEZ TORRES LAURA', NULL, NULL, NULL, NULL, NULL, 'Bogotá', NULL, NULL, 'empleado', 1, '2026-03-26 21:36:05', '2026-03-26 21:36:05');

-- --------------------------------------------------------

--
-- Table structure for table `tipos_comprobante`
--

CREATE TABLE `tipos_comprobante` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `codigo` varchar(5) NOT NULL COMMENT 'Ej: NA,CE,NC,ND,RC,CP',
  `nombre` varchar(80) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `consecutivo_actual` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de documento contable: Nota de Ajuste, Comprobante de Egreso, etc.';

--
-- Dumping data for table `tipos_comprobante`
--

INSERT INTO `tipos_comprobante` (`id`, `empresa_id`, `codigo`, `nombre`, `descripcion`, `consecutivo_actual`, `activo`) VALUES
(1, 1, 'NA', 'Ajuste (Nota) Contable', 'Comprobante de ajuste y notas contables', 0, 1),
(2, 1, 'CC', 'Comprobante de Contabilidad', 'Asiento de diario general', 0, 1),
(3, 1, 'CE', 'Comprobante de Egreso', 'Pagos y salidas de caja/banco', 0, 1),
(4, 1, 'RC', 'Recibo de Caja', 'Ingresos y recaudos', 0, 1),
(5, 1, 'NC', 'Nota Crédito', 'Devolución y descuentos', 0, 1),
(6, 1, 'ND', 'Nota Débito', 'Cobros adicionales', 0, 1),
(7, 1, 'NI', 'Nota de Ingreso', 'Entradas de inventario', 0, 1),
(8, 1, 'NS', 'Nota de Salida', 'Salidas de inventario', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tipos_retencion`
--

CREATE TABLE `tipos_retencion` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL COMMENT 'Codificación SAR',
  `nombre` varchar(100) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tipo` enum('ISV','FUENTE') NOT NULL DEFAULT 'FUENTE',
  `cuenta_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Cuenta contable relacionada en el PUC',
  `activa` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipos_retencion`
--

INSERT INTO `tipos_retencion` (`id`, `codigo`, `nombre`, `porcentaje`, `tipo`, `cuenta_id`, `activa`) VALUES
(1, 'R15', 'Retención de ISV (15%) - Régimen de Pagos', 15.00, 'ISV', NULL, 1),
(2, 'R01', 'Retención de ISV (1%) - Proveedores del Estado', 1.00, 'ISV', NULL, 1),
(3, 'RF1', 'Retención en la Fuente - Honorarios Profesionales', 10.00, 'FUENTE', NULL, 1),
(4, 'RF2', 'Retención en la Fuente - Comisiones', 12.50, 'FUENTE', NULL, 1),
(5, 'RF3', 'Retención en la Fuente - Alquileres', 10.00, 'FUENTE', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` smallint(5) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `rol` enum('admin','contador','auditor','consulta') NOT NULL DEFAULT 'consulta',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `empresa_id`, `username`, `password_hash`, `nombre`, `email`, `rol`, `activo`, `ultimo_login`, `created_at`) VALUES
(1, 1, 'admin', '$2y$10$wntQAiBCAVlBKMsbj94RvevwLKG/Shz7oGZkimbNtCqv06qiOAYD6', 'Administrador', 'admin@ingenierialife.com', 'admin', 1, '2026-03-27 17:28:20', '2026-03-26 21:36:05'),
(2, 1, 'contador3', '$2y$10$wntQAiBCAVlBKMsbj94RvevwLKG/Shz7oGZkimbNtCqv06qiOAYD6', 'Contador Principal', 'contador@ingenierialife.com', 'contador', 1, NULL, '2026-03-26 21:36:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activos_depreciaciones`
--
ALTER TABLE `activos_depreciaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_deprec_periodo` (`activo_id`,`periodo_id`),
  ADD KEY `fk_deprec_periodo` (`periodo_id`),
  ADD KEY `fk_deprec_comp` (`comprobante_id`);

--
-- Indexes for table `activos_fijos`
--
ALTER TABLE `activos_fijos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_activo_empresa_codigo` (`empresa_id`,`codigo`),
  ADD KEY `fk_activo_sucursal` (`sucursal_id`),
  ADD KEY `fk_activo_cta_act` (`cuenta_activo_id`),
  ADD KEY `fk_activo_cta_dep` (`cuenta_deprec_acum_id`),
  ADD KEY `fk_activo_cta_gas` (`cuenta_gasto_deprec_id`),
  ADD KEY `fk_activo_ceco` (`ceco_id`);

--
-- Indexes for table `asientos`
--
ALTER TABLE `asientos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_asiento_comp_linea` (`comprobante_id`,`linea`),
  ADD KEY `idx_asiento_cuenta` (`empresa_id`,`cuenta_id`),
  ADD KEY `idx_asiento_tercero` (`empresa_id`,`tercero_id`),
  ADD KEY `idx_asiento_fecha_cuenta` (`empresa_id`,`cuenta_id`),
  ADD KEY `fk_asiento_cuenta` (`cuenta_id`),
  ADD KEY `fk_asiento_tercero` (`tercero_id`),
  ADD KEY `fk_asiento_ceco` (`ceco_id`),
  ADD KEY `fk_asiento_proyecto` (`proyecto_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_empresa_tabla` (`empresa_id`,`tabla`),
  ADD KEY `idx_audit_usuario` (`usuario_id`),
  ADD KEY `idx_audit_fecha` (`created_at`);

--
-- Indexes for table `bancos_cuentas`
--
ALTER TABLE `bancos_cuentas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_banco_empresa` (`empresa_id`),
  ADD KEY `fk_banco_cta_puc` (`cuenta_id`);

--
-- Indexes for table `cartera_creditos`
--
ALTER TABLE `cartera_creditos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_tercero` (`tercero_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Indexes for table `cartera_cuotas`
--
ALTER TABLE `cartera_cuotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_credito` (`credito_id`),
  ADD KEY `idx_vencimiento` (`fecha_vencimiento`);

--
-- Indexes for table `cartera_recaudos`
--
ALTER TABLE `cartera_recaudos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa` (`empresa_id`),
  ADD KEY `idx_tercero` (`tercero_id`),
  ADD KEY `idx_credito` (`credito_id`),
  ADD KEY `idx_fecha` (`fecha`);

--
-- Indexes for table `centros_costo`
--
ALTER TABLE `centros_costo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ceco_empresa` (`empresa_id`,`codigo`);

--
-- Indexes for table `certificados_retencion`
--
ALTER TABLE `certificados_retencion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cert_emp_corr` (`empresa_id`,`correlativo`),
  ADD KEY `fk_cert_ter` (`tercero_id`),
  ADD KEY `fk_cert_tipo` (`tipo_retencion_id`),
  ADD KEY `fk_cert_asiento` (`comprobante_id`);

--
-- Indexes for table `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_comp_empresa_tipo_num` (`empresa_id`,`tipo_comp_id`,`numero`),
  ADD KEY `idx_comp_fecha` (`empresa_id`,`fecha`),
  ADD KEY `idx_comp_periodo` (`periodo_id`),
  ADD KEY `idx_comp_tercero` (`empresa_id`,`tercero_id`),
  ADD KEY `idx_comp_estado` (`empresa_id`,`estado`),
  ADD KEY `fk_comp_sucursal` (`sucursal_id`),
  ADD KEY `fk_comp_tipo` (`tipo_comp_id`),
  ADD KEY `fk_comp_tercero` (`tercero_id`),
  ADD KEY `fk_comp_usuario` (`usuario_id`);

--
-- Indexes for table `comprobantes_recurrentes`
--
ALTER TABLE `comprobantes_recurrentes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_recu_empresa` (`empresa_id`);

--
-- Indexes for table `com_cai`
--
ALTER TABLE `com_cai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cai_empresa` (`empresa_id`);

--
-- Indexes for table `com_categorias`
--
ALTER TABLE `com_categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cat_empresa` (`empresa_id`);

--
-- Indexes for table `com_facturas`
--
ALTER TABLE `com_facturas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fact_empresa_num` (`empresa_id`,`numero_factura`),
  ADD KEY `fk_fact_cai` (`cai_id`);

--
-- Indexes for table `com_facturas_detalle`
--
ALTER TABLE `com_facturas_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_det_factura` (`factura_id`),
  ADD KEY `fk_det_prod` (`producto_id`);

--
-- Indexes for table `com_productos`
--
ALTER TABLE `com_productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prod_empresa_codigo` (`empresa_id`,`codigo`),
  ADD KEY `fk_prod_cat` (`categoria_id`);

--
-- Indexes for table `com_trazabilidad`
--
ALTER TABLE `com_trazabilidad`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_traza_empresa` (`empresa_id`),
  ADD KEY `fk_traza_prod` (`producto_id`);

--
-- Indexes for table `conciliaciones`
--
ALTER TABLE `conciliaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conciliacion_banco_periodo` (`banco_cuenta_id`,`periodo_id`),
  ADD KEY `fk_conc_periodo` (`periodo_id`),
  ADD KEY `fk_conc_usuario` (`usuario_id`);

--
-- Indexes for table `conciliaciones_det`
--
ALTER TABLE `conciliaciones_det`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conc_det_asiento` (`conciliacion_id`,`asiento_id`),
  ADD KEY `fk_cdet_asiento` (`asiento_id`);

--
-- Indexes for table `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_empresas_codigo` (`codigo`);

--
-- Indexes for table `libros_folios`
--
ALTER TABLE `libros_folios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_libro_empresa` (`empresa_id`,`libro_tipo`);

--
-- Indexes for table `libros_periodos`
--
ALTER TABLE `libros_periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_libro_periodo` (`empresa_id`,`periodo_id`,`libro_tipo`),
  ADD KEY `fk_lp_periodo` (`periodo_id`);

--
-- Indexes for table `periodos`
--
ALTER TABLE `periodos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_periodo_empresa` (`empresa_id`,`anio`,`mes`),
  ADD KEY `idx_periodo_estado` (`empresa_id`,`estado`);

--
-- Indexes for table `proyectos`
--
ALTER TABLE `proyectos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_proyecto_empresa` (`empresa_id`,`codigo`);

--
-- Indexes for table `puc_cuentas`
--
ALTER TABLE `puc_cuentas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_puc_empresa_codigo` (`empresa_id`,`codigo`),
  ADD KEY `idx_puc_nivel` (`empresa_id`,`nivel`),
  ADD KEY `idx_puc_padre` (`empresa_id`,`codigo_padre`),
  ADD KEY `idx_puc_tipo` (`tipo_cuenta`);

--
-- Indexes for table `saldos_periodo`
--
ALTER TABLE `saldos_periodo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_saldo_empresa_periodo_cuenta` (`empresa_id`,`periodo_id`,`cuenta_id`),
  ADD KEY `idx_saldo_cuenta` (`empresa_id`,`cuenta_id`),
  ADD KEY `fk_saldo_periodo` (`periodo_id`),
  ADD KEY `fk_saldo_cuenta` (`cuenta_id`);

--
-- Indexes for table `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_suc_empresa_codigo` (`empresa_id`,`codigo`);

--
-- Indexes for table `terceros`
--
ALTER TABLE `terceros`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tercero_empresa_codigo` (`empresa_id`,`codigo`),
  ADD KEY `idx_tercero_nit` (`empresa_id`,`nit_cc`),
  ADD KEY `idx_tercero_nombre` (`empresa_id`,`razon_social`);

--
-- Indexes for table `tipos_comprobante`
--
ALTER TABLE `tipos_comprobante`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tipo_comp_empresa` (`empresa_id`,`codigo`);

--
-- Indexes for table `tipos_retencion`
--
ALTER TABLE `tipos_retencion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ret_cuenta` (`cuenta_id`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuario_empresa` (`empresa_id`,`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activos_depreciaciones`
--
ALTER TABLE `activos_depreciaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activos_fijos`
--
ALTER TABLE `activos_fijos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asientos`
--
ALTER TABLE `asientos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bancos_cuentas`
--
ALTER TABLE `bancos_cuentas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cartera_creditos`
--
ALTER TABLE `cartera_creditos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cartera_cuotas`
--
ALTER TABLE `cartera_cuotas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cartera_recaudos`
--
ALTER TABLE `cartera_recaudos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `centros_costo`
--
ALTER TABLE `centros_costo`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `certificados_retencion`
--
ALTER TABLE `certificados_retencion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comprobantes`
--
ALTER TABLE `comprobantes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comprobantes_recurrentes`
--
ALTER TABLE `comprobantes_recurrentes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_cai`
--
ALTER TABLE `com_cai`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_categorias`
--
ALTER TABLE `com_categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_facturas`
--
ALTER TABLE `com_facturas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_facturas_detalle`
--
ALTER TABLE `com_facturas_detalle`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_productos`
--
ALTER TABLE `com_productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_trazabilidad`
--
ALTER TABLE `com_trazabilidad`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conciliaciones`
--
ALTER TABLE `conciliaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conciliaciones_det`
--
ALTER TABLE `conciliaciones_det`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `libros_folios`
--
ALTER TABLE `libros_folios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `libros_periodos`
--
ALTER TABLE `libros_periodos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `periodos`
--
ALTER TABLE `periodos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `proyectos`
--
ALTER TABLE `proyectos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `puc_cuentas`
--
ALTER TABLE `puc_cuentas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `saldos_periodo`
--
ALTER TABLE `saldos_periodo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `terceros`
--
ALTER TABLE `terceros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tipos_comprobante`
--
ALTER TABLE `tipos_comprobante`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tipos_retencion`
--
ALTER TABLE `tipos_retencion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activos_depreciaciones`
--
ALTER TABLE `activos_depreciaciones`
  ADD CONSTRAINT `fk_deprec_activo` FOREIGN KEY (`activo_id`) REFERENCES `activos_fijos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_deprec_comp` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`),
  ADD CONSTRAINT `fk_deprec_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`);

--
-- Constraints for table `activos_fijos`
--
ALTER TABLE `activos_fijos`
  ADD CONSTRAINT `fk_activo_ceco` FOREIGN KEY (`ceco_id`) REFERENCES `centros_costo` (`id`),
  ADD CONSTRAINT `fk_activo_cta_act` FOREIGN KEY (`cuenta_activo_id`) REFERENCES `puc_cuentas` (`id`),
  ADD CONSTRAINT `fk_activo_cta_dep` FOREIGN KEY (`cuenta_deprec_acum_id`) REFERENCES `puc_cuentas` (`id`),
  ADD CONSTRAINT `fk_activo_cta_gas` FOREIGN KEY (`cuenta_gasto_deprec_id`) REFERENCES `puc_cuentas` (`id`),
  ADD CONSTRAINT `fk_activo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_activo_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`);

--
-- Constraints for table `asientos`
--
ALTER TABLE `asientos`
  ADD CONSTRAINT `fk_asiento_ceco` FOREIGN KEY (`ceco_id`) REFERENCES `centros_costo` (`id`),
  ADD CONSTRAINT `fk_asiento_comp` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_asiento_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `puc_cuentas` (`id`),
  ADD CONSTRAINT `fk_asiento_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_asiento_proyecto` FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`),
  ADD CONSTRAINT `fk_asiento_tercero` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`);

--
-- Constraints for table `bancos_cuentas`
--
ALTER TABLE `bancos_cuentas`
  ADD CONSTRAINT `fk_banco_cta_puc` FOREIGN KEY (`cuenta_id`) REFERENCES `puc_cuentas` (`id`),
  ADD CONSTRAINT `fk_banco_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `cartera_cuotas`
--
ALTER TABLE `cartera_cuotas`
  ADD CONSTRAINT `fk_cuota_credito` FOREIGN KEY (`credito_id`) REFERENCES `cartera_creditos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `centros_costo`
--
ALTER TABLE `centros_costo`
  ADD CONSTRAINT `fk_ceco_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `certificados_retencion`
--
ALTER TABLE `certificados_retencion`
  ADD CONSTRAINT `fk_cert_asiento` FOREIGN KEY (`comprobante_id`) REFERENCES `comprobantes` (`id`),
  ADD CONSTRAINT `fk_cert_emp` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_cert_ter` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`),
  ADD CONSTRAINT `fk_cert_tipo` FOREIGN KEY (`tipo_retencion_id`) REFERENCES `tipos_retencion` (`id`);

--
-- Constraints for table `comprobantes`
--
ALTER TABLE `comprobantes`
  ADD CONSTRAINT `fk_comp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_comp_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  ADD CONSTRAINT `fk_comp_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  ADD CONSTRAINT `fk_comp_tercero` FOREIGN KEY (`tercero_id`) REFERENCES `terceros` (`id`),
  ADD CONSTRAINT `fk_comp_tipo` FOREIGN KEY (`tipo_comp_id`) REFERENCES `tipos_comprobante` (`id`),
  ADD CONSTRAINT `fk_comp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `comprobantes_recurrentes`
--
ALTER TABLE `comprobantes_recurrentes`
  ADD CONSTRAINT `fk_recu_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `com_cai`
--
ALTER TABLE `com_cai`
  ADD CONSTRAINT `fk_cai_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `com_categorias`
--
ALTER TABLE `com_categorias`
  ADD CONSTRAINT `fk_cat_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `com_facturas`
--
ALTER TABLE `com_facturas`
  ADD CONSTRAINT `fk_fact_cai` FOREIGN KEY (`cai_id`) REFERENCES `com_cai` (`id`),
  ADD CONSTRAINT `fk_fact_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `com_facturas_detalle`
--
ALTER TABLE `com_facturas_detalle`
  ADD CONSTRAINT `fk_det_factura` FOREIGN KEY (`factura_id`) REFERENCES `com_facturas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_det_prod` FOREIGN KEY (`producto_id`) REFERENCES `com_productos` (`id`);

--
-- Constraints for table `com_productos`
--
ALTER TABLE `com_productos`
  ADD CONSTRAINT `fk_prod_cat` FOREIGN KEY (`categoria_id`) REFERENCES `com_categorias` (`id`),
  ADD CONSTRAINT `fk_prod_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `com_trazabilidad`
--
ALTER TABLE `com_trazabilidad`
  ADD CONSTRAINT `fk_traza_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_traza_prod` FOREIGN KEY (`producto_id`) REFERENCES `com_productos` (`id`);

--
-- Constraints for table `conciliaciones`
--
ALTER TABLE `conciliaciones`
  ADD CONSTRAINT `fk_conc_banco` FOREIGN KEY (`banco_cuenta_id`) REFERENCES `bancos_cuentas` (`id`),
  ADD CONSTRAINT `fk_conc_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`),
  ADD CONSTRAINT `fk_conc_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `conciliaciones_det`
--
ALTER TABLE `conciliaciones_det`
  ADD CONSTRAINT `fk_cdet_asiento` FOREIGN KEY (`asiento_id`) REFERENCES `asientos` (`id`),
  ADD CONSTRAINT `fk_cdet_conc` FOREIGN KEY (`conciliacion_id`) REFERENCES `conciliaciones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `libros_folios`
--
ALTER TABLE `libros_folios`
  ADD CONSTRAINT `fk_libro_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `libros_periodos`
--
ALTER TABLE `libros_periodos`
  ADD CONSTRAINT `fk_lp_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_lp_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`);

--
-- Constraints for table `periodos`
--
ALTER TABLE `periodos`
  ADD CONSTRAINT `fk_periodo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `proyectos`
--
ALTER TABLE `proyectos`
  ADD CONSTRAINT `fk_proyecto_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `puc_cuentas`
--
ALTER TABLE `puc_cuentas`
  ADD CONSTRAINT `fk_puc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `saldos_periodo`
--
ALTER TABLE `saldos_periodo`
  ADD CONSTRAINT `fk_saldo_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `puc_cuentas` (`id`),
  ADD CONSTRAINT `fk_saldo_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  ADD CONSTRAINT `fk_saldo_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `periodos` (`id`);

--
-- Constraints for table `sucursales`
--
ALTER TABLE `sucursales`
  ADD CONSTRAINT `fk_suc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terceros`
--
ALTER TABLE `terceros`
  ADD CONSTRAINT `fk_tercero_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `tipos_comprobante`
--
ALTER TABLE `tipos_comprobante`
  ADD CONSTRAINT `fk_tc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);

--
-- Constraints for table `tipos_retencion`
--
ALTER TABLE `tipos_retencion`
  ADD CONSTRAINT `fk_ret_cuenta` FOREIGN KEY (`cuenta_id`) REFERENCES `puc_cuentas` (`id`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usu_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
