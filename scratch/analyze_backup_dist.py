
import re

backup_path = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql"

puc_map = {}
# Phase 1: Map PUC IDs to Codigos
with open(backup_path, "r", encoding="utf-8", errors="ignore") as f:
    for line in f:
        if "INSERT INTO `puc_cuentas` VALUES" in line:
            # Extract everything between VALUES ( and );
            start = line.find("VALUES") + 7
            content = line[start:].strip()
            # Split by ),( to handle multi-inserts
            records = content.split("),(")
            for r in records:
                # Clean up
                r = r.strip(" ();")
                # Using csv light split
                parts = []
                current = []
                in_quote = False
                for char in r:
                    if char == "'" and not in_quote: in_quote = True
                    elif char == "'" and in_quote: in_quote = False
                    elif char == "," and not in_quote:
                        parts.append("".join(current).strip("'"))
                        current = []
                    else:
                        current.append(char)
                parts.append("".join(current).strip("'"))
                
                if len(parts) >= 3:
                    puc_map[parts[0]] = parts[2]

print(f"Mapped {len(puc_map)} accounts from backup.")

# Phase 2: Analyze distribution
counts = {}
rows_total = 0
with open(backup_path, "r", encoding="utf-8", errors="ignore") as f:
    for line in f:
        if "INSERT INTO `asientos` VALUES" in line:
            start = line.find("VALUES") + 7
            content = line[start:].strip()
            records = content.split("),(")
            for r in records:
                r = r.strip(" ();")
                # field 4 is index 4 (0:id, 1:comp, 2:emp, 3:line, 4:cuenta)
                # Quick split by comma
                fields = []
                current = []
                in_quote = False
                for char in r:
                    if char == "'" and not in_quote: in_quote = True
                    elif char == "'" and in_quote: in_quote = False
                    elif char == "," and not in_quote:
                        fields.append("".join(current).strip("'"))
                        current = []
                    else:
                        current.append(char)
                fields.append("".join(current).strip("'"))
                                
                if len(fields) > 4:
                    rows_total += 1
                    cid = fields[4]
                    code = puc_map.get(cid, f"ID:{cid}")
                    counts[code] = counts.get(code, 0) + 1

print(f"Total asientos found: {rows_total}")
print("Top 20 accounts:")
sorted_counts = sorted(counts.items(), key=lambda x: x[1], reverse=True)
for code, count in sorted_counts[:20]:
    print(f"  {code}: {count}")
