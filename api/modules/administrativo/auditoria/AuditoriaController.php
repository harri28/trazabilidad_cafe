<?php
class AuditoriaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    private function generarCodigo(): string
    {
        $año  = date('Y');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM auditorias WHERE EXTRACT(YEAR FROM creado_en) = :anio"
        );
        $stmt->execute([':anio' => $año]);
        $n = (int)$stmt->fetchColumn() + 1;
        return "AUD-{$año}-" . str_pad($n, 4, '0', STR_PAD_LEFT);
    }

    // GET /auditorias
    public function index(): void
    {
        $tipo   = $_GET['tipo']   ?? null;
        $estado = $_GET['estado'] ?? null;
        $anio   = isset($_GET['año']) ? (int)$_GET['año'] : null;

        $where  = ['1=1'];
        $params = [];
        if ($tipo)   { $where[] = 'a.tipo = :tipo';     $params[':tipo']   = $tipo; }
        if ($estado) { $where[] = 'a.estado = :estado'; $params[':estado'] = $estado; }
        if ($anio)   { $where[] = 'a.campana = :anio';  $params[':anio']   = $anio; }

        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       (SELECT COUNT(*) FROM auditoria_hallazgos h
                        WHERE h.auditoria_id = a.id) AS total_hallazgos,
                       (SELECT COUNT(*) FROM auditoria_hallazgos h
                        WHERE h.auditoria_id = a.id AND h.estado = 'abierto') AS hallazgos_abiertos
                FROM auditorias a
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.fecha_auditoria DESC
                LIMIT 200
            ");
            $stmt->execute($params);
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    // POST /auditorias
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['titulo']))          { Response::error('El título es requerido'); return; }
        if (empty($data['fecha_auditoria'])) { Response::error('La fecha es requerida'); return; }

        $codigo = $this->generarCodigo();

        try {
            $stmt = $this->db->prepare("
                INSERT INTO auditorias
                    (codigo, tipo, titulo, descripcion, auditor, organismo,
                     fecha_auditoria, fecha_proxima, estado, resultado, puntaje, campana, notas)
                VALUES
                    (:codigo, :tipo, :titulo, :desc, :auditor, :org,
                     :fecha, :prox, :estado, :resultado, :puntaje, :campana, :notas)
            ");
            $stmt->execute([
                ':codigo'    => $codigo,
                ':tipo'      => $data['tipo']            ?? 'interna',
                ':titulo'    => $data['titulo'],
                ':desc'      => $data['descripcion']     ?? null,
                ':auditor'   => $data['auditor']         ?? null,
                ':org'       => $data['organismo']       ?? null,
                ':fecha'     => $data['fecha_auditoria'],
                ':prox'      => $data['fecha_proxima']   ?? null,
                ':estado'    => $data['estado']          ?? 'programada',
                ':resultado' => $data['resultado']       ?? null,
                ':puntaje'   => $data['puntaje']         ?? null,
                ':campana'   => $data['campaña']         ?? date('Y'),
                ':notas'     => $data['notas']           ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error al guardar: ' . $e->getMessage(), 500);
            return;
        }

        $id = Database::lastId($this->db, 'auditorias');
        $this->show(['id' => $id]);
    }

    // GET /auditorias/{id}
    public function show(array $p): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*,
                       (SELECT COUNT(*) FROM auditoria_hallazgos h
                        WHERE h.auditoria_id = a.id) AS total_hallazgos,
                       (SELECT COUNT(*) FROM auditoria_hallazgos h
                        WHERE h.auditoria_id = a.id AND h.estado = 'abierto') AS hallazgos_abiertos
                FROM auditorias a WHERE a.id = :id
            ");
            $stmt->execute([':id' => $p['id']]);
            $aud = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$aud) { Response::error('Auditoría no encontrada', 404); return; }

            $stmtH = $this->db->prepare(
                "SELECT * FROM auditoria_hallazgos WHERE auditoria_id = :id ORDER BY id"
            );
            $stmtH->execute([':id' => $p['id']]);
            $aud['hallazgos'] = $stmtH->fetchAll(PDO::FETCH_ASSOC);

            Response::json($aud);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    // PUT /auditorias/{id}
    public function update(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $stmt = $this->db->prepare("
                UPDATE auditorias SET
                    tipo            = COALESCE(:tipo,     tipo),
                    titulo          = COALESCE(:titulo,   titulo),
                    descripcion     = COALESCE(:desc,     descripcion),
                    auditor         = COALESCE(:auditor,  auditor),
                    organismo       = COALESCE(:org,      organismo),
                    fecha_auditoria = COALESCE(:fecha,    fecha_auditoria),
                    fecha_proxima   = COALESCE(:prox,     fecha_proxima),
                    estado          = COALESCE(:estado,   estado),
                    resultado       = COALESCE(:resultado,resultado),
                    puntaje         = COALESCE(:puntaje,  puntaje),
                    notas           = COALESCE(:notas,    notas)
                WHERE id = :id
            ");
            $stmt->execute([
                ':tipo'      => $data['tipo']            ?? null,
                ':titulo'    => $data['titulo']          ?? null,
                ':desc'      => $data['descripcion']     ?? null,
                ':auditor'   => $data['auditor']         ?? null,
                ':org'       => $data['organismo']       ?? null,
                ':fecha'     => $data['fecha_auditoria'] ?? null,
                ':prox'      => $data['fecha_proxima']   ?? null,
                ':estado'    => $data['estado']          ?? null,
                ':resultado' => $data['resultado']       ?? null,
                ':puntaje'   => $data['puntaje']         ?? null,
                ':notas'     => $data['notas']           ?? null,
                ':id'        => $p['id'],
            ]);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
            return;
        }
        $this->show($p);
    }

    // POST /auditorias/{id}/hallazgos
    public function addHallazgo(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data['descripcion'])) { Response::error('La descripción es requerida'); return; }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO auditoria_hallazgos
                    (auditoria_id, tipo, descripcion, area, responsable,
                     fecha_limite, estado, accion_correctiva, evidencia)
                VALUES
                    (:aud_id, :tipo, :desc, :area, :resp,
                     :limite, :estado, :accion, :evidencia)
            ");
            $stmt->execute([
                ':aud_id'    => $p['id'],
                ':tipo'      => $data['tipo']              ?? 'observacion',
                ':desc'      => $data['descripcion'],
                ':area'      => $data['area']              ?? null,
                ':resp'      => $data['responsable']       ?? null,
                ':limite'    => $data['fecha_limite']      ?? null,
                ':estado'    => $data['estado']            ?? 'abierto',
                ':accion'    => $data['accion_correctiva'] ?? null,
                ':evidencia' => $data['evidencia']         ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
            return;
        }
        $id = Database::lastId($this->db, 'auditoria_hallazgos');
        Response::json(['id' => $id, 'message' => 'Hallazgo registrado'], 201);
    }

    // PUT /auditorias/hallazgos/{id}
    public function updateHallazgo(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $stmt = $this->db->prepare("
                UPDATE auditoria_hallazgos SET
                    estado            = COALESCE(:estado,   estado),
                    accion_correctiva = COALESCE(:accion,   accion_correctiva),
                    fecha_cierre      = COALESCE(:cierre,   fecha_cierre),
                    responsable       = COALESCE(:resp,     responsable),
                    evidencia         = COALESCE(:evidencia,evidencia)
                WHERE id = :id
            ");
            $stmt->execute([
                ':estado'    => $data['estado']            ?? null,
                ':accion'    => $data['accion_correctiva'] ?? null,
                ':cierre'    => $data['fecha_cierre']      ?? null,
                ':resp'      => $data['responsable']       ?? null,
                ':evidencia' => $data['evidencia']         ?? null,
                ':id'        => $p['id'],
            ]);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
            return;
        }
        Response::json(['updated' => true]);
    }

    // GET /auditorias/estadisticas
    public function estadisticas(): void
    {
        try {
            $stats = $this->db->query("
                SELECT
                    COUNT(*)                                                  AS total,
                    COUNT(*) FILTER (WHERE estado = 'completada')            AS completadas,
                    COUNT(*) FILTER (WHERE resultado = 'aprobada')           AS aprobadas,
                    COUNT(*) FILTER (WHERE estado = 'programada')            AS programadas,
                    (SELECT COUNT(*) FROM auditoria_hallazgos
                     WHERE estado = 'abierto')                               AS hallazgos_abiertos,
                    (SELECT COUNT(*) FROM auditoria_hallazgos)               AS total_hallazgos
                FROM auditorias
            ")->fetch(PDO::FETCH_ASSOC);
            Response::json($stats);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    // GET /seguridad/log
    public function securityLog(): void
    {
        $modulo = $_GET['modulo'] ?? null;
        $limit  = min((int)($_GET['per_page'] ?? 50), 200);

        $where  = ['1=1'];
        $params = [];
        if ($modulo) { $where[] = 'modulo = :modulo'; $params[':modulo'] = $modulo; }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM seguridad_log WHERE " . implode(' AND ', $where) .
                " ORDER BY fecha DESC LIMIT {$limit}"
            );
            $stmt->execute($params);
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    // POST /seguridad/log
    public function addLog(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data['accion'])) { Response::error('La acción es requerida'); return; }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO seguridad_log (usuario, accion, modulo, detalle, ip_address)
                VALUES (:usuario, :accion, :modulo, :detalle, :ip)
            ");
            $stmt->execute([
                ':usuario' => $data['usuario'] ?? 'sistema',
                ':accion'  => $data['accion'],
                ':modulo'  => $data['modulo']  ?? null,
                ':detalle' => $data['detalle'] ?? null,
                ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
            return;
        }
        Response::json(['message' => 'Log registrado'], 201);
    }
}
