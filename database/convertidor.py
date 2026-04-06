import pandas as pd
from dbfread import DBF

def conversion_emergencia(archivo_dbf, nombre_tabla, archivo_sql):
    try:
        # Forzamos la lectura ignorando tipos de campo erróneos
        table = DBF(archivo_dbf, load=True, ignore_missing_memofile=True, char_decode_errors='ignore')
        
        # Intentamos extraer los datos registro por registro manualmente
        registros = []
        for record in table:
            registros.append(record)
        
        df = pd.DataFrame(registros)
        
        # Limpiar nombres de columnas
        df.columns = [str(col).replace(' ', '_').replace('.', '_') for col in df.columns]

        with open(archivo_sql, 'w', encoding='utf-8') as f:
            f.write(f"DROP TABLE IF EXISTS `{nombre_tabla}`;\n")
            f.write(f"CREATE TABLE `{nombre_tabla}` (\n")
            f.write(",\n".join([f"  `{col}` TEXT" for col in df.columns]))
            f.write("\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n\n")

            for _, row in df.iterrows():
                cols = "`, `".join(df.columns)
                vals = "', '".join([str(val).replace("'", "''").replace("None", "").strip() for val in row.values])
                f.write(f"INSERT INTO `{nombre_tabla}` (`{cols}`) VALUES ('{vals}');\n")

        print(f"✅ ¡LOGRADO! Script creado: {archivo_sql}")

    except Exception as e:
        print(f"⚠️ El archivo está muy dañado para lectura directa: {e}")
        print("Sigue el paso de la 'Opción B' abajo.")

conversion_emergencia('Resumen VF asientos.DBF', 'resumen_asientos', 'migracion_villafrancis.sql')