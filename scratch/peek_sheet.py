import pandas as pd
file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
try:
    df = pd.read_excel(file_path, sheet_name='archivo-final.txt', nrows=20)
    print(df.to_string())
except Exception as e:
    print("Error:", e)
