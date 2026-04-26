import struct
import re

DBF_PATH = r"database/Resumen VF asientos.DBF"

def find_fields():
    with open(DBF_PATH, 'rb') as f:
        data = f.read(1077)
        # Standard descriptors start at 32 and are 32 bytes each.
        # But let's just find "ACCT" and "FECHA"
        acct_pos = data.find(b'ACCT')
        fecha_pos = data.find(b'FECHA')
        
        print(f"ACCT found at: {acct_pos}")
        print(f"FECHA found at: {fecha_pos}")
        
        if acct_pos != -1:
            # Descriptor for ACCT starts at 32 * k
            k = (acct_pos) // 32
            desc_start = k * 32
            print(f"Descriptor block around ACCT start: {desc_start}")
            # Let's print the descriptor
            desc = data[desc_start:desc_start+32]
            print(f"Descriptor Hex: {desc.hex()}")
            ftype = chr(desc[11])
            flen = desc[16]
            print(f"Type: {ftype}, Len: {flen}")

        if fecha_pos != -1:
            k = (fecha_pos) // 32
            desc_start = k * 32
            print(f"Descriptor block around FECHA start: {desc_start}")
            desc = data[desc_start:desc_start+32]
            print(f"Descriptor Hex: {desc.hex()}")
            ftype = chr(desc[11])
            flen = desc[16]
            print(f"Type: {ftype}, Len: {flen}")

if __name__ == '__main__':
    find_fields()
