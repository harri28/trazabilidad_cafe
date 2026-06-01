<?php
/**
 * Módulo: Operativo → Ventas → Cotizaciones
 * Previas a una orden de venta. Una cotización aceptada puede convertirse en venta.
 */
class CotizacionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /ventas/cotizaciones?estado=enviada&comprador_id=1
    public function index(): void
    {
        $page   = max(1, (int)($_GET['page']     ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;
        $where  = ['1=1'];
        $params = [];

        foreach (['comprador_id' => ':comp', 'estado' => ':estado', 'acopio_id' => ':lote'] as $f => $b) {
            if (!empty($_GET[$f])) { $where[] = "cot.{$f} = {$b}"; $params[$b] = $_GET[$f]; }
        }

        $whereSQL = implode(' AND ', $where);

        $count = $this->db->prepare("SELECT COUNT(*) FROM cotizaciones cot WHERE {$whereSQL}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT cot.*,
                   c.razon_social AS comprador, c.pais_destino,
                   l.codigo AS acopio_codigo, l.variedad, l.peso_actual_kg AS stock_kg,
                   (cot.fecha_vencimiento - CURRENT_DATE) AS dias_para_vencer
            FROM cotizaciones cot
            JOIN clientes c ON c.id  = cot.comprador_id
            JOIN acopios l    ON l.id  = cot.acopio_id
            WHERE {$whereSQL}
            ORDER BY cot.fecha_cotizacion DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /ventas/cotizaciones/{id}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare("
            SELECT cot.*,
                   c.razon_social AS comprador, c.email AS email_comprador, c.pais_destino,
                   l.codigo AS acopio_codigo, l.variedad, l.proceso_beneficio,
                   l.peso_actual_kg AS stock_disponible,
                   la.score_taza, la.clasificacion
            FROM cotizaciones cot
            JOIN clientes c ON c.id  = cot.comprador_id
            JOIN acopios l    ON l.id  = cot.acopio_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE acopio_id = cot.acopio_id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE cot.id = :id
        ");
        $stmt->execute([':id' => $p['id']]);
        $cot = $stmt->fetch();
        if (!$cot) { Response::error('Cotización no encontrada', 404); return; }
        Response::json($cot);
    }

    // POST /ventas/cotizaciones
    public function store(): void
    {
        $data   = json_decode(file_get_contents('php://input'), true) ?? [];
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $numero = $this->generarNumero();

        $stmt = $this->db->prepare("
            INSERT INTO cotizaciones
                (numero, comprador_id, acopio_id, fecha_cotizacion, fecha_vencimiento,
                 cantidad_kg, precio_usd_kg, incoterm, condiciones, notas)
            VALUES
                (:num, :comp, :lote, :fecha, :venc,
                 :qty, :precio, :incoterm, :cond, :notas)
        ");
        $stmt->execute([
            ':num'     => $numero,
            ':comp'    => $data['comprador_id'],
            ':lote'    => $data['acopio_id'],
            ':fecha'   => $data['fecha_cotizacion'],
            ':venc'    => $data['fecha_vencimiento'],
            ':qty'     => $data['cantidad_kg'],
            ':precio'  => $data['precio_usd_kg'],
            ':incoterm'=> $data['incoterm']     ?? 'FOB',
            ':cond'    => $data['condiciones']  ?? null,
            ':notas'   => $data['notas']        ?? null,
        ]);
        $this->show(['id' => Database::lastId($this->db, 'cotizaciones')]);
    }

    // PUT /ventas/cotizaciones/{id}/enviar
    public function enviar(array $p): void
    {
        $this->cambiarEstado($p['id'], ['borrador'], 'enviada');
    }

    // PUT /ventas/cotizaciones/{id}/convertir
    // Convierte cotización aceptada en una Orden de Venta (borrador)
    public function convertir(array $p): void
    {
        $stmt = $this->db->prepare("SELECT * FROM cotizaciones WHERE id = :id");
        $stmt->execute([':id' => $p['id']]);
        $cot = $stmt->fetch();

        if (!$cot) { Response::error('Cotización no encontrada', 404); return; }
        if ($cot['estado'] !== 'enviada') {
            Response::error("Solo se pueden convertir cotizaciones en estado 'enviada'", 409);
            return;
        }
        if ($cot['venta_id']) {
            Response::error('Esta cotización ya fue convertida en venta #' . $cot['venta_id'], 409);
            return;
        }

        $this->db->beginTransaction();
        try {
            // Verificar stock
            $stmtS = $this->db->prepare("SELECT peso_actual_kg FROM acopios WHERE id = :id");
            $stmtS->execute([':id' => $cot['acopio_id']]);
            $lote = $stmtS->fetch();

            if ($lote['peso_actual_kg'] < $cot['cantidad_kg']) {
                Response::error('Stock insuficiente para convertir la cotización', 409);
                $this->db->rollBack();
                return;
            }

            // Crear venta en borrador
            $numVenta = 'CONT-' . date('Y') . '-' .
                str_pad((int)$this->db->query("SELECT COUNT(*)+1 FROM ventas")->fetchColumn(), 4, '0', STR_PAD_LEFT);

            $stmtV = $this->db->prepare("
                INSERT INTO ventas
                    (numero_contrato, comprador_id, acopio_id, fecha_contrato,
                     cantidad_kg, precio_usd_kg, incoterm, notas, usuario)
                VALUES
                    (:num, :comp, :lote, :fecha, :qty, :precio, :incoterm, :notas, 'sistema')
            ");
            $stmtV->execute([
                ':num'     => $numVenta,
                ':comp'    => $cot['comprador_id'],
                ':lote'    => $cot['acopio_id'],
                ':fecha'   => date('Y-m-d'),
                ':qty'     => $cot['cantidad_kg'],
                ':precio'  => $cot['precio_usd_kg'],
                ':incoterm'=> $cot['incoterm'],
                ':notas'   => 'Generado desde cotización ' . $cot['numero'],
            ]);
            $ventaId = Database::lastId($this->db, 'ventas');

            // Actualizar cotización
            $this->db->prepare("
                UPDATE cotizaciones SET estado = 'aceptada', venta_id = :vid WHERE id = :id
            ")->execute([':vid' => $ventaId, ':id' => $p['id']]);

            $this->db->commit();
            Response::json(['mensaje' => 'Cotización convertida', 'venta_id' => $ventaId, 'numero_venta' => $numVenta]);

        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::error('Error al convertir cotización: ' . $e->getMessage(), 500);
        }
    }

    // PUT /ventas/cotizaciones/{id}/rechazar
    public function rechazar(array $p): void
    {
        $this->cambiarEstado($p['id'], ['enviada', 'borrador'], 'rechazada');
    }

    private function cambiarEstado(int $id, array $estadosValidos, string $nuevo): void
    {
        $stmt = $this->db->prepare("SELECT estado FROM cotizaciones WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $cot = $stmt->fetch();

        if (!$cot) { Response::error('Cotización no encontrada', 404); return; }
        if (!in_array($cot['estado'], $estadosValidos)) {
            Response::error("Estado actual '{$cot['estado']}' no permite esta operación", 409);
            return;
        }

        $this->db->prepare("UPDATE cotizaciones SET estado = :e WHERE id = :id")
                 ->execute([':e' => $nuevo, ':id' => $id]);

        $this->show(['id' => $id]);
    }

    private function generarNumero(): string
    {
        $n = (int)$this->db->query("SELECT COUNT(*)+1 FROM cotizaciones")->fetchColumn();
        return 'COT-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $d): array
    {
        $errors = [];
        if (empty($d['comprador_id']))      $errors[] = 'comprador_id es requerido';
        if (empty($d['acopio_id']))           $errors[] = 'acopio_id es requerido';
        if (empty($d['fecha_cotizacion']))  $errors[] = 'fecha_cotizacion es requerido';
        if (empty($d['fecha_vencimiento'])) $errors[] = 'fecha_vencimiento es requerido';
        if (empty($d['cantidad_kg']) || $d['cantidad_kg'] <= 0)
            $errors[] = 'cantidad_kg debe ser > 0';
        if (empty($d['precio_usd_kg']) || $d['precio_usd_kg'] <= 0)
            $errors[] = 'precio_usd_kg debe ser > 0';
        return $errors;
    }
}
