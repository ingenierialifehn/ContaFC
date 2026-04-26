import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'database', 'excel_full_sync.json')

with open(json_path, 'r', encoding='utf-8') as f:
    sync_map = json.load(f)

sql_path = os.path.join(base_dir, 'database', 'sincronizacion_total.sql')

with open(sql_path, 'w', encoding='utf-8') as f:
    f.write("-- REPARACION TOTAL DE CUENTAS POR COBRAR\n")
    f.write("-- 1. ELIMINAR DUPLICADOS SIN ID (LIMPIEZA DE 17K REGISTROS)\n")
    f.write("DELETE a1 FROM asientos a1\n")
    f.write("INNER JOIN asientos a2 ON a1.id > a2.id \n")
    f.write("  AND a1.empresa_id = a2.empresa_id\n")
    f.write("  AND a1.cuenta_id = a2.cuenta_id\n")
    f.write("  AND a1.debito = a2.debito\n")
    f.write("  AND a1.credito = a2.credito\n")
    f.write("  AND a1.descripcion = a2.descripcion\n")
    f.write("  AND a1.conteo IS NULL\n")
    f.write("  AND a2.conteo IS NULL\n")
    f.write("WHERE a1.empresa_id = 1\n")
    f.write("  AND a1.cuenta_id = (SELECT id FROM puc_cuentas WHERE codigo='11050101' AND empresa_id=1 LIMIT 1);\n\n")

    f.write("-- 2. ACTUALIZACION MASIVA DE FECHAS SEGUN EXCEL\n")
    f.write("UPDATE asientos \n")
    f.write("SET fecha = CASE conteo \n")
    
    count = 0
    for conteo, fecha in sync_map.items():
        f.write(f"  WHEN {conteo} THEN '{fecha}' \n")
        count += 1
        if count % 1000 == 0: # Para no hacer un CASE infinito que MySQL no aguante
            f.write(f"  ELSE fecha END\nWHERE conteo IN ({','.join(list(sync_map.keys())[count-1000:count])});\n\n")
            if count < len(sync_map):
                f.write("UPDATE asientos SET fecha = CASE conteo \n")
    
    # Escribir el resto si queda
    remaining_keys = list(sync_map.keys())[count - (count % 1000):]
    if remaining_keys:
        f.write(f"  ELSE fecha END\nWHERE conteo IN ({','.join(remaining_keys)});\n")

print(f"Script SQL generado exitosamente en: {sql_path}")
