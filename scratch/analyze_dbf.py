import struct

DBF_PATH = r"database/Resumen VF asientos.DBF"

def analyze_structure():
    with open(DBF_PATH, 'rb') as f:
        header = f.read(32)
        num_rec, hdr_size, rec_size = struct.unpack_from('<IHH', header, 4)
        print(f"Records: {num_rec}, Header Size: {hdr_size}, Record Size: {rec_size}")
        
        f.seek(32)
        offset = 1 # deleted flag
        fields = []
        while True:
            descriptor = f.read(32)
            if not descriptor or descriptor[0] == 0x0D:
                break
            name = descriptor[0:11].split(b'\x00')[0].decode('latin-1').strip()
            # clean name
            name = "".join([c for c in name if c.isalnum() or c == '_'])
            ftype = chr(descriptor[11])
            flen = descriptor[16]
            fields.append({'name': name, 'type': ftype, 'len': flen, 'offset': offset})
            offset += flen
            
        for field in fields:
            print(f"Field: {field['name']:15} Type: {field['type']} Len: {field['len']} Offset: {field['offset']}")

if __name__ == '__main__':
    analyze_structure()
