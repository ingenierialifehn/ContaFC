import struct, os

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
OUT_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\dbf_structure.txt"

with open(DBF_PATH, 'rb') as f:
    raw = f.read(2048)   # read 2 KB — enough for all headers

out = []

def w(s=""):
    out.append(str(s))

# Full hex dump of first 2048 bytes
w("=== HEX DUMP (first 2048 bytes) ===")
for i in range(0, len(raw), 16):
    row = raw[i:i+16]
    hex_part = ' '.join(f'{b:02X}' for b in row)
    asc_part = ''.join(chr(b) if 32 <= b < 127 else '.' for b in row)
    w(f'{i:05d}  {hex_part:<47}  {asc_part}')

w()

# Scan for printable strings >= 3 chars — helps find field names
w("=== PRINTABLE STRINGS (len>=3) found in first 2048 bytes ===")
current = []
current_start = 0
for i, b in enumerate(raw):
    if 32 <= b < 127:
        if not current:
            current_start = i
        current.append(chr(b))
    else:
        if len(current) >= 3:
            w(f"  offset {current_start:5d}: {''.join(current)!r}")
        current = []
if len(current) >= 3:
    w(f"  offset {current_start:5d}: {''.join(current)!r}")

# Try treating as Visual FoxPro (VFP) version 0x30
w()
w("=== VFP PARSE ATTEMPT ===")
ver = raw[0]
w(f"Version byte: 0x{ver:02X}")
# VFP table versions: 0x30=VFP, 0xF5=VFP with memo, 0x03=dBase III, 0x83=dBase III+memo
# VFP field descriptors differ slightly — field name is 32 bytes, not 11

if ver in (0x30, 0xF5, 0x31):
    w("Detected Visual FoxPro format")
    # VFP header: 32 bytes standard + optional table flags
    num_rec  = struct.unpack_from('<I', raw, 4)[0]
    hdr_size = struct.unpack_from('<H', raw, 8)[0]
    rec_size = struct.unpack_from('<H', raw, 10)[0]
    w(f"Records : {num_rec}")
    w(f"Hdr size: {hdr_size}")
    w(f"Rec size: {rec_size}")
    w()
    # VFP field descriptors start at 32, each is 32 bytes
    # Byte 0   : first char of name (if 0x0D => terminator)
    # Bytes 0-10: field name (null padded)
    # Byte 11  : field type
    # Byte 12-15: field offset in record
    # Byte 16  : field length
    # Byte 17  : decimal count
    # Byte 18-31: reserved/flags
    w(f"{'#':>3}  {'Name':<18} {'Type':^6} {'Offset':>8} {'Len':>5} {'Dec':>5}")
    w("-" * 50)
    off = 32
    idx = 1
    while off + 32 <= len(raw):
        fd = raw[off:off+32]
        if fd[0] == 0x0D:
            w(f"  [Terminator 0x0D at offset {off}]")
            break
        name  = fd[0:11].rstrip(b'\x00').decode('latin-1', errors='replace').strip()
        ftype = chr(fd[11]) if 32 <= fd[11] < 127 else f'0x{fd[11]:02X}'
        foff  = struct.unpack_from('<I', fd, 12)[0]
        flen  = fd[16]
        fdec  = fd[17]
        w(f"{idx:>3}. {name:<18} {ftype:^6} {foff:>8} {flen:>5} {fdec:>5}")
        idx += 1
        off += 32
else:
    w(f"Not VFP standard — version 0x{ver:02X}")
    # Try dBase III
    num_rec  = struct.unpack_from('<I', raw, 4)[0]
    hdr_size = struct.unpack_from('<H', raw, 8)[0]
    rec_size = struct.unpack_from('<H', raw, 10)[0]
    w(f"Records : {num_rec}")
    w(f"Hdr size: {hdr_size}")
    w(f"Rec size: {rec_size}")

with open(OUT_PATH, 'w', encoding='utf-8') as f:
    f.write('\n'.join(out) + '\n')

print(f"Done. See: {OUT_PATH}")
