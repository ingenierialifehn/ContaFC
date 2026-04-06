"""
migrar_asientos_2025.py
═══════════════════════════════════════════════════════════════════════════
Lee el archivo DBF "Resumen VF asientos.DBF", extrae únicamente los
registros del año 2025, y genera un script SQL listo para importar en
la tabla `asientos` de ContaFC.

Requisitos:
    pip install dbfread
    (si no está instalado: pip install dbfread)

Uso:
    python migrar_asientos_2025.py

Salida:
    database/migracion_asientos_2025.sql   ← script SQL
    database/migracion_asientos_2025.log   ← resumen / errores

Configuración:
    Ajusta las constantes de la sección CONFIG antes de correr.
═══════════════════════════════════════════════════════════════════════════
"""

import os
import sys
import re
import datetime
import struct
import math

# ─── CONFIG ──────────────────────────────────────────────────────────────────
DBF_PATH       = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\Resumen VF asientos.DBF"
OUTPUT_SQL     = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\migracion_asientos_2025.sql"
OUTPUT_LOG     = r"c:\Users\Annonymous\Documents\Fernando\Aplicaciones\PHP\ContaFC\database\migracion_asientos_2025.log"

DEFAULT_EMPRESA_ID  = 1
DEFAULT_USUARIO_ID  = 1
ANO_FILTRO          = 2025     # Solo se migran registros de este anio
BATCH_SIZE          = 500      # Registros por INSERT
DBF_ENCODING        = 'latin-1'

# ID del comprobante "paraguas" para la migración 2025.
# Cambia este valor si ya tienes un comprobante de migración con otro ID.
COMPROBANTE_MIGR_ID = 10000
TIPO_COMPROBANTE_ID = 99       # Tipo "Migración Legacy" (codigo='MIG')
# ─────────────────────────────────────────────────────────────────────────────


# ─── LECTURA MANUAL DEL DBF ────────────────────────────────────────────────
# El archivo usa un formato propietario (versión 0x04, bloque 48 bytes).
# El campo FECHA es de tipo @ (8 bytes, timestamp OLE de FoxPro).
# Para evitar dependencias extra se lee en modo raw.

FIELD_DEFS = [
    # (nombre_dbf, tipo, longitud, nombre_mysql)
    # Tipos: I=int32 C=char O=double @=OLE datetime
    ('CONTEO',      'I', 4,   'conteo'),
    ('ACCT',        'I', 4,   '_acct_raw'),     # → se resuelve a cuenta_id
    ('FECHA',       '@', 8,   'fecha'),
    ('TIPO',        'C', 2,   'doc_cruce_tipo'),
    ('TANDA',       'I', 4,   'doc_cruce_num'),
    ('ID_N',        'I', 4,   '_idn_raw'),      # → tercero_id
    ('SALDANT',     'O', 8,   'saldo_anterior'),
    ('COMPANYCUST', 'C', 35,  '_companycust'),  # solo para lookup tercero
    ('INVC',        'C', 8,   'invc'),
    ('DETALLE',     'C', 40,  'descripcion'),
    ('DEBITO',      'O', 8,   'debito'),
    ('CREDITO',     'O', 8,   'credito'),
    ('SALDO',       'O', 8,   'saldo'),
    ('SALDOFINAL',  'O', 8,   'saldo_final'),
    ('RAZON',       'C', 150, 'razon'),
    ('NIT',         'C', 14,  'nit_tercero'),
    ('ID_N1',       'I', 4,   '_idn1'),         # ID interno legacy, ignorar
    ('DESCRIPCION', 'C', 40,  '_descripcion2'), # segunda desc, ver abajo
    ('DESCRIPCION2','C', 30,  'descripcion2'),
    ('DIRECCION1',  'C', 33,  'direccion_tercero'),
    ('USUARIO',     'C', 10,  '_usuario'),      # solo para log
]

RECORD_SIZE_EXPECTED = 1 + sum(l for _, _, l, _ in FIELD_DEFS)  # 1=flag borrado


