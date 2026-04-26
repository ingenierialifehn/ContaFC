import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    df['ACCT'] = df['ACCT'].astype(str).str.strip()
    df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')
    
    # 2023 specifically
    cxc_2023 = df[(df['ACCT'] == '11050101') & (df['FECHA'].dt.year == 2023)]
    
    # Sort by credito DESC
    top_credits = cxc_2023.sort_values(by='CREDITO', ascending=False).head(10)
    
    print("Top 10 Credits for 11050101 in Excel (ONLY 2023):")
    print(top_credits[['FECHA', 'DEBITO', 'CREDITO', 'CONTEO', 'DESCRIPCION2']].to_string())
    
    # Check one of these in the DB mapping
    sample_conteo = top_credits.iloc[0]['CONTEO']
    print(f"\nChecking sample CONTEO {sample_conteo} in JSON mapping...")
    import json
    json_path = os.path.join(base_dir, 'database', 'excel_full_sync.json')
    with open(json_path, 'r') as f:
        sync_map = json.load(f)
    print(f"Mapping for {sample_conteo}:", sync_map.get(str(int(sample_conteo))))

except Exception as e:
    print("Error:", e)
