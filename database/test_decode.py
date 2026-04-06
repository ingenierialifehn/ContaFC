import struct, math

raw_int = bytes([128, 183, 66, 122])  # 0x80 0xB7 0x42 0x7A
b = bytearray(raw_int)
b[0] ^= 0x80
i_val = struct.unpack('>i', bytes(b))[0]
print("ACCT decode:", i_val, "  (Expected: 12010106)")

raw_double_1 = bytes([193, 219, 137, 21, 85, 85, 85, 85]) # Example big endian FoxPro double positive
b_pos = bytearray(raw_double_1)
b_pos[0] ^= 0x80
d_pos = struct.unpack('>d', bytes(b_pos))[0]
print("Pos Double decode:", d_pos)

raw_double_2 = bytes([64, 40, 169, 13, 255, 255, 255, 255]) # Example big endian FoxPro double negative
b_neg = bytearray(raw_double_2)
for i in range(8): b_neg[i] ^= 0xFF
d_neg = struct.unpack('>d', bytes(b_neg))[0]
print("Neg Double decode:", d_neg)
