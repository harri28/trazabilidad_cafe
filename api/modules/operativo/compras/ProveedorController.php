<?php
/**
 * Módulo: Operativo → Compras → Proveedores
 * Gestión de proveedores de insumos y servicios (≠ clientes-productores).
 */
class ProveedorController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /compras/proveedores?categoria=insumos&search=xxx
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;
        $where  = ['activo = true'];
        $params = [];

        if (!empty($_GET['categoria'])) {
            $where[]           = 'categoria = :cat';
            $params[':cat']    = $_GET['categoria'];
        }
        if (!empty($_GET['search'])) {
            $where[]           = '(razon_social ILIKE :s OR ruc ILIKE :s2)';
            $params[':s']      = "%{$_GET['search']}%";
            $params[':s2']     = "%{$_GET['search']}%";
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM proveedores {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM ordenes_compra oc WHERE oc.proveedor_id = p.id) AS total_oc,
                   (SELECT COALESCE(SUM(cp.monto_total - cp.monto_pagado), 0)
                    FROM cuentas_pagar cp
                    WHERE cp.proveedor_id = p.id AND cp.estado IN ('pendiente','parcial')) AS deuda_pendiente
            FROM proveedores p {$whereSQL}
            ORDER BY razon_social
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /compras/proveedores/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $prov = $stmt->fetch();
        if (!$prov) { Response::error('Proveedor no encontrado', 404); return; }

        $stmtOC = $this->db->prepare("
            SELECT numero, fecha_emision, estado, total, moneda
            FROM ordenes_compra WHERE proveedor_id = :id ORDER BY fecha_emision DESC LIMIT 10
        ");
        $stmtOC->execute([':id' => $p['id']]);
        $prov['ultimas_oc'] = $stmtOC->fetchAll();

        Response::json($prov);
    }

    // POST /compras/proveedores
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO proveedores
                (razon_social, ruc, contacto, telefono, email,
                 direccion, categoria, condiciones_pago, notas)
            VALUES
                (:razon, :ruc, :contacto, :tel, :email,
                 :dir, :cat, :condiciones, :notas)
        ");
        $stmt->execute([
            ':razon'       => $data['razon_social'],
            ':ruc'         => $data['ruc']               ?? null,
            ':contacto'    => $data['contacto']          ?? null,
            ':tel'         => $data['telefono']          ?? null,
            ':email'       => $data['email']             ?? null,
            ':dir'         => $data['direccion']         ?? null,
            ':cat'         => $data['categoria']         ?? 'insumos',
            ':condiciones' => $data['condiciones_pago']  ?? null,
            ':notas'       => $data['notas']             ?? null,
        ]);
        $this->show(['id' => Database::lastId($this->db, 'proveedores')]);
    }

    // PUT /compras/proveedores/{id}
    public function update(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $this->db->prepare("
            UPDATE proveedores SET
                razon_social     = :razon,
                ruc              = :ruc,
                contacto         = :contacto,
                telefono         = :tel,
                email            = :email,
                direccion        = :dir,
                categoria        = :cat,
                condiciones_pago = :condiciones,
                notas            = :notas
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'          => $p['id'],
            ':razon'       => $data['razon_social'],
            ':ruc'         => $data['ruc']               ?? null,
            ':contacto'    => $data['contacto']          ?? null,
            ':tel'         => $data['telefono']          ?? null,
            ':email'       => $data['email']             ?? null,
            ':dir'         => $data['direccion']         ?? null,
            ':cat'         => $data['categoria']         ?? 'insumos',
            ':condiciones' => $data['condiciones_pago']  ?? null,
            ':notas'       => $data['notas']             ?? null,
        ]);
        $this->show($p);
    }

    // DELETE /compras/proveedores/{id}  (baja lógica)
    public function destroy(array $p): void
    {
        $stmt = $this->db->prepare("UPDATE proveedores SET activo = false WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        Response::json(['message' => 'Proveedor desactivado']);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['razon_social'])) $errors[] = 'razon_social es requerido';
        if (!empty($d['email']) && !filter_var($d['email'], FILTER_VALIDATE_EMAIL))
            $errors[] = 'email inválido';
        if (!empty($d['ruc'])) {
            $s = $this->db->prepare("SELECT id FROM proveedores WHERE ruc = :ruc");
            $s->execute([':ruc' => $d['ruc']]);
            if ($s->fetch()) $errors[] = 'RUC ya registrado';
        }
        return $errors;
    }
}
