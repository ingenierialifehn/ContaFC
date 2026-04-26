import openpyxl
import json

file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
wb = openpyxl.load_workbook(file_path, data_only=True)
sheet = wb['Archivo 2024']

# Mapeo de ID (TANDA) -> Nombre (COMPANYcust)
tanda_to_name = {}
for row in sheet.iter_rows(min_row=6, values_only=True):
    tanda = row[3]
    if tanda is not None:
        try:
            tanda_int = int(float(str(tanda)))
            if tanda_int not in tanda_to_name:
                tanda_to_name[tanda_int] = row[5]
        except:
            pass

# Códigos misteriosos de la DB
target_codes = [
    '312256640', '329033856', '873735810', '902001026', '903751811', '904962691', 
    '915816577', '917027457', '918778242', '935555458', '949371009', '950581889', 
    '952332674', '1002664322', '1021192323', '1037969539', '1050034305', '1138632835', 
    '1222518915', '1239296131', '1256073347', '1289627779', '1457399939', '1490954371', 
    '1558063235', '1574840451', '1641949315', '1965045891', '1977715841', '1980072322'
]

results = {}
for code_str in target_codes:
    code_val = int(code_str)
    # Intentar encontrar el ID al final del código
    # Probamos con los últimos 3, 4 o 5 dígitos
    found = False
    for i in range(3, 7):
        suffix = code_val % (10**i)
        if suffix in tanda_to_name:
            results[code_str] = tanda_to_name[suffix]
            found = True
            break
    if not found:
        results[code_str] = "No encontrado"

print(json.dumps(results, indent=4, default=str))