def parse_fecha(raw8: bytes):
    """
    Este DBF almacena fechas como big-endian double que representa
    100-nanosegundos (ticks .NET) desde 0001-01-01, con un desplazamiento
    de -1 dia respecto a la representacion estandar .NET.

    Verificado empiricamente:
      BE-dbl = 63825321600000.0  ->  INVC='18072023'  ->  2023-07-18
      BE-dbl / 10000 ticks desde 0001-01-01 da 2023-07-19, restar 1 dia.
    """
    try:
        be_val = struct.unpack('>d', raw8)[0]
        if be_val <= 0:
            return None
        # Convertir a ticks (100 ns) y luego a dias
        ticks_100ns = int(be_val) * 10000
        seconds = ticks_100ns // 10_000_000
        base   = datetime.date(1, 1, 1)
        d      = base + datetime.timedelta(seconds=seconds) - datetime.timedelta(days=1)
        # Sanidad: fechas razonables para un sistema contable
        if d.year < 2000 or d.year > 2030:
            return None
        return d.strftime('%Y-%m-%d')
    except Exception:
        return None


def foxpro_int(raw4: bytes):
    """FoxPro Integer field: 4 bytes, XOR con 0x80 en el byte mas significativo (que en big endian es b[0])."""
    b = bytearray(raw4)
    b[0] ^= 0x80
    return struct.unpack('>i', bytes(b))[0]


def foxpro_double(raw8: bytes):
    """FoxPro Double (tipo O): Big endian, con inversion de bit de signo u octetos completos."""
    b = bytearray(raw8)
    if b[0] & 0x80:
        b[0] ^= 0x80
    else:
        for i in range(8):
            b[i] ^= 0xFF
    try:
        val = struct.unpack('>d', bytes(b))[0]
        if math.isnan(val) or math.isinf(val):
            return 0.0
        return val
    except Exception:
        return 0.0


def esc(s):
    """Escapa una cadena para SQL."""
    if s is None:
        return 'NULL'
    return "'" + str(s).replace('\\', '\\\\').replace("'", "''").strip() + "'"


def fmt_decimal(v, decimals=4):
    if v is None:
        return 'NULL'
    try:
        return f"{float(v):.{decimals}f}"
    except Exception:
        return 'NULL'


# ─── MAIN ─────────────────────────────────────────────────────────────────────

