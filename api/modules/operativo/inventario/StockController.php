<?php
/**
 * Módulo: Operativo → Inventario → Stock en tiempo real
 * Agrega el kardex para mostrar stock actual por lote, tipo de café y almacén.
 * Incluye gestión de series/lotes y alertas de stock mínimo.
 */
class StockController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /inventario/stock?tipo_cafe_id=1&estado=disponible&campana=2025
    public function index(): void
    {
        $where  = ["l.estado NOT IN ('vendido')"];
        $params = [];

        if (!empty($_GET['tipo_cafe_id'])) {
            $where[]              = 'l.tipo_cafe_id = :tipo';
            $params[':tipo']      = $_GET['tipo_cafe_id'];
        }
        if (!empty($_GET['estado'])) {
            $where[]              = 'l.estado = :estado';
            $params[':estado']    = $_GET['estado'];
        }
        if (!empty($_GET['campana'])) {
            $where[]              = 'l.campaña = :campana';
            $params[':campana']   = $_GET['campana'];
        }
        if (!empty($_GET['productor_id'])) {
            $where[]              = 'l.productor_id = :prod';
            $params[':prod']      = $_GET['productor_id'];
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                l.id, l.codigo, l.campaña, l.estado,
                tc.nombre                                           AS tipo_cafe,
                c.razon_social                                      AS productor,
                l.variedad, l.proceso_beneficio,
                l.peso_inicial_kg,
                l.peso_actual_kg                                    AS stock_actual_kg,
                -- Comprometido en ventas activas
                COALESCE((
                    SELECT SUM(v.cantidad_kg)
                    FROM ventas v
                    WHERE v.acopio_id = l.id
                      AND v.estado IN ('borrador','confirmado','en_proceso')
                ), 0)                                               AS comprometido_kg,
                -- Stock libre = actual - comprometido
                l.peso_actual_kg - COALESCE((
                    SELECT SUM(v.cantidad_kg)
                    FROM ventas v
                    WHERE v.acopio_id = l.id
                      AND v.estado IN ('borrador','confirmado','en_proceso')
                ), 0)                                               AS stock_libre_kg,
                la.score_taza, la.clasificacion,
                l.fecha_acopio
            FROM acopios l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c    ON c.id  = l.productor_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE acopio_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE {$whereSQL}
            ORDER BY tc.nombre, l.codigo
        ");
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    // GET /inventario/stock/resumen
    // Stock total agrupado por tipo de café y estado
    public function resumen(): void
    {
        $campana = $_GET['campana'] ?? date('Y');

        $stmt = $this->db->prepare("
            SELECT
                tc.nombre                   AS tipo_cafe,
                l.estado,
                COUNT(l.id)                 AS num_lotes,
                ROUND(SUM(l.peso_actual_kg), 2) AS stock_total_kg,
                ROUND(AVG(la.score_taza), 2)    AS score_promedio
            FROM acopios l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE acopio_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE l.campaña = :campana
            GROUP BY tc.id, l.estado
            ORDER BY tc.nombre, l.estado
        ");
        $stmt->execute([':campana' => $campana]);

        // Totales generales
        $totStmt = $this->db->prepare("
            SELECT ROUND(SUM(l.peso_actual_kg), 2) AS stock_total_kg,
                   COUNT(l.id)                     AS total_lotes
            FROM acopios l WHERE l.campaña = :campana AND l.estado != 'vendido'
        ");
        $totStmt->execute([':campana' => $campana]);

        Response::json([
            'campana'    => $campana,
            'totales'    => $totStmt->fetch(),
            'por_tipo'   => $stmt->fetchAll(),
        ]);
    }

    // GET /inventario/stock/{id}/movimientos
    // Historial completo de movimientos de un lote
    public function movimientos(array $p): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(200, (int)($_GET['per_page'] ?? 50));
        $offset = ($page - 1) * $limit;

        $count = $this->db->prepare("SELECT COUNT(*) FROM kardex WHERE acopio_id = :id");
        $count->execute([':id' => $p['id']]);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT k.id, k.tipo_movimiento, k.concepto, k.fecha,
                   k.cantidad_kg, k.precio_unitario, k.moneda, k.total_monto,
                   k.saldo_kg, k.referencia_tipo, k.usuario, k.creado_en
            FROM kardex k
            WHERE k.acopio_id = :id
            ORDER BY k.fecha DESC, k.id DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':id',     $p['id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit',  $limit,   PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /inventario/stock/alertas
    // Lotes con stock bajo (< umbral) o próximos a vencer en campaña
    public function alertas(): void
    {
        $umbral = (float)($_GET['umbral_kg'] ?? 500);

        $stmt = $this->db->prepare("
            SELECT l.codigo, l.estado, l.peso_actual_kg,
                   tc.nombre AS tipo_cafe, c.razon_social AS productor,
                   CASE
                       WHEN l.peso_actual_kg < :umbral THEN 'stock_bajo'
                       WHEN l.estado = 'acopio'        THEN 'pendiente_proceso'
                       ELSE 'normal'
                   END AS alerta
            FROM acopios l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c    ON c.id  = l.productor_id
            WHERE l.estado NOT IN ('vendido')
              AND (l.peso_actual_kg < :umbral2 OR l.estado = 'acopio')
            ORDER BY l.peso_actual_kg ASC
        ");
        $stmt->execute([':umbral' => $umbral, ':umbral2' => $umbral]);
        Response::json($stmt->fetchAll());
    }
}
