import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

results = {}
for row in sheet.iter_rows(min_row=6, values_only=True):
    acct = row[2] # ACCT is index 2
    if acct is not None:
        s_acct = str(acct)
        if len(s_acct) >= 10:
            results[s_acct] = row[4] # DESCRIPCION2 is index 4
    if len(results) > 1000: break

print(json.dumps(results, indent=4, default=str))
