SET FOREIGN_KEY_CHECKS = 0;
CREATE TEMPORARY TABLE tmp_dbf_mig (conteo INT, fecha DATE, debito DECIMAL(15,2), credito DECIMAL(15,2));


-- 1. Comprobantes
INSERT INTO comprobantes (empresa_id, tipo_comp_id, numero, fecha, observaciones, usuario_id, estado, periodo_id)
SELECT DISTINCT 1, 1, 90000 + (YEAR(fecha)*100 + MONTH(fecha)), fecha, CONCAT('MIG-', fecha), 1, 'registrado', 1
FROM tmp_dbf_mig d
WHERE NOT EXISTS (SELECT 1 FROM comprobantes c WHERE c.observaciones = CONCAT('MIG-', d.fecha));

-- 2. Periodos
UPDATE comprobantes c JOIN periodos p ON p.mes = MONTH(c.fecha) AND p.anio = YEAR(c.fecha) AND p.empresa_id = c.empresa_id SET c.periodo_id = p.id WHERE c.observaciones LIKE 'MIG-%';

-- 3. Asientos
UPDATE asientos a JOIN tmp_dbf_mig d ON a.conteo = d.conteo JOIN comprobantes c_new ON c_new.observaciones = CONCAT('MIG-', d.fecha) SET a.comprobante_id = c_new.id, a.fecha = d.fecha WHERE a.comprobante_id = 9999;
SET FOREIGN_KEY_CHECKS = 1;
