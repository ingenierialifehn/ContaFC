import struct, os, sys, io

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"

def hexdump(data, length=16):
    result = []
    for i in range(0, len(data), length):
        row = data[i:i+length]
        hex_part = ' '.join(f'{b:02X}' for b in row)
        asc_part = ''.join(chr(b) if 32 <= b < 127 else '.' for b in row)
        result.append(f'{i:04X}  {hex_part:<{length*3}}  {asc_part}')
    return '\n'.join(result)

with open(DBF_PATH, 'rb') as f:
    header_raw = f.read(512)

print("=== First 512 bytes hex dump ===")
print(hexdump(header_raw, 16))

# Try to find the 0x0D terminator position
print("\n=== Looking for 0x0D field terminator ===")
for i, b in enumerate(header_raw):
    if b == 0x0D:
        print(f"  0x0D found at byte offset {i} (0x{i:04X})")
        break

# Parse as standard dBase III+
print("\n=== Standard dBase III+ parse ===")
ver        = header_raw[0]
yy,mm,dd   = header_raw[1], header_raw[2], header_raw[3]
num_rec    = struct.unpack_from('<I', header_raw, 4)[0]
hdr_size   = struct.unpack_from('<H', header_raw, 8)[0]
rec_size   = struct.unpack_from('<H', header_raw, 10)[0]
print(f"Version: 0x{ver:02X}")
print(f"Date: {yy}/{mm}/{dd}")
print(f"Records: {num_rec}")
print(f"Header size: {hdr_size}")
print(f"Record size: {rec_size}")

# Parse fields manually, printing raw bytes too
print("\n=== Field descriptors raw ===")
off = 32
idx = 1
while off + 32 <= len(header_raw):
    fd = header_raw[off:off+32]
    if fd[0] == 0x0D:
        print(f"  [Terminator at offset {off}]")
        break
    name_raw  = fd[0:11]
    ftype     = chr(fd[11]) if 32 <= fd[11] < 127 else f'0x{fd[11]:02X}'
    addr      = struct.unpack_from('<I', fd, 12)[0]
    flen      = fd[16]
    fdec      = fd[17]
    name_str  = name_raw.decode('latin-1', errors='replace').rstrip('\x00').strip()
    print(f"  {idx:2d}. RAW='{hexdump(name_raw, 11).split()[2:]}' name={name_str!r:14} type={ftype} len={flen} dec={fdec} addr={addr}")
    idx += 1
    off += 32
