
import os
import sys
import pandas as pd
import re
import datetime

# Configuración
XLSX_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Rep. archivo-final.xlsx"
SCHEMA_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\schema.sql"
OUTPUT_SQL = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\migracion_asientos.sql"
DEFAULT_EMPRESA_ID = 1

def run_migration():
    print(f"🚀 Iniciando migración profunda desde Dataset...")
    
    # 1. Cargar Datos
    df = None
    if os.path.exists(XLSX_PATH):
        print(f"🔍 Cargando Excel: {os.path.basename(XLSX_PATH)}...")
        df_raw = pd.read_excel(XLSX_PATH, header=None)
        # Buscar la fila de encabezados
        header_idx = -1
        for i, row in df_raw.iterrows():
            row_vals = [str(v).upper() for v in row.values]
            if 'FECHA' in row_vals and ('ACCT' in row_vals or 'CUENTA' in row_vals):
                df = df_raw.iloc[i+1:].copy()
                df.columns = [str(c).upper().strip() for c in row.values]
                header_idx = i
                break
    
    if df is None:
        print("❌ No se pudo cargar el dataset correctamente.")
        return

    # Limpiar nombres de columnas nulas
    df.columns = [c if not pd.isna(c) and str(c).strip() != '' else f'COL_{i}' for i, c in enumerate(df.columns)]
    print(f"📋 Columnas detectadas: {df.columns.tolist()}")

    # 2. Definición de Mapeo
    mapping = {
        'ACCT': 'cuenta_id',
        'FECHA': 'fecha',
        'TANE': 'tercero_id',
        'COMPANYCUST': 'tercero_id',
        'DESCRI': 'descripcion',
        'DETALLE': 'descripcion',
        'DEBITO': 'debito',
        'CREDITO': 'credito',
        'CENT': 'ceco_id',
        'TANDA': 'doc_cruce_num',
        'CONTEO': 'conteo'
    }
    
    target_fields = sorted(list(set(mapping[c] for c in df.columns if c in mapping)))
    print(f"🎯 Campos destino: {target_fields}")

    # 3. Generar SQL
    with open(OUTPUT_SQL, 'w', encoding='utf-8') as f:
        f.write(f"-- MIGRACIÓN INTEGRAL - {datetime.datetime.now()}\n")
        f.write("SET foreign_key_checks = 0;\nUSE `contafc`;\n\n")

        f.write("-- 1. ASEGURAR COLUMNAS\n")
        f.write("ALTER TABLE `asientos` ADD COLUMN IF NOT EXISTS `conteo` INT DEFAULT NULL;\n")
        f.write("ALTER TABLE `asientos` ADD COLUMN IF NOT EXISTS `fecha` DATE DEFAULT NULL;\n")
        f.write("ALTER TABLE `asientos` ADD COLUMN IF NOT EXISTS `doc_cruce_num` VARCHAR(50) DEFAULT NULL;\n\n")

        # Cuentas
        if 'ACCT' in df.columns:
            print("📦 Generando Cuentas...")
            f.write("-- 2. REQUISITO: Cuentas\n")
            unique_accts = df['ACCT'].dropna().unique()
            for acct in unique_accts:
                clean_acct = str(acct).strip().replace('.', '')
                if not clean_acct: continue
                f.write(f"INSERT IGNORE INTO puc_cuentas (empresa_id, codigo, nombre, nivel, naturaleza, tipo_cuenta, acepta_movimiento) ")
                f.write(f"VALUES ({DEFAULT_EMPRESA_ID}, '{clean_acct}', 'Cuenta Migrada {clean_acct}', 4, 'D', 'A', 1);\n")
            f.write("\n")

        # Terceros
        ter_source = 'COMPANYCUST' if 'COMPANYCUST' in df.columns else ('TANE' if 'TANE' in df.columns else None)
        if ter_source:
            print(f"📦 Generando Terceros desde {ter_source}...")
            f.write("-- 3. REQUISITO: Terceros\n")
            unique_ters = df[ter_source].dropna().unique()
            for ter in unique_ters:
                ter_esc = str(ter).replace("'", "''").strip()
                if not ter_esc: continue
                code = re.sub(r'[^a-zA-Z0-9]', '', ter_esc)[:10].upper()
                f.write(f"INSERT IGNORE INTO terceros (empresa_id, codigo, razon_social, nit_cc, tipo_documento, tipo_tercero) ")
                f.write(f"VALUES ({DEFAULT_EMPRESA_ID}, '{code}', '{ter_esc}', '', 'RTN', 'cliente');\n")
            f.write("\n")

        # Comprobante
        f.write("-- 4. REQUISITO: Comprobante\n")
        f.write(f"INSERT IGNORE INTO tipos_comprobante (id, empresa_id, codigo, nombre, activo) VALUES (99, {DEFAULT_EMPRESA_ID}, 'MIG', 'Migración Legacy', 1);\n")
        f.write(f"INSERT IGNORE INTO comprobantes (id, empresa_id, tipo_comp_id, numero, fecha, periodo_id, usuario_id, estado) ")
        f.write(f"VALUES (9999, {DEFAULT_EMPRESA_ID}, 99, 1, '2023-01-01', 1, 1, 'registrado');\n\n")

        # Asientos
        print(f"✍️ Generando {len(df)} asientos...")
        batch_size = 1000
        global_linea = 1
        
        for i in range(0, len(df), batch_size):
            batch = df.iloc[i : i + batch_size]
            cols_sql = ["empresa_id", "comprobante_id", "linea"] + target_fields
            f.write(f"INSERT IGNORE INTO `asientos` (`" + "`, `".join(cols_sql) + "`) VALUES \n")
            
            rows_sql = []
            for _, row in batch.iterrows():
                vals = [str(DEFAULT_EMPRESA_ID), "9999", str(global_linea)]
                global_linea += 1
                
                for field in target_fields:
                    # Buscar valor en columnas origen que mapean a este campo
                    sources = [src for src, tgt in mapping.items() if tgt == field and src in df.columns]
                    val = None
                    for src in sources:
                        if not pd.isna(row[src]) and str(row[src]).strip() != '':
                            val = row[src]
                            break
                    
                    if val is None:
                        if field in ['debito', 'credito', 'conteo']: vals.append("0")
                        else: vals.append("NULL")
                    elif field == 'cuenta_id':
                        clean_acct = str(val).strip().replace('.', '')
                        vals.append(f"(SELECT id FROM puc_cuentas WHERE codigo = '{clean_acct}' AND empresa_id = {DEFAULT_EMPRESA_ID} LIMIT 1)")
                    elif field == 'tercero_id':
                        ter_esc = str(val).replace("'", "''").strip()
                        vals.append(f"(SELECT id FROM terceros WHERE razon_social = '{ter_esc}' AND empresa_id = {DEFAULT_EMPRESA_ID} LIMIT 1)")
                    elif field == 'fecha':
                        try:
                            if isinstance(val, (datetime.datetime, pd.Timestamp)):
                                vals.append(f"'{val.strftime('%Y-%m-%d')}'")
                            else:
                                d_str = str(val).strip().split(' ')[0]
                                if re.match(r'\d{4}-\d{2}-\d{2}', d_str): vals.append(f"'{d_str}'")
                                else: vals.append("NULL")
                        except: vals.append("NULL")
                    elif field in ['debito', 'credito', 'conteo']:
                        try:
                            num = float(val)
                            vals.append(f"{num:.2f}" if field != 'conteo' else str(int(num)))
                        except: vals.append("0")
                    else:
                        clean_val = str(val).replace("'", "''").strip()
                        vals.append(f"'{clean_val}'")
                
                rows_sql.append("(" + ", ".join(vals) + ")")
            
            f.write(",\n".join(rows_sql) + ";\n\n")

        f.write("SET foreign_key_checks = 1;\n")
        
    print(f"✅ ¡TERMINADO! Script en {OUTPUT_SQL}")

if __name__ == "__main__":
    run_migration()