def main():
    log_lines = []

    def log(msg):
        print(msg)
        log_lines.append(msg)

    log(f"{'='*60}")
    log(f"MIGRACION DBF -> MySQL  |  Anio: {ANO_FILTRO}")
    log(f"DBF: {DBF_PATH}")
    log(f"{'='*60}")

    if not os.path.exists(DBF_PATH):
        log(f"ERROR: No se encuentra el archivo DBF: {DBF_PATH}")
        sys.exit(1)

    # ── Leer header DBF ──────────────────────────────────────────────────────
    with open(DBF_PATH, 'rb') as f:
        raw_hdr = f.read(32)

    num_records  = struct.unpack_from('<I', raw_hdr, 4)[0]
    header_size  = struct.unpack_from('<H', raw_hdr, 8)[0]
    record_size  = struct.unpack_from('<H', raw_hdr, 10)[0]

    log(f"Registros totales en DBF : {num_records:,}")
    log(f"Tamaño de cabecera       : {header_size} bytes")
    log(f"Tamaño de registro       : {record_size} bytes")
    log(f"Tamaño esperado por campo: {RECORD_SIZE_EXPECTED} bytes")
    if record_size != RECORD_SIZE_EXPECTED:
        log(f"ADVERTENCIA: El tamaño de registro no coincide. "
            f"Esperado={RECORD_SIZE_EXPECTED}, Real={record_size}. "
            f"Se usará el tamaño real del archivo.")

    # ── Leer y filtrar registros ─────────────────────────────────────────────
    records_2025 = []
    skipped      = 0
    errors       = 0

    with open(DBF_PATH, 'rb') as f:
        f.seek(header_size)
        for rec_idx in range(num_records):
            raw_rec = f.read(record_size)
            if not raw_rec or len(raw_rec) < record_size:
                break

            # Byte 0: 0x20=activo, 0x2A=borrado
            if raw_rec[0] == 0x2A:
                skipped += 1
                continue

            data = raw_rec[1:]  # quitar flag de borrado
            pos  = 0
            row  = {}
            ok   = True

            for (fname, ftype, flen, mfield) in FIELD_DEFS:
                chunk = data[pos:pos+flen]
                pos  += flen

                try:
                    if ftype == 'I':
                        row[mfield] = foxpro_int(chunk)
                    elif ftype == 'O':
                        row[mfield] = foxpro_double(chunk)
                    elif ftype == '@':
                        row[mfield] = parse_fecha(chunk)
                    elif ftype == 'C':
                        row[mfield] = chunk.decode(DBF_ENCODING, errors='replace').rstrip()
                    else:
                        row[mfield] = None
                except Exception as e:
                    row[mfield] = None
                    errors += 1

            # Enforcing chk_asiento_pd (debito >= 0 AND credito >= 0)
            debito_tmp = row.get('debito') or 0.0
            credito_tmp = row.get('credito') or 0.0
            
            if debito_tmp < 0:
                credito_tmp += abs(debito_tmp)
                debito_tmp = 0.0
            if credito_tmp < 0:
                debito_tmp += abs(credito_tmp)
                credito_tmp = 0.0
                
            row['debito'] = debito_tmp
            row['credito'] = credito_tmp

            # Filtrar por anio
            fecha = row.get('fecha')
            if fecha and str(fecha)[:4] == str(ANO_FILTRO):
                records_2025.append(row)

    log(f"\nRegistros anio {ANO_FILTRO} : {len(records_2025):,}")
    log(f"Registros borrados       : {skipped:,}")
    log(f"Errores de parseo        : {errors:,}")

    if not records_2025:
        log(f"\nNo se encontraron registros para el anio {ANO_FILTRO}.")
        log("Verifica el decodificador FECHA en parse_fecha().")
        with open(OUTPUT_LOG, 'w', encoding='utf-8') as f:
            f.write('\n'.join(log_lines))
        sys.exit(0)

    # ── Colectar terceros y cuentas únicos ───────────────────────────────────
    unique_accts    = set()
    unique_terceros = {}   # idn_raw → companycust

    for row in records_2025:
        acct = row.get('_acct_raw')
        if acct and acct > 0:
            unique_accts.add(acct)
        idn = row.get('_idn_raw')
        cc  = row.get('_companycust', '').strip()
        if idn and idn > 0 and cc:
            unique_terceros[idn] = cc

    log(f"Cuentas unicas           : {len(unique_accts)}")
    log(f"Terceros unicos          : {len(unique_terceros)}")

    # ── Generar SQL ──────────────────────────────────────────────────────────
    now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    with open(OUTPUT_SQL, 'w', encoding='utf-8') as f:

        f.write(f"-- ══════════════════════════════════════════════════════\n")
        f.write(f"-- ContaFC · Migración DBF → MySQL\n")
        f.write(f"-- Origen     : Resumen VF asientos.DBF\n")
        f.write(f"-- Anio filtro : {ANO_FILTRO}\n")
        f.write(f"-- Registros  : {len(records_2025):,}\n")
        f.write(f"-- Generado   : {now}\n")
        f.write(f"-- ══════════════════════════════════════════════════════\n\n")

        f.write("SET NAMES utf8mb4;\n")
        f.write("SET time_zone = '-06:00';\n")
        f.write("SET foreign_key_checks = 0;\n")
        f.write("SET sql_mode = '';\n")
        f.write("USE `contafc`;\n\n")

        # --- Paso 1: Tipo de comprobante migración ---
        f.write("-- ── 1. TIPO COMPROBANTE MIGRACION ─────────────────────\n")
        f.write(f"INSERT IGNORE INTO `tipos_comprobante` "
                f"(id, empresa_id, codigo, nombre, activo) "
                f"VALUES ({TIPO_COMPROBANTE_ID}, {DEFAULT_EMPRESA_ID}, 'MIG', 'Migración Legacy DBF', 1);\n\n")

        # --- Paso 2: Periodos 2025 ---
        f.write("-- ── 2. PERIODOS 2025 ──────────────────────────────────\n")
        for mes in range(1, 13):
            f.write(f"INSERT IGNORE INTO `periodos` "
                    f"(empresa_id, anio, mes, estado) "
                    f"VALUES ({DEFAULT_EMPRESA_ID}, {ANO_FILTRO}, {mes}, 'cerrado');\n")
        f.write("\n")

        # --- Paso 3: Comprobante paraguas por mes ---
        f.write("-- ── 3. COMPROBANTES PARAGUAS (uno por mes de 2025) ────\n")
        for mes in range(1, 13):
            comp_id  = COMPROBANTE_MIGR_ID + mes
            fecha_cm = f"{ANO_FILTRO}-{mes:02d}-01"
            f.write(
                f"INSERT IGNORE INTO `comprobantes` "
                f"(id, empresa_id, tipo_comp_id, numero, fecha, periodo_id, usuario_id, estado, observaciones) "
                f"VALUES ({comp_id}, {DEFAULT_EMPRESA_ID}, {TIPO_COMPROBANTE_ID}, "
                f"{COMPROBANTE_MIGR_ID + mes}, '{fecha_cm}', "
                f"(SELECT id FROM periodos WHERE empresa_id={DEFAULT_EMPRESA_ID} "
                f"AND anio={ANO_FILTRO} AND mes={mes} LIMIT 1), "
                f"{DEFAULT_USUARIO_ID}, 'registrado', 'Migracion DBF {ANO_FILTRO}-{mes:02d}');\n"
            )
        f.write("\n")

        # --- Paso 4: Cuentas PC faltantes ---
        f.write("-- ── 4. CUENTAS FALTANTES (INSERT IGNORE) ──────────────\n")
        for acct_code in sorted(unique_accts):
            code_str = str(acct_code)
            f.write(
                f"INSERT IGNORE INTO `puc_cuentas` "
                f"(empresa_id, codigo, nombre, nivel, naturaleza, tipo_cuenta, acepta_movimiento) "
                f"VALUES ({DEFAULT_EMPRESA_ID}, '{code_str}', 'Cuenta Migrada {code_str}', "
                f"4, 'D', 'A', 1);\n"
            )
        f.write("\n")

        # --- Paso 5: Terceros faltantes ---
        f.write("-- ── 5. TERCEROS FALTANTES (INSERT IGNORE) ─────────────\n")
        for idn, cc in sorted(unique_terceros.items()):
            cc_esc  = cc.replace("'", "''")
            code    = re.sub(r'[^a-zA-Z0-9]', '', cc)[:10].upper() or f'T{idn}'
            f.write(
                f"INSERT IGNORE INTO `terceros` "
                f"(empresa_id, codigo, razon_social, nit_cc, tipo_documento, tipo_tercero) "
                f"VALUES ({DEFAULT_EMPRESA_ID}, '{code}', '{cc_esc}', '', 'RTN', 'otro');\n"
            )
        f.write("\n")

        # --- Paso 6: Asientos 2025 ---
        f.write("-- ── 6. ASIENTOS 2025 ──────────────────────────────────\n")

        cols = (
            "empresa_id, comprobante_id, linea, fecha, "
            "cuenta_id, tercero_id, debito, credito, "
            "descripcion, razon, descripcion2, "
            "doc_cruce_tipo, doc_cruce_num, invc, "
            "saldo_anterior, saldo, saldo_final, "
            "nit_tercero, direccion_tercero, conteo"
        )

        linea_global = 1
        batch_rows   = []

        def flush_batch(rows, f, cols):
            if not rows:
                return
            f.write(f"REPLACE INTO `asientos` ({cols}) VALUES\n")
            f.write(",\n".join(rows) + ";\n\n")

        for row in records_2025:
            fecha   = row.get('fecha')
            if not fecha:
                skipped += 1
                continue

            # mes del registro → comprobante paraguas
            mes_rec  = int(str(fecha)[5:7])
            comp_id  = COMPROBANTE_MIGR_ID + mes_rec

            # cuenta
            acct_raw = row.get('_acct_raw', 0)
            acct_sub = (f"(SELECT id FROM puc_cuentas WHERE codigo='{acct_raw}' "
                        f"AND empresa_id={DEFAULT_EMPRESA_ID} LIMIT 1)")

            # tercero
            idn_raw = row.get('_idn_raw', 0)
            if idn_raw and idn_raw > 0:
                cc = unique_terceros.get(idn_raw, '').replace("'", "''")
                if cc:
                    ter_sub = (f"(SELECT id FROM terceros WHERE razon_social='{cc}' "
                               f"AND empresa_id={DEFAULT_EMPRESA_ID} LIMIT 1)")
                else:
                    ter_sub = 'NULL'
            else:
                ter_sub = 'NULL'

            debito   = fmt_decimal(row.get('debito', 0))
            credito  = fmt_decimal(row.get('credito', 0))
            sal_ant  = fmt_decimal(row.get('saldo_anterior'))
            sal      = fmt_decimal(row.get('saldo'))
            sal_fin  = fmt_decimal(row.get('saldo_final'))

            desc     = esc(row.get('descripcion') or row.get('_descripcion2'))
            razon    = esc(row.get('razon'))
            desc2    = esc(row.get('descripcion2'))
            doc_tipo = esc(row.get('doc_cruce_tipo'))
            doc_num  = esc(row.get('doc_cruce_num'))
            invc     = esc(row.get('invc'))
            nit      = esc(row.get('nit_tercero'))
            dir1     = esc(row.get('direccion_tercero'))
            conteo   = row.get('conteo') or 0

            val = (
                f"({DEFAULT_EMPRESA_ID}, {comp_id}, {linea_global}, '{fecha}', "
                f"{acct_sub}, {ter_sub}, {debito}, {credito}, "
                f"{desc}, {razon}, {desc2}, "
                f"{doc_tipo}, {doc_num}, {invc}, "
                f"{sal_ant}, {sal}, {sal_fin}, "
                f"{nit}, {dir1}, {conteo})"
            )
            batch_rows.append(val)
            linea_global += 1

            if len(batch_rows) >= BATCH_SIZE:
                flush_batch(batch_rows, f, cols)
                batch_rows = []

        flush_batch(batch_rows, f, cols)

        f.write("-- ── 7. RECALCULAR TOTALES EN COMPROBANTES ─────────────\n")
        for mes in range(1, 13):
            comp_id = COMPROBANTE_MIGR_ID + mes
            f.write(
                f"UPDATE `comprobantes` SET "
                f"total_debitos  = (SELECT COALESCE(SUM(debito),0)  FROM asientos WHERE comprobante_id={comp_id}), "
                f"total_creditos = (SELECT COALESCE(SUM(credito),0) FROM asientos WHERE comprobante_id={comp_id}) "
                f"WHERE id={comp_id};\n"
            )
        f.write("\n")

        f.write("SET foreign_key_checks = 1;\n")
        f.write(f"-- FIN DE LA MIGRACION {ANO_FILTRO}\n")

    log(f"\nOK SQL generado en: {OUTPUT_SQL}")
    log(f"   Asientos escritos: {linea_global - 1:,}")

    with open(OUTPUT_LOG, 'w', encoding='utf-8') as f:
        f.write('\n'.join(log_lines) + '\n')
    log(f"   Log en: {OUTPUT_LOG}")


if __name__ == "__main__":
    main()
