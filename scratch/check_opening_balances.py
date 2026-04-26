
import re
backup_path = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\backups\backup_20260406_104035.sql"

counts = {}
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
                    date = fields[26]
                    if date == '2023-01-01':
                        desc = fields[10]
                        counts[desc] = counts.get(desc, 0) + 1

print("Opening balance records (2023-01-01) in backup:")
for desc, count in counts.items():
    print(f"  '{desc}': {count} records")
if not counts:
    print("  No records found for 2023-01-01")
