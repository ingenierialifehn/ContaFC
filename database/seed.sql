-- ═══════════════════════════════════════════════════════════════════════════
-- ContaFC – Datos semilla
-- ═══════════════════════════════════════════════════════════════════════════
USE `contafc`;
SET NAMES utf8mb4;

-- ─── Empresa de prueba ───────────────────────────────────────────────────────
INSERT INTO `empresas` (`codigo`, `nombre`, `nit`, `ciudad`, `departamento`, `pais`) VALUES
('CONT_HN', 'Contabilidad Honduras S.A. de C.V.', '08019012345678', 'Tegucigalpa', 'Francisco Morazán', 'HND');

-- ─── Sucursal ────────────────────────────────────────────────────────────────
INSERT INTO `sucursales` (`empresa_id`, `codigo`, `nombre`) VALUES
(1, '01', 'Principal');

-- ─── Usuario admin (password: Admin2026!) ────────────────────────────────────
-- Hash argon2id de 'Admin2026!'
INSERT INTO `usuarios` (`empresa_id`, `username`, `password_hash`, `nombre`, `email`, `rol`) VALUES
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=1$dHZCcERlVkJ3WloxUGtaTA$WPJEfPl0cT9EvJP0XzuJWujjXGj7nGK2R1rWtFb2aRY', 'Administrador', 'admin@ingenierialife.com', 'admin'),
(1, 'contador3', '$argon2id$v=19$m=65536,t=4,p=1$dHZCcERlVkJ3WloxUGtaTA$WPJEfPl0cT9EvJP0XzuJWujjXGj7nGK2R1rWtFb2aRY', 'Contador Principal', 'contador@ingenierialife.com', 'contador');

-- ─── Tipos de comprobante (equivalentes WX-Manager) ─────────────────────────
INSERT INTO `tipos_comprobante` (`empresa_id`, `codigo`, `nombre`, `descripcion`) VALUES
(1, 'NA', 'Ajuste (Nota) Contable',      'Comprobante de ajuste y notas contables'),
(1, 'CC', 'Comprobante de Contabilidad', 'Asiento de diario general'),
(1, 'CE', 'Comprobante de Egreso',       'Pagos y salidas de caja/banco'),
(1, 'RC', 'Recibo de Caja',              'Ingresos y recaudos'),
(1, 'NC', 'Nota Crédito',                'Devolución y descuentos'),
(1, 'ND', 'Nota Débito',                 'Cobros adicionales'),
(1, 'NI', 'Nota de Ingreso',             'Entradas de inventario'),
(1, 'NS', 'Nota de Salida',              'Salidas de inventario');

-- ─── Centro de costos base ───────────────────────────────────────────────────
INSERT INTO `centros_costo` (`empresa_id`, `codigo`, `nombre`) VALUES
(1, 'GEN', 'General'),
(1, 'ADM', 'Administración'),
(1, 'VEN', 'Ventas'),
(1, 'OPE', 'Operaciones');

-- ─── Proyecto base ───────────────────────────────────────────────────────────
INSERT INTO `proyectos` (`empresa_id`, `codigo`, `nombre`) VALUES
(1, 'GEN', 'General');

-- ─── Periodo inicial ─────────────────────────────────────────────────────────
INSERT INTO `periodos` (`empresa_id`, `anio`, `mes`, `estado`) VALUES
(1, 2026, 1,  'cerrado'),
(1, 2026, 2,  'cerrado'),
(1, 2026, 3,  'abierto'),
(1, 2026, 4,  'abierto'),
(1, 2026, 5,  'abierto'),
(1, 2026, 6,  'abierto'),
(1, 2026, 7,  'abierto'),
(1, 2026, 8,  'abierto'),
(1, 2026, 9,  'abierto'),
(1, 2026, 10, 'abierto'),
(1, 2026, 11, 'abierto'),
(1, 2026, 12, 'abierto');

