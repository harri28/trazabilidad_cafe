<?php
class ClienteController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /clientes?tipo=productor&page=1&search=Juan
    public function index(): void {
        $tipo   = $_GET['tipo']   ?? null;
        $search = $_GET['search'] ?? null;
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;

        $where  = ['activo = true'];
        $params = [];

        if ($tipo) {
            $where[]  = "tipo IN ('productor','ambos')";
            if ($tipo === 'comprador') $where[count($where)-1] = "tipo IN ('comprador','ambos')";
            elseif ($tipo === 'productor') $where[count($where)-1] = "tipo IN ('productor','ambos')";
        }
        if ($search) {
            $where[]  = '(razon_social ILIKE :search OR ruc_dni ILIKE :search2 OR asociacion ILIKE :search3)';
            $params[':search']  = "%{$search}%";
            $params[':search2'] = "%{$search}%";
            $params[':search3'] = "%{$search}%";
        }

        $whereSQL = 'WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM clientes {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT id, tipo, razon_social, ruc_dni, contacto, telefono, email,
                   departamento, provincia, distrito, asociacion,
                   altitud_msnm, hectareas, pais_destino, notas,
                   activo, creado_en
            FROM clientes {$whereSQL}
            ORDER BY razon_social
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /clientes/{id}
    public function show(array $params): void {
        $stmt = $this->db->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);
        $cliente = $stmt->fetch();

        if (!$cliente) {
            Response::error('Cliente no encontrado', 404);
            return;
        }
        Response::json($cliente);
    }

    // POST /clientes
    public function store(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO clientes
                (tipo, razon_social, ruc_dni, contacto, telefono, email,
                 direccion, departamento, provincia, distrito,
                 altitud_msnm, hectareas, asociacion,
                 pais_destino, moneda_pref, notas)
            VALUES
                (:tipo, :razon_social, :ruc_dni, :contacto, :telefono, :email,
                 :direccion, :departamento, :provincia, :distrito,
                 :altitud_msnm, :hectareas, :asociacion,
                 :pais_destino, :moneda_pref, :notas)
        ");
        $stmt->execute([
            ':tipo'          => $data['tipo'],
            ':razon_social'  => $data['razon_social'],
            ':ruc_dni'       => $data['ruc_dni']       ?? null,
            ':contacto'      => $data['contacto']      ?? null,
            ':telefono'      => $data['telefono']      ?? null,
            ':email'         => $data['email']         ?? null,
            ':direccion'     => $data['direccion']     ?? null,
            ':departamento'  => $data['departamento']  ?? null,
            ':provincia'     => $data['provincia']     ?? null,
            ':distrito'      => $data['distrito']      ?? null,
            ':altitud_msnm'  => $data['altitud_msnm']  ?? null,
            ':hectareas'     => $data['hectareas']     ?? null,
            ':asociacion'    => $data['asociacion']    ?? null,
            ':pais_destino'  => $data['pais_destino']  ?? null,
            ':moneda_pref'   => $data['moneda_pref']   ?? 'USD',
            ':notas'         => $data['notas']         ?? null,
        ]);

        $id = Database::lastId($this->db, 'clientes');
        $this->show(['id' => $id]);
    }

    // PUT /clientes/{id}
    public function update(array $params): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validate($data, (int)$params['id']);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            UPDATE clientes SET
                tipo = :tipo, razon_social = :razon_social, ruc_dni = :ruc_dni,
                contacto = :contacto, telefono = :telefono, email = :email,
                direccion = :direccion, departamento = :departamento,
                provincia = :provincia, distrito = :distrito,
                altitud_msnm = :altitud_msnm, hectareas = :hectareas,
                asociacion = :asociacion, pais_destino = :pais_destino,
                moneda_pref = :moneda_pref, notas = :notas
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'            => $params['id'],
            ':tipo'          => $data['tipo'],
            ':razon_social'  => $data['razon_social'],
            ':ruc_dni'       => $data['ruc_dni']       ?? null,
            ':contacto'      => $data['contacto']      ?? null,
            ':telefono'      => $data['telefono']      ?? null,
            ':email'         => $data['email']         ?? null,
            ':direccion'     => $data['direccion']     ?? null,
            ':departamento'  => $data['departamento']  ?? null,
            ':provincia'     => $data['provincia']     ?? null,
            ':distrito'      => $data['distrito']      ?? null,
            ':altitud_msnm'  => $data['altitud_msnm']  ?? null,
            ':hectareas'     => $data['hectareas']     ?? null,
            ':asociacion'    => $data['asociacion']    ?? null,
            ':pais_destino'  => $data['pais_destino']  ?? null,
            ':moneda_pref'   => $data['moneda_pref']   ?? 'USD',
            ':notas'         => $data['notas']         ?? null,
        ]);

        $this->show($params);
    }

    // DELETE /clientes/{id}  (baja lógica)
    public function destroy(array $params): void {
        // Verificar que no tiene acopios activos
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM acopios WHERE productor_id = :id AND estado != 'vendido'
        ");
        $stmt->execute([':id' => $params['id']]);
        if ($stmt->fetchColumn() > 0) {
            Response::error('No se puede eliminar: el productor tiene acopios activos', 409);
            return;
        }

        $stmt = $this->db->prepare("UPDATE clientes SET activo = false WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);
        Response::json(['message' => 'Cliente desactivado correctamente']);
    }

    // GET /clientes/{id}/acopios
    public function acopios(array $params): void {
        $stmt = $this->db->prepare("
            SELECT l.*, tc.nombre AS tipo_cafe
            FROM acopios l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            WHERE l.productor_id = :id
            ORDER BY l.fecha_acopio DESC
        ");
        $stmt->execute([':id' => $params['id']]);
        Response::json($stmt->fetchAll());
    }

    private function validate(array $data, ?int $excludeId = null): array {
        $errors = [];
        if (empty($data['razon_social'])) $errors[] = 'razon_social es requerido';
        if (empty($data['tipo']) || !in_array($data['tipo'], ['productor','comprador','ambos'])) {
            $errors[] = 'tipo debe ser: productor, comprador o ambos';
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'email inválido';
        }
        if (!empty($data['ruc_dni'])) {
            $q = $this->db->prepare(
                "SELECT id FROM clientes WHERE ruc_dni = :ruc" .
                ($excludeId ? " AND id != :id" : "")
            );
            $bind = [':ruc' => $data['ruc_dni']];
            if ($excludeId) $bind[':id'] = $excludeId;
            $q->execute($bind);
            if ($q->fetch()) $errors[] = 'RUC/DNI ya registrado';
        }
        return $errors;
    }
}
