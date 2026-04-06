import pandas as pd
import sys

XLSX_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx"

try:
    df_raw = pd.read_excel(XLSX_PATH, header=None, nrows=100)
    for i, row in df_raw.iterrows():
        row_vals = [str(v).upper() for v in row.values]
        if 'FECHA' in row_vals and ('ACCT' in row_vals or 'CUENTA' in row_vals):
            df = pd.read_excel(XLSX_PATH, skiprows=i+1)
            print("XLSX Loaded.")
            print(f"Total rows: {len(df)}")
            print("Columns:", df.columns.tolist())
            
            # Check years
            if 'FECHA' in df.columns:
                df['year'] = pd.to_datetime(df['FECHA'], errors='coerce').dt.year
                print("Years found in XLSX:")
                print(df['year'].value_counts())
            
            print("\nFirst 5 accounts:")
            print(df['ACCT'].head())
            sys.exit(0)
    print("Header not found")
except Exception as e:
    print(f"Error: {e}")
