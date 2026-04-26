import os
import struct
import json
import datetime

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
JSON_OUT = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\dbf_data.json"

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
        if be_val <= 0: return None
        ticks_100ns = int(be_val) * 10000
        seconds = ticks_100ns // 10_000_000
        base   = datetime.date(1, 1, 1)
        d      = base + datetime.timedelta(seconds=seconds) - datetime.timedelta(days=1)
        if d.year < 2000 or d.year > 2030: return None
        return d.strftime('%Y-%m-%d')
    except Exception: return None

def foxpro_int(raw4: bytes):
    b = bytearray(raw4)
    b[0] ^= 0x80
    return struct.unpack('>i', bytes(b))[0]

def foxpro_double(raw8: bytes):
    b = bytearray(raw8)
    if b[0] & 0x80:
        b[0] ^= 0x80
    else:
        for i in range(8): b[i] ^= 0xFF
    try:
        val = struct.unpack('>d', bytes(b))[0]
        return 0.0 if str(val) == 'nan' or 'inf' in str(val).lower() else val
    except: return 0.0

def main():
    if not os.path.exists(DBF_PATH): return

    with open(DBF_PATH, 'rb') as f:
        raw_hdr = f.read(32)

    num_records  = struct.unpack_from('<I', raw_hdr, 4)[0]
    header_size  = struct.unpack_from('<H', raw_hdr, 8)[0]
    record_size  = struct.unpack_from('<H', raw_hdr, 10)[0]

    out = []
    
    with open(DBF_PATH, 'rb') as f:
        f.seek(header_size)
        for _ in range(num_records):
            raw_rec = f.read(record_size)
            if not raw_rec or len(raw_rec) < record_size: break
            if raw_rec[0] == 0x2A: continue

            data = raw_rec[1:]
            pos  = 0
            row  = {}
            
            for (fname, ftype, flen) in FIELD_DEFS:
                chunk = data[pos:pos+flen]
                pos  += flen
                
                if fname == 'CONTEO': row['conteo'] = foxpro_int(chunk)
                elif fname == 'ACCT': row['acct'] = str(foxpro_int(chunk))
                elif fname == 'FECHA': row['fecha'] = parse_fecha(chunk)
                elif fname == 'DEBITO': row['debito'] = round(foxpro_double(chunk), 4)
                elif fname == 'CREDITO': row['credito'] = round(foxpro_double(chunk), 4)
                elif fname == 'DESCRIPCION': row['desc'] = chunk.decode('latin-1', errors='replace').rstrip()
                elif fname == 'DETALLE': row['detalle'] = chunk.decode('latin-1', errors='replace').rstrip()
            
            out.append(row)

    with open(JSON_OUT, 'w', encoding='utf-8') as f:
        json.dump(out, f, ensure_ascii=False)
    print(f"Exported {len(out)} records to {JSON_OUT}")

if __name__ == "__main__":
    main()
