<?php
/**
 * Módulo: Operativo → Compras → Requisiciones Internas
 * Solicitudes de compra generadas por áreas internas antes de emitir una OC.
 */
class RequisicionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /compras/requisiciones?estado=pendiente&area=produccion
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['estado'])) {
            $where[]          = 'estado = :estado';
            $params[':estado']= $_GET['estado'];
        }
        if (!empty($_GET['area'])) {
            $where[]          = 'area_solicitante LIKE :area';
            $params[':area']  = "%{$_GET['area']}%";
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM requisiciones WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT r.*,
                   (SELECT COUNT(*) FROM requisicion_items ri WHERE ri.requisicion_id = r.id) AS num_items
            FROM requisiciones r
            WHERE {$whereSQL}
            ORDER BY r.fecha_solicitud DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /compras/requisiciones/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("SELECT * FROM requisiciones WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $req = $stmt->fetch();
        if (!$req) { Response::error('Requisición no encontrada', 404); return; }

        $stmtI = $this->db->prepare(
            "SELECT * FROM requisicion_items WHERE requisicion_id = :id"
        );
        $stmtI->execute([':id' => $p['id']]);
        $req['items'] = $stmtI->fetchAll();

        Response::json($req);
    }

    // POST /compras/requisiciones  (con items)
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $numero = $this->generarNumero();

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO requisiciones
                    (numero, area_solicitante, solicitante, fecha_solicitud, fecha_requerida, notas)
                VALUES (:num, :area, :sol, :fecha, :fecha_req, :notas)
            ");
            $stmt->execute([
                ':num'      => $numero,
                ':area'     => $data['area_solicitante'],
                ':sol'      => $data['solicitante']   ?? null,
                ':fecha'    => $data['fecha_solicitud'],
                ':fecha_req'=> $data['fecha_requerida'] ?? null,
                ':notas'    => $data['notas']           ?? null,
            ]);
            $reqId = Database::lastId($this->db, 'requisiciones');

            if (!empty($data['items'])) {
                $stmtI = $this->db->prepare("
                    INSERT INTO requisicion_items
                        (requisicion_id, descripcion, cantidad, unidad, justificacion)
                    VALUES (:rid, :desc, :qty, :unidad, :just)
                ");
                foreach ($data['items'] as $item) {
                    $stmtI->execute([
                        ':rid'    => $reqId,
                        ':desc'   => $item['descripcion'],
                        ':qty'    => $item['cantidad'],
                        ':unidad' => $item['unidad'],
                        ':just'   => $item['justificacion'] ?? null,
                    ]);
                }
            }

            $this->db->commit();
            $this->show(['id' => $reqId]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::error('Error al crear requisición: ' . $e->getMessage(), 500);
        }
    }

    // PUT /compras/requisiciones/{id}/aprobar
    public function aprobar(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $this->db->prepare("SELECT estado FROM requisiciones WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $req = $stmt->fetch();

        if (!$req) { Response::error('Requisición no encontrada', 404); return; }
        if ($req['estado'] !== 'pendiente') {
            Response::error("Solo se pueden aprobar requisiciones en estado 'pendiente'", 409);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE requisiciones SET estado = :estado, aprobado_por = :por WHERE id = :id
        ");
        $stmt->execute([
            ':estado' => $data['aprobado'] ? 'aprobada' : 'rechazada',
            ':por'    => $data['aprobado_por'] ?? 'sistema',
            ':id'     => $p['id'],
        ]);
        $this->show($p);
    }

    private function generarNumero(): string
    {
        $n = (int)$this->db->query("SELECT COUNT(*)+1 FROM requisiciones")->fetchColumn();
        return 'REQ-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['area_solicitante']))  $errors[] = 'area_solicitante es requerido';
        if (empty($d['fecha_solicitud']))   $errors[] = 'fecha_solicitud es requerido';
        if (empty($d['items']) || !is_array($d['items']) || count($d['items']) === 0)
            $errors[] = 'Debe incluir al menos un item';
        return $errors;
    }
}
