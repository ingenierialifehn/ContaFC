import json
import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')

sql_path = os.path.join(base_dir, 'database', 'sincronizacion_total.sql')

with open(sql_path, 'w', encoding='utf-8') as f:
    f.write("-- ==================================================\n")
    f.write("-- SCRIPT MAESTRO DE REPARACION CXC\n")
    f.write("-- ==================================================\n\n")

    f.write("-- 1. ELIMINAR DUPLICADOS EXACTOS SIN CONTEO\n")
    f.write("DELETE a1 FROM asientos a1\n")
    f.write("INNER JOIN asientos a2 ON a1.id > a2.id \n")
    f.write("  AND a1.empresa_id = a2.empresa_id\n")
    f.write("  AND a1.cuenta_id = a2.cuenta_id\n")
    f.write("  AND (ABS(a1.debito - a2.debito) < 0.01)\n")
    f.write("  AND (ABS(a1.credito - a2.credito) < 0.01)\n")
    f.write("  AND a1.descripcion = a2.descripcion\n")
    f.write("  AND a1.conteo IS NULL\n")
    f.write("  AND a2.conteo IS NULL\n")
    f.write("WHERE a1.empresa_id = 1\n")
    f.write("  AND a1.cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo='11050101' AND empresa_id=1 LIMIT 1);\n\n")

    f.write("-- 2. RECUPERAR IDS (CONTEO) PARA ASIENTOS QUE NO LO TIENEN\n")
    f.write("-- Emparejamos por monto y descripcion para que la sincronizacion funcione\n")
    
    # We'll generate updates for the largest unique records
    # Focus on those with high credits or debits that are likely unique
    df['ACCT'] = df['ACCT'].astype(str).str.strip()
    cxc = df[df['ACCT'] == '11050101'].dropna(subset=['CONTEO'])
    
    for _, row in cxc.head(2000).iterrows():
        # Only for likely unique large values to avoid wrong matches
        if row['DEBITO'] > 1000 or row['CREDITO'] > 1000:
            monto = row['DEBITO'] if row['DEBITO'] > 0 else row['CREDITO']
            col = 'debito' if row['DEBITO'] > 0 else 'credito'
            desc = row['DESCRIPCION2'].replace("'", "''")
            f.write(f"UPDATE asientos SET conteo = {int(row['CONTEO'])} WHERE {col} = {monto} AND (descripcion = '{desc}' OR descripcion = 'Factura sobre Orden Compra') AND conteo IS NULL AND empresa_id = 1 LIMIT 1;\n")

    f.write("\n-- 3. ACTUALIZACION MASIVA DE FECHAS (BASADO EN CONTEO)\n")
    
    sync_map = {}
    for _, row in df[['CONTEO', 'FECHA']].dropna().iterrows():
        sync_map[str(int(row['CONTEO']))] = row['FECHA'].strftime('%Y-%m-%d')

    count = 0
    klist = list(sync_map.keys())
    while count < len(klist):
        batch = klist[count:count+500]
        f.write("UPDATE asientos SET fecha = CASE conteo \n")
        for k in batch:
            f.write(f"  WHEN {k} THEN '{sync_map[k]}' \n")
        f.write(f"  ELSE fecha END WHERE conteo IN ({','.join(batch)});\n")
        count += 500

print(f"Script SQL completo generado en: {sql_path}")
