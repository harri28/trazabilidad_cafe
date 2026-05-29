<?php
class LoteController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /lotes  — con filtros: estado, campaña, productor_id, certificacion
    public function index(): void {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(100, (int)($_GET['per_page'] ?? 20));
        $offset = ($page - 1) * $limit;

        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['estado'])) {
            $where[] = 'l.estado = :estado';
            $params[':estado'] = $_GET['estado'];
        }
        if (!empty($_GET['campana'])) {
            $where[] = 'l.campaña = :campana';
            $params[':campana'] = $_GET['campana'];
        }
        if (!empty($_GET['productor_id'])) {
            $where[] = 'l.productor_id = :prod_id';
            $params[':prod_id'] = $_GET['productor_id'];
        }
        if (!empty($_GET['certificacion'])) {
            $where[] = 'EXISTS (
                SELECT 1 FROM lote_certificaciones lc2
                JOIN certificaciones_catalogo cc ON cc.id = lc2.certificacion_id
                WHERE lc2.lote_id = l.id AND cc.codigo = :cert
            )';
            $params[':cert'] = $_GET['certificacion'];
        }

        $whereSQL = implode(' AND ', $where);

        $countSQL = "SELECT COUNT(*) FROM lotes l WHERE {$whereSQL}";
        $countStmt = $this->db->prepare($countSQL);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT
                l.id, l.codigo, l.estado, l.campaña, l.fecha_acopio,
                l.peso_inicial_kg, l.peso_actual_kg, l.merma_kg, l.rendimiento_pct,
                l.variedad, l.proceso_beneficio, l.finca, l.altitud_msnm,
                tc.nombre AS tipo_cafe,
                c.razon_social AS productor,
                c.ruc_dni AS productor_ruc,
                c.departamento, c.provincia,
                la.score_taza, la.clasificacion, la.humedad_pct,
                (SELECT STRING_AGG(cert2.codigo, ', ' ORDER BY cert2.codigo)
                 FROM lote_certificaciones lc2
                 JOIN certificaciones_catalogo cert2 ON cert2.id = lc2.certificacion_id
                 WHERE lc2.lote_id = l.id) AS certificaciones
            FROM lotes l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c    ON c.id  = l.productor_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE {$whereSQL}
            ORDER BY l.fecha_acopio DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        Response::paginated($stmt->fetchAll(), $total, $page, $limit);
    }

    // GET /lotes/{id}  — detalle completo con historial de kardex y análisis
    public function show(array $params): void {
        $stmt = $this->db->prepare("
            SELECT l.*, tc.nombre AS tipo_cafe,
                   c.razon_social AS productor, c.departamento, c.provincia,
                   c.asociacion, c.altitud_msnm AS productor_altitud
            FROM lotes l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes c    ON c.id  = l.productor_id
            WHERE l.id = :id
        ");
        $stmt->execute([':id' => $params['id']]);
        $lote = $stmt->fetch();

        if (!$lote) { Response::error('Lote no encontrado', 404); return; }

        // Certificaciones
        $stmt2 = $this->db->prepare("
            SELECT cc.codigo, cc.nombre, lc.fecha_inicio, lc.fecha_vencimiento, lc.numero_certificado
            FROM lote_certificaciones lc
            JOIN certificaciones_catalogo cc ON cc.id = lc.certificacion_id
            WHERE lc.lote_id = :id
        ");
        $stmt2->execute([':id' => $params['id']]);
        $lote['certificaciones'] = $stmt2->fetchAll();

        // Últimos análisis
        $stmt3 = $this->db->prepare("
            SELECT * FROM laboratorio_analisis WHERE lote_id = :id ORDER BY fecha_analisis DESC LIMIT 5
        ");
        $stmt3->execute([':id' => $params['id']]);
        $lote['analisis'] = $stmt3->fetchAll();

        // Ventas asociadas
        $stmt4 = $this->db->prepare("
            SELECT v.numero_contrato, v.estado, v.cantidad_kg, v.precio_usd_kg,
                   v.total_usd, v.fecha_contrato, c.razon_social AS comprador
            FROM ventas v
            JOIN clientes c ON c.id = v.comprador_id
            WHERE v.lote_id = :id AND v.estado != 'cancelado'
        ");
        $stmt4->execute([':id' => $params['id']]);
        $lote['ventas'] = $stmt4->fetchAll();

        // Kardex reciente
        $stmt5 = $this->db->prepare("
            SELECT tipo_movimiento, concepto, fecha, cantidad_kg, precio_unitario, total_monto, saldo_kg
            FROM kardex WHERE lote_id = :id ORDER BY creado_en DESC LIMIT 20
        ");
        $stmt5->execute([':id' => $params['id']]);
        $lote['kardex'] = $stmt5->fetchAll();

        Response::json($lote);
    }

    // POST /lotes
    public function store(): void {
        $data   = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        $codigo = $this->generarCodigo($data['campaña'] ?? date('Y'));

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO lotes
                    (codigo, tipo_cafe_id, productor_id, fecha_acopio, campaña,
                     peso_inicial_kg, peso_actual_kg, peso_bruto_kg,
                     sacos, humedad_entrada_pct, rend_entrada_pct,
                     region, finca, altitud_msnm,
                     variedad, proceso_beneficio, notas)
                VALUES
                    (:codigo, :tipo_cafe_id, :productor_id, :fecha_acopio, :campana,
                     :peso_inicial_kg, :peso_actual_kg, :peso_bruto_kg,
                     :sacos, :humedad_entrada_pct, :rend_entrada_pct,
                     :region, :finca, :altitud_msnm,
                     :variedad, :proceso_beneficio, :notas)
            ");
            $stmt->execute([
                ':codigo'              => $codigo,
                ':tipo_cafe_id'        => $data['tipo_cafe_id'],
                ':productor_id'        => $data['productor_id'],
                ':fecha_acopio'        => $data['fecha_acopio'],
                ':campana'             => $data['campaña'] ?? date('Y'),
                ':peso_inicial_kg'     => $data['peso_inicial_kg'],
                ':peso_actual_kg'      => 0, // el trigger trg_kardex_after_insert lo actualiza al insertar el kardex de entrada
                ':peso_bruto_kg'       => $data['peso_bruto_kg']        ?? null,
                ':sacos'               => $data['sacos']                ?? null,
                ':humedad_entrada_pct' => $data['humedad_entrada_pct']  ?? null,
                ':rend_entrada_pct'    => $data['rend_entrada_pct']     ?? null,
                ':region'              => $data['region']               ?? null,
                ':finca'               => $data['finca']                ?? null,
                ':altitud_msnm'        => $data['altitud_msnm']         ?? null,
                ':variedad'            => $data['variedad']             ?? null,
                ':proceso_beneficio'   => $data['proceso_beneficio']    ?? 'lavado',
                ':notas'               => $data['notas']                ?? null,
            ]);
            $loteId = Database::lastId($this->db, 'lotes');

            // Entrada automática en kardex
            $stmtK = $this->db->prepare("
                INSERT INTO kardex
                    (lote_id, tipo_movimiento, concepto, fecha, cantidad_kg,
                     precio_unitario, moneda, tipo_cambio, prima_diferencial, referencia_tipo, usuario)
                VALUES
                    (:lote_id, 'entrada', :concepto, :fecha, :cantidad_kg,
                     :precio, :moneda, :tc, :prima, 'compra', :usuario)
            ");
            $stmtK->execute([
                ':lote_id'    => $loteId,
                ':concepto'   => 'Compra inicial - ' . ($data['proveedor_detalle'] ?? 'Acopio'),
                ':fecha'      => $data['fecha_acopio'],
                ':cantidad_kg'=> $data['peso_inicial_kg'],
                ':precio'     => $data['precio_unitario']    ?? null,
                ':moneda'     => $data['moneda']             ?? 'PEN',
                ':tc'         => $data['tipo_cambio']        ?? 1,
                ':prima'      => $data['prima_diferencial']  ?? 0,
                ':usuario'    => $data['usuario']            ?? 'sistema',
            ]);

            // Certificaciones (si vienen en el payload)
            if (!empty($data['certificaciones'])) {
                $stmtC = $this->db->prepare("
                    INSERT INTO lote_certificaciones
                        (lote_id, certificacion_id, fecha_inicio, fecha_vencimiento, numero_certificado)
                    VALUES (:lote_id, :cert_id, :fi, :fv, :num)
                ");
                foreach ($data['certificaciones'] as $cert) {
                    $stmtC->execute([
                        ':lote_id' => $loteId,
                        ':cert_id' => $cert['certificacion_id'],
                        ':fi'      => $cert['fecha_inicio']      ?? null,
                        ':fv'      => $cert['fecha_vencimiento'] ?? null,
                        ':num'     => $cert['numero_certificado'] ?? null,
                    ]);
                }
            }

            $this->db->commit();
            $this->show(['id' => $loteId]);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('Error al crear el lote: ' . $e->getMessage(), 500);
        }
    }

    // PUT /lotes/{id}
    public function update(array $params): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $this->db->prepare("
            UPDATE lotes SET
                tipo_cafe_id = :tipo_cafe_id,
                fecha_acopio = :fecha_acopio,
                region = :region, finca = :finca,
                altitud_msnm = :altitud_msnm,
                variedad = :variedad,
                proceso_beneficio = :proceso_beneficio,
                notas = :notas
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'               => $params['id'],
            ':tipo_cafe_id'     => $data['tipo_cafe_id'],
            ':fecha_acopio'     => $data['fecha_acopio'],
            ':region'           => $data['region']            ?? null,
            ':finca'            => $data['finca']             ?? null,
            ':altitud_msnm'     => $data['altitud_msnm']      ?? null,
            ':variedad'         => $data['variedad']          ?? null,
            ':proceso_beneficio'=> $data['proceso_beneficio'] ?? 'lavado',
            ':notas'            => $data['notas']             ?? null,
        ]);

        $this->show($params);
    }

    // POST /lotes/{id}/certificaciones
    public function addCertificacion(array $params): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $this->db->prepare("
            INSERT INTO lote_certificaciones
                (lote_id, certificacion_id, fecha_inicio, fecha_vencimiento, numero_certificado)
            VALUES (:lote_id, :cert_id, :fi, :fv, :num)
            ON DUPLICATE KEY UPDATE
                fecha_inicio = VALUES(fecha_inicio),
                fecha_vencimiento = VALUES(fecha_vencimiento),
                numero_certificado = VALUES(numero_certificado)
        ");
        $stmt->execute([
            ':lote_id' => $params['id'],
            ':cert_id' => $data['certificacion_id'],
            ':fi'      => $data['fecha_inicio']      ?? null,
            ':fv'      => $data['fecha_vencimiento'] ?? null,
            ':num'     => $data['numero_certificado'] ?? null,
        ]);

        Response::json(['message' => 'Certificación registrada']);
    }

    // POST /lotes/{id}/transformar
    // Convierte el lote (ej: pergamino → oro), actualiza pesos y kardex
    public function transformar(array $params): void {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['peso_salida_kg']) || $data['peso_salida_kg'] <= 0) {
            Response::error('peso_salida_kg es requerido y debe ser mayor a 0', 422);
            return;
        }

        $lote = $this->db->prepare("SELECT * FROM lotes WHERE id = :id");
        $lote->execute([':id' => $params['id']]);
        $loteData = $lote->fetch();

        if (!$loteData) { Response::error('Lote no encontrado', 404); return; }
        if ($data['peso_salida_kg'] > $loteData['peso_actual_kg']) {
            Response::error('El peso de salida supera el stock disponible', 409);
            return;
        }

        $this->db->beginTransaction();
        try {
            // Registrar transformación
            $stmtT = $this->db->prepare("
                INSERT INTO transformaciones
                    (lote_origen_id, tipo_transformacion, fecha, peso_entrada_kg, peso_salida_kg, operador, notas)
                VALUES (:origen_id, :tipo, :fecha, :entrada, :salida, :op, :notas)
            ");
            $stmtT->execute([
                ':origen_id' => $params['id'],
                ':tipo'      => $data['tipo_transformacion'] ?? 'Despergaminado',
                ':fecha'     => $data['fecha'] ?? date('Y-m-d'),
                ':entrada'   => $loteData['peso_actual_kg'],
                ':salida'    => $data['peso_salida_kg'],
                ':op'        => $data['operador'] ?? null,
                ':notas'     => $data['notas']    ?? null,
            ]);
            $transId = Database::lastId($this->db, 'transformaciones');

            // Salida en kardex
            $merma = $loteData['peso_actual_kg'] - $data['peso_salida_kg'];
            $stmtK = $this->db->prepare("
                INSERT INTO kardex
                    (lote_id, tipo_movimiento, concepto, fecha, cantidad_kg,
                     referencia_id, referencia_tipo, usuario)
                VALUES (:lote_id, 'transformacion', :concepto, :fecha, :kg,
                        :ref_id, 'transformacion', :usuario)
            ");
            $stmtK->execute([
                ':lote_id'  => $params['id'],
                ':concepto' => 'Transformación: ' . ($data['tipo_transformacion'] ?? 'Despergaminado'),
                ':fecha'    => $data['fecha'] ?? date('Y-m-d'),
                ':kg'       => $loteData['peso_actual_kg'],
                ':ref_id'   => $transId,
                ':usuario'  => $data['usuario'] ?? 'sistema',
            ]);

            // Actualizar peso_final_kg del lote
            $stmtU = $this->db->prepare("
                UPDATE lotes
                SET peso_final_kg = :pf, estado = 'disponible'
                WHERE id = :id
            ");
            $stmtU->execute([':pf' => $data['peso_salida_kg'], ':id' => $params['id']]);

            // Re-entrada con el peso transformado
            $stmtRe = $this->db->prepare("
                INSERT INTO kardex
                    (lote_id, tipo_movimiento, concepto, fecha, cantidad_kg,
                     referencia_id, referencia_tipo, usuario)
                VALUES (:lote_id, 'entrada', :concepto, :fecha, :kg,
                        :ref_id, 'transformacion', :usuario)
            ");
            $stmtRe->execute([
                ':lote_id'  => $params['id'],
                ':concepto' => 'Entrada post-transformación (' . $data['tipo_transformacion'] . ')',
                ':fecha'    => $data['fecha'] ?? date('Y-m-d'),
                ':kg'       => $data['peso_salida_kg'],
                ':ref_id'   => $transId,
                ':usuario'  => $data['usuario'] ?? 'sistema',
            ]);

            $this->db->commit();
            Response::json([
                'transformacion_id' => $transId,
                'merma_kg'          => $merma,
                'peso_salida_kg'    => $data['peso_salida_kg'],
                'rendimiento_pct'   => round(($data['peso_salida_kg'] / $loteData['peso_actual_kg']) * 100, 2),
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error('Error en transformación: ' . $e->getMessage(), 500);
        }
    }

    private function generarCodigo(int $anio): string {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) + 1 AS siguiente FROM lotes WHERE campaña = :anio
        ");
        $stmt->execute([':anio' => $anio]);
        $n = (int)$stmt->fetchColumn();
        return "LOT-{$anio}-" . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    private function validate(array $data): array {
        $errors = [];
        if (empty($data['tipo_cafe_id']))    $errors[] = 'tipo_cafe_id es requerido';
        if (empty($data['productor_id']))    $errors[] = 'productor_id es requerido';
        if (empty($data['fecha_acopio']))    $errors[] = 'fecha_acopio es requerido';
        if (empty($data['peso_inicial_kg']) || $data['peso_inicial_kg'] <= 0)
                                            $errors[] = 'peso_inicial_kg debe ser mayor a 0';
        return $errors;
    }
}
