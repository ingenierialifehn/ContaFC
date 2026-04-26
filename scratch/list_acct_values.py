import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

unique_acct = set()
for row in sheet.iter_rows(min_row=6, values_only=True):
    if row[2] is not None:
        unique_acct.add(str(row[2]))
    if len(unique_acct) > 1000: break

print(json.dumps(list(unique_acct), indent=4))
