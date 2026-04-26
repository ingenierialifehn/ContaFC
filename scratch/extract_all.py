import pandas as pd
import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')

df['ACCT'] = df['ACCT'].astype(str).str.strip()
df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')

# Extraer ABSOLUTAMENTE TODO de la cuenta 11050101 (Todos los años: 2023, 2024, 2025...)
cxc_all = df[df['ACCT'] == '11050101'].copy()

result = []
for _, row in cxc_all.iterrows():
    result.append({
        'debito': round(float(row['DEBITO']), 2) if pd.notnull(row['DEBITO']) else 0,
        'credito': round(float(row['CREDITO']), 2) if pd.notnull(row['CREDITO']) else 0,
        'desc': str(row['DESCRIPCION2']),
        'fecha': row['FECHA'].strftime('%Y-%m-%d') if pd.notnull(row['FECHA']) else '2023-12-31',
        'conteo': int(row['CONTEO']) if pd.notnull(row['CONTEO']) else 0
    })

# Aquí no hace falta el ajuste de centavos porque el balance se va acumulando
# Pero si quieres, podemos revisar el saldo final después.

json_path = os.path.join(base_dir, 'database', 'excel_full_years_cxc.json')
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(result, f)

print(f"Éxito: Se extrajeron {len(result)} registros de todos los años.")
