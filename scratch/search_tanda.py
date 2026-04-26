import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

target_codes = [
    312256640, 329033856, 873735810, 902001026, 903751811, 904962691, 
    915816577, 917027457, 918778242, 935555458, 949371009, 950581889, 
    952332674, 1002664322, 1021192323, 1037969539, 1050034305, 1138632835, 
    1222518915, 1239296131, 1256073347, 1289627779, 1457399939, 1490954371, 
    1558063235, 1574840451, 1641949315, 1965045891, 1977715841, 1980072322
]

mapping = {}

for row in sheet.iter_rows(min_row=6, values_only=True):
    # ACCT (idx 2), TANDA (idx 3), COMPANYcust (idx 5)
    tanda = row[3]
    if tanda is not None:
        try:
            tanda_int = int(float(str(tanda)))
            if tanda_int in target_codes:
                person_name = row[5]
                if str(tanda_int) not in mapping:
                    mapping[str(tanda_int)] = person_name
        except:
            pass

print(json.dumps(mapping, indent=4, default=str))
