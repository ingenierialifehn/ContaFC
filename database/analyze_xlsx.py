import pandas as pd
import os

filepath = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx"
if os.path.exists(filepath):
    try:
        # Read first 10 rows to find header
        df = pd.read_excel(filepath, nrows=10, header=None)
        print("XLSX Data Preview:")
        print(df.head(10))
    except Exception as e:
        print(f"Error reading XLSX: {e}")
else:
    print(f"File not found: {filepath}")
