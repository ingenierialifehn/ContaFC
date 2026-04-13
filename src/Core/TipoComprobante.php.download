<?php
declare(strict_types=1);

namespace ContaFC\Core;

/**
 * TipoComprobante – Enum de los tipos de documento contable estándar.
 */
enum TipoComprobante: string
{
    case NotaAjuste          = 'NA';
    case ComprobanteContab   = 'CC';
    case ComprobanteEgreso   = 'CE';
    case ReciboCaja          = 'RC';
    case NotaCredito         = 'NC';
    case NotaDebito          = 'ND';
    case NotaIngreso         = 'NI';
    case NotaSalida          = 'NS';

    public function label(): string
    {
        return match($this) {
            self::NotaAjuste        => 'Ajuste (Nota) Contable',
            self::ComprobanteContab => 'Comprobante de Contabilidad',
            self::ComprobanteEgreso => 'Comprobante de Egreso',
            self::ReciboCaja        => 'Recibo de Caja',
            self::NotaCredito       => 'Nota Crédito',
            self::NotaDebito        => 'Nota Débito',
            self::NotaIngreso       => 'Nota de Ingreso',
            self::NotaSalida        => 'Nota de Salida',
        };
    }
}
