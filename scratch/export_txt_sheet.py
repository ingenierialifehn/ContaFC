import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['archivo-final.txt']

data = []
for row in sheet.iter_rows(min_row=1, max_row=5000, values_only=True):
    data.append(row)

with open(r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\scratch\txt_sheet_5000.json', 'w') as f:
    json.dump(data, f, indent=4, default=str)

print("txt_sheet_5000.json guardado")
