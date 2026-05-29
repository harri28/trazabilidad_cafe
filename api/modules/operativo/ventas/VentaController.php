<?php
class VentaController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /ventas?estado=confirmado&comprador_id=2
    public function index(): void {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;

        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['estado'])) {
            $where[] = 'v.estado = :estado';
            $params[':estado'] = $_GET['estado'];
        }
        if (!empty($_GET['comprador_id'])) {
            $where[] = 'v.comprador_id = :comp_id';
            $params[':comp_id'] = $_GET['comprador_id'];
        }
        if (!empty($_GET['lote_id'])) {
            $where[] = 'v.lote_id = :lote_id';
            $params[':lote_id'] = $_GET['lote_id'];
        }
        if (!empty($_GET['desde'])) {
            $where[] = 'v.fecha_contrato >= :desde';
            $params[':desde'] = $_GET['desde'];
        }
        if (!empty($_GET['sunat_estado'])) {
            $where[] = 'v.sunat_estado = :sunat_estado';
            $params[':sunat_estado'] = $_GET['sunat_estado'];
        }
        if (!empty($_GET['pendiente_factura'])) {
            $where[] = "v.sunat_documento_id IS NULL AND v.estado IN ('confirmado','en_proceso','entregado')";
        }

        $whereSQL = implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM ventas v WHERE {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT
                v.*,
                l.codigo        AS lote_codigo, l.variedad,
                comp.razon_social AS comprador,
                comp.pais_destino,
                comp.telefono   AS telefono_comprador,
                prod.razon_social AS productor
            FROM ventas v
            JOIN lotes l       ON l.id   = v.lote_id
            JOIN clientes comp ON comp.id = v.comprador_id
            JOIN clientes prod ON prod.id = l.productor_id
            WHERE {$whereSQL}
            ORDER BY v.fecha_contrato DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $val) $stmt->bindValue($k, $val);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /ventas/{id}
    public function show(array $params): void {
        $stmt = $this->db->prepare("
            SELECT
                v.*,
                l.codigo AS lote_codigo, l.variedad, l.proceso_beneficio,
                l.peso_actual_kg AS stock_disponible_lote,
                comp.razon_social AS comprador, comp.pais_destino, comp.email AS email_comprador,
                prod.razon_social AS productor,
                -- Último análisis del lote
                la.score_taza, la.clasificacion, la.humedad_pct
            FROM ventas v
            JOIN lotes l       ON l.id   = v.lote_id
            JOIN clientes comp ON comp.id = v.comprador_id
            JOIN clientes prod ON prod.id = l.productor_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = v.lote_id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE v.id = :id
        ");
        $stmt->execute([':id' => $params['id']]);
        $venta = $stmt->fetch();

        if (!$venta) { Response::error('Venta no encontrada', 404); return; }
        Response::json($venta);
    }

    // POST /ventas  — crear contrato en borrador
    public function store(): void {
        $data   = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        // Regla de negocio: validar stock disponible
        $stockCheck = $this->db->prepare("
            SELECT peso_actual_kg,
                   peso_actual_kg - COALESCE((
                       SELECT SUM(cantidad_kg) FROM ventas
                       WHERE lote_id = :lote_id
                         AND estado IN ('borrador','confirmado','en_proceso')
                   ), 0) AS stock_libre
            FROM lotes WHERE id = :lote_id2
        ");
        $stockCheck->execute([':lote_id' => $data['lote_id'], ':lote_id2' => $data['lote_id']]);
        $stock = $stockCheck->fetch();

        if (!$stock || $stock['stock_libre'] < $data['cantidad_kg']) {
            Response::error(
                'Stock insuficiente. Disponible: ' . ($stock['stock_libre'] ?? 0) . ' kg',
                409
            );
            return;
        }

        $numero = $this->generarNumeroContrato();

        $stmt = $this->db->prepare("
            INSERT INTO ventas
                (numero_contrato, comprador_id, lote_id, fecha_contrato, fecha_entrega,
                 cantidad_kg, precio_usd_kg, tipo_cambio, moneda_factura,
                 incoterm, puerto_embarque,
                 humedad_max_pct, defectos_max, score_min, notas, usuario)
            VALUES
                (:numero, :comprador_id, :lote_id, :fecha_contrato, :fecha_entrega,
                 :cantidad_kg, :precio_usd_kg, :tipo_cambio, :moneda_factura,
                 :incoterm, :puerto_embarque,
                 :humedad_max_pct, :defectos_max, :score_min, :notas, :usuario)
        ");
        $stmt->execute([
            ':numero'          => $numero,
            ':comprador_id'    => $data['comprador_id'],
            ':lote_id'         => $data['lote_id'],
            ':fecha_contrato'  => $data['fecha_contrato'],
            ':fecha_entrega'   => $data['fecha_entrega']   ?? null,
            ':cantidad_kg'     => $data['cantidad_kg'],
            ':precio_usd_kg'   => $data['precio_usd_kg'],
            ':tipo_cambio'     => $data['tipo_cambio']     ?? 1.0,
            ':moneda_factura'  => $data['moneda_factura']  ?? 'USD',
            ':incoterm'        => $data['incoterm']        ?? 'FOB',
            ':puerto_embarque' => $data['puerto_embarque'] ?? null,
            ':humedad_max_pct' => $data['humedad_max_pct'] ?? null,
            ':defectos_max'    => $data['defectos_max']    ?? null,
            ':score_min'       => $data['score_min']       ?? null,
            ':notas'           => $data['notas']           ?? null,
            ':usuario'         => $data['usuario']         ?? 'sistema',
        ]);

        $id = Database::lastId($this->db, 'ventas');
        $this->show(['id' => $id]);
    }

    // PUT /ventas/{id}/confirmar  — confirmar contrato y descontar stock
    public function confirmar(array $params): void {
        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);
        $venta = $stmt->fetch();

        if (!$venta) { Response::error('Venta no encontrada', 404); return; }
        if ($venta['estado'] !== 'borrador') {
            Response::error("Solo se pueden confirmar ventas en estado 'borrador'. Estado actual: {$venta['estado']}", 409);
            return;
        }

        $this->db->beginTransaction();
        try {
            // Cambiar estado
            $stmtU = $this->db->prepare("UPDATE ventas SET estado = 'confirmado' WHERE id = :id");
            $stmtU->execute([':id' => $params['id']]);

            // Salida de inventario en kardex
            $stmtK = $this->db->prepare("
                INSERT INTO kardex
                    (lote_id, tipo_movimiento, concepto, fecha, cantidad_kg,
                     precio_unitario, moneda, tipo_cambio, referencia_id, referencia_tipo, usuario)
                VALUES
                    (:lote_id, 'salida', :concepto, :fecha, :kg,
                     :precio, 'USD', :tc, :ref_id, 'venta', :usuario)
            ");
            $stmtK->execute([
                ':lote_id'  => $venta['lote_id'],
                ':concepto' => 'Venta - Contrato ' . $venta['numero_contrato'],
                ':fecha'    => date('Y-m-d'),
                ':kg'       => $venta['cantidad_kg'],
                ':precio'   => $venta['precio_usd_kg'],
                ':tc'       => $venta['tipo_cambio'],
                ':ref_id'   => $venta['id'],
                ':usuario'  => 'sistema',
            ]);

            $this->db->commit();

            // Notificación email al comprador (no bloquea si falla)
            $stmtEmail = $this->db->prepare("
                SELECT comp.email, comp.razon_social AS comprador_nombre,
                       l.codigo AS lote_codigo, l.variedad
                FROM ventas v
                JOIN clientes comp ON comp.id = v.comprador_id
                JOIN lotes    l    ON l.id    = v.lote_id
                WHERE v.id = :id
            ");
            $stmtEmail->execute([':id' => $params['id']]);
            $emailData = $stmtEmail->fetch();

            if ($emailData && !empty($emailData['email'])) {
                try {
                    (new MailService())->sendVentaConfirmada(
                        $emailData['email'],
                        $emailData['comprador_nombre'],
                        [
                            'numero_contrato' => $venta['numero_contrato'],
                            'lote_codigo'     => $emailData['lote_codigo'],
                            'variedad'        => $emailData['variedad'],
                            'cantidad_kg'     => $venta['cantidad_kg'],
                            'precio_usd_kg'   => $venta['precio_usd_kg'],
                            'total_usd'       => $venta['total_usd'],
                            'fecha_entrega'   => $venta['fecha_entrega'],
                            'incoterm'        => $venta['incoterm'],
                        ]
                    );
                } catch (\Throwable $e) {
                    error_log('[VentaController] Email no enviado: ' . $e->getMessage());
                }
            }

            $this->show($params);

        } catch (PDOException $e) {
            $this->db->rollBack();
            if (str_contains($e->getMessage(), 'Stock insuficiente')) {
                Response::error('Stock insuficiente para confirmar la venta', 409);
            } else {
                Response::error('Error al confirmar: ' . $e->getMessage(), 500);
            }
        }
    }

    // PUT /ventas/{id}/cancelar
    public function cancelar(array $params): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);
        $venta = $stmt->fetch();

        if (!$venta) { Response::error('Venta no encontrada', 404); return; }
        if (in_array($venta['estado'], ['entregado','cancelado'])) {
            Response::error("No se puede cancelar una venta en estado '{$venta['estado']}'", 409);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmtU = $this->db->prepare("UPDATE ventas SET estado = 'cancelado' WHERE id = :id");
            $stmtU->execute([':id' => $params['id']]);

            // Si ya había salida en kardex, revertirla con una entrada
            if ($venta['estado'] === 'confirmado') {
                $stmtK = $this->db->prepare("
                    INSERT INTO kardex
                        (lote_id, tipo_movimiento, concepto, fecha, cantidad_kg,
                         referencia_id, referencia_tipo, usuario)
                    VALUES (:lote_id, 'entrada', :concepto, :fecha, :kg, :ref_id, 'cancelacion', 'sistema')
                ");
                $stmtK->execute([
                    ':lote_id'  => $venta['lote_id'],
                    ':concepto' => 'Reversión por cancelación - Contrato ' . $venta['numero_contrato'],
                    ':fecha'    => date('Y-m-d'),
                    ':kg'       => $venta['cantidad_kg'],
                    ':ref_id'   => $venta['id'],
                ]);
            }

            $this->db->commit();
            Response::json(['message' => 'Venta cancelada. Razón: ' . ($data['razon'] ?? 'No especificada')]);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('Error al cancelar: ' . $e->getMessage(), 500);
        }
    }

    // PUT /ventas/{id}/en_proceso
    public function enProceso(array $params): void {
        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);
        $venta = $stmt->fetch();

        if (!$venta) { Response::error('Venta no encontrada', 404); return; }
        if ($venta['estado'] !== 'confirmado') {
            Response::error("Solo se pueden procesar ventas confirmadas. Estado actual: {$venta['estado']}", 409);
            return;
        }

        $stmt = $this->db->prepare("UPDATE ventas SET estado = 'en_proceso', actualizado_en = NOW() WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);

        $this->show($params);
    }

    // PUT /ventas/{id}/entregar
    public function entregar(array $params): void {
        $stmt = $this->db->prepare("SELECT * FROM ventas WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);
        $venta = $stmt->fetch();

        if (!$venta) { Response::error('Venta no encontrada', 404); return; }
        if ($venta['estado'] !== 'en_proceso') {
            Response::error("Solo se pueden entregar ventas en estado 'en_proceso'. Estado actual: {$venta['estado']}", 409);
            return;
        }

        $stmt = $this->db->prepare("UPDATE ventas SET estado = 'entregado', actualizado_en = NOW() WHERE id = :id");
        $stmt->execute([':id' => $params['id']]);

        $this->show($params);
    }

    // GET /ventas/dashboard  — métricas generales
    public function dashboard(): void {
        $campana = $_GET['campana'] ?? date('Y');

        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                                   AS total_contratos,
                SUM(CASE WHEN estado = 'confirmado'   THEN 1 ELSE 0 END) AS confirmados,
                SUM(CASE WHEN estado = 'entregado'    THEN 1 ELSE 0 END) AS entregados,
                SUM(CASE WHEN estado = 'en_proceso'   THEN 1 ELSE 0 END) AS en_proceso,
                ROUND(SUM(CASE WHEN estado != 'cancelado' THEN cantidad_kg  ELSE 0 END), 2) AS kg_totales,
                ROUND(SUM(CASE WHEN estado != 'cancelado' THEN total_usd    ELSE 0 END), 2) AS usd_totales,
                ROUND(AVG(CASE WHEN estado != 'cancelado' THEN precio_usd_kg ELSE NULL END), 4) AS precio_promedio_usd
            FROM ventas v
            JOIN lotes l ON l.id = v.lote_id
            WHERE l.campaña = :campana
        ");
        $stmt->execute([':campana' => $campana]);
        $resumen = $stmt->fetch();

        // Top compradores
        $stmtTop = $this->db->prepare("
            SELECT c.razon_social, c.pais_destino,
                   COUNT(v.id)   AS num_contratos,
                   SUM(v.cantidad_kg) AS kg_comprados,
                   SUM(v.total_usd)   AS usd_total
            FROM ventas v
            JOIN clientes c ON c.id = v.comprador_id
            JOIN lotes l    ON l.id  = v.lote_id
            WHERE v.estado != 'cancelado' AND l.campaña = :campana
            GROUP BY v.comprador_id
            ORDER BY usd_total DESC
            LIMIT 10
        ");
        $stmtTop->execute([':campana' => $campana]);

        // Métricas de facturación SUNAT
        $stmtSunat = $this->db->prepare("
            SELECT
                COUNT(*) FILTER (WHERE v.sunat_documento_id IS NOT NULL)                              AS facturadas,
                COUNT(*) FILTER (WHERE v.sunat_documento_id IS NULL
                                   AND v.estado IN ('confirmado','en_proceso','entregado'))            AS pendientes_factura,
                COUNT(*) FILTER (WHERE v.sunat_estado = 'aceptado')                                   AS aceptadas_sunat,
                COUNT(*) FILTER (WHERE v.sunat_estado IN ('rechazado','observado'))                    AS con_problemas_sunat,
                COALESCE(SUM(v.total_usd) FILTER (WHERE v.sunat_estado = 'aceptado'), 0)              AS usd_facturado_aceptado
            FROM ventas v
            JOIN lotes l ON l.id = v.lote_id
            WHERE v.estado != 'cancelado' AND l.campaña = :campana
        ");
        $stmtSunat->execute([':campana' => $campana]);

        Response::json([
            'campana'          => $campana,
            'resumen'          => $resumen,
            'top_compradores'  => $stmtTop->fetchAll(),
            'sunat'            => $stmtSunat->fetch(),
        ]);
    }

    private function generarNumeroContrato(): string {
        $stmt = $this->db->query("SELECT COUNT(*) + 1 FROM ventas");
        $n = (int)$stmt->fetchColumn();
        return 'CONT-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $data): array {
        $errors = [];
        if (empty($data['comprador_id']))  $errors[] = 'comprador_id es requerido';
        if (empty($data['lote_id']))       $errors[] = 'lote_id es requerido';
        if (empty($data['fecha_contrato'])) $errors[] = 'fecha_contrato es requerido';
        if (empty($data['cantidad_kg']) || $data['cantidad_kg'] <= 0)
                                           $errors[] = 'cantidad_kg debe ser > 0';
        if (empty($data['precio_usd_kg']) || $data['precio_usd_kg'] <= 0)
                                           $errors[] = 'precio_usd_kg debe ser > 0';

        // Verificar que el comprador exista y sea comprador
        if (!empty($data['comprador_id'])) {
            $stmt = $this->db->prepare("SELECT tipo FROM clientes WHERE id = :id AND activo = true");
            $stmt->execute([':id' => $data['comprador_id']]);
            $c = $stmt->fetch();
            if (!$c) $errors[] = 'comprador_id no existe';
            elseif (!in_array($c['tipo'], ['comprador', 'ambos']))
                $errors[] = 'El cliente no está registrado como comprador';
        }

        // Verificar que el lote exista y tenga stock
        if (!empty($data['lote_id'])) {
            $stmt = $this->db->prepare("SELECT estado FROM lotes WHERE id = :id");
            $stmt->execute([':id' => $data['lote_id']]);
            $l = $stmt->fetch();
            if (!$l) $errors[] = 'lote_id no existe';
            elseif ($l['estado'] === 'vendido') $errors[] = 'El lote ya fue vendido completamente';
        }

        return $errors;
    }
}
