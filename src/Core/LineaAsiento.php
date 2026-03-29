<?php
declare(strict_types=1);

namespace ContaFC\Core;

/**
 * Representa un line-item del asiento contable (partida doble).
 */
final class LineaAsiento
{
    public function __construct(
        public readonly int     $cuentaId,
        public readonly float   $debito,
        public readonly float   $credito,
        public readonly ?string $descripcion    = null,
        public readonly ?int    $terceroId      = null,
        public readonly ?int    $cecoId         = null,
        public readonly ?int    $proyectoId     = null,
        public readonly ?string $docCruceTipo   = null,
        public readonly ?string $docCruceNum    = null,
        public readonly ?int    $docCruceCuota  = null,
        public readonly ?string $vencimiento    = null,
        public readonly float   $baseRetencion  = 0.0,
    ) {
        if ($debito < 0.0 || $credito < 0.0) {
            throw new \InvalidArgumentException('Débito y crédito no pueden ser negativos.');
        }
        if ($debito > 0.0 && $credito > 0.0) {
            throw new \InvalidArgumentException('Una línea no puede tener simultáneamente débito y crédito.');
        }
    }
}
