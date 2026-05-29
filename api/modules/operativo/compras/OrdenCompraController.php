<?php
/**
 * Módulo: Operativo → Compras → Órdenes de Compra
 * Gestión de OC a proveedores. Flujo: borrador → enviada → confirmada → completada.
 * Al completarse genera automáticamente una cuenta por pagar.
 */
class OrdenCompraController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /compras/ordenes?proveedor_id=1&estado=confirmada
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        foreach (['proveedor_id' => ':prov', 'estado' => ':estado'] as $f => $b) {
            if (!empty($_GET[$f])) { $where[] = "oc.{$f} = {$b}"; $params[$b] = $_GET[$f]; }
        }
        if (!empty($_GET['desde'])) {
            $where[]          = 'oc.fecha_emision >= :desde';
            $params[':desde'] = $_GET['desde'];
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM ordenes_compra oc WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT oc.*, p.razon_social AS proveedor, p.ruc
            FROM ordenes_compra oc
            JOIN proveedores p ON p.id = oc.proveedor_id
            WHERE {$whereSQL}
            ORDER BY oc.fecha_emision DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /compras/ordenes/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("
            SELECT oc.*, pv.razon_social AS proveedor, pv.ruc, pv.email AS email_proveedor,
                   pv.condiciones_pago
            FROM ordenes_compra oc
            JOIN proveedores pv ON pv.id = oc.proveedor_id
            WHERE oc.id = :id
        ");
        $stmt->execute([':id' => $p['id']]);
        $oc = $stmt->fetch();
        if (!$oc) { Response::error('Orden de compra no encontrada', 404); return; }

        $stmtI = $this->db->prepare("SELECT * FROM oc_items WHERE orden_compra_id = :id");
        $stmtI->execute([':id' => $p['id']]);
        $oc['items'] = $stmtI->fetchAll();

        Response::json($oc);
    }

    // POST /compras/ordenes
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $numero = $this->generarNumero();

        $this->db->beginTransaction();
        try {
            // Calcular totales desde los items
            $subtotal = array_sum(array_map(
                fn($i) => ($i['cantidad'] ?? 0) * ($i['precio_unitario'] ?? 0),
                $data['items']
            ));
            $igv   = round($subtotal * 0.18, 2);
            $total = round($subtotal + $igv, 2);

            $stmt = $this->db->prepare("
                INSERT INTO ordenes_compra
                    (numero, proveedor_id, requisicion_id, fecha_emision, fecha_entrega,
                     moneda, tipo_cambio, subtotal, igv, total, notas)
                VALUES
                    (:num, :prov, :req, :fecha, :entrega,
                     :moneda, :tc, :subtotal, :igv, :total, :notas)
            ");
            $stmt->execute([
                ':num'     => $numero,
                ':prov'    => $data['proveedor_id'],
                ':req'     => $data['requisicion_id']  ?? null,
                ':fecha'   => $data['fecha_emision'],
                ':entrega' => $data['fecha_entrega']   ?? null,
                ':moneda'  => $data['moneda']          ?? 'PEN',
                ':tc'      => $data['tipo_cambio']     ?? 1,
                ':subtotal'=> $subtotal,
                ':igv'     => $igv,
                ':total'   => $total,
                ':notas'   => $data['notas']           ?? null,
            ]);
            $ocId = Database::lastId($this->db, 'ordenes_compra');

            $stmtI = $this->db->prepare("
                INSERT INTO oc_items (orden_compra_id, descripcion, cantidad, unidad, precio_unitario)
                VALUES (:oc_id, :desc, :qty, :unidad, :precio)
            ");
            foreach ($data['items'] as $item) {
                $stmtI->execute([
                    ':oc_id'  => $ocId,
                    ':desc'   => $item['descripcion'],
                    ':qty'    => $item['cantidad'],
                    ':unidad' => $item['unidad']         ?? 'und',
                    ':precio' => $item['precio_unitario'],
                ]);
            }

            $this->db->commit();
            $this->show(['id' => $ocId]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::error('Error al crear OC: ' . $e->getMessage(), 500);
        }
    }

    // PUT /compras/ordenes/{id}/confirmar
    public function confirmar(array $p): void
    {
        $this->cambiarEstado($p['id'], 'borrador', 'confirmada');
    }

    // PUT /compras/ordenes/{id}/completar
    // Al completar genera la cuenta por pagar automáticamente
    public function completar(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $this->db->prepare("SELECT * FROM ordenes_compra WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $oc = $stmt->fetch();

        if (!$oc) { Response::error('OC no encontrada', 404); return; }
        if (!in_array($oc['estado'], ['confirmada', 'parcial'])) {
            Response::error("Solo se pueden completar OCs en estado 'confirmada' o 'parcial'", 409);
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE ordenes_compra SET estado = 'completada' WHERE id = :id")
                     ->execute([':id' => $p['id']]);

            // Generar cuenta por pagar automáticamente
            $numDoc = $data['numero_documento'] ?? 'F-' . str_pad($p['id'], 6, '0', STR_PAD_LEFT);
            $venc   = $data['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days'));

            $stmtCxP = $this->db->prepare("
                INSERT INTO cuentas_pagar
                    (proveedor_id, orden_compra_id, numero_documento, tipo_documento,
                     fecha_emision, fecha_vencimiento, monto_total, moneda)
                VALUES
                    (:prov, :oc, :num_doc, :tipo_doc, :hoy, :venc, :monto, :moneda)
            ");
            $stmtCxP->execute([
                ':prov'     => $oc['proveedor_id'],
                ':oc'       => $oc['id'],
                ':num_doc'  => $numDoc,
                ':tipo_doc' => $data['tipo_documento'] ?? 'factura',
                ':hoy'      => date('Y-m-d'),
                ':venc'     => $venc,
                ':monto'    => $oc['total'],
                ':moneda'   => $oc['moneda'],
            ]);

            $this->db->commit();
            $this->show($p);

        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::error('Error al completar OC: ' . $e->getMessage(), 500);
        }
    }

    // PUT /compras/ordenes/{id}/cancelar
    public function cancelar(array $p): void
    {
        $this->cambiarEstado($p['id'], null, 'cancelada', ['borrador', 'confirmada']);
    }

    private function cambiarEstado(int $id, ?string $estadoReq, string $nuevoEstado, array $validos = []): void
    {
        $stmt = $this->db->prepare("SELECT estado FROM ordenes_compra WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $oc = $stmt->fetch();

        if (!$oc) { Response::error('OC no encontrada', 404); return; }

        $permitidos = $validos ?: [$estadoReq];
        if (!in_array($oc['estado'], $permitidos)) {
            Response::error("Estado actual '{$oc['estado']}' no permite esta operación", 409);
            return;
        }

        $this->db->prepare("UPDATE ordenes_compra SET estado = :e WHERE id = :id")
                 ->execute([':e' => $nuevoEstado, ':id' => $id]);

        $this->show(['id' => $id]);
    }

    private function generarNumero(): string
    {
        $n = (int)$this->db->query("SELECT COUNT(*)+1 FROM ordenes_compra")->fetchColumn();
        return 'OC-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['proveedor_id']))    $errors[] = 'proveedor_id es requerido';
        if (empty($d['fecha_emision']))   $errors[] = 'fecha_emision es requerido';
        if (empty($d['items']) || !is_array($d['items']) || count($d['items']) === 0)
            $errors[] = 'Debe incluir al menos un item';
        foreach ($d['items'] ?? [] as $i => $item) {
            if (empty($item['descripcion']))   $errors[] = "Item {$i}: descripcion requerida";
            if (empty($item['cantidad']) || $item['cantidad'] <= 0) $errors[] = "Item {$i}: cantidad inválida";
            if (!isset($item['precio_unitario']) || $item['precio_unitario'] < 0) $errors[] = "Item {$i}: precio_unitario requerido";
        }
        return $errors;
    }
}
