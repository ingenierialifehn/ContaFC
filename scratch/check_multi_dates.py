import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    
    # Group by CONTEO and find those with more than 1 unique date
    date_counts = df.groupby('CONTEO')['FECHA'].nunique()
    multi_date = date_counts[date_counts > 1]
    
    print(f"CONTEOs with multiple dates: {len(multi_date)}")
    if len(multi_date) > 0:
        first_multi = multi_date.index[0]
        print(f"\nExample CONTEO: {first_multi}")
        print(df[df['CONTEO'] == first_multi][['FECHA', 'ACCT', 'DEBITO', 'CREDITO', 'DESCRIPCION2']].to_string())

except Exception as e:
    print("Error:", e)
