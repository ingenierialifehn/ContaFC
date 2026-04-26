-- =============================================================
-- FIX PASO 1: Eliminar duplicados en asientos
-- Causa: INSERT IGNORE sin clave UNIQUE duplicó entradas
-- HACER BACKUP EN PHPMYADMIN ANTES (Exportar -> SQL)
-- =============================================================
USE contafc;

-- Verificar cuántos duplicados hay ANTES
SELECT 
    'Antes del fix' AS estado,
    COUNT(*) AS total_registros,
    (SELECT COUNT(*) FROM asientos a1
     INNER JOIN asientos a2
       ON  a1.empresa_id     = a2.empresa_id
       AND a1.comprobante_id = a2.comprobante_id
       AND a1.linea          = a2.linea
       AND a1.id             > a2.id) AS duplicados_a_eliminar
FROM asientos WHERE empresa_id = 1;

-- Eliminar duplicados: conserva el primer INSERT (id más bajo)
DELETE a1 FROM asientos a1
INNER JOIN asientos a2
  ON  a1.empresa_id     = a2.empresa_id
  AND a1.comprobante_id = a2.comprobante_id
  AND a1.linea          = a2.linea
  AND a1.id             > a2.id;

-- Verificar resultado DESPUÉS
SELECT 
    'Después del fix' AS estado,
    COUNT(*) AS total_registros,
    SUM(CASE WHEN credito < 0 THEN 1 ELSE 0 END) AS creditos_negativos_restantes
FROM asientos WHERE empresa_id = 1;

-- =============================================================
-- FIX PASO 2: Agregar clave ÚNICA para prevenir duplicados futuros
-- Esto hace que INSERT IGNORE funcione correctamente en el futuro
-- =============================================================
ALTER TABLE asientos 
ADD CONSTRAINT uq_asiento_comp_linea 
UNIQUE KEY (empresa_id, comprobante_id, linea);

-- Verificar totales finales por año (usando a.fecha del asiento)
SELECT 
    YEAR(a.fecha)      AS anio,
    COUNT(*)           AS asientos,
    SUM(a.debito)      AS total_debitos,
    SUM(a.credito)     AS total_creditos,
    SUM(CASE WHEN a.credito < 0 THEN a.debito + a.credito 
             ELSE a.debito - a.credito END) AS saldo_neto_correcto
FROM asientos a
JOIN comprobantes c ON a.comprobante_id = c.id
WHERE a.empresa_id = 1
  AND a.fecha IS NOT NULL
GROUP BY YEAR(a.fecha)
ORDER BY anio;
