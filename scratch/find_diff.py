import pandas as pd
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')

# Buscar montos cercanos a 67,999.74
df['MONTO'] = df['DEBITO'].fillna(0) + df['CREDITO'].fillna(0)
search = df[df['MONTO'].between(67990, 68000)]

print("Resultados encontrados en Excel:")
print(search[['FECHA', 'ACCT', 'DEBITO', 'CREDITO', 'DESCRIPCION2', 'CONTEO']])
