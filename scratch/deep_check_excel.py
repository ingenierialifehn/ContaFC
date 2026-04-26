import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    df['ACCT'] = df['ACCT'].astype(str).str.strip()
    
    cxc = df[df['ACCT'] == '11050101']
    print(f"Total rows for 11050101: {len(cxc)}")
    
    print("\nResumen de columnas:")
    print(cxc[['DEBITO', 'CREDITO']].describe())
    
    print("\nSuma DEBITO:", cxc['DEBITO'].sum())
    print("Suma CREDITO:", cxc['CREDITO'].sum())
    
    # Check for negative values
    neg_creds = cxc[cxc['CREDITO'] < 0]
    print(f"\nRows with negative CREDITO: {len(neg_creds)}")
    if len(neg_creds) > 0:
        print("Suma credits negativos:", neg_creds['CREDITO'].sum())

except Exception as e:
    print("Error:", e)
