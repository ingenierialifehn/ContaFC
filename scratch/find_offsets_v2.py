import struct
import datetime

DBF_PATH = r"database/Resumen VF asientos.DBF"

def find_financiac():
    with open(DBF_PATH, 'rb') as f:
        header = f.read(32)
        num_rec, hdr_size, rec_size = struct.unpack_from('<IHH', header, 4)
        print(f"Header size: {hdr_size}, Rec size: {rec_size}")
        f.seek(hdr_size)
        
        for i in range(num_rec):
            rec = f.read(rec_size)
            if b'Financiac' in rec:
                print(f"Record {i} matches!")
                # Print hex of the first 100 bytes
                print(f"Hex: {rec[:120].hex()}")
                # Try to decode CONTEO at 1
                conteo = struct.unpack('<i', rec[1:5])[0]
                print(f"CONTEO at 1: {conteo}")
                # Try to decode FECHA (look for YYYYMMDD string or Julian)
                # We saw 18072023 at offset 70 in a previous record.
                # Let's see what's at offset 70 here.
                print(f"String at 70: {rec[70:85].decode('latin-1', errors='ignore')}")
                
                # Check for @ date at 5 or 9 or 13
                for off in [5, 9, 13, 21]:
                    try:
                        j1, j2 = struct.unpack('<II', rec[off:off+8])
                        if 2400000 < j1 < 2500000:
                            dt = datetime.date(1858,11,17) + datetime.timedelta(days=(j1 - 2400001))
                            print(f"Found @ Date at {off}: {dt}")
                    except: pass
                
                if i > 30000: break # Safety

if __name__ == '__main__':
    find_financiac()
