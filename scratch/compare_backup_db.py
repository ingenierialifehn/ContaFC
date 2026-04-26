
import re
import mysql.connector

# DB Config from .env
db_config = {
    'host': 'localhost',
    'port': 3307, # Maped port in docker-compose for external access
    'user': 'contafc_user',
    'password': 'C0nt4FC!2026',
    'database': 'contafc'
}

backup_path = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql"
protected_codes = {'11050101', '110301', '11020101'}

# Phase 1: Map PUC IDs in Backup
puc_map = {}
with open(backup_path, "r", encoding="utf-8", errors="ignore") as f:
    for line in f:
        if "INSERT INTO `puc_cuentas` VALUES" in line:
            start = line.find("VALUES") + 7
            content = line[start:].strip()
            records = content.split("),(")
            for r in records:
                r = r.strip(" ();")
                parts = r.split(",") # Rough split
                if len(parts) >= 3:
                    cid = parts[0].strip("'")
                    code = parts[2].strip("'")
                    puc_map[cid] = code

# Phase 2: Get distribution in Backup
backup_counts = {}
with open(backup_path, "r", encoding="utf-8", errors="ignore") as f:
    for line in f:
        if "INSERT INTO `asientos` VALUES" in line:
            start = line.find("VALUES") + 7
            content = line[start:].strip()
            records = content.split("),(")
            for r in records:
                r = r.strip(" ();")
                fields = r.split(",")
                if len(fields) > 4:
                    cid = fields[4].strip("'")
                    code = puc_map.get(cid, cid)
                    if code not in protected_codes:
                        backup_counts[code] = backup_counts.get(code, 0) + 1

# Phase 3: Get distribution in DB
try:
    conn = mysql.connector.connect(**db_config)
    cursor = conn.cursor()
    cursor.execute("SELECT c.codigo, COUNT(*) FROM asientos a JOIN puc_cuentas c ON a.cuenta_id = c.id WHERE a.comprobante_id = 9999 GROUP BY c.codigo")
    db_counts = {row[0]: row[1] for row in cursor.fetchall()}
    conn.close()
except Exception as e:
    print(f"DB Error: {e}")
    db_counts = {}

print(f"{'Code':<15} | {'Backup':<10} | {'DB (9999)':<10} | {'Diff'}")
print("-" * 50)
all_codes = set(backup_counts.keys()) | set(db_counts.keys())
for code in sorted(all_codes):
    b = backup_counts.get(code, 0)
    d = db_counts.get(code, 0)
    if b > d or (b == 0 and d > 0):
        print(f"{code:<15} | {b:<10} | {d:<10} | {b-d}")
