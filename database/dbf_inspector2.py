import struct, os, sys

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
OUT_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\dbf_structure.txt"

lines = []

def w(s=""):
    lines.append(str(s))

with open(DBF_PATH, 'rb') as f:
    raw = f.read(800)  # read enough for header + all field defs

# --- Parse standard dBase header ---
ver      = raw[0]
yy,mm,dd = raw[1], raw[2], raw[3]
num_rec  = struct.unpack_from('<I', raw, 4)[0]
hdr_size = struct.unpack_from('<H', raw, 8)[0]
rec_size = struct.unpack_from('<H', raw, 10)[0]

w("=== DBF HEADER ===")
w(f"Version byte : 0x{ver:02X}")
w(f"Last update  : {yy}/{mm}/{dd}")
w(f"Num records  : {num_rec}")
w(f"Header size  : {hdr_size} bytes")
w(f"Record size  : {rec_size} bytes")
w()

# --- Hexdump first 128 bytes ---
w("=== HEX DUMP (first 128 bytes) ===")
for i in range(0, 128, 16):
    row = raw[i:i+16]
    hex_part = ' '.join(f'{b:02X}' for b in row)
    asc_part = ''.join(chr(b) if 32 <= b < 127 else '.' for b in row)
    w(f'{i:04X}  {hex_part:<47}  {asc_part}')
w()

# --- Parse field descriptors ---
w("=== FIELD DESCRIPTORS ===")
w(f"{'#':>3}  {'Name':<14} {'T':^4} {'Len':>5} {'Dec':>5}")
w("-" * 42)

fields = []
off = 32
idx = 1
while off + 32 <= len(raw):
    fd = raw[off:off+32]
    if fd[0] == 0x0D or fd[0] == 0x00:
        w(f"  [Terminator found at offset {off}, byte=0x{fd[0]:02X}]")
        break
    # Name is first 11 bytes, null-terminated
    name_bytes = fd[0:11]
    name = name_bytes.rstrip(b'\x00').decode('latin-1', errors='replace').strip()
    ftype = chr(fd[11]) if 32 <= fd[11] < 127 else f'?{fd[11]:02X}'
    # In dBase III: fd[12:16] = field address in memory (ignore for reading)
    flen  = fd[16]
    fdec  = fd[17]
    w(f"{idx:>3}. {name:<14} {ftype:^4} {flen:>5} {fdec:>5}")
    fields.append((name, ftype, flen, fdec))
    idx += 1
    off += 32

w()
w(f"Total fields found: {len(fields)}")

# --- Write output ---
with open(OUT_PATH, 'w', encoding='utf-8') as fout:
    fout.write('\n'.join(lines) + '\n')

print(f"Written to: {OUT_PATH}")
print(f"Fields found: {len(fields)}")
for i, (n,t,l,d) in enumerate(fields, 1):
    print(f"  {i:2d}. {n:<14} type={t} len={l} dec={d}")
