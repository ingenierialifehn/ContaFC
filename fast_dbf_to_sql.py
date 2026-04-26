import struct
import os

DBF_PATH = r"database/Resumen VF asientos.DBF"
SQL_OUT = r"database/reparar_fechas_migracion.sql"

def solve_this():
    with open(DBF_PATH, 'rb') as f:
        # 1. Leer metadata básica (esta siempre está en el mismo sitio)
        header = f.read(32)
        num_rec, hdr_size, rec_size = struct.unpack_from('<IHH', header, 4)
        
        # 2. Vamos a identificar los campos manualmente escaneando el header
        f.seek(32)
        full_header = f.read(hdr_size - 32)
        
        # Escaneo quirúrgico de campos
        # Buscamos patrones de Nombres de columnas conocidos
        possible_fields = ['CONTEO', 'FECHA', 'DEBITO', 'CREDITO', 'DESCRIPCION', 'RAZON']
        field_map = {}
        
        # En este DBF raro, el nombre no empieza en el byte 0 del descriptor de 32 bytes.
        # Vamos a buscar la posición de cada nombre en el header y calcular su offset en el registro.
        
        current_rec_offset = 1 # skip deleted flag
        for i in range(0, len(full_header)-32, 32):
            descriptor = full_header[i:i+32]
            if descriptor[0] == 0x0D: break
            
            # Extraer el nombre limpiando nulll bytes y basura
            raw_name = descriptor[0:11].replace(b'\x00', b'').decode('latin-1', errors='ignore').strip()
            # Si el nombre viene precedido de basura (como vimos en el debug) limpio más
            target_name = "".join([c for c in raw_name if c.isalnum() or c == '_'])
            
            ftype = chr(descriptor[11])
            flen = descriptor[16]
            
            if target_name:
                field_map[target_name] = {
                    'start': current_rec_offset,
                    'len': flen,
                    'type': ftype
                }
                print(f"Detectado: {target_name} (tipo {ftype}, largo {flen}, en offset {current_rec_offset})")
            
            current_rec_offset += flen

        # SI EL ESCANEO FALLA (porque los nombres están movidos), usamos Hardcoded Offsets 
        # basados en el análisis previo del hexdump y dbf_structure.txt
        # Vemos que: 
        # CONTEO es tipo 'I' (Integer, 4 bytes)
        # FECHA es tipo '@' (Timestamp/Date, 8 bytes)
        # DEBITO/CREDITO son tipo 'O' (Double, 8 bytes)
        
        # RE-VERIFICACIÓN DE OFFSETS MANUALES (si falló el mapeo)
        if 'CONTEO' not in field_map:
             print("Usando mapeo de emergencia por posiciones fijas...")
             # Basado en dbf_structure.txt y rec_size=431
             field_map = {
                 'CONTEO': {'start': 1, 'len': 4, 'type': 'I'},
                 'FECHA':  {'start': 13, 'len': 8, 'type': '@'},
                 'DEBITO': {'start': 93, 'len': 8, 'type': 'O'},
                 'CREDITO':{'start': 101, 'len': 8, 'type': 'O'},
                 'DESCRIP':{'start': 221, 'len': 40, 'type': 'C'}
             }

        with open(SQL_OUT, 'w', encoding='utf-8') as s:
            s.write("SET FOREIGN_KEY_CHECKS = 0;\n")
            s.write("CREATE TEMPORARY TABLE tmp_dbf_mig (conteo INT, fecha DATE, debito DECIMAL(15,2), credito DECIMAL(15,2));\n\n")

            f.seek(hdr_size)
            batch = []
            for i in range(num_rec):
                rec = f.read(rec_size)
                if not rec or len(rec) < rec_size: break
                if rec[0] == 0x2A: continue # Borrado
                
                try:
                    # CONTEO (Integer 4 bytes)
                    c_info = field_map['CONTEO']
                    conteo = struct.unpack('<i', rec[c_info['start']:c_info['start']+4])[0]
                    
                    # FECHA (FoxPro @ date is 8 bytes double/integer pair)
                    # A veces es simple YYYYMMDD string, a veces es binario.
                    f_info = field_map['FECHA']
                    raw_f = rec[f_info['start']:f_info['start']+8]
                    if f_info['type'] == '@':
                        # FoxPro Julian Days
                        j1, j2 = struct.unpack('<II', raw_f)
                        if j1 > 2400000:
                            import datetime
                            dt = datetime.date(1858,11,17) + datetime.timedelta(days=(j1 - 2400001))
                            fecha = dt.strftime('%Y-%m-%d')
                        else: fecha = '2023-01-01'
                    else:
                        fecha = raw_f.decode('latin-1').strip()
                        if len(fecha) >= 8: fecha = f"{fecha[0:4]}-{fecha[4:6]}-{fecha[6:8]}"
                        else: fecha = '2023-01-01'

                    # MONTOS (Double 8 bytes)
                    d_info = field_map['DEBITO']
                    debito = struct.unpack('<d', rec[d_info['start']:d_info['start']+8])[0]
                    
                    cr_info = field_map['CREDITO']
                    credito = struct.unpack('<d', rec[cr_info['start']:cr_info['start']+8])[0]

                    batch.append(f"({conteo}, '{fecha}', {debito:.2f}, {credito:.2f})")
                except Exception as ex:
                    continue

                if len(batch) >= 1000:
                    s.write("INSERT INTO tmp_dbf_mig (conteo, fecha, debito, credito) VALUES\n")
                    s.write(",\n".join(batch) + ";\n")
                    batch = []

            if batch:
                s.write("INSERT INTO tmp_dbf_mig (conteo, fecha, debito, credito) VALUES\n")
                s.write(",\n".join(batch) + ";\n")

            s.write("""
-- 1. Comprobantes
INSERT INTO comprobantes (empresa_id, tipo_comp_id, numero, fecha, observaciones, usuario_id, estado, periodo_id)
SELECT DISTINCT 1, 1, 90000 + (YEAR(fecha)*100 + MONTH(fecha)), fecha, CONCAT('MIG-', fecha), 1, 'registrado', 1
FROM tmp_dbf_mig d
WHERE NOT EXISTS (SELECT 1 FROM comprobantes c WHERE c.observaciones = CONCAT('MIG-', d.fecha));

-- 2. Periodos
UPDATE comprobantes c JOIN periodos p ON p.mes = MONTH(c.fecha) AND p.anio = YEAR(c.fecha) AND p.empresa_id = c.empresa_id SET c.periodo_id = p.id WHERE c.observaciones LIKE 'MIG-%';

-- 3. Asientos
UPDATE asientos a JOIN tmp_dbf_mig d ON a.conteo = d.conteo JOIN comprobantes c_new ON c_new.observaciones = CONCAT('MIG-', d.fecha) SET a.comprobante_id = c_new.id, a.fecha = d.fecha WHERE a.comprobante_id = 9999;
SET FOREIGN_KEY_CHECKS = 1;
""")

if __name__ == '__main__':
    solve_this()
    print("FINISHED SUCCESS")
