import struct
import os

DBF_PATH = r"database/Resumen VF asientos.DBF"

def inspect_fields():
    with open(DBF_PATH, 'rb') as f:
        header = f.read(1077)
        off = 32
        while off + 32 <= 1077:
            fd = header[off:off+32]
            if fd[0] == 0x0D: break
            name = fd[0:11].rstrip(b'\x00').decode('latin-1').strip()
            print(f"Field: '{name}'")
            off += 32

if __name__ == '__main__':
    inspect_fields()
