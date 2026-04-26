import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    df['ACCT'] = df['ACCT'].astype(str).str.strip()
    
    # Rows with Intereses Financiación in Desc
    intereses = df[df['DESCRIPCION2'].str.contains('Intereses Financiación', case=False, na=False)]
    print(f"Total Intereses Financiación rows: {len(intereses)}")
    print(intereses[['FECHA', 'DEBITO', 'CREDITO', 'CONTEO']].head(20).to_string())
    
    # Years of these interests
    print("\nYears for Intereses Financiación:")
    print(pd.to_datetime(intereses['FECHA']).dt.year.value_counts())

except Exception as e:
    print("Error:", e)
