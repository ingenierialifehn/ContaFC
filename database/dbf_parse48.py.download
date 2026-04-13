import struct, os

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
OUT_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\dbf_full_structure.txt"

with open(DBF_PATH, 'rb') as f:
    raw = f.read(2048)

out = []
def w(s=""): out.append(str(s))

# Parse header
ver      = raw[0]
num_rec  = struct.unpack_from('<I', raw, 4)[0]
hdr_size = struct.unpack_from('<H', raw, 8)[0]
rec_size = struct.unpack_from('<H', raw, 10)[0]

w(f"Version : 0x{ver:02X}")
w(f"Records : {num_rec}")
w(f"Hdr size: {hdr_size}")
w(f"Rec size: {rec_size}")
w()

# Field names extracted from printable strings scan:
# Each block seems to be 48 bytes in this custom format.
# Names start at byte 4 of each block (first 4 bytes are \x00\x00\x00\x00 EXCEPT first block which has DBWINUS0)
# Let's parse with stride=48 starting at offset 32

w("=== Parsing with stride=48 (observed pattern) ===")
fields_48 = []
stride = 48
off = 32
idx = 1
while off + stride <= hdr_size:
    block = raw[off:off+stride]
    if block[0] == 0x0D:
        w(f"  Terminator 0x0D at offset {off}")
        break
    # Name starts at byte 4 in each block (first 4 null)
    name_bytes = block[4:20]
    name = name_bytes.rstrip(b'\x00').decode('latin-1', errors='replace').strip()
    # Type at byte 20? or after name...
    # Look at the pattern: CONTEO block at offset 64:
    # 64: 00 00 00 00 43 4F 4E 54 45 4F 00... (name starts at +4 with 'C'='43')
    # Type indicator: offset 96 = 49='I' → integer. So type is at the start of NEXT block - 16?
    # Actually looking at the data:
    # offset 32: DBWINUS0 (first block, 48 bytes → next at 80)
    # offset 80: ....ACCT (name at +4)  → next at 128
    # wait, CONTEO is at 68 and ACCT at 116... difference is 48.
    # Block at 32: DBWINUS0 at bytes 0-7, zeros rest
    # Block at 80: starts at 80: 00 00 00 00 41 43 43 54... ACCT
    # But CONTEO is at 68 = 32+36... hmm
    # Let me try stride=48 but name offset varies
    
    # Actually re-reading: the names appear at: 68,116,164,212,260,308,356,404,452,500,548,596,644,692,740,788,836,884,932,980,1028
    # Differences: 48,48,48,48,48,48,48,48,48,48,48,48,48,48,48,48,48,48,48,48
    # First name at 68 = 32 + 36
    # Hmm: 68-32=36, so name is at offset 36 within first "real" descriptor block?
    # OR: first block (32-79) is a special header block with "DBWINUS0"
    # Then field descriptors start at 80, stride 48, name at offset 4 from block start
    
    off += stride
    idx += 1

# The field names from the string scan, with their offsets:
field_offsets = [68, 116, 164, 212, 260, 308, 356, 404, 452, 500, 548, 596, 644, 692, 740, 788, 836, 884, 932, 980, 1028]
names_found   = ['CONTEO','ACCT','FECHA','TIPO','TANDA','ID_N','SALDANT','COMPANYCUST','INVC','DETALLE','DEBITO','CREDITO','SALDO','SALDOFINAL','RAZON','NIT','ID_N1','DESCRIPCION','DESCRIPCION2','DIRECCION1','USUARIO']

# For each field, read the type byte and length
# Structurally each block is 48 bytes starting at offset 80 (after the DBWINUS0 block at 32-79)
# name at block+4, type at... let's check CONTEO block
# CONTEO: offset 68 (name), block start = 68-4 = 64
# At offset 64+16=80: 00 00 00 00... next block start
# Type byte for CONTEO: looking at offset 96 = 49 = 'I' → integer. But 96 is start of next name block
# Wait: block(64): bytes 0-3=0000, bytes 4-15=CONTEO\x00..., bytes 16-31=000000...
# bytes 32 = 49='I'... so type is at block_start+32? That puts it at 64+32=96
# But 96 starts the ACCT name area... 
# Let me look differently. Pattern:
# offset 64: 00 00 00 00 43 4F 4E 54 45 4F 00... (CONTEO + padding to 16 chars)
# offset 80: 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 (16 zeros)
# offset 96: 00 00 00 00 49 04 00 00 00 00 00 00 00 00 00 00 → I=0x49, then 04 00 00 00
# So: block_start(64) + 32 = type byte position = 96, and type=0x49='I'
# block_start(64) + 36 = length = 04 → length=4 (32-bit integer)
# Next name ACCT at 116:
# Its block starts at 112: 00 00 00 00 41 43 43 54... 
# At 112+32=144: 00 00 00 00 49 04 00 00 → type=I, len=4
# This fits! So block size = 48, layout:
#   [0-3]   = 4 bytes (zeros/flags?)
#   [4-15]  = 12-char field name (null padded)
#   [16-31] = 16 bytes (zeros)
#   [32]    = type char (C=char, I=int, O=double, D=date, N=num)
#   [33]    = ??? (always 0?)
#   [34]    = ??? 
#   [35]    = ??? 
#   [36]    = field length
#   ...
# Wait: at offset 96: 00 00 00 00 49 04 00 00 00 00 00 00 00 00 00 00
# That's: 4 zeros, then 49='I', then 04, then zeros
# So in the block starting at 64:
#   offset 96 - 64 = 32 → byte[32] = 0 (this is the 4th zero of '00 00 00 00')
#   byte[32..35] = 00 00 00 00
#   byte[36] = 49 = 'I'  → TYPE is at block+36!
#   byte[37] = 04 → LENGTH

w("=== Field descriptors (block_size=48, name@+4, type@+36, len@+37) ===")
w(f"{'#':>3}  {'Name':<14} {'Type':^6} {'Len':>5} {'Dec':>5}")
w("-" * 42)

fields = []
block_start_0 = 32  # DBWINUS0 block
block_start_fields = 64  # First field block

for i, (foff, fname) in enumerate(zip(field_offsets, names_found)):
    block_start = foff - 4  # name is at block+4
    # Type and length are in the block that starts 32 bytes after block_start
    meta_start = block_start + 32
    if meta_start + 8 <= len(raw):
        # meta_start+4 = type, meta_start+5 = length
        tb = raw[meta_start:meta_start+8]
        # Find first non-zero byte after the 4-zero prefix
        for j in range(8):
            if tb[j] != 0x00:
                type_byte = tb[j]
                len_byte  = tb[j+1] if j+1 < 8 else 0
                break
        else:
            type_byte, len_byte = 0, 0
        ftype = chr(type_byte) if 32 <= type_byte < 127 else f'0x{type_byte:02X}'
        flen  = len_byte
    else:
        ftype, flen = '?', 0
    
    w(f"{i+1:>3}. {fname:<14} {ftype:^6} {flen:>5}")
    fields.append((fname, ftype, flen))

w()
w(f"Total fields: {len(fields)}")

with open(OUT_PATH, 'w', encoding='utf-8') as f:
    f.write('\n'.join(out) + '\n')

print(f"Written to: {OUT_PATH}")
for i,(n,t,l) in enumerate(fields, 1):
    print(f"  {i:2d}. {n:<14} type={t} len={l}")
