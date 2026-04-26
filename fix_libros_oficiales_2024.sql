-- TEST query

SELECT 
    YEAR(COALESCE(a.fecha, c.fecha)) as anio, 
    COUNT(a.id) as asientos,
    SUM(a.debito) as total_debitos, 
    SUM(a.credito) as total_creditos
FROM asientos a 
JOIN comprobantes c ON a.comprobante_id = c.id
WHERE a.empresa_id = 1 AND c.estado = 'registrado'
GROUP BY YEAR(COALESCE(a.fecha, c.fecha))
ORDER BY anio;

