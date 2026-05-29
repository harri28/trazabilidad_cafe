<?php
/**
 * Módulo: Administrativo → Financiero → Asientos Contables
 * Registro y consulta del libro diario. Los asientos pueden generarse
 * automáticamente desde ventas, compras y OTs, o registrarse manualmente.
 * Valida partida doble: suma(debe) = suma(haber).
 */
class AsientoContableController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /financiero/asientos?desde=2025-01-01&hasta=2025-12-31&estado=validado
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 50));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['estado'])) {
            $where[]           = 'a.estado = :estado';
            $params[':estado'] = $_GET['estado'];
        }
        if (!empty($_GET['desde'])) {
            $where[]           = 'a.fecha >= :desde';
            $params[':desde']  = $_GET['desde'];
        }
        if (!empty($_GET['hasta'])) {
            $where[]           = 'a.fecha <= :hasta';
            $params[':hasta']  = $_GET['hasta'];
        }
        if (!empty($_GET['referencia_tipo'])) {
            $where[]           = 'a.referencia_tipo = :ref_tipo';
            $params[':ref_tipo'] = $_GET['referencia_tipo'];
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM asientos_contables a WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT a.id, a.numero, a.fecha, a.concepto,
                   a.referencia_tipo, a.referencia_id, a.estado,
                   a.total_debe, a.total_haber, a.creado_en
            FROM asientos_contables a
            WHERE {$whereSQL}
            ORDER BY a.fecha DESC, a.id DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /financiero/asientos/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("SELECT * FROM asientos_contables WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $asiento = $stmt->fetch();
        if (!$asiento) { Response::error('Asiento no encontrado', 404); return; }

        $stmtL = $this->db->prepare("
            SELECT al.*, pc.codigo AS cuenta_codigo, pc.nombre AS cuenta_nombre, pc.tipo AS cuenta_tipo,
                   cc.nombre AS centro_costo
            FROM asiento_lineas al
            JOIN plan_cuentas pc ON pc.id = al.cuenta_id
            LEFT JOIN centros_costo cc ON cc.id = al.centro_costo_id
            WHERE al.asiento_id = :id
            ORDER BY al.id
        ");
        $stmtL->execute([':id' => $p['id']]);
        $asiento['lineas'] = $stmtL->fetchAll();

        Response::json($asiento);
    }

    // POST /financiero/asientos
    // Crea un asiento con sus líneas validando partida doble
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $totalDebe  = array_sum(array_column($data['lineas'], 'debe'));
        $totalHaber = array_sum(array_column($data['lineas'], 'haber'));

        if (abs($totalDebe - $totalHaber) > 0.01) {
            Response::error(
                "Partida doble no balanceada: debe={$totalDebe}, haber={$totalHaber}",
                422
            );
            return;
        }

        $numero = $this->generarNumero($data['fecha']);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO asientos_contables
                    (numero, fecha, concepto, referencia_tipo, referencia_id,
                     estado, total_debe, total_haber)
                VALUES
                    (:num, :fecha, :concepto, :ref_tipo, :ref_id,
                     :estado, :debe, :haber)
            ");
            $stmt->execute([
                ':num'      => $numero,
                ':fecha'    => $data['fecha'],
                ':concepto' => $data['concepto'],
                ':ref_tipo' => $data['referencia_tipo'] ?? null,
                ':ref_id'   => $data['referencia_id']   ?? null,
                ':estado'   => $data['estado']          ?? 'borrador',
                ':debe'     => $totalDebe,
                ':haber'    => $totalHaber,
            ]);
            $asientoId = Database::lastId($this->db, 'asientos_contables');

            $stmtL = $this->db->prepare("
                INSERT INTO asiento_lineas
                    (asiento_id, cuenta_id, centro_costo_id, debe, haber, descripcion)
                VALUES (:asiento, :cuenta, :cc, :debe, :haber, :desc)
            ");
            foreach ($data['lineas'] as $linea) {
                $stmtL->execute([
                    ':asiento' => $asientoId,
                    ':cuenta'  => $linea['cuenta_id'],
                    ':cc'      => $linea['centro_costo_id'] ?? null,
                    ':debe'    => $linea['debe']            ?? 0,
                    ':haber'   => $linea['haber']           ?? 0,
                    ':desc'    => $linea['descripcion']     ?? null,
                ]);
            }

            $this->db->commit();
            $this->show(['id' => $asientoId]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::error('Error al crear asiento: ' . $e->getMessage(), 500);
        }
    }

    // PUT /financiero/asientos/{id}/validar
    public function validar(array $p): void
    {
        $stmt = $this->db->prepare("SELECT estado FROM asientos_contables WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $a = $stmt->fetch();
        if (!$a) { Response::error('Asiento no encontrado', 404); return; }
        if ($a['estado'] !== 'borrador') {
            Response::error("Solo se pueden validar asientos en estado 'borrador'", 409); return;
        }

        $this->db->prepare("UPDATE asientos_contables SET estado = 'validado' WHERE id = :id")
                 ->execute([':id' => $p['id']]);
        $this->show($p);
    }

    // GET /financiero/asientos/plan-cuentas
    public function planCuentas(): void
    {
        $stmt = $this->db->query("
            SELECT pc.*, padre.codigo AS padre_codigo
            FROM plan_cuentas pc
            LEFT JOIN plan_cuentas padre ON padre.id = pc.padre_id
            WHERE pc.activo = true
            ORDER BY pc.codigo
        ");
        Response::json($stmt->fetchAll());
    }

    // POST /financiero/asientos/plan-cuentas
    public function storeCuenta(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = [];
        if (empty($data['codigo'])) $errors[] = 'codigo es requerido';
        if (empty($data['nombre'])) $errors[] = 'nombre es requerido';
        if (empty($data['tipo']))   $errors[] = 'tipo es requerido';
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO plan_cuentas (codigo, nombre, tipo, padre_id, nivel)
            VALUES (:cod, :nom, :tipo, :padre, :nivel)
        ");
        $stmt->execute([
            ':cod'   => $data['codigo'],
            ':nom'   => $data['nombre'],
            ':tipo'  => $data['tipo'],
            ':padre' => $data['padre_id'] ?? null,
            ':nivel' => $data['nivel']    ?? 1,
        ]);
        Response::json(['id' => Database::lastId($this->db, 'plan_cuentas')], 201);
    }

    private function generarNumero(string $fecha): string
    {
        $anio = substr($fecha, 0, 4);
        $mes  = substr($fecha, 5, 2);
        $n = (int)$this->db->prepare(
            "SELECT COUNT(*)+1 FROM asientos_contables WHERE fecha LIKE :mes"
        )->execute([":mes" => "{$anio}-{$mes}%"]) ? 0 : 0;
        $count = $this->db->prepare("SELECT COUNT(*)+1 FROM asientos_contables WHERE EXTRACT(YEAR FROM fecha)=:a AND EXTRACT(MONTH FROM fecha)=:m");
        $count->execute([':a' => $anio, ':m' => (int)$mes]);
        $n = (int)$count->fetchColumn();
        return "A-{$anio}{$mes}-" . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['fecha']))    $errors[] = 'fecha es requerido';
        if (empty($d['concepto'])) $errors[] = 'concepto es requerido';
        if (empty($d['lineas']) || count($d['lineas']) < 2)
            $errors[] = 'Se requieren al menos 2 líneas (partida doble)';
        return $errors;
    }
}
