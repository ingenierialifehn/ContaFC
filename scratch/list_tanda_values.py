import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

unique_tanda = set()
for row in sheet.iter_rows(min_row=6, values_only=True):
    if row[3] is not None:
        unique_tanda.add(str(row[3]))
    if len(unique_tanda) > 500: break

print(json.dumps(list(unique_tanda), indent=4))
