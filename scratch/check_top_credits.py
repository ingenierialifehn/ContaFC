import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    df['ACCT'] = df['ACCT'].astype(str).str.strip()
    
    # Filter 11050101
    cxc = df[df['ACCT'] == '11050101']
    
    # Sort by credito DESC
    top_credits = cxc.sort_values(by='CREDITO', ascending=False).head(10)
    
    print("Top 10 Credits for 11050101 in Excel:")
    print(top_credits[['FECHA', 'DEBITO', 'CREDITO', 'CONTEO', 'DESCRIPCION2']].to_string())

except Exception as e:
    print("Error:", e)
