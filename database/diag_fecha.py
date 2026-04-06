"""
Diagnóstico de campo FECHA en el DBF.
Muestra bytes crudos y prueba múltiples decodificaciones.
"""
import struct, os, datetime

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
OUT      = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\fecha_diag.txt"

# Layout confirmado por hex dump (data bytes tras el flag de borrado)
# CONTEO(I,4), ACCT(I,4), FECHA(@,8), TIPO(C,2), TANDA(I,4), ID_N(I,4),
# SALDANT(O,8), COMPANYCUST(C,35), INVC(C,8), DETALLE(C,40), ...
FECHA_DATA_OFFSET = 4 + 4          # después de CONTEO y ACCT = byte 8
INVC_DATA_OFFSET  = 4+4+8+2+4+4+8+35  # byte 69, confirmado = "18072023"
TIPO_DATA_OFFSET  = 4+4+8          # byte 16
TANDA_DATA_OFFSET = 4+4+8+2        # byte 18

with open(DBF_PATH, 'rb') as f:
    raw = f.read(32)
hdr_size = struct.unpack_from('<H', raw, 8)[0]
rec_size = struct.unpack_from('<H', raw, 10)[0]
num_rec  = struct.unpack_from('<I', raw, 4)[0]

lines = []
def w(s=""): lines.append(str(s))

w(f"hdr_size={hdr_size} rec_size={rec_size} num_rec={num_rec}")
w()

def try_all(raw8, label):
    # 1. LE double (standard IEEE 754)
    try:    v1 = struct.unpack('<d', raw8)[0]
    except: v1 = None
    # 2. BE double
    try:    v2 = struct.unpack('>d', raw8)[0]
    except: v2 = None
    # 3. FoxPro 'O' double decode
    b = bytearray(raw8)
    if b[7] & 0x80:
        b[7] ^= 0x80
    else:
        for i in range(8): b[i] ^= 0xFF
    try:    v3 = struct.unpack('>d', bytes(b))[0]
    except: v3 = None
    # 4. LE int32 first 4 bytes
    try:    v4 = struct.unpack('<I', raw8[:4])[0]
    except: v4 = None
    # 5. BE int32 first 4 bytes (XOR first byte)
    b5 = bytearray(raw8[:4]); b5[0] ^= 0x80
    try:    v5 = struct.unpack('>i', bytes(b5))[0]
    except: v5 = None
    # 6. LE int32 XOR 0x80000000
    try:    v6 = (struct.unpack('<I', raw8[:4])[0]) ^ 0x80000000
    except: v6 = None
    # 7. Days since 1899-12-30 (OLE/Excel)
    if v1 and 0 < v1 < 200000:
        base = datetime.date(1899, 12, 30)
        try: v7 = (base + datetime.timedelta(days=int(v1))).isoformat()
        except: v7 = f"LE-double={v1:.2f}"
    else: v7 = f"LE-double={v1}"
    # 8. Julian v4 → date
    JD_OFFSET = 2400001  # FoxPro epoch shift sometimes used
    v8 = None
    if v4 and 2400000 < v4 < 2500000:
        # direct JDN
        try:
            a = v4 + 32044
            b_ = (4*a+3)//146097
            c = a - (146097*b_)//4
            d = (4*c+3)//1461
            e = c - (1461*d)//4
            m = (5*e+2)//153
            day   = e - (153*m+2)//5 + 1
            month = m + 3 - 12*(m//10)
            year  = 100*b_ + d - 4800 + m//10
            v8 = f"{year:04d}-{month:02d}-{day:02d}"
        except: pass
    # 9. v6 as JDN
    v9 = None
    if v6 and 2400000 < v6 < 2500000:
        try:
            a = v6 + 32044
            b_ = (4*a+3)//146097
            c = a - (146097*b_)//4
            d = (4*c+3)//1461
            e = c - (1461*d)//4
            m = (5*e+2)//153
            day   = e - (153*m+2)//5 + 1
            month = m + 3 - 12*(m//10)
            year  = 100*b_ + d - 4800 + m//10
            v9 = f"{year:04d}-{month:02d}-{day:02d}"
        except: pass
    # 10. Ascii representation
    try:    v10 = raw8.decode('latin-1', errors='replace')
    except: v10 = '?'

    hex_str = ' '.join(f'{b:02X}' for b in raw8)
    w(f"  {label}: [{hex_str}]")
    w(f"    LE-dbl={v1}  BE-dbl={v2}  Fox-O-dbl={v3}")
    w(f"    LE-int32={v4}  BEi-XOR0={v5}  LE-XOR80={v6}")
    w(f"    OLE-excel-date={v7}  JDN(v4)={v8}  JDN(v6)={v9}")
    w(f"    ASCII={v10!r}")

with open(DBF_PATH, 'rb') as f:
    f.seek(hdr_size)
    for i in range(15):  # primeros 15 registros
        raw_rec = f.read(rec_size)
        if not raw_rec or len(raw_rec) < rec_size: break
        if raw_rec[0] == 0x2A:
            w(f"Rec {i+1}: BORRADO")
            continue
        data = raw_rec[1:]
        fecha_raw = data[FECHA_DATA_OFFSET:FECHA_DATA_OFFSET+8]
        invc_raw  = data[INVC_DATA_OFFSET:INVC_DATA_OFFSET+8]
        tipo_raw  = data[TIPO_DATA_OFFSET:TIPO_DATA_OFFSET+2]
        try: tipo_str = tipo_raw.decode('latin-1').strip()
        except: tipo_str = '?'
        invc_str = invc_raw.decode('latin-1', errors='replace').strip()
        w(f"Rec {i+1}: TIPO={tipo_str!r} INVC={invc_str!r}")
        try_all(fecha_raw, "FECHA")
        w()

with open(OUT, 'w', encoding='utf-8') as f:
    f.write('\n'.join(lines))
print(f"Escrito en: {OUT}")
