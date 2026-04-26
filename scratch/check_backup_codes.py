import re

backup_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql'
target_codes = ['11050101', '110301', '11020101']

found = {}
with open(backup_path, 'r', encoding='utf-8', errors='ignore') as f:
    for line in f:
        if "INSERT INTO `puc_cuentas`" in line:
            for code in target_codes:
                if f"'{code}'" in line:
                    found[code] = True

print(f"Códigos encontrados en el respaldo: {list(found.keys())}")
