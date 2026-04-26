import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    # Filter account 11050101 and where CREDITO > 0
    creditos = df[(df['ACCT'] == '11050101') & (df['CREDITO'] > 0)]
    print(f"Total creditos found in Excel for 11050101: {len(creditos)}")
    print(f"Sum of credits: {creditos['CREDITO'].sum():,.2f}")
    print("\nSample of credit rows:")
    print(creditos[['FECHA', 'ACCT', 'DEBITO', 'CREDITO', 'CONTEO']].head(10).to_string())
    
    # Check if they have CONTEO
    with_conteo = creditos[creditos['CONTEO'].notnull()]
    print(f"\nCreditos with CONTEO: {len(with_conteo)}")

except Exception as e:
    print("Error:", e)
