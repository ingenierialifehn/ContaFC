import struct

DBF_PATH = r"database/Resumen VF asientos.DBF"

def scan_for_jd():
    with open(DBF_PATH, 'rb') as f:
        header = f.read(32)
        num_rec, hdr_size, rec_size = struct.unpack_from('<IHH', header, 4)
        f.seek(hdr_size)
        
        for i in range(10):
            rec = f.read(rec_size)
            if not rec: break
            print(f"Record {i}:")
            for j in range(0, rec_size - 4, 1):
                val = struct.unpack('<I', rec[j:j+4])[0]
                if 2400000 < val < 2500000:
                    print(f"  Pos {j}: Found JD-like {val}")
            print(f"  Hex: {rec[:40].hex()}")

if __name__ == '__main__':
    scan_for_jd()
