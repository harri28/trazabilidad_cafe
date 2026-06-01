<?php
/**
 * Módulo: Operativo → Producción → Órdenes de Trabajo
 * Gestiona el procesamiento físico del café (secado, despergaminado, tostado, etc.)
 * Incluye control de avance y consumo de materiales.
 */
class OrdenTrabajoController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /produccion/ordenes-trabajo?acopio_id=5&estado=en_proceso
    public function index(): void
    {
        $where  = ['1=1'];
        $params = [];
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;

        foreach (['acopio_id' => ':lote', 'estado' => ':estado', 'tipo_proceso' => ':tipo'] as $field => $bind) {
            if (!empty($_GET[$field])) {
                $where[]       = "ot.{$field} = {$bind}";
                $params[$bind] = $_GET[$field];
            }
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM ordenes_trabajo ot WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT ot.*, l.codigo AS acopio_codigo, l.peso_actual_kg,
                   tc.nombre AS tipo_cafe, c.razon_social AS productor
            FROM ordenes_trabajo ot
            JOIN acopios l    ON l.id  = ot.acopio_id
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c ON c.id  = l.productor_id
            WHERE {$whereSQL}
            ORDER BY ot.fecha_inicio DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /produccion/ordenes-trabajo/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("
            SELECT ot.*, l.codigo AS acopio_codigo, l.peso_actual_kg,
                   tc.nombre AS tipo_cafe, c.razon_social AS productor
            FROM ordenes_trabajo ot
            JOIN acopios l    ON l.id  = ot.acopio_id
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c ON c.id  = l.productor_id
            WHERE ot.id = :id
        ");
        $stmt->execute([':id' => $p['id']]);
        $ot = $stmt->fetch();
        if (!$ot) { Response::error('Orden de trabajo no encontrada', 404); return; }

        $stmtM = $this->db->prepare(
            "SELECT * FROM ot_consumo_materiales WHERE orden_trabajo_id = :id ORDER BY fecha"
        );
        $stmtM->execute([':id' => $p['id']]);
        $ot['materiales'] = $stmtM->fetchAll();

        Response::json($ot);
    }

    // POST /produccion/ordenes-trabajo
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $numero = $this->generarNumero();

        $stmt = $this->db->prepare("
            INSERT INTO ordenes_trabajo
                (numero, acopio_id, plan_maestro_id, tipo_proceso,
                 fecha_inicio, fecha_fin_estimada, operador, maquinaria, notas)
            VALUES
                (:numero, :acopio_id, :plan_id, :tipo,
                 :fecha_inicio, :fecha_fin, :operador, :maquinaria, :notas)
        ");
        $stmt->execute([
            ':numero'      => $numero,
            ':acopio_id'     => $data['acopio_id'],
            ':plan_id'     => $data['plan_maestro_id']    ?? null,
            ':tipo'        => $data['tipo_proceso'],
            ':fecha_inicio'=> $data['fecha_inicio'],
            ':fecha_fin'   => $data['fecha_fin_estimada'] ?? null,
            ':operador'    => $data['operador']            ?? null,
            ':maquinaria'  => $data['maquinaria']          ?? null,
            ':notas'       => $data['notas']               ?? null,
        ]);
        $this->show(['id' => Database::lastId($this->db, 'ordenes_trabajo')]);
    }

    // PUT /produccion/ordenes-trabajo/{id}/avance
    // Actualiza el % de avance y opcionalmente cierra la OT
    public function actualizarAvance(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (!isset($data['avance_pct']) || $data['avance_pct'] < 0 || $data['avance_pct'] > 100) {
            Response::error('avance_pct debe estar entre 0 y 100', 422);
            return;
        }

        $estado = $data['avance_pct'] == 100 ? 'completada' : ($data['estado'] ?? 'en_proceso');
        $fechaFin = ($data['avance_pct'] == 100) ? ($data['fecha_fin_real'] ?? date('Y-m-d')) : null;

        $stmt = $this->db->prepare("
            UPDATE ordenes_trabajo SET
                avance_pct    = :avance,
                estado        = :estado,
                fecha_fin_real = COALESCE(:fecha_fin, fecha_fin_real)
            WHERE id = :id
        ");
        $stmt->execute([
            ':avance'    => $data['avance_pct'],
            ':estado'    => $estado,
            ':fecha_fin' => $fechaFin,
            ':id'        => $p['id'],
        ]);
        $this->show($p);
    }

    // POST /produccion/ordenes-trabajo/{id}/materiales
    public function registrarMaterial(array $p): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = [];
        if (empty($data['descripcion']))  $errors[] = 'descripcion es requerido';
        if (empty($data['cantidad']) || $data['cantidad'] <= 0) $errors[] = 'cantidad debe ser > 0';
        if (empty($data['unidad']))       $errors[] = 'unidad es requerido';
        if (empty($data['fecha']))        $errors[] = 'fecha es requerido';
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO ot_consumo_materiales
                (orden_trabajo_id, descripcion, cantidad, unidad, costo_unitario, moneda, fecha, notas)
            VALUES (:ot_id, :desc, :qty, :unidad, :costo, :moneda, :fecha, :notas)
        ");
        $stmt->execute([
            ':ot_id'  => $p['id'],
            ':desc'   => $data['descripcion'],
            ':qty'    => $data['cantidad'],
            ':unidad' => $data['unidad'],
            ':costo'  => $data['costo_unitario'] ?? null,
            ':moneda' => $data['moneda']          ?? 'PEN',
            ':fecha'  => $data['fecha'],
            ':notas'  => $data['notas']           ?? null,
        ]);
        Response::json(['id' => Database::lastId($this->db, 'ot_consumo_materiales'), 'message' => 'Material registrado'], 201);
    }

    private function generarNumero(): string
    {
        $n = (int)$this->db->query("SELECT COUNT(*)+1 FROM ordenes_trabajo")->fetchColumn();
        return 'OT-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['acopio_id']))      $errors[] = 'acopio_id es requerido';
        if (empty($d['tipo_proceso'])) $errors[] = 'tipo_proceso es requerido';
        if (empty($d['fecha_inicio'])) $errors[] = 'fecha_inicio es requerido';
        $tipos = ['secado','despergaminado','tostado','molido','envasado','seleccion','otro'];
        if (!in_array($d['tipo_proceso'] ?? '', $tipos))
            $errors[] = 'tipo_proceso inválido: ' . implode(', ', $tipos);
        return $errors;
    }
}
