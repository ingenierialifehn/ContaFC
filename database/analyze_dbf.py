
import struct
import datetime
import os

def analyze_dbf(filepath):
    if not os.path.exists(filepath):
        print(f"Error: {filepath} not found.")
        return

    with open(filepath, 'rb') as f:
        # DBF Header (32 bytes)
        # 0: version/signature
        # 1-3: date (y, m, d)
        # 4-7: record count
        # 8-9: header length
        # 10-11: record length
        header = f.read(32)
        signature, y, m, d, count, header_len, record_len = struct.unpack('<BBBBIII', header[:16])
        
        print(f"--- DBF File Analysis: {os.path.basename(filepath)} ---")
        print(f"Records: {count}")
        print(f"Header Length: {header_len}")
        print(f"Record Length: {record_len}")
        print("-" * 40)
        
        # Field Descriptors (32 bytes each)
        # Starts at byte 32, ends when first byte is 0x0D
        fields = []
        f.seek(32)
        while True:
            field_data = f.read(32)
            if field_data[0] == 0x0D:
                break
            
            # Format: 11 bytes name, 1 byte type, 4 bytes displacement, 1 byte length, 1 byte decimal, rest reserved
            name = field_data[:11].decode('ascii', errors='ignore').strip('\x00').strip()
            type_code = chr(field_data[11])
            length = field_data[16]
            decimals = field_data[17]
            
            fields.append({
                'name': name,
                'type': type_code,
                'length': length,
                'decimals': decimals
            })
            
        print("Fields detected:")
        for idx, field in enumerate(fields):
            print(f"{idx+1:2d}. {field['name']:12} | Type: {field['type']} | Len: {field['length']:3} | Dec: {field['decimals']}")
        
        print("-" * 40)
        return fields, count, header_len, record_len

if __name__ == "__main__":
    analyze_dbf(r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF")
