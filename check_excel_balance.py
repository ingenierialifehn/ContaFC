import pandas as pd
import sys

XLSX_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx"

try:
    df_raw = pd.read_excel(XLSX_PATH, header=None)
    for i, row in df_raw.iterrows():
        row_vals = [str(v).upper() for v in row.values]
        if 'FECHA' in row_vals and 'DEBITO' in row_vals:
            df = df_raw.iloc[i+1:].copy()
            df.columns = [str(c).upper().strip() for c in row.values]
            
            deb = pd.to_numeric(df['DEBITO'], errors='coerce').sum()
            cre = pd.to_numeric(df['CREDITO'], errors='coerce').sum()
            print(f"📊 Totales del Excel:")
            print(f"Total Débito: {deb:,.2f}")
            print(f"Total Crédito: {cre:,.2f}")
            print(f"Diferencia: {deb - cre:,.2f}")
            print(f"Registros encontrados: {len(df)}")
            break
except Exception as e:
    print(f"Error: {e}")
