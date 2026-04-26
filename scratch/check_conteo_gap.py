import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    df['ACCT'] = df['ACCT'].astype(str).str.strip()
    
    # 2023 and account 11050101
    df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')
    df_2023 = df[(df['FECHA'].dt.year == 2023) & (df['ACCT'] == '11050101')]
    
    print(f"Excel 2023 Rows for 11050101: {len(df_2023)}")
    print(f"Sum Debito:  {df_2023['DEBITO'].sum():,.2f}")
    print(f"Sum Credito: {df_2023['CREDITO'].sum():,.2f}")
    
    # How many have CONTEO?
    with_conteo = df_2023[df_2023['CONTEO'].notnull()]
    print(f"Rows with CONTEO: {len(with_conteo)} / {len(df_2023)}")
    
    # If some don't have CONTEO, how much is their credit?
    without_conteo = df_2023[df_2023['CONTEO'].isnull()]
    print(f"Sum Credito WITHOUT CONTEO: {without_conteo['CREDITO'].sum():,.2f}")

except Exception as e:
    print("Error:", e)
