<?php
/**
 * Módulo: Administrativo → Campañas y Backups
 * Gestión de campañas anuales y registro de backups.
 */
class CampanaController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /campanas
    public function index(): void
    {
        try {
            $stmt = $this->db->query(
                "SELECT año, fecha_inicio, fecha_fin, estado, notas, creado_en
                 FROM campanas ORDER BY año DESC"
            );
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {
            Response::error('Error al obtener campañas: ' . $e->getMessage(), 500);
        }
    }

    // POST /campanas
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $anio = (int)($data['año'] ?? 0);

        if (!$anio || $anio < 2000 || $anio > 2100) {
            Response::error('Año inválido (debe estar entre 2000 y 2100)');
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO campanas (año, fecha_inicio, fecha_fin, estado, notas)
                VALUES (:anio, :inicio, :fin, :estado, :notas)
                ON CONFLICT (año) DO UPDATE SET
                    estado       = EXCLUDED.estado,
                    fecha_inicio = COALESCE(EXCLUDED.fecha_inicio, campanas.fecha_inicio),
                    fecha_fin    = COALESCE(EXCLUDED.fecha_fin, campanas.fecha_fin),
                    notas        = COALESCE(EXCLUDED.notas, campanas.notas)
            ");
            $stmt->execute([
                ':anio'   => $anio,
                ':inicio' => $data['fecha_inicio'] ?? null,
                ':fin'    => $data['fecha_fin']    ?? null,
                ':estado' => $data['estado']       ?? 'activa',
                ':notas'  => $data['notas']        ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error al guardar campaña: ' . $e->getMessage(), 500);
            return;
        }

        Response::json(['año' => $anio, 'message' => 'Campaña guardada'], 201);
    }

    // PUT /campanas/{año}
    public function update(array $p): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $stmt = $this->db->prepare("
                UPDATE campanas SET
                    estado       = COALESCE(:estado, estado),
                    fecha_fin    = COALESCE(:fin,    fecha_fin),
                    notas        = COALESCE(:notas,  notas)
                WHERE año = :anio
            ");
            $stmt->execute([
                ':estado' => $data['estado']    ?? null,
                ':fin'    => $data['fecha_fin'] ?? null,
                ':notas'  => $data['notas']     ?? null,
                ':anio'   => (int)$p['año'],
            ]);
        } catch (\PDOException $e) {
            Response::error('Error al actualizar campaña: ' . $e->getMessage(), 500);
            return;
        }
        Response::json(['updated' => true]);
    }

    // GET /campanas/backups?tipo=diario&año=2026
    public function backups(): void
    {
        $tipo = $_GET['tipo'] ?? null;
        $anio = isset($_GET['año']) ? (int)$_GET['año'] : null;

        $where  = ['1=1'];
        $params = [];

        if ($tipo) { $where[] = 'tipo = :tipo';          $params[':tipo'] = $tipo; }
        if ($anio) { $where[] = 'campana_año = :anio';   $params[':anio'] = $anio; }

        try {
            $stmt = $this->db->prepare(
                "SELECT id, campana_año, tipo, fecha_backup, descripcion, realizado_por, estado, notas
                 FROM backups_registro WHERE " . implode(' AND ', $where) .
                " ORDER BY fecha_backup DESC LIMIT 100"
            );
            $stmt->execute($params);
            Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (\PDOException $e) {
            Response::error('Error al obtener backups: ' . $e->getMessage(), 500);
        }
    }

    // POST /campanas/backups
    public function registrarBackup(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $anio = (int)($data['campana_año'] ?? date('Y'));
        $tipo = $data['tipo'] ?? 'diario';

        if (!in_array($tipo, ['diario', 'mensual', 'anual'])) {
            Response::error('Tipo debe ser diario, mensual o anual');
            return;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO backups_registro (campana_año, tipo, descripcion, realizado_por, estado, notas)
                VALUES (:anio, :tipo, :desc, :por, :estado, :notas)
            ");
            $stmt->execute([
                ':anio'   => $anio,
                ':tipo'   => $tipo,
                ':desc'   => $data['descripcion']   ?? null,
                ':por'    => $data['realizado_por'] ?? 'Administrador',
                ':estado' => $data['estado']        ?? 'completado',
                ':notas'  => $data['notas']         ?? null,
            ]);
        } catch (\PDOException $e) {
            Response::error('Error al registrar backup: ' . $e->getMessage(), 500);
            return;
        }

        $id = Database::lastId($this->db, 'backups_registro');
        Response::json(['id' => $id, 'message' => 'Backup registrado'], 201);
    }
}
