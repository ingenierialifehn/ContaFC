import struct
import datetime

DBF_PATH = r"database/Resumen VF asientos.DBF"

def decode_foxpro_date(data):
    # FoxPro Julian Days
    # It's two 4-byte integers
    j1, j2 = struct.unpack('<II', data)
    if j1 == 0: return None
    return datetime.date(1858,11,17) + datetime.timedelta(days=(j1 - 2400001))

def find_sample():
    with open(DBF_PATH, 'rb') as f:
        header = f.read(32)
        num_rec, hdr_size, rec_size = struct.unpack_from('<IHH', header, 4)
        f.seek(hdr_size)
        
        for i in range(min(num_rec, 5000)):
            rec = f.read(rec_size)
            if not rec: break
            if b'Financiac' in rec:
                conteo = struct.unpack('<i', rec[1:5])[0]
                acct = struct.unpack('<i', rec[5:9])[0]
                date_bytes = rec[13:21]
                fecha = decode_foxpro_date(date_bytes)
                
                print(f"Record {i}:")
                print(f"  CONTEO: {conteo}")
                print(f"  ACCT: {acct}")
                print(f"  FECHA: {fecha}")
                # print first 50 bytes of rec as hex
                print(f"  Raw Start: {rec[:40].hex()}")
                return

if __name__ == '__main__':
    find_sample()
