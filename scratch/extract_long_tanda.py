import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

results = []
for row in sheet.iter_rows(min_row=6, values_only=True):
    tanda = row[3]
    if tanda is not None:
        s_tanda = str(tanda)
        if len(s_tanda) >= 8:
            results.append({
                "tanda": s_tanda,
                "name": row[5]
            })
    if len(results) > 1000: break

print(json.dumps(results, indent=4, default=str))
