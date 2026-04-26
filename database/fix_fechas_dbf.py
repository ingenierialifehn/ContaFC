import os
import struct
import datetime

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
OUTPUT_SQL = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\fix_fechas.sql"
DBF_ENCODING = 'latin-1'

FIELD_DEFS = [
    ('CONTEO',      'I', 4),
    ('ACCT',        'I', 4),
    ('FECHA',       '@', 8),
    ('TIPO',        'C', 2),
    ('TANDA',       'I', 4),
    ('ID_N',        'I', 4),
    ('SALDANT',     'O', 8),
    ('COMPANYCUST', 'C', 35),
    ('INVC',        'C', 8),
    ('DETALLE',     'C', 40),
    ('DEBITO',      'O', 8),
    ('CREDITO',     'O', 8),
    ('SALDO',       'O', 8),
    ('SALDOFINAL',  'O', 8),
    ('RAZON',       'C', 150),
    ('NIT',         'C', 14),
    ('ID_N1',       'I', 4),
    ('DESCRIPCION', 'C', 40),
    ('DESCRIPCION2','C', 30),
    ('DIRECCION1',  'C', 33),
    ('USUARIO',     'C', 10),
]

RECORD_SIZE_EXPECTED = 1 + sum(l for _, _, l in FIELD_DEFS)

def parse_fecha(raw8: bytes):
    try:
        be_val = struct.unpack('>d', raw8)[0]
        if be_val <= 0:
            return None
        ticks_100ns = int(be_val) * 10000
        seconds = ticks_100ns // 10_000_000
        base   = datetime.date(1, 1, 1)
        d      = base + datetime.timedelta(seconds=seconds) - datetime.timedelta(days=1)
        if d.year < 2000 or d.year > 2030:
            return None
        return d.strftime('%Y-%m-%d')
    except Exception:
        return None

def foxpro_int(raw4: bytes):
    b = bytearray(raw4)
    b[0] ^= 0x80
    return struct.unpack('>i', bytes(b))[0]

def main():
    if not os.path.exists(DBF_PATH):
        print(f"ERROR: {DBF_PATH} no encontrado.")
        return

    with open(DBF_PATH, 'rb') as f:
        raw_hdr = f.read(32)

    num_records  = struct.unpack_from('<I', raw_hdr, 4)[0]
    header_size  = struct.unpack_from('<H', raw_hdr, 8)[0]
    record_size  = struct.unpack_from('<H', raw_hdr, 10)[0]

    updates = []
    
    with open(DBF_PATH, 'rb') as f:
        f.seek(header_size)
        for rec_idx in range(num_records):
            raw_rec = f.read(record_size)
            if not raw_rec or len(raw_rec) < record_size:
                break

            if raw_rec[0] == 0x2A:
                continue

            data = raw_rec[1:]
            pos  = 0
            
            conteo = None
            fecha = None
            
            for (fname, ftype, flen) in FIELD_DEFS:
                chunk = data[pos:pos+flen]
                pos  += flen
                
                if fname == 'CONTEO':
                    conteo = foxpro_int(chunk)
                elif fname == 'FECHA':
                    fecha = parse_fecha(chunk)
            
            if conteo is not None and fecha is not None:
                updates.append(f"UPDATE asientos SET fecha = '{fecha}' WHERE conteo = {conteo} AND (fecha != '{fecha}' OR fecha IS NULL);")

    print(f"Total valid DBF records with dates: {len(updates)}")
    with open(OUTPUT_SQL, 'w', encoding='utf-8') as f:
        for stmt in updates:
            f.write(stmt + "\n")
    print(f"Updates written to {OUTPUT_SQL}")

if __name__ == "__main__":
    main()
