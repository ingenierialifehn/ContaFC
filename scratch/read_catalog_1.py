import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Catalago']

data = []
for row in sheet.iter_rows(min_row=1, max_row=1000, values_only=True):
    if row[1] is not None and str(row[1]).startswith('1'):
        data.append(row)

print(json.dumps(data, indent=4, default=str))
