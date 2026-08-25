<?php
/**
 * Módulo: Operativo → Producción → Plan Maestro (MRP básico)
 * Gestiona planes de producción por campaña y tipo de café.
 * Calcula brechas entre meta y producción real usando lotes y ventas.
 */
class PlanMaestroController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /produccion/plan-maestro?campana=2025&estado=activo
    public function index(): void
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['campana'])) {
            $where[]            = 'pm.campaña = :campana';
            $params[':campana'] = $_GET['campana'];
        }
        if (!empty($_GET['estado'])) {
            $where[]           = 'pm.estado = :estado';
            $params[':estado'] = $_GET['estado'];
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                pm.*,
                tc.nombre AS tipo_cafe,
                pm.cantidad_real_kg / NULLIF(pm.cantidad_meta_kg, 0) * 100 AS avance_pct,
                pm.cantidad_meta_kg - pm.cantidad_real_kg                   AS brecha_kg,
                -- Kg en ventas confirmadas para esta campaña/tipo
                (SELECT COALESCE(SUM(v.cantidad_kg), 0)
                 FROM ventas v
                 JOIN acopios l ON l.id = v.acopio_id
                 WHERE l.campaña = pm.campaña
                   AND l.tipo_cafe_id = pm.tipo_cafe_id
                   AND v.estado NOT IN ('cancelado','borrador')) AS kg_comprometidos
            FROM plan_maestro pm
            JOIN tipos_cafe tc ON tc.id = pm.tipo_cafe_id
            WHERE {$whereSQL}
            ORDER BY pm.campaña DESC, tc.nombre
        ");
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    // GET /produccion/plan-maestro/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("
            SELECT pm.*, tc.nombre AS tipo_cafe
            FROM plan_maestro pm
            JOIN tipos_cafe tc ON tc.id = pm.tipo_cafe_id
            WHERE pm.id = :id
        ");
        $stmt->execute([':id' => $p['id']]);
        $plan = $stmt->fetch();

        if (!$plan) { Response::error('Plan no encontrado', 404); return; }

        // Lotes asociados a esta campaña y tipo
        $stmtL = $this->db->prepare("
            SELECT l.codigo, l.peso_inicial_kg, l.peso_actual_kg, l.estado,
                   c.razon_social AS productor
            FROM acopios l
            JOIN clientes c ON c.id = l.productor_id
            WHERE l.campaña = :campana AND l.tipo_cafe_id = :tipo_id
            ORDER BY l.fecha_acopio
        ");
        $stmtL->execute([':campana' => $plan['campaña'], ':tipo_id' => $plan['tipo_cafe_id']]);
        $plan['lotes'] = $stmtL->fetchAll();

        Response::json($plan);
    }

    // POST /produccion/plan-maestro
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO plan_maestro
                (campaña, tipo_cafe_id, cantidad_meta_kg, fecha_inicio, fecha_fin,
                 estado, responsable, notas)
            VALUES
                (:campana, :tipo_cafe_id, :meta_kg, :fecha_inicio, :fecha_fin,
                 :estado, :responsable, :notas)
        ");
        $stmt->execute([
            ':campana'      => $data['campaña'],
            ':tipo_cafe_id' => $data['tipo_cafe_id'],
            ':meta_kg'      => $data['cantidad_meta_kg'],
            ':fecha_inicio' => $data['fecha_inicio'],
            ':fecha_fin'    => $data['fecha_fin'],
            ':estado'       => $data['estado']      ?? 'borrador',
            ':responsable'  => $data['responsable'] ?? null,
            ':notas'        => $data['notas']        ?? null,
        ]);
        $this->show(['id' => Database::lastId($this->db, 'plan_maestro')]);
    }

    // PUT /produccion/plan-maestro/{id}
    public function update(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $stmt = $this->db->prepare("
            UPDATE plan_maestro SET
                cantidad_meta_kg  = :meta_kg,
                cantidad_real_kg  = :real_kg,
                fecha_inicio      = :fecha_inicio,
                fecha_fin         = :fecha_fin,
                estado            = :estado,
                responsable       = :responsable,
                notas             = :notas
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'          => $p['id'],
            ':meta_kg'     => $data['cantidad_meta_kg'],
            ':real_kg'     => $data['cantidad_real_kg'] ?? 0,
            ':fecha_inicio'=> $data['fecha_inicio'],
            ':fecha_fin'   => $data['fecha_fin'],
            ':estado'      => $data['estado']            ?? 'activo',
            ':responsable' => $data['responsable']       ?? null,
            ':notas'       => $data['notas']             ?? null,
        ]);
        $this->show($p);
    }

    // GET /produccion/plan-maestro/mrp?campana=2025
    // MRP básico: proyección de necesidades vs. stock disponible
    public function mrp(): void
    {
        $campana = $_GET['campana'] ?? date('Y');

        $stmt = $this->db->prepare("
            SELECT
                tc.nombre                                           AS tipo_cafe,
                pm.cantidad_meta_kg                                 AS meta_kg,
                pm.cantidad_real_kg                                 AS acopiado_kg,
                COALESCE(SUM(l.peso_actual_kg), 0)                 AS stock_disponible_kg,
                COALESCE(SUM(CASE WHEN v.estado NOT IN ('cancelado','borrador')
                               THEN v.cantidad_kg ELSE 0 END), 0)  AS comprometido_kg,
                pm.cantidad_meta_kg - pm.cantidad_real_kg           AS brecha_acopio_kg,
                pm.fecha_inicio,
                pm.fecha_fin,
                pm.estado
            FROM plan_maestro pm
            JOIN tipos_cafe tc ON tc.id = pm.tipo_cafe_id
            LEFT JOIN acopios l   ON l.tipo_cafe_id = pm.tipo_cafe_id
                                AND l.campaña     = pm.campaña
                                AND l.estado      NOT IN ('vendido')
            LEFT JOIN ventas v  ON v.acopio_id      = l.id
            WHERE pm.campaña = :campana
            GROUP BY pm.id, tc.id
        ");
        $stmt->execute([':campana' => $campana]);
        Response::json(['campana' => $campana, 'mrp' => $stmt->fetchAll()]);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['campaña']))          $errors[] = 'campaña es requerido';
        if (empty($d['tipo_cafe_id']))      $errors[] = 'tipo_cafe_id es requerido';
        if (empty($d['cantidad_meta_kg']) || $d['cantidad_meta_kg'] <= 0)
                                           $errors[] = 'cantidad_meta_kg debe ser > 0';
        if (empty($d['fecha_inicio']))      $errors[] = 'fecha_inicio es requerido';
        if (empty($d['fecha_fin']))         $errors[] = 'fecha_fin es requerido';
        return $errors;
    }
}
