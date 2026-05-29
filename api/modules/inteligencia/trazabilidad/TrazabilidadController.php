<?php
/**
 * TrazabilidadController
 * Reportes de trazabilidad completa: por lote, por productor, por cliente/destino.
 *
 * GET /trazabilidad/lote/{id}      → línea de tiempo completa de un lote
 * GET /trazabilidad/productor/{id} → todos los lotes de un productor con métricas
 * GET /trazabilidad/cliente/{id}   → todas las compras de un cliente con origen
 * GET /trazabilidad/resumen        → visión general filtrable por campaña
 */
class TrazabilidadController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // ── GET /trazabilidad/lote/{id} ──────────────────────────────────────────
    // Línea de tiempo completa: acopio → transformaciones → análisis → ventas
    public function lote(array $params): void {
        $id = (int)$params['id'];

        // Lote base con productor y tipo
        $stmt = $this->db->prepare("
            SELECT
                l.*,
                tc.nombre  AS tipo_cafe,
                c.razon_social  AS productor_nombre,
                c.ruc_dni       AS productor_ruc,
                c.departamento, c.provincia, c.distrito,
                c.altitud_msnm  AS productor_altitud_msnm,
                c.asociacion, c.hectareas,
                c.telefono      AS productor_telefono,
                c.email         AS productor_email
            FROM lotes l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            JOIN clientes   c  ON c.id  = l.productor_id
            WHERE l.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $lote = $stmt->fetch();

        if (!$lote) { Response::error('Lote no encontrado', 404); return; }

        // Certificaciones
        $stmtC = $this->db->prepare("
            SELECT cc.codigo, cc.nombre,
                   lc.fecha_inicio, lc.fecha_vencimiento, lc.numero_certificado
            FROM lote_certificaciones lc
            JOIN certificaciones_catalogo cc ON cc.id = lc.certificacion_id
            WHERE lc.lote_id = :id
            ORDER BY cc.nombre
        ");
        $stmtC->execute([':id' => $id]);
        $certificaciones = $stmtC->fetchAll();

        // Análisis de calidad (todos)
        $stmtA = $this->db->prepare("
            SELECT id, fecha_analisis, analista, laboratorio,
                   humedad_pct, rendimiento_pct, densidad_gr_l,
                   defectos_cat1, defectos_cat2,
                   score_taza, fragancia, aroma, sabor, post_gusto,
                   acidez, cuerpo, uniformidad, balance,
                   limpieza_taza, dulzura, defecto_taza,
                   clasificacion, aprobado, notas_catacion
            FROM laboratorio_analisis
            WHERE lote_id = :id
            ORDER BY fecha_analisis DESC
        ");
        $stmtA->execute([':id' => $id]);
        $analisis = $stmtA->fetchAll();

        // Transformaciones
        $stmtT = $this->db->prepare("
            SELECT id, tipo_transformacion, fecha,
                   peso_entrada_kg, peso_salida_kg, merma_kg, rendimiento_pct,
                   operador, maquinaria, notas
            FROM transformaciones
            WHERE lote_origen_id = :id
            ORDER BY fecha
        ");
        $stmtT->execute([':id' => $id]);
        $transformaciones = $stmtT->fetchAll();

        // Ventas / destinos
        $stmtV = $this->db->prepare("
            SELECT v.id, v.numero_contrato, v.estado, v.fecha_contrato, v.fecha_entrega,
                   v.cantidad_kg, v.precio_usd_kg, v.total_usd, v.total_local,
                   v.moneda_factura, v.incoterm, v.puerto_embarque,
                   v.humedad_max_pct, v.defectos_max, v.score_min,
                   c.razon_social AS comprador_nombre,
                   c.ruc_dni      AS comprador_ruc,
                   c.pais_destino, c.email AS comprador_email
            FROM ventas v
            JOIN clientes c ON c.id = v.comprador_id
            WHERE v.lote_id = :id AND v.estado != 'cancelado'
            ORDER BY v.fecha_contrato
        ");
        $stmtV->execute([':id' => $id]);
        $ventas = $stmtV->fetchAll();

        // Kardex completo
        $stmtK = $this->db->prepare("
            SELECT tipo_movimiento, concepto, fecha, cantidad_kg,
                   precio_unitario, moneda, tipo_cambio, total_monto,
                   prima_diferencial, prima_total, saldo_kg,
                   referencia_tipo, usuario, notas
            FROM kardex
            WHERE lote_id = :id
            ORDER BY creado_en
        ");
        $stmtK->execute([':id' => $id]);
        $kardex = $stmtK->fetchAll();

        // Construir línea de tiempo cronológica
        $timeline = [];

        // 1. Acopio (fecha inicial)
        $timeline[] = [
            'etapa'   => 'Acopio',
            'fecha'   => $lote['fecha_acopio'],
            'detalle' => "Ingreso de {$lote['peso_inicial_kg']} kg — Finca: " . ($lote['finca'] ?? 'N/A'),
        ];

        // 2. Transformaciones
        foreach ($transformaciones as $t) {
            $timeline[] = [
                'etapa'          => 'Transformación',
                'fecha'          => $t['fecha'],
                'tipo'           => $t['tipo_transformacion'],
                'peso_entrada_kg'=> (float)$t['peso_entrada_kg'],
                'peso_salida_kg' => (float)$t['peso_salida_kg'],
                'merma_kg'       => (float)$t['merma_kg'],
                'rendimiento_pct'=> (float)$t['rendimiento_pct'],
                'operador'       => $t['operador'],
            ];
        }

        // 3. Análisis de calidad
        foreach ($analisis as $a) {
            $timeline[] = [
                'etapa'          => 'Análisis de Calidad',
                'fecha'          => $a['fecha_analisis'],
                'analista'       => $a['analista'],
                'laboratorio'    => $a['laboratorio'],
                'score_taza'     => (float)$a['score_taza'],
                'clasificacion'  => $a['clasificacion'],
                'humedad_pct'    => (float)$a['humedad_pct'],
                'aprobado'       => (bool)$a['aprobado'],
            ];
        }

        // 4. Ventas
        foreach ($ventas as $v) {
            $timeline[] = [
                'etapa'          => 'Venta',
                'fecha'          => $v['fecha_contrato'],
                'numero_contrato'=> $v['numero_contrato'],
                'estado'         => $v['estado'],
                'comprador'      => $v['comprador_nombre'],
                'pais_destino'   => $v['pais_destino'],
                'cantidad_kg'    => (float)$v['cantidad_kg'],
                'precio_usd_kg'  => (float)$v['precio_usd_kg'],
                'total_usd'      => (float)$v['total_usd'],
            ];
        }

        // Ordenar timeline por fecha
        usort($timeline, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));

        // Separar datos del productor del lote
        $productor = [
            'id'            => $lote['productor_id'],
            'nombre'        => $lote['productor_nombre'],
            'ruc_dni'       => $lote['productor_ruc'],
            'departamento'  => $lote['departamento'],
            'provincia'     => $lote['provincia'],
            'distrito'      => $lote['distrito'],
            'altitud_msnm'  => $lote['productor_altitud_msnm'],
            'asociacion'    => $lote['asociacion'],
            'hectareas'     => $lote['hectareas'],
            'telefono'      => $lote['productor_telefono'],
            'email'         => $lote['productor_email'],
        ];

        // Limpiar claves de productor del objeto lote
        foreach (['productor_nombre','productor_ruc','departamento','provincia','distrito',
                  'productor_altitud_msnm','asociacion','hectareas','productor_telefono','productor_email'] as $k) {
            unset($lote[$k]);
        }

        Response::json([
            'lote'             => $lote,
            'productor'        => $productor,
            'linea_tiempo'     => $timeline,
            'certificaciones'  => $certificaciones,
            'analisis_calidad' => $analisis,
            'transformaciones' => $transformaciones,
            'ventas'           => $ventas,
            'kardex'           => $kardex,
        ]);
    }

    // ── GET /trazabilidad/productor/{id} ─────────────────────────────────────
    // Todos los lotes del productor con métricas agregadas por campaña
    public function productor(array $params): void {
        $id = (int)$params['id'];

        // Datos del productor
        $stmtP = $this->db->prepare("
            SELECT id, razon_social, ruc_dni, tipo, contacto, telefono, email,
                   direccion, departamento, provincia, distrito,
                   altitud_msnm, hectareas, asociacion, notas
            FROM clientes
            WHERE id = :id AND activo = true AND tipo IN ('productor','ambos')
        ");
        $stmtP->execute([':id' => $id]);
        $productor = $stmtP->fetch();

        if (!$productor) { Response::error('Productor no encontrado', 404); return; }

        // Filtros opcionales
        $campana = $_GET['campana'] ?? null;
        $whereExtra = $campana ? 'AND l.campaña = :campana' : '';

        // Lotes con análisis y ventas
        $stmtL = $this->db->prepare("
            SELECT
                l.id, l.codigo, l.estado, l.campaña, l.fecha_acopio,
                l.peso_inicial_kg, l.peso_actual_kg, l.merma_kg, l.rendimiento_pct,
                l.variedad, l.proceso_beneficio, l.finca, l.altitud_msnm, l.region,
                tc.nombre AS tipo_cafe,
                la.score_taza, la.clasificacion, la.humedad_pct, la.aprobado,
                (SELECT STRING_AGG(cc.codigo, ', ' ORDER BY cc.codigo)
                 FROM lote_certificaciones lc
                 JOIN certificaciones_catalogo cc ON cc.id = lc.certificacion_id
                 WHERE lc.lote_id = l.id) AS certificaciones,
                (SELECT COALESCE(SUM(v2.cantidad_kg), 0)
                 FROM ventas v2
                 WHERE v2.lote_id = l.id AND v2.estado NOT IN ('cancelado','borrador')) AS kg_vendido,
                (SELECT COALESCE(SUM(v2.total_usd), 0)
                 FROM ventas v2
                 WHERE v2.lote_id = l.id AND v2.estado NOT IN ('cancelado','borrador')) AS ingresos_usd,
                (SELECT COUNT(*)
                 FROM ventas v2
                 WHERE v2.lote_id = l.id AND v2.estado NOT IN ('cancelado','borrador')) AS ventas_count
            FROM lotes l
            JOIN tipos_cafe tc ON tc.id = l.tipo_cafe_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE l.productor_id = :id {$whereExtra}
            ORDER BY l.fecha_acopio DESC
        ");
        $execParams = [':id' => $id];
        if ($campana) $execParams[':campana'] = $campana;
        $stmtL->execute($execParams);
        $lotes = $stmtL->fetchAll();

        // Métricas resumen
        $stmtR = $this->db->prepare("
            SELECT
                COUNT(DISTINCT l.id)                AS total_lotes,
                COALESCE(SUM(l.peso_inicial_kg), 0) AS total_kg_acopiado,
                COALESCE(SUM(l.peso_actual_kg),  0) AS total_kg_disponible,
                COALESCE(AVG(la.score_taza), 0)     AS promedio_score_taza,
                COUNT(DISTINCT l.campaña)            AS total_campanas,
                STRING_AGG(DISTINCT l.campaña::text, ', ' ORDER BY l.campaña::text DESC) AS campanas
            FROM lotes l
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE l.productor_id = :id {$whereExtra}
        ");
        $stmtR->execute($execParams);
        $resumen = $stmtR->fetch();

        // Kg vendidos e ingresos totales
        $stmtVen = $this->db->prepare("
            SELECT
                COALESCE(SUM(v.cantidad_kg), 0) AS kg_vendido,
                COALESCE(SUM(v.total_usd),   0) AS ingresos_totales_usd,
                COUNT(DISTINCT v.comprador_id)   AS total_compradores
            FROM ventas v
            JOIN lotes l ON l.id = v.lote_id
            WHERE l.productor_id = :id
              AND v.estado NOT IN ('cancelado','borrador')
              {$whereExtra}
        ");
        $stmtVen->execute($execParams);
        $ventasRes = $stmtVen->fetch();

        // Compradores (destinos)
        $stmtDest = $this->db->prepare("
            SELECT DISTINCT
                c.id, c.razon_social, c.pais_destino,
                SUM(v.cantidad_kg) AS kg_total,
                SUM(v.total_usd)   AS usd_total
            FROM ventas v
            JOIN lotes   l ON l.id  = v.lote_id
            JOIN clientes c ON c.id = v.comprador_id
            WHERE l.productor_id = :id
              AND v.estado NOT IN ('cancelado','borrador')
              {$whereExtra}
            GROUP BY c.id, c.razon_social, c.pais_destino
            ORDER BY usd_total DESC
        ");
        $stmtDest->execute($execParams);
        $destinos = $stmtDest->fetchAll();

        Response::json([
            'productor' => $productor,
            'resumen'   => [
                'total_lotes'          => (int)$resumen['total_lotes'],
                'total_kg_acopiado'    => (float)$resumen['total_kg_acopiado'],
                'total_kg_disponible'  => (float)$resumen['total_kg_disponible'],
                'kg_vendido'           => (float)$ventasRes['kg_vendido'],
                'ingresos_totales_usd' => (float)$ventasRes['ingresos_totales_usd'],
                'total_compradores'    => (int)$ventasRes['total_compradores'],
                'promedio_score_taza'  => round((float)$resumen['promedio_score_taza'], 2),
                'campanas'             => $resumen['campanas'],
            ],
            'destinos'  => $destinos,
            'lotes'     => $lotes,
        ]);
    }

    // ── GET /trazabilidad/cliente/{id} ───────────────────────────────────────
    // Todas las compras de un cliente/comprador con origen (productor + lote + calidad)
    public function cliente(array $params): void {
        $id = (int)$params['id'];

        // Datos del cliente
        $stmtC = $this->db->prepare("
            SELECT id, razon_social, ruc_dni, tipo, contacto, telefono, email,
                   direccion, departamento, provincia, distrito,
                   pais_destino, moneda_pref, notas
            FROM clientes
            WHERE id = :id AND activo = true AND tipo IN ('comprador','ambos')
        ");
        $stmtC->execute([':id' => $id]);
        $cliente = $stmtC->fetch();

        if (!$cliente) { Response::error('Cliente/comprador no encontrado', 404); return; }

        // Filtros opcionales
        $campana = $_GET['campana'] ?? null;
        $estado  = $_GET['estado']  ?? null;
        $whereExtra = [];
        $execParams = [':id' => $id];
        if ($campana) { $whereExtra[] = 'l.campaña = :campana'; $execParams[':campana'] = $campana; }
        if ($estado)  { $whereExtra[] = 'v.estado = :estado';   $execParams[':estado']  = $estado; }
        $extraSQL = $whereExtra ? 'AND ' . implode(' AND ', $whereExtra) : '';

        // Compras con detalle completo de origen
        $stmtV = $this->db->prepare("
            SELECT
                v.id            AS venta_id,
                v.numero_contrato, v.estado, v.fecha_contrato, v.fecha_entrega,
                v.cantidad_kg, v.precio_usd_kg, v.total_usd, v.total_local,
                v.moneda_factura, v.incoterm, v.puerto_embarque,
                v.humedad_max_pct, v.defectos_max, v.score_min,
                l.id            AS lote_id,
                l.codigo        AS lote_codigo,
                l.campaña, l.variedad, l.proceso_beneficio,
                l.finca, l.altitud_msnm AS lote_altitud, l.region,
                l.peso_inicial_kg, l.peso_actual_kg,
                tc.nombre       AS tipo_cafe,
                prod.id         AS productor_id,
                prod.razon_social AS productor_nombre,
                prod.ruc_dni    AS productor_ruc,
                prod.departamento, prod.provincia, prod.asociacion,
                la.score_taza, la.clasificacion, la.humedad_pct, la.aprobado,
                la.fecha_analisis AS fecha_analisis,
                (SELECT STRING_AGG(cc.codigo, ', ' ORDER BY cc.codigo)
                 FROM lote_certificaciones lc
                 JOIN certificaciones_catalogo cc ON cc.id = lc.certificacion_id
                 WHERE lc.lote_id = l.id) AS certificaciones
            FROM ventas v
            JOIN lotes     l    ON l.id   = v.lote_id
            JOIN tipos_cafe tc  ON tc.id  = l.tipo_cafe_id
            JOIN clientes   prod ON prod.id = l.productor_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE v.comprador_id = :id {$extraSQL}
            ORDER BY v.fecha_contrato DESC
        ");
        $stmtV->execute($execParams);
        $compras = $stmtV->fetchAll();

        // Métricas resumen
        $stmtR = $this->db->prepare("
            SELECT
                COUNT(*)                              AS total_contratos,
                COALESCE(SUM(v.cantidad_kg),   0)    AS total_kg,
                COALESCE(SUM(v.total_usd),     0)    AS total_usd,
                COALESCE(AVG(v.precio_usd_kg), 0)    AS precio_promedio_usd_kg,
                COALESCE(AVG(la.score_taza),   0)    AS promedio_score_taza,
                COUNT(DISTINCT l.productor_id)        AS total_productores,
                STRING_AGG(DISTINCT l.campaña::text, ', ' ORDER BY l.campaña::text DESC) AS campanas
            FROM ventas v
            JOIN lotes l ON l.id = v.lote_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE v.comprador_id = :id
              AND v.estado NOT IN ('cancelado')
              {$extraSQL}
        ");
        $stmtR->execute($execParams);
        $resumen = $stmtR->fetch();

        // Productores (orígenes) agrupados
        $stmtProd = $this->db->prepare("
            SELECT
                prod.id, prod.razon_social, prod.departamento, prod.provincia,
                prod.asociacion,
                COUNT(DISTINCT l.id)        AS total_lotes,
                SUM(v.cantidad_kg)          AS kg_total,
                SUM(v.total_usd)            AS usd_total,
                AVG(la.score_taza)          AS promedio_score
            FROM ventas v
            JOIN lotes      l    ON l.id    = v.lote_id
            JOIN clientes   prod ON prod.id = l.productor_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            WHERE v.comprador_id = :id
              AND v.estado NOT IN ('cancelado')
              {$extraSQL}
            GROUP BY prod.id, prod.razon_social, prod.departamento, prod.provincia, prod.asociacion
            ORDER BY usd_total DESC
        ");
        $stmtProd->execute($execParams);
        $productores = $stmtProd->fetchAll();

        Response::json([
            'cliente'    => $cliente,
            'resumen'    => [
                'total_contratos'       => (int)$resumen['total_contratos'],
                'total_kg'              => (float)$resumen['total_kg'],
                'total_usd'             => (float)$resumen['total_usd'],
                'precio_promedio_usd_kg'=> round((float)$resumen['precio_promedio_usd_kg'], 4),
                'promedio_score_taza'   => round((float)$resumen['promedio_score_taza'], 2),
                'total_productores'     => (int)$resumen['total_productores'],
                'campanas'              => $resumen['campanas'],
            ],
            'productores_origen' => $productores,
            'compras'    => $compras,
        ]);
    }

    // ── GET /trazabilidad/resumen ─────────────────────────────────────────────
    // Visión general del flujo: acopio → proceso → ventas (filtrable por campaña)
    public function resumen(): void {
        $campana = $_GET['campana'] ?? null;
        $whereL = $campana ? 'WHERE l.campaña = :campana' : '';
        $whereV = $campana ? 'WHERE l.campaña = :campana AND v.estado != \'cancelado\'' : "WHERE v.estado != 'cancelado'";
        $params = $campana ? [':campana' => $campana] : [];

        // Lotes
        $stmtL = $this->db->prepare("
            SELECT
                COUNT(*)                                   AS total_lotes,
                COALESCE(SUM(l.peso_inicial_kg), 0)        AS total_kg_ingresado,
                COALESCE(SUM(l.peso_actual_kg),  0)        AS total_kg_disponible,
                COUNT(DISTINCT l.productor_id)              AS total_productores,
                COUNT(DISTINCT l.campaña)                   AS total_campanas,
                COALESCE(AVG(la.score_taza), 0)             AS promedio_score_taza,
                COUNT(CASE WHEN l.estado = 'disponible' THEN 1 END) AS lotes_disponibles,
                COUNT(CASE WHEN l.estado = 'vendido'    THEN 1 END) AS lotes_vendidos,
                COUNT(CASE WHEN l.estado = 'proceso'    THEN 1 END) AS lotes_en_proceso,
                COUNT(CASE WHEN l.estado = 'parcial'    THEN 1 END) AS lotes_parciales
            FROM lotes l
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            {$whereL}
        ");
        $stmtL->execute($params);
        $lotesRes = $stmtL->fetch();

        // Ventas
        $stmtV = $this->db->prepare("
            SELECT
                COUNT(*)                             AS total_contratos,
                COALESCE(SUM(v.cantidad_kg),   0)   AS total_kg_vendido,
                COALESCE(SUM(v.total_usd),     0)   AS total_ingresos_usd,
                COALESCE(SUM(v.total_local),   0)   AS total_ingresos_pen,
                COALESCE(AVG(v.precio_usd_kg), 0)   AS precio_promedio_usd_kg,
                COUNT(DISTINCT v.comprador_id)        AS total_compradores
            FROM ventas v
            JOIN lotes l ON l.id = v.lote_id
            {$whereV}
        ");
        $stmtV->execute($params);
        $ventasRes = $stmtV->fetch();

        // Por estado de venta
        $stmtEst = $this->db->prepare("
            SELECT v.estado, COUNT(*) AS total, SUM(v.cantidad_kg) AS kg, SUM(v.total_usd) AS usd
            FROM ventas v
            JOIN lotes l ON l.id = v.lote_id
            {$whereV}
            GROUP BY v.estado
        ");
        $stmtEst->execute($params);
        $porEstado = $stmtEst->fetchAll();

        // Top productores
        $stmtTop = $this->db->prepare("
            SELECT
                c.id, c.razon_social, c.departamento,
                COUNT(DISTINCT l.id)          AS total_lotes,
                SUM(l.peso_inicial_kg)         AS total_kg,
                ROUND(AVG(la.score_taza)::numeric, 2) AS score_promedio
            FROM lotes l
            JOIN clientes c ON c.id = l.productor_id
            LEFT JOIN laboratorio_analisis la ON la.id = (
                SELECT id FROM laboratorio_analisis
                WHERE lote_id = l.id ORDER BY fecha_analisis DESC LIMIT 1
            )
            {$whereL}
            GROUP BY c.id, c.razon_social, c.departamento
            ORDER BY total_kg DESC
            LIMIT 10
        ");
        $stmtTop->execute($params);
        $topProductores = $stmtTop->fetchAll();

        // Top destinos
        $stmtTopD = $this->db->prepare("
            SELECT
                c.id, c.razon_social, c.pais_destino,
                COUNT(DISTINCT v.id)     AS total_contratos,
                SUM(v.cantidad_kg)       AS kg_total,
                SUM(v.total_usd)         AS usd_total,
                SUM(v.total_local)       AS pen_total
            FROM ventas v
            JOIN lotes    l ON l.id  = v.lote_id
            JOIN clientes c ON c.id  = v.comprador_id
            {$whereV}
            GROUP BY c.id, c.razon_social, c.pais_destino
            ORDER BY usd_total DESC
            LIMIT 10
        ");
        $stmtTopD->execute($params);
        $topDestinos = $stmtTopD->fetchAll();

        // Distribución por clasificación de calidad
        $stmtCal = $this->db->prepare("
            SELECT la.clasificacion, COUNT(*) AS total, AVG(la.score_taza) AS score_promedio
            FROM laboratorio_analisis la
            JOIN lotes l ON l.id = la.lote_id
            {$whereL}
            GROUP BY la.clasificacion
            ORDER BY score_promedio DESC
        ");
        $stmtCal->execute($params);
        $calidadDist = $stmtCal->fetchAll();

        Response::json([
            'filtros'  => ['campana' => $campana],
            'lotes'    => [
                'total'              => (int)$lotesRes['total_lotes'],
                'total_kg_ingresado' => (float)$lotesRes['total_kg_ingresado'],
                'total_kg_disponible'=> (float)$lotesRes['total_kg_disponible'],
                'total_productores'  => (int)$lotesRes['total_productores'],
                'promedio_score_taza'=> round((float)$lotesRes['promedio_score_taza'], 2),
                'por_estado'         => [
                    'disponibles' => (int)$lotesRes['lotes_disponibles'],
                    'vendidos'    => (int)$lotesRes['lotes_vendidos'],
                    'en_proceso'  => (int)$lotesRes['lotes_en_proceso'],
                    'parciales'   => (int)$lotesRes['lotes_parciales'],
                ],
            ],
            'ventas'   => [
                'total_contratos'       => (int)$ventasRes['total_contratos'],
                'total_kg_vendido'      => (float)$ventasRes['total_kg_vendido'],
                'total_ingresos_usd'    => (float)$ventasRes['total_ingresos_usd'],
                'total_ingresos_pen'    => (float)$ventasRes['total_ingresos_pen'],
                'precio_promedio_usd_kg'=> round((float)$ventasRes['precio_promedio_usd_kg'], 4),
                'total_compradores'     => (int)$ventasRes['total_compradores'],
                'por_estado'            => $porEstado,
            ],
            'calidad_distribucion' => $calidadDist,
            'top_productores'      => $topProductores,
            'top_destinos'         => $topDestinos,
        ]);
    }
}
