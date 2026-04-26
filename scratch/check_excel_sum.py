import json
import os

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
json_path = os.path.join(base_dir, 'database', 'excel_2023_cxc.json')

with open(json_path, 'r', encoding='utf-8') as f:
    data = json.load(f)

# Filter for account 11050101
cxc = [d for d in data if d['acct'] == '11050101']

total_debito = sum(d['debito'] for d in cxc)
total_credito = sum(d['credito'] for d in cxc)
total_neto = total_debito - total_credito

print(f"Resumen Excel 2023 para 11050101:")
print(f"Total Debito:  {total_debito:,.2f}")
print(f"Total Credito: {total_credito:,.2f}")
print(f"Total Neto:    {total_neto:,.2f}")
print(f"Cantidad mov:  {len(cxc)}")
