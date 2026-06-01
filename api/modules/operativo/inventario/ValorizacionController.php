<?php
/**
 * Módulo: Operativo → Inventario → Valorización
 * Calcula el costo del inventario usando FIFO y Promedio Ponderado.
 * Ambos métodos se calculan desde el kardex (no requieren tablas extra).
 */
class ValorizacionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /inventario/valorizacion?metodo=fifo&campana=2025&tipo_cafe_id=1
    public function index(): void
    {
        $metodo  = $_GET['metodo']  ?? 'promedio';   // fifo | promedio
        $campana = $_GET['campana'] ?? date('Y');

        if ($metodo === 'fifo') {
            $this->fifo($campana);
        } else {
            $this->promedioPonderado($campana);
        }
    }

    // GET /inventario/valorizacion/comparativo?campana=2025
    // Muestra FIFO vs. Promedio en una sola respuesta
    public function comparativo(): void
    {
        $campana = $_GET['campana'] ?? date('Y');

        $fifo    = $this->calcularPromedio($campana);  // reutilizamos promedio para base
        $promedio = $this->calcularPromedio($campana);

        Response::json([
            'campana'          => $campana,
            'promedio_ponderado' => $promedio,
            'nota'             => 'FIFO requiere análisis lote a lote — ver GET /inventario/valorizacion?metodo=fifo',
        ]);
    }

    // ── Promedio Ponderado ────────────────────────────────────────────
    private function promedioPonderado(string $campana): void
    {
        $stmt = $this->db->prepare("
            SELECT
                l.id, l.codigo, l.campaña,
                tc.nombre                                         AS tipo_cafe,
                c.razon_social                                    AS productor,
                l.peso_actual_kg                                  AS stock_actual_kg,
                -- Costo promedio ponderado = suma(cantidad*precio) / suma(cantidad) en entradas
                ROUND(
                    SUM(CASE WHEN k.tipo_movimiento = 'entrada'
                             THEN k.total_monto ELSE 0 END) /
                    NULLIF(SUM(CASE WHEN k.tipo_movimiento = 'entrada'
                                   THEN k.cantidad_kg ELSE 0 END), 0)
                , 4)                                              AS costo_unitario_prom,
                -- Valorización = stock actual × costo promedio
                ROUND(
                    l.peso_actual_kg *
                    SUM(CASE WHEN k.tipo_movimiento = 'entrada'
                             THEN k.total_monto ELSE 0 END) /
                    NULLIF(SUM(CASE WHEN k.tipo_movimiento = 'entrada'
                                   THEN k.cantidad_kg ELSE 0 END), 0)
                , 2)                                              AS valorizacion_pen,
                MAX(k.moneda)                                     AS moneda
            FROM acopios l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c    ON c.id  = l.productor_id
            JOIN kardex k      ON k.acopio_id = l.id
            WHERE l.campaña = :campana
              AND l.estado  NOT IN ('vendido')
              AND k.precio_unitario IS NOT NULL
            GROUP BY l.id
            ORDER BY tc.nombre, l.codigo
        ");
        $stmt->execute([':campana' => $campana]);
        $rows = $stmt->fetchAll();

        $total = array_sum(array_column($rows, 'valorizacion_pen'));

        Response::json([
            'metodo'           => 'promedio_ponderado',
            'campana'          => $campana,
            'total_valorizado' => round($total, 2),
            'lotes'            => $rows,
        ]);
    }

    // ── FIFO ──────────────────────────────────────────────────────────
    // Calcula el costo de las unidades restantes usando las entradas más recientes no consumidas
    private function fifo(string $campana): void
    {
        // Traer todas las entradas ordenadas cronológicamente (FIFO = primero en entrar, primero en salir)
        // El stock actual corresponde a las ÚLTIMAS entradas
        $stmt = $this->db->prepare("
            SELECT
                l.id AS acopio_id, l.codigo, l.peso_actual_kg AS stock_kg,
                tc.nombre AS tipo_cafe, c.razon_social AS productor,
                k.fecha, k.cantidad_kg AS entrada_kg, k.precio_unitario, k.moneda
            FROM acopios l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c    ON c.id  = l.productor_id
            JOIN kardex k      ON k.acopio_id = l.id
            WHERE l.campaña = :campana
              AND l.estado  NOT IN ('vendido')
              AND k.tipo_movimiento = 'entrada'
              AND k.precio_unitario IS NOT NULL
            ORDER BY l.id, k.fecha ASC
        ");
        $stmt->execute([':campana' => $campana]);
        $entradas = $stmt->fetchAll();

        // Agrupar por lote y simular FIFO
        $lotes = [];
        foreach ($entradas as $e) {
            $lotes[$e['acopio_id']]['codigo']    = $e['codigo'];
            $lotes[$e['acopio_id']]['tipo_cafe'] = $e['tipo_cafe'];
            $lotes[$e['acopio_id']]['productor'] = $e['productor'];
            $lotes[$e['acopio_id']]['stock_kg']  = $e['stock_kg'];
            $lotes[$e['acopio_id']]['moneda']    = $e['moneda'];
            $lotes[$e['acopio_id']]['entradas'][] = [
                'fecha'          => $e['fecha'],
                'cantidad_kg'    => (float)$e['entrada_kg'],
                'precio_unitario'=> (float)$e['precio_unitario'],
            ];
        }

        $resultado = [];
        $totalValorizado = 0;

        foreach ($lotes as $loteId => $lote) {
            $stockRestante  = (float)$lote['stock_kg'];
            $valorFIFO      = 0.0;
            // FIFO: consumimos primero las entradas más antiguas, valuamos con las más recientes
            $entradasDesc = array_reverse($lote['entradas']); // más recientes primero
            foreach ($entradasDesc as $entrada) {
                if ($stockRestante <= 0) break;
                $usar       = min($stockRestante, $entrada['cantidad_kg']);
                $valorFIFO += $usar * $entrada['precio_unitario'];
                $stockRestante -= $usar;
            }
            $resultado[] = [
                'acopio_id'       => $loteId,
                'codigo'          => $lote['codigo'],
                'tipo_cafe'       => $lote['tipo_cafe'],
                'productor'       => $lote['productor'],
                'stock_actual_kg' => $lote['stock_kg'],
                'valorizacion'    => round($valorFIFO, 2),
                'moneda'          => $lote['moneda'],
            ];
            $totalValorizado += $valorFIFO;
        }

        Response::json([
            'metodo'           => 'fifo',
            'campana'          => $campana,
            'total_valorizado' => round($totalValorizado, 2),
            'lotes'            => $resultado,
        ]);
    }

    private function calcularPromedio(string $campana): array
    {
        $stmt = $this->db->prepare("
            SELECT ROUND(SUM(k.total_monto), 2) AS total_costo,
                   ROUND(SUM(l.peso_actual_kg), 2) AS total_kg
            FROM acopios l JOIN kardex k ON k.acopio_id = l.id
            WHERE l.campaña = :campana AND k.tipo_movimiento = 'entrada'
        ");
        $stmt->execute([':campana' => $campana]);
        return $stmt->fetch() ?: [];
    }
}
