<?php
/**
 * Módulo: Administrativo → Financiero → Centros de Costo
 * Agrupa gastos e ingresos por área para análisis de rentabilidad.
 */
class CentroCostoController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /financiero/centros-costo
    public function index(): void
    {
        $stmt = $this->db->query("
            SELECT cc.*,
                   padre.nombre AS padre_nombre,
                   (SELECT ROUND(SUM(al.debe - al.haber), 2)
                    FROM asiento_lineas al
                    WHERE al.centro_costo_id = cc.id)  AS saldo_contable,
                   (SELECT ROUND(SUM(fc.monto_pen), 2)
                    FROM flujo_caja fc
                    WHERE fc.centro_costo_id = cc.id
                      AND fc.tipo = 'egreso'
                      AND EXTRACT(YEAR FROM fc.fecha) = EXTRACT(YEAR FROM CURRENT_DATE)) AS gasto_anual
            FROM centros_costo cc
            LEFT JOIN centros_costo padre ON padre.id = cc.padre_id
            WHERE cc.activo = true
            ORDER BY cc.codigo
        ");
        Response::json($stmt->fetchAll());
    }

    // GET /financiero/centros-costo/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("SELECT * FROM centros_costo WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $cc = $stmt->fetch();
        if (!$cc) { Response::error('Centro de costo no encontrado', 404); return; }

        // Detalle de flujo de caja por mes en el año actual
        $stmtF = $this->db->prepare("
            SELECT TO_CHAR(fecha,'YYYY-MM') AS mes, tipo,
                   ROUND(SUM(monto_pen), 2) AS total
            FROM flujo_caja
            WHERE centro_costo_id = :id AND EXTRACT(YEAR FROM fecha) = EXTRACT(YEAR FROM CURRENT_DATE)
            GROUP BY mes, tipo
            ORDER BY mes
        ");
        $stmtF->execute([':id' => $p['id']]);
        $cc['flujo_mensual'] = $stmtF->fetchAll();

        Response::json($cc);
    }

    // POST /financiero/centros-costo
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = [];
        if (empty($data['codigo'])) $errors[] = 'codigo es requerido';
        if (empty($data['nombre'])) $errors[] = 'nombre es requerido';
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $stmt = $this->db->prepare("
            INSERT INTO centros_costo (codigo, nombre, descripcion, padre_id)
            VALUES (:cod, :nom, :desc, :padre)
        ");
        $stmt->execute([
            ':cod'   => $data['codigo'],
            ':nom'   => $data['nombre'],
            ':desc'  => $data['descripcion'] ?? null,
            ':padre' => $data['padre_id']    ?? null,
        ]);
        $this->show(['id' => Database::lastId($this->db, 'centros_costo')]);
    }

    // PUT /financiero/centros-costo/{id}
    public function update(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $this->db->prepare("
            UPDATE centros_costo SET
                codigo = :cod, nombre = :nom, descripcion = :desc, padre_id = :padre
            WHERE id = :id
        ")->execute([
            ':id'    => $p['id'],
            ':cod'   => $data['codigo'],
            ':nom'   => $data['nombre'],
            ':desc'  => $data['descripcion'] ?? null,
            ':padre' => $data['padre_id']    ?? null,
        ]);
        $this->show($p);
    }

    // GET /financiero/centros-costo/analisis?anio=2025
    // Rentabilidad por centro de costo: ingresos vs. egresos
    public function analisis(): void
    {
        $anio = (int)($_GET['anio'] ?? date('Y'));

        $stmt = $this->db->prepare("
            SELECT
                cc.codigo, cc.nombre,
                ROUND(SUM(CASE WHEN fc.tipo='ingreso' THEN fc.monto_pen ELSE 0 END), 2) AS total_ingresos,
                ROUND(SUM(CASE WHEN fc.tipo='egreso'  THEN fc.monto_pen ELSE 0 END), 2) AS total_egresos,
                ROUND(SUM(CASE WHEN fc.tipo='ingreso' THEN fc.monto_pen ELSE -fc.monto_pen END), 2) AS resultado
            FROM centros_costo cc
            LEFT JOIN flujo_caja fc ON fc.centro_costo_id = cc.id AND EXTRACT(YEAR FROM fc.fecha) = :anio
            WHERE cc.activo = true
            GROUP BY cc.id
            ORDER BY resultado DESC
        ");
        $stmt->execute([':anio' => $anio]);
        Response::json(['anio' => $anio, 'centros' => $stmt->fetchAll()]);
    }
}
