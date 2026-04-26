import pandas as pd
file_path = r'c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx'
try:
    xl = pd.ExcelFile(file_path)
    print("Sheets:", xl.sheet_names)
except Exception as e:
    print("Error:", e)
