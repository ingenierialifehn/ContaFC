import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

mapping = {}
for row in sheet.iter_rows(min_row=6, values_only=True):
    tanda = row[3] # TANDA is index 3
    if tanda is not None:
        try:
            tanda_int = int(float(str(tanda)))
            if str(tanda_int) not in mapping:
                mapping[str(tanda_int)] = row[5] # COMPANYcust
        except:
            pass

print(json.dumps(mapping, indent=4, default=str))
