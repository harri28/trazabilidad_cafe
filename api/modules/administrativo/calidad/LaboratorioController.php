<?php

class LaboratorioController {
    private PDO $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // GET /laboratorio?lote_id=5&clasificacion=specialty
    public function index(): void {
        $where  = ['1=1'];
        $params = [];

        if (!empty($_GET['lote_id'])) {
            $where[] = 'la.lote_id = :lote_id';
            $params[':lote_id'] = $_GET['lote_id'];
        }
        if (!empty($_GET['clasificacion'])) {
            $where[] = 'la.clasificacion = :clasif';
            $params[':clasif'] = $_GET['clasificacion'];
        }
        if (!empty($_GET['aprobado'])) {
            $where[] = 'la.aprobado = :aprobado';
            $params[':aprobado'] = (int)$_GET['aprobado'];
        }
        if (!empty($_GET['desde'])) {
            $where[] = 'la.fecha_analisis >= :desde';
            $params[':desde'] = $_GET['desde'];
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT
                la.*,
                l.codigo        AS lote_codigo,
                l.variedad,
                l.proceso_beneficio,
                l.productor_id,
                c.razon_social  AS productor
            FROM laboratorio_analisis la
            JOIN lotes l    ON l.id = la.lote_id
            JOIN clientes c ON c.id = l.productor_id
            WHERE {$whereSQL}
            ORDER BY la.fecha_analisis DESC
        ");
        $stmt->execute($params);
        Response::json($stmt->fetchAll());
    }

    // GET /laboratorio/{id}
    public function show(array $params): void {
        $stmt = $this->db->prepare("
            SELECT la.*, l.codigo AS lote_codigo, l.variedad,
                   c.razon_social AS productor
            FROM laboratorio_analisis la
            JOIN lotes l    ON l.id = la.lote_id
            JOIN clientes c ON c.id = l.productor_id
            WHERE la.id = :id
        ");
        $stmt->execute([':id' => $params['id']]);
        $analisis = $stmt->fetch();

        if (!$analisis) { Response::error('Análisis no encontrado', 404); return; }
        Response::json($analisis);
    }

    // POST /laboratorio
    public function store(): void {
        $data   = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validate($data);
        if ($errors) { Response::error('Datos inválidos', 422, $errors); return; }

        // Regla de negocio: humedad óptima entre 10% y 14%
        $alertas = [];
        if (!empty($data['humedad_pct'])) {
            if ($data['humedad_pct'] < 10) $alertas[] = 'Humedad muy baja (<10%): riesgo de quiebre';
            if ($data['humedad_pct'] > 14) $alertas[] = 'Humedad alta (>14%): riesgo de hongos';
        }
        if (!empty($data['score_taza']) && $data['score_taza'] < 60) {
            $alertas[] = 'Score < 60: considerar descarte o renegociación';
        }

        $stmt = $this->db->prepare("
            INSERT INTO laboratorio_analisis
                (lote_id, fecha_analisis, analista, laboratorio,
                 humedad_pct, rendimiento_pct, densidad_gr_l,
                 defectos_cat1, defectos_cat2,
                 score_taza, fragancia, aroma, sabor, post_gusto,
                 acidez, cuerpo, uniformidad, balance, limpieza_taza,
                 dulzura, defecto_taza, notas_catacion, aprobado)
            VALUES
                (:lote_id, :fecha_analisis, :analista, :laboratorio,
                 :humedad_pct, :rendimiento_pct, :densidad_gr_l,
                 :defectos_cat1, :defectos_cat2,
                 :score_taza, :fragancia, :aroma, :sabor, :post_gusto,
                 :acidez, :cuerpo, :uniformidad, :balance, :limpieza_taza,
                 :dulzura, :defecto_taza, :notas_catacion, :aprobado)
        ");
        $stmt->execute([
            ':lote_id'        => $data['lote_id'],
            ':fecha_analisis' => $data['fecha_analisis'],
            ':analista'       => $data['analista']       ?? null,
            ':laboratorio'    => $data['laboratorio']    ?? 'Interno',
            ':humedad_pct'    => $data['humedad_pct']    ?? null,
            ':rendimiento_pct'=> $data['rendimiento_pct'] ?? null,
            ':densidad_gr_l'  => $data['densidad_gr_l']  ?? null,
            ':defectos_cat1'  => $data['defectos_cat1']  ?? 0,
            ':defectos_cat2'  => $data['defectos_cat2']  ?? 0,
            ':score_taza'     => $data['score_taza']     ?? null,
            ':fragancia'      => $data['fragancia']      ?? null,
            ':aroma'          => $data['aroma']          ?? null,
            ':sabor'          => $data['sabor']          ?? null,
            ':post_gusto'     => $data['post_gusto']     ?? null,
            ':acidez'         => $data['acidez']         ?? null,
            ':cuerpo'         => $data['cuerpo']         ?? null,
            ':uniformidad'    => $data['uniformidad']    ?? null,
            ':balance'        => $data['balance']        ?? null,
            ':limpieza_taza'  => $data['limpieza_taza']  ?? null,
            ':dulzura'        => $data['dulzura']        ?? null,
            ':defecto_taza'   => $data['defecto_taza']   ?? 0,
            ':notas_catacion' => $data['notas_catacion'] ?? null,
            ':aprobado'       => $data['aprobado']       ?? null,
        ]);

        $id = Database::lastId($this->db, 'laboratorio_analisis');
        $result = $stmt = $this->db->prepare("SELECT * FROM laboratorio_analisis WHERE id = :id");
        $result->execute([':id' => $id]);
        $analisis = $result->fetch();
        $analisis['alertas'] = $alertas;

        Response::json($analisis, 201);
    }

    // PUT /laboratorio/{id}
    public function update(array $params): void {
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $this->db->prepare("
            UPDATE laboratorio_analisis SET
                fecha_analisis = :fecha_analisis, analista = :analista,
                laboratorio = :laboratorio,
                humedad_pct = :humedad_pct, rendimiento_pct = :rendimiento_pct,
                densidad_gr_l = :densidad_gr_l,
                defectos_cat1 = :defectos_cat1, defectos_cat2 = :defectos_cat2,
                score_taza = :score_taza, fragancia = :fragancia, aroma = :aroma,
                sabor = :sabor, post_gusto = :post_gusto, acidez = :acidez,
                cuerpo = :cuerpo, uniformidad = :uniformidad, balance = :balance,
                limpieza_taza = :limpieza_taza, dulzura = :dulzura,
                defecto_taza = :defecto_taza, notas_catacion = :notas_catacion,
                aprobado = :aprobado
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'             => $params['id'],
            ':fecha_analisis' => $data['fecha_analisis'],
            ':analista'       => $data['analista']       ?? null,
            ':laboratorio'    => $data['laboratorio']    ?? 'Interno',
            ':humedad_pct'    => $data['humedad_pct']    ?? null,
            ':rendimiento_pct'=> $data['rendimiento_pct'] ?? null,
            ':densidad_gr_l'  => $data['densidad_gr_l']  ?? null,
            ':defectos_cat1'  => $data['defectos_cat1']  ?? 0,
            ':defectos_cat2'  => $data['defectos_cat2']  ?? 0,
            ':score_taza'     => $data['score_taza']     ?? null,
            ':fragancia'      => $data['fragancia']      ?? null,
            ':aroma'          => $data['aroma']          ?? null,
            ':sabor'          => $data['sabor']          ?? null,
            ':post_gusto'     => $data['post_gusto']     ?? null,
            ':acidez'         => $data['acidez']         ?? null,
            ':cuerpo'         => $data['cuerpo']         ?? null,
            ':uniformidad'    => $data['uniformidad']    ?? null,
            ':balance'        => $data['balance']        ?? null,
            ':limpieza_taza'  => $data['limpieza_taza']  ?? null,
            ':dulzura'        => $data['dulzura']        ?? null,
            ':defecto_taza'   => $data['defecto_taza']   ?? 0,
            ':notas_catacion' => $data['notas_catacion'] ?? null,
            ':aprobado'       => $data['aprobado']       ?? null,
        ]);
        $this->show($params);
    }

    // GET /laboratorio/estadisticas  — promedios por campaña, variedad, productor
    public function estadisticas(): void {
        $campana = $_GET['campana'] ?? date('Y');
        $stmt = $this->db->prepare("
            SELECT
                l.campaña,
                l.variedad,
                c.razon_social          AS productor,
                COUNT(la.id)            AS num_analisis,
                ROUND(AVG(la.score_taza), 2)       AS score_promedio,
                ROUND(AVG(la.humedad_pct), 2)      AS humedad_promedio,
                ROUND(AVG(la.rendimiento_pct), 2)  AS rendimiento_promedio,
                ROUND(MIN(la.score_taza), 2)       AS score_minimo,
                ROUND(MAX(la.score_taza), 2)       AS score_maximo,
                SUM(CASE WHEN la.clasificacion = 'specialty' THEN 1 ELSE 0 END) AS n_specialty,
                SUM(CASE WHEN la.clasificacion = 'premium'   THEN 1 ELSE 0 END) AS n_premium,
                SUM(CASE WHEN la.clasificacion = 'comercial' THEN 1 ELSE 0 END) AS n_comercial,
                SUM(CASE WHEN la.aprobado = false            THEN 1 ELSE 0 END) AS n_rechazados
            FROM laboratorio_analisis la
            JOIN lotes l    ON l.id = la.lote_id
            JOIN clientes c ON c.id = l.productor_id
            WHERE l.campaña = :campana
            GROUP BY l.variedad, l.productor_id
            ORDER BY score_promedio DESC
        ");
        $stmt->execute([':campana' => $campana]);
        Response::json($stmt->fetchAll());
    }

    private function validate(array $data): array {
        $errors = [];
        if (empty($data['lote_id']))       $errors[] = 'lote_id es requerido';
        if (empty($data['fecha_analisis'])) $errors[] = 'fecha_analisis es requerido';

        // Validar score SCA (0-100)
        if (isset($data['score_taza']) && ($data['score_taza'] < 0 || $data['score_taza'] > 100)) {
            $errors[] = 'score_taza debe estar entre 0 y 100';
        }
        // Validar humedad (0-30 razonable)
        if (isset($data['humedad_pct']) && ($data['humedad_pct'] < 0 || $data['humedad_pct'] > 30)) {
            $errors[] = 'humedad_pct fuera de rango (0-30)';
        }

        // Verificar que el lote exista
        if (!empty($data['lote_id'])) {
            $stmt = $this->db->prepare("SELECT id FROM lotes WHERE id = :id");
            $stmt->execute([':id' => $data['lote_id']]);
            if (!$stmt->fetch()) $errors[] = 'lote_id no existe';
        }
        return $errors;
    }
}
