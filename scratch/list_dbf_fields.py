import struct
import os

DBF_PATH = r"database/Resumen VF asientos.DBF"

def list_fields():
    with open(DBF_PATH, 'rb') as f:
        f.seek(32)
        fields = []
        while True:
            descriptor = f.read(32)
            if not descriptor or descriptor[0] == 0x0D:
                break
            name = descriptor[0:11].replace(b'\x00', b'').decode('latin-1', errors='ignore').strip()
            # Clean non-printable characters
            name = "".join([c for c in name if c.isalnum() or c == '_'])
            fields.append(name)
        
        print(", ".join(fields))

if __name__ == '__main__':
    list_fields()
