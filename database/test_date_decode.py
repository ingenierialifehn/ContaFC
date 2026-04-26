import struct
import datetime

# Test with a raw 8 bytes from the DBF if possible.
# Let's take the first hex dump line that has FECHA
# We don't have the exact 8 bytes yet. Let's open the DBF and read the first record's FECHA.

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"

def parse_fecha_legacy(raw8):
    try:
        be_val = struct.unpack('>d', raw8)[0]
        if be_val <= 0: return None
        ticks = int(be_val) * 10000
        seconds = ticks // 10_000_000
        base = datetime.date(1, 1, 1)
        d = base + datetime.timedelta(seconds=seconds) - datetime.timedelta(days=1)
        return d.strftime('%Y-%m-%d')
    except Exception as e:
        return None

def parse_fecha_julian(raw8):
    try:
        # dBASE 7 Timestamp: 2 x 32-bit integers (Little Endian usually)
        days, ms = struct.unpack('<II', raw8)
        # Julian day to Gregorian
        if days == 0: return None
        
        # conversion formula from Julian Day Number to Gregorian Date
        # standard astronomical JDN
        J = days
        y = 4716
        v = 3
        j = 1461
        u = 123
        y2 = 2
        m = 2
        f = J + 1401 + (((4 * J + 274277) // 146097) * 3) // 4 - 38
        e = 4 * f + 3
        g = (e % 1461) // 4
        h = 5 * g + 2
        day = (h % 153) // 5 + 1
        month = (h // 153 + 2) % 12 + 1
        year = (e // 1461) - 4716 + (12 + 2 - month) // 12
        
        return f"{year:04d}-{month:02d}-{day:02d}"
    except Exception as e:
        return None

with open(DBF_PATH, 'rb') as f:
    hdr = f.read(32)
    header_size = struct.unpack_from('<H', hdr, 8)[0]
    record_size = struct.unpack_from('<H', hdr, 10)[0]
    
    f.seek(header_size)
    raw_rec = f.read(record_size)
    # FECHA is the 3rd field.
    # CONTEO (4), ACCT (4), FECHA (8)
    # The record starts with a 1-byte deletion flag.
    # offset = 1 + 4 + 4 = 9
    fecha_raw = raw_rec[9:17]
    
    print("Raw hex:", fecha_raw.hex())
    print("Legacy decode:", parse_fecha_legacy(fecha_raw))
    print("Julian decode:", parse_fecha_julian(fecha_raw))
