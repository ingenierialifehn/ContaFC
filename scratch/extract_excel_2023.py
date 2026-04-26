import os
import pandas as pd
import json

# Rutas relativas al directorio del proyecto
base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
excel_path = os.path.join(base_dir, 'database', 'Rep. archivo-final.xlsx')
output_path = os.path.join(base_dir, 'database', 'excel_2023_cxc.json')

try:
    print("Leyendo Excel (esto puede tardar)...")
    df = pd.read_excel(excel_path, sheet_name='archivo-final.txt')
    
    # Convertir FECHA a datetime si no lo es
    df['FECHA'] = pd.to_datetime(df['FECHA'], errors='coerce')
    
    # Filtrar solo 2023
    df_2023 = df[df['FECHA'].dt.year == 2023].copy()
    
    # Nos interesan principalmente: CONTEO, ACCT, FECHA, DEBITO, CREDITO
    # También DESCRIPCION2 para referencia
    result = []
    for _, row in df_2023.iterrows():
        result.append({
            'conteo': int(row['CONTEO']) if not pd.isna(row['CONTEO']) else None,
            'acct': str(row['ACCT']),
            'fecha': row['FECHA'].strftime('%Y-%m-%d') if not pd.isna(row['FECHA']) else None,
            'debito': float(row['DEBITO']) if not pd.isna(row['DEBITO']) else 0.0,
            'credito': float(row['CREDITO']) if not pd.isna(row['CREDITO']) else 0.0,
            'desc': str(row['DESCRIPCION2']) if not pd.isna(row['DESCRIPCION2']) else ''
        })
    
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(result, f, indent=2)
        
    print(f"Exito: {len(result)} registros de 2023 guardados en {output_path}")

except Exception as e:
    print(f"Error: {str(e)}")
