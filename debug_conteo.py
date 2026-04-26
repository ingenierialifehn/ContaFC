import struct
import os

DBF_PATH = r"database/Resumen VF asientos.DBF"

def debug_conteo():
    with open(DBF_PATH, 'rb') as f:
        header_start = f.read(32)
        num_rec, hdr_size, rec_size = struct.unpack_from('<IHH', header_start, 4)
        
        f.seek(32)
        fields_data = f.read(hdr_size - 32)
        fields = []
        off = 0
        while off + 32 <= len(fields_data):
            fd = fields_data[off:off+32]
            if fd[0] == 0x0D: break
            name = ""
            for k in range(11):
                if 32 <= fd[k] <= 126: name += chr(fd[k])
            name = name.strip()
            ftype = chr(fd[11])
            flen = fd[16]
            fields.append({'name': name, 'type': ftype, 'len': flen})
            off += 32
        
        offsets = {}
        curr = 1 
        for field in fields:
            offsets[field['name']] = (curr, field['len'], field['type'])
            curr += field['len']
            
        print(f"Checking CONTEO offset: {offsets.get('CONTEO')}")
        
        f.seek(hdr_size)
        for i in range(10):
            rec = f.read(rec_size)
            st, ln, tp = offsets['CONTEO']
            raw = rec[st:st+ln]
            print(f"Rec {i} CONTEO raw: {raw.hex()} type {tp}")

if __name__ == '__main__':
    debug_conteo()
