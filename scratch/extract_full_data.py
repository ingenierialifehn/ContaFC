import pandas as pd
import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')

df['ACCT'] = df['ACCT'].astype(str).str.strip()
df['DEBITO'] = df['DEBITO'].fillna(0)
df['CREDITO'] = df['CREDITO'].fillna(0)
df['DESCRIPCION2'] = df['DESCRIPCION2'].fillna('')
df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')
df['CONTEO'] = df['CONTEO'].fillna(0).astype(int)

# Filter all rows for 11050101 regardless of year
cxc_all = df[df['ACCT'] == '11050101'].copy()

result = []
for _, row in cxc_all.iterrows():
    result.append({
        'acct': row['ACCT'],
        'debito': float(row['DEBITO']),
        'credito': float(row['CREDITO']),
        'desc': str(row['DESCRIPCION2']),
        'fecha': row['FECHA'].strftime('%Y-%m-%d') if pd.notnull(row['FECHA']) else '2023-12-31',
        'conteo': int(row['CONTEO'])
    })

json_path = os.path.join(base_dir, 'database', 'excel_full_data.json')
with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(result, f)

print(f"Extracción total completada: {len(result)} registros guardados en {json_path}")
