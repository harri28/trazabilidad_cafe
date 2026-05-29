<?php
/**
 * Módulo: Administrativo → Financiero → Flujo de Caja
 * Registro y proyección de entradas/salidas de efectivo.
 * Integra pagos de ventas, pagos a proveedores y movimientos manuales.
 */
class FlujoCajaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /financiero/flujo-caja?tipo=ingreso&desde=2025-01-01&hasta=2025-12-31
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(200, (int)($_GET['per_page'] ?? 50));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        foreach (['tipo' => ':tipo', 'categoria' => ':cat', 'moneda' => ':moneda'] as $f => $b) {
            if (!empty($_GET[$f])) { $where[] = "fc.{$f} = {$b}"; $params[$b] = $_GET[$f]; }
        }
        if (!empty($_GET['desde'])) {
            $where[]          = 'fc.fecha >= :desde';
            $params[':desde'] = $_GET['desde'];
        }
        if (!empty($_GET['hasta'])) {
            $where[]          = 'fc.fecha <= :hasta';
            $params[':hasta'] = $_GET['hasta'];
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM flujo_caja fc WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT fc.*, cc.nombre AS centro_costo
            FROM flujo_caja fc
            LEFT JOIN centros_costo cc ON cc.id = fc.centro_costo_id
            WHERE {$whereSQL}
            ORDER BY fc.fecha DESC, fc.id DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /financiero/flujo-caja/resumen?desde=2025-01-01&hasta=2025-12-31
    public function resumen(): void
    {
        $desde  = $_GET['desde'] ?? date('Y-01-01');
        $hasta  = $_GET['hasta'] ?? date('Y-12-31');
        $params = [':desde' => $desde, ':hasta' => $hasta];

        // Totales por tipo en PEN
        $stmt = $this->db->prepare("
            SELECT
                tipo, categoria,
                COUNT(*)                        AS movimientos,
                ROUND(SUM(monto_pen), 2)        AS total_pen,
                ROUND(SUM(monto), 2)            AS total_moneda_orig
            FROM flujo_caja
            WHERE fecha BETWEEN :desde AND :hasta
            GROUP BY tipo, categoria
            ORDER BY tipo, categoria
        ");
        $stmt->execute($params);
        $detalle = $stmt->fetchAll();

        // Saldo neto
        $stmtSaldo = $this->db->prepare("
            SELECT
                ROUND(SUM(CASE WHEN tipo='ingreso' THEN monto_pen ELSE 0 END), 2) AS total_ingresos,
                ROUND(SUM(CASE WHEN tipo='egreso'  THEN monto_pen ELSE 0 END), 2) AS total_egresos,
                ROUND(SUM(CASE WHEN tipo='ingreso' THEN monto_pen ELSE -monto_pen END), 2) AS saldo_neto
            FROM flujo_caja
            WHERE fecha BETWEEN :desde AND :hasta
        ");
        $stmtSaldo->execute($params);

        // Por mes (para gráfico)
        $stmtMes = $this->db->prepare("
            SELECT
                TO_CHAR(fecha, 'YYYY-MM') AS mes,
                ROUND(SUM(CASE WHEN tipo='ingreso' THEN monto_pen ELSE 0 END), 2) AS ingresos,
                ROUND(SUM(CASE WHEN tipo='egreso'  THEN monto_pen ELSE 0 END), 2) AS egresos
            FROM flujo_caja
            WHERE fecha BETWEEN :desde AND :hasta
            GROUP BY mes
            ORDER BY mes
        ");
        $stmtMes->execute($params);

        Response::json([
            'periodo'  => ['desde' => $desde, 'hasta' => $hasta],
            'saldo'    => $stmtSaldo->fetch(),
            'detalle'  => $detalle,
            'por_mes'  => $stmtMes->fetchAll(),
        ]);
    }

    // POST /financiero/flujo-caja  (registro manual)
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO flujo_caja
                (fecha, tipo, categoria, concepto, monto, moneda, tipo_cambio,
                 referencia_tipo, referencia_id, cuenta_banco, centro_costo_id, notas)
            VALUES
                (:fecha, :tipo, :cat, :concepto, :monto, :moneda, :tc,
                 :ref_tipo, :ref_id, :banco, :cc_id, :notas)
        ");
        $stmt->execute([
            ':fecha'    => $data['fecha'],
            ':tipo'     => $data['tipo'],
            ':cat'      => $data['categoria']       ?? 'operativo',
            ':concepto' => $data['concepto'],
            ':monto'    => $data['monto'],
            ':moneda'   => $data['moneda']          ?? 'PEN',
            ':tc'       => $data['tipo_cambio']     ?? 1,
            ':ref_tipo' => $data['referencia_tipo'] ?? null,
            ':ref_id'   => $data['referencia_id']   ?? null,
            ':banco'    => $data['cuenta_banco']    ?? null,
            ':cc_id'    => $data['centro_costo_id'] ?? null,
            ':notas'    => $data['notas']           ?? null,
        ]);
        Response::json(['id' => Database::lastId($this->db, 'flujo_caja'), 'message' => 'Movimiento registrado'], 201);
    }

    // GET /financiero/flujo-caja/proyeccion
    // Proyecta cobros y pagos pendientes de los próximos N días
    public function proyeccion(): void
    {
        $dias = (int)($_GET['dias'] ?? 30);

        // Cobros pendientes: ventas confirmadas aún no pagadas
        $stmtCobros = $this->db->prepare("
            SELECT 'venta' AS tipo_ref, v.numero_contrato AS referencia,
                   v.fecha_entrega AS fecha_estimada, v.total_usd AS monto, 'USD' AS moneda,
                   c.razon_social AS contraparte
            FROM ventas v
            JOIN clientes c ON c.id = v.comprador_id
            WHERE v.estado IN ('confirmado','en_proceso')
              AND (v.fecha_entrega IS NULL OR v.fecha_entrega <= CURRENT_DATE + (:dias * INTERVAL '1 day'))
        ");
        $stmtCobros->execute([':dias' => $dias]);

        // Pagos pendientes: cuentas por pagar próximas a vencer
        $stmtPagos = $this->db->prepare("
            SELECT 'cuenta_pagar' AS tipo_ref, cp.numero_documento AS referencia,
                   cp.fecha_vencimiento AS fecha_estimada,
                   (cp.monto_total - cp.monto_pagado) AS monto, cp.moneda,
                   p.razon_social AS contraparte
            FROM cuentas_pagar cp
            JOIN proveedores p ON p.id = cp.proveedor_id
            WHERE cp.estado IN ('pendiente','parcial')
              AND cp.fecha_vencimiento <= CURRENT_DATE + (:dias * INTERVAL '1 day')
        ");
        $stmtPagos->execute([':dias' => $dias]);

        Response::json([
            'horizonte_dias' => $dias,
            'cobros_estimados' => $stmtCobros->fetchAll(),
            'pagos_estimados'  => $stmtPagos->fetchAll(),
        ]);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['fecha']))    $errors[] = 'fecha es requerido';
        if (empty($d['tipo']) || !in_array($d['tipo'], ['ingreso','egreso']))
            $errors[] = 'tipo debe ser ingreso o egreso';
        if (empty($d['concepto'])) $errors[] = 'concepto es requerido';
        if (empty($d['monto']) || $d['monto'] <= 0) $errors[] = 'monto debe ser > 0';
        return $errors;
    }
}
