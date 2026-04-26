import sys
from dbfread import DBF

def inspect_dbf(file_path):
    try:
        table = DBF(file_path, load=False, encoding='latin1')
        print(f"Fields: {table.field_names}")
        print("\nFirst 3 records:")
        for i, record in enumerate(table):
            print(record)
            if i >= 2: break
    except Exception as e:
        print(f"Error: {e}")

if __name__ == '__main__':
    inspect_dbf('database/Resumen VF asientos.DBF')
