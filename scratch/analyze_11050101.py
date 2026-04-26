
import re
backup_path = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql"

puc_map = {}
with open(backup_path, "r", encoding="utf-8", errors="ignore") as f:
    for line in f:
        if "INSERT INTO `puc_cuentas` VALUES" in line:
            start = line.find("VALUES") + 7
            content = line[start:].strip()
            records = content.split("),(")
            for r in records:
                r = r.strip(" ();")
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

# Find ID for 11050101
id_11050101 = next((k for k, v in puc_map.items() if v == '11050101'), None)
print(f"ID for 11050101 is: {id_11050101}")

if not id_11050101:
    print("Could not find ID for 11050101")
    exit()

dates = {}
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
                                
                if len(fields) > 26 and fields[4] == id_11050101:
                    date = fields[26]
                    year = date[:4]
                    dates[year] = dates.get(year, 0) + 1

print("Distribution of records for 11050101 by year:")
for year in sorted(dates.keys()):
    print(f"  {year}: {dates[year]} records")
