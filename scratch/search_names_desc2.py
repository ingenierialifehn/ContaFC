import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

# Nombres de terceros encontrados en la DB
target_names = [
    "ESLY DALENY CRUZ GUERRERO",
    "LOURDES ANTONIA GAMEZ HERNANDEZ",
    "RIGOBERTO CANACA VARELA",
    "LETICIA ABIGAIL CANALES CANACA"
]

results = []
for row in sheet.iter_rows(min_row=6, values_only=True):
    desc2 = str(row[4]) if row[4] is not None else ""
    comp = str(row[5]) if row[5] is not None else ""
    for name in target_names:
        if name in desc2 or name in comp:
            results.append({
                "acct": row[2],
                "desc2": row[4],
                "comp": row[5]
            })
    if len(results) > 100: break

print(json.dumps(results, indent=4, default=str))
