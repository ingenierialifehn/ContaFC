import openpyxl
import json
import re

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

results = []
for row in sheet.iter_rows(min_row=6, values_only=True):
    # COMPANYcust is index 5
    cust = str(row[5]) if row[5] is not None else ""
    if re.search(r'^\d', cust):
        results.append({
            "row": row
        })
    if len(results) > 100: break

print(json.dumps(results, indent=4, default=str))
