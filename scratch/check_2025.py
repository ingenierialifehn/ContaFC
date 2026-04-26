import os
import pandas as pd

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')

try:
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')
    print("Rango de fechas en archivo-final.txt:")
    print("Min:", df['FECHA'].min())
    print("Max:", df['FECHA'].max())
    
    # Check if Archivo 2025 sheet exists and what it has
    xl = pd.ExcelFile(excel_path)
    if 'Archivo 2025' in xl.sheet_names:
        df25 = pd.read_excel(excel_path, sheet_name='Archivo 2025', nrows=10)
        print("\nPrimeras filas de Archivo 2025:")
        print(df25.to_string())

except Exception as e:
    print("Error:", e)
