import openpyxl
import json

def get_tanda_map(sheet_name):
    print(f"Mapeando hoja {sheet_name}...")
    wb = openpyxl.load_workbook(r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx', data_only=True)
    sheet = wb[sheet_name]
    mapping = {}
    for row in sheet.iter_rows(min_row=6, values_only=True):
        tanda = row[3]
        if tanda is not None:
            try:
                tanda_int = int(float(str(tanda)))
                if tanda_int not in mapping:
                    mapping[tanda_int] = row[5]
            except:
                pass
    return mapping

map2024 = get_tanda_map('Archivo 2024')
map2025 = get_tanda_map('Archivo 2025')

# Combinar mapas
full_map = {**map2024, **map2025}

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
    found = False
    # Probar sufijos de mayor a menor longitud
    for i in range(9, 0, -1):
        suffix = code_val % (10**i)
        if suffix in full_map:
            results[code_str] = full_map[suffix]
            found = True
            break
    if not found:
        results[code_str] = "No encontrado"

print(json.dumps(results, indent=4, default=str))
