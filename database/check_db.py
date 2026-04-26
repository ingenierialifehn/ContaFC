import os
import mysql.connector

def check_db():
    conn = mysql.connector.connect(
        host=os.getenv('DB_HOST', '127.0.0.1'),
        port=int(os.getenv('DB_PORT', 3306)),
        user=os.getenv('DB_USER', 'contafc_user'),
        password=os.getenv('DB_PASSWORD', os.getenv('DB_PASS', 'C0nt4FC!2026')),
        database=os.getenv('DB_NAME', 'contafc')
    )
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT COUNT(*) as c FROM asientos WHERE conteo IS NOT NULL AND conteo > 0")
    print("Asientos with conteo > 0:", cursor.fetchone()['c'])
    cursor.execute("SELECT COUNT(*) as c FROM asientos")
    print("Total Asientos:", cursor.fetchone()['c'])
    
    # Check current puc_cuentas
    cursor.execute("SELECT id, codigo, nombre, tipo_cuenta, naturaleza FROM puc_cuentas WHERE nombre LIKE 'Cuenta Migrada%' LIMIT 5")
    cuentas = cursor.fetchall()
    print("Sample cuentas migradas:", cuentas)
    
if __name__ == '__main__':
    check_db()
