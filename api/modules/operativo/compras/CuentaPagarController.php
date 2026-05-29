<?php
/**
 * Módulo: Operativo → Compras → Cuentas por Pagar
 * Seguimiento de obligaciones con proveedores.
 * Auto-generadas al completar una OC; también pueden registrarse manualmente.
 */
class CuentaPagarController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /compras/cuentas-pagar?estado=pendiente&proveedor_id=1&vence_antes=2025-12-31
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['estado'])) {
            $where[]           = 'cp.estado = :estado';
            $params[':estado'] = $_GET['estado'];
        }
        if (!empty($_GET['proveedor_id'])) {
            $where[]           = 'cp.proveedor_id = :prov';
            $params[':prov']   = $_GET['proveedor_id'];
        }
        if (!empty($_GET['vence_antes'])) {
            $where[]           = 'cp.fecha_vencimiento <= :vence';
            $params[':vence']  = $_GET['vence_antes'];
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM cuentas_pagar cp WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT cp.*, p.razon_social AS proveedor,
                   cp.monto_total - cp.monto_pagado AS saldo_pendiente,
                   (cp.fecha_vencimiento - CURRENT_DATE) AS dias_para_vencer
            FROM cuentas_pagar cp
            JOIN proveedores p ON p.id = cp.proveedor_id
            WHERE {$whereSQL}
            ORDER BY cp.fecha_vencimiento ASC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /compras/cuentas-pagar/resumen
    // Totales por estado y proveedor — útil para tesorería
    public function resumen(): void
    {
        $stmt = $this->db->query("
            SELECT
                cp.estado,
                COUNT(*)                                  AS cantidad,
                ROUND(SUM(cp.monto_total), 2)             AS total_monto,
                ROUND(SUM(cp.monto_pagado), 2)            AS total_pagado,
                ROUND(SUM(cp.monto_total - cp.monto_pagado), 2) AS total_pendiente
            FROM cuentas_pagar cp
            GROUP BY cp.estado
        ");
        $porEstado = $stmt->fetchAll();

        $stmtV = $this->db->query("
            SELECT p.razon_social AS proveedor,
                   ROUND(SUM(cp.monto_total - cp.monto_pagado), 2) AS deuda_total,
                   MIN(cp.fecha_vencimiento)                        AS proximo_venc
            FROM cuentas_pagar cp
            JOIN proveedores p ON p.id = cp.proveedor_id
            WHERE cp.estado IN ('pendiente','parcial','vencido')
            GROUP BY cp.proveedor_id
            ORDER BY deuda_total DESC
        ");

        Response::json([
            'por_estado'      => $porEstado,
            'por_proveedor'   => $stmtV->fetchAll(),
        ]);
    }

    // POST /compras/cuentas-pagar/{id}/pagar
    public function registrarPago(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['monto_pago']) || $data['monto_pago'] <= 0) {
            Response::error('monto_pago debe ser > 0', 422);
            return;
        }

        $stmt = $this->db->prepare("SELECT * FROM cuentas_pagar WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $cp = $stmt->fetch();

        if (!$cp) { Response::error('Cuenta por pagar no encontrada', 404); return; }

        $saldo    = $cp['monto_total'] - $cp['monto_pagado'];
        $pago     = min((float)$data['monto_pago'], $saldo);
        $nuevo    = $cp['monto_pagado'] + $pago;
        $estado   = ($nuevo >= $cp['monto_total']) ? 'pagado' : 'parcial';

        $this->db->prepare("
            UPDATE cuentas_pagar SET monto_pagado = :pagado, estado = :estado WHERE id = :id
        ")->execute([':pagado' => $nuevo, ':estado' => $estado, ':id' => $p['id']]);

        // Registrar en flujo de caja
        $this->db->prepare("
            INSERT INTO flujo_caja
                (fecha, tipo, concepto, monto, moneda, referencia_tipo, referencia_id)
            VALUES (:fecha, 'egreso', :concepto, :monto, :moneda, 'cuenta_pagar', :ref_id)
        ")->execute([
            ':fecha'   => $data['fecha']  ?? date('Y-m-d'),
            ':concepto'=> 'Pago a ' . ($cp['numero_documento'] ?? ''),
            ':monto'   => $pago,
            ':moneda'  => $cp['moneda'],
            ':ref_id'  => $p['id'],
        ]);

        Response::json(['saldo_anterior' => $saldo, 'pago_aplicado' => $pago, 'nuevo_estado' => $estado]);
    }

    // POST /compras/cuentas-pagar  (registro manual)
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = [];
        if (empty($data['proveedor_id']))      $errors[] = 'proveedor_id es requerido';
        if (empty($data['numero_documento']))  $errors[] = 'numero_documento es requerido';
        if (empty($data['fecha_emision']))     $errors[] = 'fecha_emision es requerido';
        if (empty($data['fecha_vencimiento'])) $errors[] = 'fecha_vencimiento es requerido';
        if (empty($data['monto_total']) || $data['monto_total'] <= 0) $errors[] = 'monto_total debe ser > 0';
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO cuentas_pagar
                (proveedor_id, orden_compra_id, numero_documento, tipo_documento,
                 fecha_emision, fecha_vencimiento, monto_total, moneda, notas)
            VALUES
                (:prov, :oc, :num_doc, :tipo_doc, :emision, :venc, :monto, :moneda, :notas)
        ");
        $stmt->execute([
            ':prov'     => $data['proveedor_id'],
            ':oc'       => $data['orden_compra_id']  ?? null,
            ':num_doc'  => $data['numero_documento'],
            ':tipo_doc' => $data['tipo_documento']   ?? 'factura',
            ':emision'  => $data['fecha_emision'],
            ':venc'     => $data['fecha_vencimiento'],
            ':monto'    => $data['monto_total'],
            ':moneda'   => $data['moneda']            ?? 'PEN',
            ':notas'    => $data['notas']             ?? null,
        ]);
        Response::json(['id' => Database::lastId($this->db, 'cuentas_pagar'), 'message' => 'Cuenta por pagar registrada'], 201);
    }
}
