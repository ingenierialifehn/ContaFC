import struct
import os
import sys

DBF_PATH = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"

def read_dbf_header(filepath):
    with open(filepath, 'rb') as f:
        raw = f.read(32)
        version    = raw[0]
        yy, mm, dd = raw[1], raw[2], raw[3]
        num_records = struct.unpack_from('<I', raw, 4)[0]
        header_size = struct.unpack_from('<H', raw, 8)[0]
        record_size = struct.unpack_from('<H', raw, 10)[0]

        print(f"Version byte  : 0x{version:02X}")
        print(f"Last update   : 19{yy:02d}-{mm:02d}-{dd:02d}")
        print(f"Num records   : {num_records}")
        print(f"Header size   : {header_size} bytes")
        print(f"Record size   : {record_size} bytes")
        print()

        # Field descriptors start at offset 32, each 32 bytes, terminated by 0x0D
        fields = []
        offset = 32
        while True:
            f.seek(offset)
            fd = f.read(32)
            if not fd or fd[0] == 0x0D:
                break
            name      = fd[0:11].decode('ascii', errors='replace').rstrip('\x00').strip()
            ftype     = chr(fd[11])
            flength   = fd[16]
            fdecimals = fd[17]
            fields.append((name, ftype, flength, fdecimals))
            offset += 32

        print(f"{'#':>3}  {'Field Name':<14} {'Type':<6} {'Len':>5} {'Dec':>5}")
        print("-" * 40)
        for i, (name, ftype, flen, fdec) in enumerate(fields, 1):
            print(f"{i:>3}. {name:<14} {ftype:<6} {flen:>5} {fdec:>5}")
        print()
        return fields, num_records, header_size, record_size

def read_sample_records(filepath, fields, header_size, record_size, max_rows=5):
    """Read first max_rows records to see sample data."""
    with open(filepath, 'rb') as f:
        f.seek(header_size)
        print(f"=== Sample Records (first {max_rows}) ===")
        for row_idx in range(max_rows):
            raw_rec = f.read(record_size)
            if not raw_rec or len(raw_rec) < record_size:
                break
            deleted = raw_rec[0:1]
            data_bytes = raw_rec[1:]  # skip deletion flag
            pos = 0
            row = {}
            for (name, ftype, flen, fdec) in fields:
                chunk = data_bytes[pos:pos+flen]
                try:
                    val = chunk.decode('latin-1').strip()
                except:
                    val = repr(chunk)
                row[name] = val
                pos += flen
            print(f"Record {row_idx+1}: {row}")
        print()

def count_by_year(filepath, fields, header_size, record_size):
    """Count records per year using date-like fields."""
    date_fields = [f[0] for f in fields if f[1] == 'D']
    char_fields  = [f[0] for f in fields if f[1] == 'C']
    num_fields   = [f[0] for f in fields if f[1] in ('N','F')]
    
    print(f"Date fields: {date_fields}")
    print(f"Char fields: {char_fields}")
    print(f"Numeric fields: {num_fields}")
    print()

    # Build field offset map
    offsets = {}
    pos = 0
    for (name, ftype, flen, fdec) in fields:
        offsets[name] = (pos, flen, ftype, fdec)
        pos += flen

    year_counts = {}
    with open(filepath, 'rb') as f:
        f.seek(header_size)
        total = 0
        while True:
            raw_rec = f.read(record_size)
            if not raw_rec or len(raw_rec) < record_size:
                break
            if raw_rec[0] == 0x2A:  # deleted record
                continue
            data_bytes = raw_rec[1:]
            total += 1

            # Try to find year from date fields first
            year = None
            for df in date_fields:
                if df in offsets:
                    p, l, _, _ = offsets[df]
                    val = data_bytes[p:p+l].decode('latin-1', errors='replace').strip()
                    if len(val) >= 4:
                        yr = val[:4]
                        if yr.isdigit():
                            year = yr
                            break

            # Fallback: try char fields that look like year (ANIO, YEAR, AÑO, etc.)
            if year is None:
                for cf in char_fields:
                    if cf.upper() in ('ANIO', 'YEAR', 'ANO', 'AO'):
                        if cf in offsets:
                            p, l, _, _ = offsets[cf]
                            val = data_bytes[p:p+l].decode('latin-1', errors='replace').strip()
                            if val.isdigit() and len(val) == 4:
                                year = val
                                break

            year = year or 'UNKNOWN'
            year_counts[year] = year_counts.get(year, 0) + 1

        print(f"Total active records: {total}")
        print("Records per year:")
        for yr in sorted(year_counts):
            print(f"  {yr}: {year_counts[yr]:>7,}")

if __name__ == "__main__":
    fields, num_records, hsize, rsize = read_dbf_header(DBF_PATH)
    read_sample_records(DBF_PATH, fields, hsize, rsize, max_rows=3)
    count_by_year(DBF_PATH, fields, hsize, rsize)
