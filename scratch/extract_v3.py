import pandas as pd
import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')

df['ACCT'] = df['ACCT'].astype(str).str.strip()
df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')

# Extraer TODO lo de la cuenta 11050101 en el año 2023
cxc_2023 = df[(df['ACCT'] == '11050101') & (df['FECHA'].dt.year == 2023)].copy()

result = []
for _, row in cxc_2023.iterrows():
    result.append({
        'debito': float(row['DEBITO']) if pd.notnull(row['DEBITO']) else 0,
        'credito': float(row['CREDITO']) if pd.notnull(row['CREDITO']) else 0,
        'desc': str(row['DESCRIPCION2']),
        'fecha': row['FECHA'].strftime('%Y-%m-%d'),
        'conteo': int(row['CONTEO']) if pd.notnull(row['CONTEO']) else 0
    })

json_path = os.path.join(base_dir, 'database', 'excel_full_2023.json')
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(result, f)

print(f"Éxito: Se extrajeron {len(result)} registros para el año 2023.")
