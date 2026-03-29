<?php
declare(strict_types=1);

namespace ContaFC\Services;

use ContaFC\Core\Database;

/**
 * ReporteService – Genera reportes contables usando consultas optimizadas.
 */
final class ReporteService
{
    public function __construct(
        private readonly Database $database
    ) {}

    /**
     * Balance de Comprobación por rango de fecha.
     * Retorna PUC con débitos, créditos y saldo del período.
     *
     * @return array<int, array{codigo:string, nombre:string, nivel:int, total_debito:float, total_credito:float, saldo:float}>
     */
    public function balanceComprobacion(
        int    $empresaId,
        string $fechaDesde,
        string $fechaHasta,
        bool   $soloMovimiento = false
    ): array {
        $sql = <<<SQL
            SELECT
                p.codigo,
                p.nombre,
                p.nivel,
                p.naturaleza,
                p.tipo_cuenta,
                COALESCE(SUM(a.debito),  0.00) AS total_debito,
                COALESCE(SUM(a.credito), 0.00) AS total_credito,
                COALESCE(SUM(a.debito),  0.00) - COALESCE(SUM(a.credito), 0.00) AS saldo
            FROM puc_cuentas p
            LEFT JOIN asientos a
                   ON a.cuenta_id = p.id
                  AND a.empresa_id = p.empresa_id
            LEFT JOIN comprobantes c
                   ON c.id = a.comprobante_id
                  AND c.empresa_id = p.empresa_id
                  AND c.fecha BETWEEN :desde AND :hasta
                  AND c.estado = 'registrado'
            WHERE p.empresa_id = :eid
              AND p.acepta_movimiento = 1
              AND p.activa = 1
            GROUP BY p.id, p.codigo, p.nombre, p.nivel, p.naturaleza, p.tipo_cuenta
            HAVING (:solo_mov = 0 OR (total_debito + total_credito) > 0)
            ORDER BY p.codigo ASC
        SQL;

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute([
            ':eid'      => $empresaId,
            ':desde'    => $fechaDesde,
            ':hasta'    => $fechaHasta,
            ':solo_mov' => $soloMovimiento ? 1 : 0,
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Balance General (activos vs pasivos+patrimonio) para una fecha corte.
     */
    public function balanceGeneral(int $empresaId, string $fechaCorte): array
    {
        $sql = <<<SQL
            SELECT
                p.codigo,
                p.nombre,
                p.nivel,
                p.tipo_cuenta,
                p.naturaleza,
                COALESCE(SUM(
                    CASE WHEN c.fecha <= :corte AND c.estado = 'registrado'
                         THEN a.debito - a.credito ELSE 0 END
                ), 0.00) AS saldo_neto
            FROM puc_cuentas p
            LEFT JOIN asientos a     ON a.cuenta_id   = p.id AND a.empresa_id = p.empresa_id
            LEFT JOIN comprobantes c ON c.id           = a.comprobante_id AND c.empresa_id = p.empresa_id
            WHERE p.empresa_id    = :eid
              AND p.tipo_cuenta   IN ('A','P','R')
              AND p.acepta_movimiento = 1
              AND p.activa = 1
            GROUP BY p.id, p.codigo, p.nombre, p.nivel, p.tipo_cuenta, p.naturaleza
            HAVING ABS(saldo_neto) > 0.001
            ORDER BY p.codigo ASC
        SQL;

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':corte' => $fechaCorte]);
        return $stmt->fetchAll();
    }

    /**
     * Estado de Resultados (PYG) para un rango de fecha.
     */
    public function estadoResultados(int $empresaId, string $desde, string $hasta): array
    {
        $sql = <<<SQL
            SELECT
                p.codigo,
                p.nombre,
                p.nivel,
                p.tipo_cuenta,
                p.naturaleza,
                COALESCE(SUM(a.debito - a.credito), 0.00) AS saldo_neto
            FROM puc_cuentas p
            LEFT JOIN asientos a     ON a.cuenta_id   = p.id AND a.empresa_id = p.empresa_id
            LEFT JOIN comprobantes c ON c.id           = a.comprobante_id AND c.empresa_id = p.empresa_id
                                    AND c.fecha BETWEEN :desde AND :hasta
                                    AND c.estado = 'registrado'
            WHERE p.empresa_id    = :eid
              AND p.tipo_cuenta   IN ('G','R')
              AND p.acepta_movimiento = 1
              AND p.activa = 1
            GROUP BY p.id, p.codigo, p.nombre, p.nivel, p.tipo_cuenta, p.naturaleza
            HAVING ABS(saldo_neto) > 0.001
            ORDER BY p.codigo ASC
        SQL;

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * Auxiliar de cuenta: movimientos detallados de una cuenta en un período.
     */
    public function auxiliarCuenta(
        int    $empresaId,
        string $codigoCuenta,
        string $desde,
        string $hasta
    ): array {
        $sql = <<<SQL
            SELECT
                c.fecha,
                tc.codigo         AS tipo_comp,
                c.numero,
                t.razon_social    AS tercero,
                a.descripcion,
                a.debito,
                a.credito,
                (@saldo := @saldo + a.debito - a.credito) AS saldo_acumulado
            FROM asientos a
            INNER JOIN comprobantes    c  ON c.id = a.comprobante_id
            INNER JOIN tipos_comprobante tc ON tc.id = c.tipo_comp_id
            INNER JOIN puc_cuentas     p  ON p.id = a.cuenta_id
            LEFT  JOIN terceros        t  ON t.id = a.tercero_id
            CROSS JOIN (SELECT @saldo := 0.00) AS init
            WHERE a.empresa_id   = :eid
              AND p.codigo        = :cod
              AND c.fecha BETWEEN :desde AND :hasta
              AND c.estado        = 'registrado'
            ORDER BY c.fecha ASC, c.numero ASC
        SQL;

        $stmt = $this->database->getPdo()->prepare($sql);
        $stmt->execute([':eid' => $empresaId, ':cod' => $codigoCuenta, ':desde' => $desde, ':hasta' => $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * Listado de comprobantes con filtros.
     */
    public function listarComprobantes(
        int     $empresaId,
        ?string $tipoCodigo  = null,
        ?string $desde       = null,
        ?string $hasta       = null,
        string  $estado      = 'registrado',
        int     $limit       = 50,
        int     $offset      = 0
    ): array {
        $where   = ['c.empresa_id = :eid', "c.estado = :estado"];
        $params  = [':eid' => $empresaId, ':estado' => $estado];

        if ($tipoCodigo !== null) {
            $where[]            = 'tc.codigo = :tipo';
            $params[':tipo']    = $tipoCodigo;
        }

        if ($desde !== null) {
            $where[]            = 'c.fecha >= :desde';
            $params[':desde']   = $desde;
        }

        if ($hasta !== null) {
            $where[]            = 'c.fecha <= :hasta';
            $params[':hasta']   = $hasta;
        }

        $whereStr = implode(' AND ', $where);

        $sql = <<<SQL
            SELECT
                c.id,
                tc.codigo        AS tipo_comp,
                tc.nombre        AS tipo_nombre,
                c.numero,
                c.fecha,
                c.total_debitos,
                c.total_creditos,
                c.diferencia,
                c.estado,
                c.revisado,
                u.username       AS usuario,
                t.razon_social   AS tercero,
                c.observaciones
            FROM comprobantes c
            INNER JOIN tipos_comprobante tc ON tc.id = c.tipo_comp_id
            INNER JOIN usuarios          u  ON u.id  = c.usuario_id
            LEFT  JOIN terceros          t  ON t.id  = c.tercero_id
            WHERE {$whereStr}
            ORDER BY c.fecha DESC, c.numero DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $stmt = $this->database->getPdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
