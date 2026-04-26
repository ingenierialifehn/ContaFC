import sys
from dbfread import DBF

def verify_dbf(file_path):
    table = DBF(file_path, load=True, encoding='latin1')
    records = list(table)
    
    print(f"Total records: {len(records)}")
    
    # Analyze account 110301 or 1305 (cuentas por cobrar clientes)
    cobrar = [r for r in records if '110301' in str(r.values()) or '1305' in str(r.values())]
    print(f"Records related to 110301 (Clientes): {len(cobrar)}")
    
    total_debitos = sum([float(r.get('DEBITO', 0) or 0) for r in cobrar])
    total_creditos = sum([float(r.get('CREDITO', 0) or 0) for r in cobrar])
    
    # Custom calculation applying the logic described by user
    # "cuando el detalle sea Intereses financiacion... no debe sumar si es negativo"
    total_custom = 0.0
    for r in cobrar:
        deb = float(r.get('DEBITO', 0) or 0)
        cre = float(r.get('CREDITO', 0) or 0)
        desc = str(r.get('DESCRIPCIO', '')).lower() + str(r.get('DESCRIPCION', '')).lower()
        
        if 'intereses financia' in desc and cre < 0:
            # According to user: "Debito - credito y como es negativo hace debito - (-credito) y los suma y no deberia"
            # It implies it should be added without changing sign (so subtraction), or something else.
            # Let's just output it to see.
            total_custom += (deb + cre)
        else:
            total_custom += (deb - cre)
            
    print(f"Total Debitos: {total_debitos}")
    print(f"Total Creditos: {total_creditos}")
    print(f"Total Debito - Credito: {total_debitos - total_creditos}")
    print(f"Total Custom (no invierte negativo si es Intereses FI): {total_custom}")
    
    print("\nSample Intereses Financiacion records:")
    intereses = [r for r in cobrar if 'intereses financia' in str(r.get('DESCRIPCIO', '')).lower() or 'intereses financia' in str(r.get('DESCRIPCION', '')).lower()]
    for i, r in enumerate(intereses[:5]):
        print(r)

if __name__ == '__main__':
    verify_dbf('database/Resumen VF asientos.DBF')
