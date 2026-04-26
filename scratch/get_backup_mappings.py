import re

backup_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql'
id_to_codigo = {}

with open(backup_path, 'r', encoding='utf-8', errors='ignore') as f:
    for line in f:
        if "INSERT INTO `puc_cuentas` VALUES" in line:
            part = line[line.find("VALUES ")+7:]
            # Splitting by '),('
            records = part.split("),")
            for r in records:
                r = r.strip(" ()\r\n;")
                fields = r.split(",")
                if len(fields) >= 3:
                    cid = fields[0].strip("'")
                    code = fields[2].strip("'")
                    id_to_codigo[cid] = code

for cid in ['86', '88', '89', '85', '98', '95']:
    print(f"ID {cid} -> {id_to_codigo.get(cid, 'Unknown')}")
