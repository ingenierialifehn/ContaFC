import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Catalago']

data = []
for row in sheet.iter_rows(min_row=1, max_row=2000, values_only=True):
    data.append(row)

with open(r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\scratch\catalogo_full.json', 'w') as f:
    json.dump(data, f, indent=4, default=str)

print("Catalogo guardado en catalogo_full.json")
