<?php
class KardexController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /kardex?acopio_id=5&tipo=entrada&desde=2024-01-01&hasta=2024-12-31
    public function index(): void {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(200, (int)($_GET['per_page'] ?? 50));
        $offset = ($page - 1) * $limit;

        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['acopio_id'])) {
            $where[] = 'k.acopio_id = :acopio_id';
            $params[':acopio_id'] = $_GET['acopio_id'];
        }
        if (!empty($_GET['tipo'])) {
            $where[] = 'k.tipo_movimiento = :tipo';
            $params[':tipo'] = $_GET['tipo'];
        }
        if (!empty($_GET['desde'])) {
            $where[] = 'k.fecha >= :desde';
            $params[':desde'] = $_GET['desde'];
        }
        if (!empty($_GET['hasta'])) {
            $where[] = 'k.fecha <= :hasta';
            $params[':hasta'] = $_GET['hasta'];
        }

        $whereSQL = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM kardex k WHERE {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT
                k.*,
                l.codigo   AS acopio_codigo,
                c.razon_social AS productor
            FROM kardex k
            JOIN acopios l   ON l.id = k.acopio_id
            JOIN clientes c ON c.id = l.productor_id
            WHERE {$whereSQL}
            ORDER BY k.fecha DESC, k.id DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // POST /kardex  — registrar movimiento manual
    public function store(): void {
        $data   = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO kardex
                    (acopio_id, tipo_movimiento, concepto, fecha, cantidad_kg,
                     precio_unitario, moneda, tipo_cambio, prima_diferencial,
                     referencia_tipo, usuario, notas)
                VALUES
                    (:acopio_id, :tipo_movimiento, :concepto, :fecha, :cantidad_kg,
                     :precio_unitario, :moneda, :tipo_cambio, :prima_diferencial,
                     :referencia_tipo, :usuario, :notas)
            ");
            $stmt->execute([
                ':acopio_id'          => $data['acopio_id'],
                ':tipo_movimiento'  => $data['tipo_movimiento'],
                ':concepto'         => $data['concepto'],
                ':fecha'            => $data['fecha'],
                ':cantidad_kg'      => $data['cantidad_kg'],
                ':precio_unitario'  => $data['precio_unitario']  ?? null,
                ':moneda'           => $data['moneda']           ?? 'PEN',
                ':tipo_cambio'      => $data['tipo_cambio']      ?? 1,
                ':prima_diferencial'=> $data['prima_diferencial'] ?? 0,
                ':referencia_tipo'  => $data['referencia_tipo']  ?? null,
                ':usuario'          => $data['usuario']          ?? 'sistema',
                ':notas'            => $data['notas']            ?? null,
            ]);

            $id = Database::lastId($this->db, 'kardex');
            $row = $this->db->prepare("
                SELECT k.*, l.codigo AS acopio_codigo, l.peso_actual_kg AS saldo_actual_lote
                FROM kardex k JOIN acopios l ON l.id = k.acopio_id WHERE k.id = :id
            ");
            $row->execute([':id' => $id]);
            Response::json($row->fetch(), 201);

        } catch (PDOException $e) {
            // El trigger lanza error si stock insuficiente
            if (str_contains($e->getMessage(), 'Stock insuficiente')) {
                Response::error('Stock insuficiente para realizar la salida', 409);
            } else {
                Response::error('Error al registrar movimiento: ' . $e->getMessage(), 500);
            }
        }
    }

    // GET /kardex/resumen?acopio_id=5
    public function resumen(): void {
        $loteId = $_GET['acopio_id'] ?? null;
        $params = [];
        $extra  = '';

        if ($loteId) {
            $extra = 'WHERE k.acopio_id = :acopio_id';
            $params[':acopio_id'] = $loteId;
        }

        $stmt = $this->db->prepare("
            SELECT
                l.codigo, l.peso_inicial_kg,
                SUM(CASE WHEN k.tipo_movimiento = 'entrada'      THEN k.cantidad_kg ELSE 0 END) AS total_entradas_kg,
                SUM(CASE WHEN k.tipo_movimiento = 'salida'       THEN k.cantidad_kg ELSE 0 END) AS total_salidas_kg,
                SUM(CASE WHEN k.tipo_movimiento = 'transformacion' THEN k.cantidad_kg ELSE 0 END) AS total_transf_kg,
                SUM(CASE WHEN k.tipo_movimiento = 'entrada'      THEN k.total_monto  ELSE 0 END) AS total_compras_monto,
                l.peso_actual_kg AS saldo_actual,
                l.estado
            FROM kardex k
            JOIN acopios l ON l.id = k.acopio_id
            {$extra}
            GROUP BY l.id
            ORDER BY l.codigo
        ");
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    // GET /kardex/reporte-costos  — costos agregados por campaña
    public function reporteCostos(): void {
        $campana = $_GET['campana'] ?? date('Y');
        $stmt = $this->db->prepare("
            SELECT
                l.campaña,
                c.razon_social      AS productor,
                l.codigo            AS lote,
                SUM(k.cantidad_kg)  AS kg_comprados,
                SUM(k.total_monto)  AS costo_total,
                SUM(k.prima_total)  AS primas_total,
                AVG(k.precio_unitario) AS precio_promedio,
                k.moneda
            FROM kardex k
            JOIN acopios l    ON l.id = k.acopio_id
            JOIN clientes c ON c.id  = l.productor_id
            WHERE k.tipo_movimiento = 'entrada' AND l.campaña = :campana
            GROUP BY l.id, k.moneda
            ORDER BY c.razon_social, l.codigo
        ");
        $stmt->execute([':campana' => $campana]);
        Response::json($stmt->fetchAll());
    }

    private function validate(array $data): array {
        $errors = [];
        if (empty($data['acopio_id']))         $errors[] = 'acopio_id es requerido';
        if (empty($data['tipo_movimiento'])) $errors[] = 'tipo_movimiento es requerido';
        if (empty($data['concepto']))        $errors[] = 'concepto es requerido';
        if (empty($data['fecha']))           $errors[] = 'fecha es requerido';
        if (empty($data['cantidad_kg']) || $data['cantidad_kg'] <= 0)
                                            $errors[] = 'cantidad_kg debe ser > 0';
        $tipos = ['entrada','salida','ajuste','transformacion'];
        if (!in_array($data['tipo_movimiento'] ?? '', $tipos))
            $errors[] = 'tipo_movimiento inválido: ' . implode(', ', $tipos);
        return $errors;
    }
}
