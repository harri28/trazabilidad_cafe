<?php
/**
 * Módulo: Administrativo → Configuración
 * Pares clave-valor para configuración global del sistema.
 */
class ConfiguracionController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = (new Database())->getConnection();
    }

    // GET /configuracion
    public function index(): void
    {
        $stmt = $this->db->query(
            "SELECT clave, valor, descripcion, actualizado_en FROM configuracion ORDER BY clave"
        );
        Response::json($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // GET /configuracion/{clave}
    public function show(array $p): void
    {
        $stmt = $this->db->prepare(
            "SELECT clave, valor, descripcion, actualizado_en FROM configuracion WHERE clave = :clave"
        );
        $stmt->execute([':clave' => $p['clave']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            Response::error('Configuración no encontrada', 404);
        } else {
            Response::json($row);
        }
    }

    // PUT /configuracion/{clave}
    public function upsert(array $p): void
    {
        $data        = json_decode(file_get_contents('php://input'), true) ?? [];
        $clave       = $p['clave'];
        $valor       = $data['valor']       ?? null;
        $descripcion = $data['descripcion'] ?? null;

        if ($valor === null) {
            Response::error('El campo valor es requerido');
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO configuracion (clave, valor, descripcion, actualizado_en)
            VALUES (:clave, :valor, :desc, NOW())
            ON CONFLICT (clave)
            DO UPDATE SET
                valor          = EXCLUDED.valor,
                descripcion    = COALESCE(EXCLUDED.descripcion, configuracion.descripcion),
                actualizado_en = NOW()
        ");
        $stmt->execute([
            ':clave' => $clave,
            ':valor' => (string) $valor,
            ':desc'  => $descripcion,
        ]);

        Response::json(['clave' => $clave, 'valor' => $valor, 'updated' => true]);
    }
}
