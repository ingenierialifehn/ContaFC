<?php
declare(strict_types=1);

namespace ContaFC\Core;

/**
 * Representa un line-item del asiento contable (partida doble).
 */
final class LineaAsiento
{
    public function __construct(
        public int     $cuentaId,
        public float   $debito,
        public float   $credito,
        public ?string $descripcion    = null,
        public ?int    $terceroId      = null,
        public ?int    $cecoId         = null,
        public ?int    $proyectoId     = null,
        public ?string $docCruceTipo   = null,
        public ?string $docCruceNum    = null,
        public ?int    $docCruceCuota  = null,
        public ?string $vencimiento    = null,
        public float   $baseRetencion  = 0.0,
    ) {
        if ($debito < 0.0 || $credito < 0.0) {
            throw new \InvalidArgumentException('Débito y crédito no pueden ser negativos.');
        }
        if ($debito > 0.0 && $credito > 0.0) {
            throw new \InvalidArgumentException('Una línea no puede tener simultáneamente débito y crédito.');
        }
    }
}
