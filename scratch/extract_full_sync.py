import os
import pandas as pd
import json

base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
output_path = os.path.join(base_dir, 'database', 'excel_full_sync.json')

try:
    print("Leyendo Excel completo (esto puede tardar)...")
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    
    df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')
    
    # Solo nos interesa CONTEO y FECHA para la sincronización rápida
    df_sync = df[['CONTEO', 'FECHA']].dropna(subset=['CONTEO', 'FECHA'])
    
    sync_map = {}
    for _, row in df_sync.iterrows():
        try:
            conteo = int(row['CONTEO'])
            fecha = row['FECHA'].strftime('%Y-%m-%d')
            sync_map[conteo] = fecha
        except:
            continue
            
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(sync_map, f)
        
    print(f"Exito: {len(sync_map)} mapeos de fecha guardados.")

except Exception as e:
    print(f"Error: {str(e)}")