-- ─── PUC Honduras ───────────────────────────────────────────────────────────
INSERT INTO `puc_cuentas` (`empresa_id`,`codigo`,`nombre`,`nivel`,`codigo_padre`,`naturaleza`,`tipo_cuenta`,`acepta_movimiento`) VALUES
(1,'1',      'ACTIVO',                          1, NULL,   'D', 'A', 0),
(1,'11',     'ACTIVO CORRIENTE',                2, '1',    'D', 'A', 0),
(1,'1101',   'EFECTIVO Y EQUIVALENTES',         3, '11',   'D', 'A', 0),
(1,'110101', 'Caja General',                    4, '1101', 'D', 'A', 1),
(1,'110102', 'Bancos - Cuentas de Cheques',     4, '1101', 'D', 'A', 1),
(1,'1103',   'CUENTAS POR COBRAR',              3, '11',   'D', 'A', 0),
(1,'110301', 'Clientes Nacionales',             4, '1103', 'D', 'A', 1),
(1,'110302', 'Documentos por Cobrar',           4, '1103', 'D', 'A', 1),
(1,'1104',   'INVENTARIOS',                     3, '11',   'D', 'A', 0),
(1,'110401', 'Mercaderías',                     4, '1104', 'D', 'A', 1),
(1,'1105',   'PAGOS ANTICIPADOS',               3, '11',   'D', 'A', 0),
(1,'110501', 'Rentas Pagadas por Adelantado',   4, '1105', 'D', 'A', 1),
(1,'12',     'ACTIVO NO CORRIENTE',             2, '1',    'D', 'A', 0),
(1,'1201',   'PROPIEDAD, PLANTA Y EQUIPO',      3, '12',   'D', 'A', 0),
(1,'120101', 'Terrenos',                        4, '1201', 'D', 'A', 1),
(1,'120102', 'Edificios',                       4, '1201', 'D', 'A', 1),
(1,'120103', 'Mobiliario y Equipo',             4, '1201', 'D', 'A', 1),
(1,'120104', 'Equipo de Computación',            4, '1201', 'D', 'A', 1),
(1,'120105', 'Vehículos',                       4, '1201', 'D', 'A', 1),
(1,'1202',   'DEPRECIACIÓN ACUMULADA (CR)',     3, '12',   'C', 'A', 1),
(1,'2',      'PASIVO',                          1, NULL,   'C', 'P', 0),
(1,'21',     'PASIVO CORRIENTE',                2, '2',    'C', 'P', 0),
(1,'2101',   'CUENTAS Y DOC. POR PAGAR',        3, '21',   'C', 'P', 0),
(1,'210101', 'Proveedores Nacionales',          4, '2101', 'C', 'P', 1),
(1,'2102',   'PRÉSTAMOS BANCARIOS',             3, '21',   'C', 'P', 1),
(1,'2103',   'OBLIGACIONES FISCALES',           3, '21',   'C', 'P', 0),
(1,'210301', 'ISV por Pagar (15%)',             4, '2103', 'C', 'P', 1),
(1,'210302', 'Retenciones por Pagar',           4, '2103', 'C', 'P', 1),
(1,'22',     'PASIVO NO CORRIENTE',             2, '2',    'C', 'P', 0),
(1,'2201',   'Préstamos a Largo Plazo',         3, '22',   'C', 'P', 1),
(1,'3',      'PATRIMONIO NETO',                 1, NULL,   'C', 'R', 0),
(1,'31',     'CAPITAL',                         2, '3',    'C', 'R', 0),
(1,'3101',   'Capital Social',                  3, '31',   'C', 'R', 1),
(1,'32',     'RESERVAS',                        2, '3',    'C', 'R', 1),
(1,'33',     'RESULTADOS ACUMULADOS',           2, '3',    'C', 'R', 0),
(1,'3301',   'Utilidades de Ejercicios Ant.',   3, '33',   'C', 'R', 1),
(1,'3302',   'Utilidad del Ejercicio',          3, '33',   'C', 'R', 1),
(1,'4',      'INGRESOS',                        1, NULL,   'C', 'R', 0),
(1,'41',     'INGRESOS OPERATIVOS',             2, '4',    'C', 'R', 0),
(1,'4101',   'Ventas de Mercaderías',           3, '41',   'C', 'R', 1),
(1,'4102',   'Prestación de Servicios',         3, '41',   'C', 'R', 1),
(1,'42',     'OTROS INGRESOS',                  2, '4',    'C', 'R', 1),
(1,'5',      'GASTOS',                          1, NULL,   'D', 'G', 0),
(1,'51',     'GASTOS DE OPERACIÓN',             2, '5',    'D', 'G', 0),
(1,'5101',   'Gastos de Venta',                 3, '51',   'D', 'G', 1),
(1,'5102',   'Gastos de Administración',        3, '51',   'D', 'G', 0),
(1,'510201', 'Sueldos y Salarios',              4, '5102', 'D', 'G', 1),
(1,'510202', 'Seguridad Social (IHSS/RAP)',     4, '5102', 'D', 'G', 1),
(1,'510203', 'Alquileres',                      4, '5102', 'D', 'G', 1),
(1,'510204', 'Servicios Públicos',              4, '5102', 'D', 'G', 1),
(1,'510205', 'Papelería y Útiles',              4, '5102', 'D', 'G', 1),
(1,'6',      'COSTOS',                          1, NULL,   'D', 'G', 0),
(1,'61',     'COSTO DE VENTAS',                 2, '6',    'D', 'G', 1);

-- ─── Terceros de prueba ───────────────────────────────────────────────────────
INSERT INTO `terceros` (`empresa_id`,`codigo`,`tipo_documento`,`nit_cc`,`razon_social`,`ciudad`,`tipo_tercero`) VALUES
(1,'CLI001','RTN','05011990123456','DISTRIBUIDORA NORTE S.A. DE C.V.', 'San Pedro Sula', 'cliente'),
(1,'CLI002','RTN','08012010456789','CONSULTORA CENTRAL S. DE R.L.', 'Tegucigalpa',    'cliente'),
(1,'PRO001','RTN','01011980321456','FERRETERÍA LA CEIBA',           'La Ceiba',       'proveedor'),
(1,'EMP001','DNI','0801198512345', 'JUAN PÉREZ RODRÍGUEZ',         'Tegucigalpa',    'empleado');
