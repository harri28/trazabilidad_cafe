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

    // POST /configuracion/logo  (multipart/form-data, campo "logo")
    public function uploadLogo(): void
    {
        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            Response::error('Archivo "logo" es requerido', 422);
            return;
        }

        $archivo = $_FILES['logo'];
        $permitidos = [
            'image/png'     => 'png',
            'image/jpeg'    => 'jpg',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
        ];
        $mime = mime_content_type($archivo['tmp_name']);
        if (!isset($permitidos[$mime])) {
            Response::error('Formato no soportado. Usa PNG, JPG, WEBP o SVG', 422);
            return;
        }
        if ($archivo['size'] > 2 * 1024 * 1024) {
            Response::error('El logo no puede superar 2 MB', 422);
            return;
        }

        $dir = __DIR__ . '/../../../public/uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Borrar logos anteriores con otra extensión
        foreach (glob($dir . '/logo.*') as $anterior) {
            unlink($anterior);
        }

        $ext      = $permitidos[$mime];
        $destino  = "{$dir}/logo.{$ext}";
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            Response::error('No se pudo guardar el archivo', 500);
            return;
        }

        // Ruta relativa (sin "/" inicial): se resuelve relativa a la página actual
        // (public/index.php o public/login.php), que siempre vive junto a uploads/,
        // tanto en local (/trazabilidad_cafe/public/) como en VPS (/public/).
        $url = "uploads/logo.{$ext}?v=" . time();

        $stmt = $this->db->prepare("
            INSERT INTO configuracion (clave, valor, descripcion, actualizado_en)
            VALUES ('logo_url', :valor, 'URL del logo del sistema', NOW())
            ON CONFLICT (clave)
            DO UPDATE SET valor = EXCLUDED.valor, actualizado_en = NOW()
        ");
        $stmt->execute([':valor' => $url]);

        Response::json(['clave' => 'logo_url', 'valor' => $url, 'updated' => true]);
    }
}
