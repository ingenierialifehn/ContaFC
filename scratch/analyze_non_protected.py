
import re
backup_path = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql"

protected_ids = {'86'} # 11050101
counts = {'2023': 0, '2024': 0, '2025': 0}

with open(backup_path, "r", encoding="utf-8", errors="ignore") as f:
    for line in f:
        if "INSERT INTO `asientos` VALUES" in line:
            start = line.find("VALUES") + 7
            content = line[start:].strip()
            records = content.split("),(")
            for r in records:
                r = r.strip(" ();")
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
                                
                if len(fields) > 26:
                    cid = fields[4]
                    if cid not in protected_ids:
                        year = fields[26][:4]
                        if year in counts:
                            counts[year] += 1

print("Distribution of NON-PROTECTED records in backup by year:")
for year, count in counts.items():
    print(f"  {year}: {count} records")
