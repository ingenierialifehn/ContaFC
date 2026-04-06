"""
Determina la epoca correcta del campo FECHA usando el valor BE-dbl
y el valor conocido INVC='18072023' -> fecha = 2023-07-18
"""
import struct, datetime

# Rec 1-7: INVC='18072023' = 2023-07-18
# BE-dbl = 63825321600000.0
known_date = datetime.date(2023, 7, 18)
be_val_1   = 63825321600000.0

# Rec 8-10: INVC='4123710' (numero de comprobante, no fecha)
# BE-dbl = 63828172800000.0  → diferencia: +2851200 segundos = 33 dias
diff_sec = 63828172800000.0 - 63825321600000.0
diff_days = diff_sec / 1000  # si son milisegundos
print(f"Diferencia rec1 vs rec8:")
print(f"  {diff_sec:.0f} unidades")
print(f"  /86400  = {diff_sec/86400:.4f} dias")
print(f"  /1000   = {diff_sec/1000:.4f} (si ms)")
print(f"  /1000/86400 = {diff_sec/1000/86400:.4f} dias")
print()

# Probar: valor / 1000 / 86400 = dias desde alguna epoca?
# Epoca = known_date - valor_en_dias
val_dias = be_val_1 / 1000 / 86400
print(f"BE_val_1 / 1000 / 86400 = {val_dias:.6f} dias")
# Si known_date - val_dias = epoca:
import datetime as dt
# Trabajar con dias como entero
val_dias_int = int(be_val_1 / 1000 / 86400)
print(f"  val_dias_int = {val_dias_int}")
# Epoca: known_date - val_dias_int dias
try:
    epoch = known_date - dt.timedelta(days=val_dias_int)
    print(f"  Epoca calculada: {epoch}")
except Exception as e:
    print(f"  Error: {e}")
print()

# Verificar con rec 8: si la epoca da sentido
be_8 = 63828172800000.0
dias_8 = int(be_8 / 1000 / 86400)
try:
    date_8 = epoch + dt.timedelta(days=dias_8)
    print(f"Rec 8 fecha calculada: {date_8}")
except: pass

print()
# Probar: valor en milisegundos desde una fecha Windows FILETIME (1601-01-01)?
# Windows FILETIME: 100ns intervals since 1601-01-01
# 63825321600000 * 10000 ns = ?
# O quizas es microsegundos desde 0001-01-01?
print("Prueba: segundos desde 0001-01-01:")
epoch2 = dt.date(1, 1, 1)
try:
    d = epoch2 + dt.timedelta(seconds=be_val_1)
    print(f"  0001-01-01 + {be_val_1:.0f}s = {d}")
except Exception as e:
    print(f"  Error: {e}")

print()
print("Prueba: milisegundos desde 0001-01-01:")
try:
    d = epoch2 + dt.timedelta(milliseconds=be_val_1)
    print(f"  0001-01-01 + {be_val_1:.0f}ms = {d}")
except Exception as e:
    print(f"  Error: {e}")

print()
print("Prueba: milisegundos desde 1900-01-01:")
epoch3 = dt.date(1900, 1, 1)
try:
    d = epoch3 + dt.timedelta(milliseconds=be_val_1)
    print(f"  1900-01-01 + {be_val_1:.0f}ms = {d}")
except Exception as e:
    print(f"  Error: {e}")

print()
print("Prueba: milisegundos desde 1899-12-30 (OLE):")
epoch4 = dt.date(1899, 12, 30)
try:
    d = epoch4 + dt.timedelta(milliseconds=be_val_1)
    print(f"  OLE + {be_val_1:.0f}ms = {d}")
except Exception as e:
    print(f"  Error: {e}")

# Probar segundos desde 1900-01-01:
print()
print("Prueba: segundos desde 1900-01-01:")
try:
    d = epoch3 + dt.timedelta(seconds=be_val_1)
    print(f"  {d}")
except Exception as e:
    print(f"  Error: {e}")

# La clave: BE-dbl diff entre rec1 y rec8 = 2851200.0
# 2851200 / 86400 = 33 dias exactos
# Si son milisegundos: 2851200/1000/86400 = 33 dias exactos!
print()
print(f"Diff = {diff_sec:.0f}")
print(f"Diff / 86400 = {diff_sec/86400:.2f}")
print(f"Diff / 1000 / 86400 = {diff_sec/1000/86400:.2f} dias (si ms)")
