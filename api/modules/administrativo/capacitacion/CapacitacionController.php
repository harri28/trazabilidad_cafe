<?php
class CapacitacionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /capacitaciones
    public function index(): void
    {
        $estado = $_GET['estado'] ?? null;
        $anio   = isset($_GET['año']) ? (int)$_GET['año'] : null;

        $where  = ['1=1'];
        $params = [];
        if ($estado) { $where[] = 'c.estado = :estado'; $params[':estado'] = $estado; }
        if ($anio)   { $where[] = 'c.campana = :anio';  $params[':anio']   = $anio; }

        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       (SELECT COUNT(*) FROM capacitacion_participantes cp
                        WHERE cp.capacitacion_id = c.id) AS total_participantes
                FROM capacitaciones c
                WHERE " . implode(' AND ', $where) . "
                ORDER BY c.fecha_inicio DESC
                LIMIT 200
            ");
            $stmt->execute($params);
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {
            Response::error('Error al obtener capacitaciones: ' . $e->getMessage(), 500);
        }
    }

    // POST /capacitaciones
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data['titulo']))       { Response::error('El título es requerido'); return; }
        if (empty($data['fecha_inicio'])) { Response::error('La fecha de inicio es requerida'); return; }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO capacitaciones
                    (titulo, descripcion, instructor, organizacion, fecha_inicio, fecha_fin,
                     lugar, modalidad, estado, max_participantes, campana, notas)
                VALUES
                    (:titulo, :desc, :instructor, :org, :inicio, :fin,
                     :lugar, :modalidad, :estado, :max, :campana, :notas)
            ");
            $stmt->execute([
                ':titulo'      => $data['titulo'],
                ':desc'        => $data['descripcion']       ?? null,
                ':instructor'  => $data['instructor']        ?? null,
                ':org'         => $data['organizacion']      ?? null,
                ':inicio'      => $data['fecha_inicio'],
                ':fin'         => $data['fecha_fin']         ?? null,
                ':lugar'       => $data['lugar']             ?? null,
                ':modalidad'   => $data['modalidad']         ?? 'presencial',
                ':estado'      => $data['estado']            ?? 'programado',
                ':max'         => $data['max_participantes'] ?? null,
                ':campana'     => $data['campaña']           ?? date('Y'),
                ':notas'       => $data['notas']             ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error al guardar: ' . $e->getMessage(), 500);
            return;
        }

        $id = Database::lastId($this->db, 'capacitaciones');
        $this->show(['id' => $id]);
    }

    // GET /capacitaciones/{id}
    public function show(array $p): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       (SELECT COUNT(*) FROM capacitacion_participantes cp
                        WHERE cp.capacitacion_id = c.id) AS total_participantes
                FROM capacitaciones c WHERE c.id = :id
            ");
            $stmt->execute([':id' => $p['id']]);
            $cap = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cap) { Response::error('Capacitación no encontrada', 404); return; }

            $stmtP = $this->db->prepare("
                SELECT cp.*, cl.razon_social AS cliente_nombre, cl.ruc_dni
                FROM capacitacion_participantes cp
                LEFT JOIN clientes cl ON cl.id = cp.cliente_id
                WHERE cp.capacitacion_id = :id
                ORDER BY cp.id
            ");
            $stmtP->execute([':id' => $p['id']]);
            $cap['participantes'] = $stmtP->fetchAll(PDO::FETCH_ASSOC);

            Response::json($cap);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    // PUT /capacitaciones/{id}
    public function update(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $stmt = $this->db->prepare("
                UPDATE capacitaciones SET
                    titulo       = COALESCE(:titulo,    titulo),
                    descripcion  = COALESCE(:desc,      descripcion),
                    instructor   = COALESCE(:instructor,instructor),
                    organizacion = COALESCE(:org,       organizacion),
                    fecha_inicio = COALESCE(:inicio,    fecha_inicio),
                    fecha_fin    = COALESCE(:fin,       fecha_fin),
                    lugar        = COALESCE(:lugar,     lugar),
                    modalidad    = COALESCE(:modalidad, modalidad),
                    estado       = COALESCE(:estado,    estado),
                    notas        = COALESCE(:notas,     notas)
                WHERE id = :id
            ");
            $stmt->execute([
                ':titulo'     => $data['titulo']       ?? null,
                ':desc'       => $data['descripcion']  ?? null,
                ':instructor' => $data['instructor']   ?? null,
                ':org'        => $data['organizacion'] ?? null,
                ':inicio'     => $data['fecha_inicio'] ?? null,
                ':fin'        => $data['fecha_fin']    ?? null,
                ':lugar'      => $data['lugar']        ?? null,
                ':modalidad'  => $data['modalidad']    ?? null,
                ':estado'     => $data['estado']       ?? null,
                ':notas'      => $data['notas']        ?? null,
                ':id'         => $p['id'],
            ]);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
            return;
        }
        $this->show($p);
    }

    // GET /capacitaciones/{id}/participantes
    public function participantes(array $p): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cp.*, cl.razon_social AS cliente_nombre, cl.ruc_dni
                FROM capacitacion_participantes cp
                LEFT JOIN clientes cl ON cl.id = cp.cliente_id
                WHERE cp.capacitacion_id = :id
                ORDER BY cp.id
            ");
            $stmt->execute([':id' => $p['id']]);
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }

    // POST /capacitaciones/{id}/participantes
    public function addParticipante(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre = trim($data['nombre_participante'] ?? '');
        if (!$nombre && empty($data['cliente_id'])) {
            Response::error('Ingresa el nombre o selecciona un cliente');
            return;
        }
        try {
            $stmt = $this->db->prepare("
                INSERT INTO capacitacion_participantes
                    (capacitacion_id, cliente_id, nombre_participante, cargo,
                     asistio, certificado_emitido, notas)
                VALUES (:cap_id, :cli_id, :nombre, :cargo, :asistio, :cert, :notas)
            ");
            $stmt->execute([
                ':cap_id'  => $p['id'],
                ':cli_id'  => $data['cliente_id']           ?? null,
                ':nombre'  => $nombre ?: null,
                ':cargo'   => $data['cargo']                ?? null,
                ':asistio' => ($data['asistio'] ?? true)             ? 'true' : 'false',
                ':cert'    => ($data['certificado_emitido'] ?? false) ? 'true' : 'false',
                ':notas'   => $data['notas']                ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
            return;
        }
        $id = Database::lastId($this->db, 'capacitacion_participantes');
        Response::json(['id' => $id, 'message' => 'Participante agregado'], 201);
    }

    // GET /capacitaciones/estadisticas
    public function estadisticas(): void
    {
        try {
            $stats = $this->db->query("
                SELECT
                    COUNT(*)                                                     AS total,
                    COUNT(*) FILTER (WHERE estado = 'completado')               AS completadas,
                    COUNT(*) FILTER (WHERE estado = 'en_curso')                 AS en_curso,
                    COUNT(*) FILTER (WHERE estado = 'programado')               AS programadas,
                    (SELECT COUNT(*) FROM capacitacion_participantes)           AS total_participantes
                FROM capacitaciones
            ")->fetch(PDO::FETCH_ASSOC);
            Response::json($stats);
        } catch (\PDOException $e) {
            Response::error('Error: ' . $e->getMessage(), 500);
        }
    }
}
