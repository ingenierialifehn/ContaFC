import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    
    # Broad search
    hits = df[df['DESCRIPCION2'].str.contains('INTERES', case=False, na=False) | 
              df['DESCRIPCION2'].str.contains('FINANC', case=False, na=False)]
    
    print(f"Total matching rows: {len(hits)}")
    if len(hits) > 0:
        print(hits[['FECHA', 'ACCT', 'DEBITO', 'CREDITO', 'CONTEO', 'DESCRIPCION2']].head(20).to_string())
        print("\nAccounts used for these rows:")
        print(hits['ACCT'].value_counts())

except Exception as e:
    print("Error:", e)
