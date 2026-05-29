<?php
/**
 * Módulo: Integraciones → SUNAT (Facturación Electrónica)
 *
 * Emite facturas y boletas electrónicas delegando al servicio conexion_sunat.
 * Los documentos se asocian a ventas del sistema de trazabilidad.
 *
 * Rutas:
 *   POST   /sunat/factura        → emitirFactura()  – recibe { venta_id }
 *   POST   /sunat/boleta         → emitirBoleta()   – recibe { venta_id }
 *   GET    /sunat/cpe/{id}       → consultarCpe()   – id = venta_id
 *   DELETE /sunat/cpe/{id}       → anularCpe()      – id = venta_id
 */
class SunatController
{
    private PDO             $db;
    private SunatApiClient  $sunat;

    public function __construct()
    {
        $this->db    = (new Database())->getConnection();
        $this->sunat = new SunatApiClient();
    }

    // ──────────────────────────────────────────────────────────
    //  POST /sunat/factura          { venta_id }
    //  POST /ventas/{id}/facturar   (id desde path)
    // ──────────────────────────────────────────────────────────
    public function emitirFactura(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($body['venta_id'])) { Response::error('venta_id es requerido', 422); return; }
        $this->emitirComprobante((int)$body['venta_id'], 'factura');
    }

    public function facturarVenta(array $params): void
    {
        $this->emitirComprobante((int)$params['id'], 'factura');
    }

    // ──────────────────────────────────────────────────────────
    //  POST /sunat/boleta           { venta_id }
    //  POST /ventas/{id}/boleta     (id desde path)
    // ──────────────────────────────────────────────────────────
    public function emitirBoleta(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($body['venta_id'])) { Response::error('venta_id es requerido', 422); return; }
        $this->emitirComprobante((int)$body['venta_id'], 'boleta');
    }

    public function boletaVenta(array $params): void
    {
        $this->emitirComprobante((int)$params['id'], 'boleta');
    }

    // ──────────────────────────────────────────────────────────
    //  GET /sunat/consulta-ruc?numero=20123456789
    // ──────────────────────────────────────────────────────────
    public function consultarRuc(): void
    {
        $numero = preg_replace('/\D/', '', trim($_GET['numero'] ?? ''));
        if (empty($numero)) {
            Response::error('Parámetro numero es requerido', 422);
            return;
        }
        if (strlen($numero) !== 8 && strlen($numero) !== 11) {
            Response::error('El número debe tener 8 dígitos (DNI) o 11 dígitos (RUC)', 422);
            return;
        }

        try {
            $data = $this->sunat->consultarRuc($numero);
            Response::json($data);
        } catch (RuntimeException $e) {
            $code = (int)$e->getCode();
            Response::error($e->getMessage(), $code >= 400 ? $code : 502);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  GET /sunat/cpe/{id}   (id = venta_id)
    // ──────────────────────────────────────────────────────────
    public function consultarCpe(array $params): void
    {
        $venta = $this->getVenta((int)$params['id']);
        if (!$venta) { Response::error('Venta no encontrada', 404); return; }

        if (!$venta['sunat_documento_id']) {
            Response::error('Esta venta no tiene comprobante SUNAT emitido', 404);
            return;
        }

        try {
            $tipo    = $venta['sunat_tipo'];
            $docData = ($tipo === 'boleta')
                ? $this->sunat->obtenerBoleta((int)$venta['sunat_documento_id'])
                : $this->sunat->obtenerFactura((int)$venta['sunat_documento_id']);

            $docData = $docData['data'] ?? $docData;

            // Sincronizar estado
            $estado = $this->extractEstado($docData);
            if ($estado !== $venta['sunat_estado']) {
                $this->actualizarVentaSunat((int)$params['id'], [
                    'sunat_estado'          => $estado,
                    'sunat_cdr_descripcion' => $docData['sunat_description'] ?? $docData['cdr_description'] ?? $venta['sunat_cdr_descripcion'],
                ]);
            }

            Response::json([
                'venta_id'           => (int)$params['id'],
                'numero_contrato'    => $venta['numero_contrato'],
                'sunat_tipo'         => $tipo,
                'sunat_serie'        => $venta['sunat_serie'],
                'sunat_numero'       => $venta['sunat_numero'],
                'sunat_estado'       => $estado,
                'cdr_descripcion'    => $docData['sunat_description'] ?? $docData['cdr_description'] ?? null,
                'sunat_emitido_en'   => $venta['sunat_emitido_en'],
                'documento'          => $docData,
            ]);

        } catch (RuntimeException $e) {
            $code = (int)$e->getCode();
            Response::error($e->getMessage(), $code >= 400 ? $code : 502);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  DELETE /sunat/cpe/{id}  (id = venta_id)
    // ──────────────────────────────────────────────────────────
    public function anularCpe(array $params): void
    {
        $venta = $this->getVenta((int)$params['id']);
        if (!$venta) { Response::error('Venta no encontrada', 404); return; }

        if (!$venta['sunat_documento_id']) {
            Response::error('Esta venta no tiene comprobante SUNAT para anular', 404);
            return;
        }

        if ($venta['sunat_estado'] === 'anulado') {
            Response::error('El comprobante ya fue anulado', 409);
            return;
        }

        // Nota: la anulación real en SUNAT requiere una comunicación de baja (resumen).
        // Aquí marcamos como anulado en el sistema local.
        // La baja electrónica se gestiona manualmente en conexion_sunat.
        $this->actualizarVentaSunat((int)$params['id'], [
            'sunat_estado' => 'anulado',
        ]);

        Response::json([
            'message'       => 'Comprobante marcado como anulado en el sistema local. Para la baja oficial en SUNAT, generar la comunicación de baja en conexion_sunat.',
            'venta_id'      => (int)$params['id'],
            'sunat_tipo'    => $venta['sunat_tipo'],
            'sunat_serie'   => $venta['sunat_serie'],
            'sunat_numero'  => $venta['sunat_numero'],
        ]);
    }

    // ──────────────────────────────────────────────────────────
    //  HELPERS PRIVADOS
    // ──────────────────────────────────────────────────────────

    /** Lógica compartida para emitir factura o boleta a SUNAT */
    private function emitirComprobante(int $ventaId, string $tipo): void
    {
        $venta = $this->getVenta($ventaId);
        if (!$venta) { Response::error('Venta no encontrada', 404); return; }

        if ($venta['sunat_documento_id']) {
            Response::error(
                "Esta venta ya tiene un comprobante SUNAT ({$venta['sunat_tipo']} {$venta['sunat_serie']}-{$venta['sunat_numero']})",
                409
            );
            return;
        }

        if (!in_array($venta['estado'], ['confirmado', 'en_proceso', 'entregado'])) {
            Response::error(
                "Solo se pueden facturar ventas confirmadas. Estado actual: {$venta['estado']}",
                409
            );
            return;
        }

        try {
            if ($tipo === 'boleta') {
                $payload      = $this->sunat->buildBoletaPayload($venta);
                $creado       = $this->sunat->crearBoleta($payload);
                $docId        = $creado['data']['id'] ?? $creado['id'] ?? null;
                if (!$docId) { Response::error('conexion_sunat no devolvió un ID de documento', 502); return; }
                $enviado      = $this->sunat->enviarBoletaSunat((int)$docId);
                $serieDefault = SunatApiClient::SERIE_BOLETA;
            } else {
                $payload      = $this->sunat->buildFacturaPayload($venta);
                $creado       = $this->sunat->crearFactura($payload);
                $docId        = $creado['data']['id'] ?? $creado['id'] ?? null;
                if (!$docId) { Response::error('conexion_sunat no devolvió un ID de documento', 502); return; }
                $enviado      = $this->sunat->enviarFacturaSunat((int)$docId);
                $serieDefault = SunatApiClient::SERIE_FACTURA;
            }

            $docData = $enviado['data'] ?? $enviado;
            $estado  = $this->extractEstado($docData);

            $this->actualizarVentaSunat($ventaId, [
                'sunat_documento_id'    => $docId,
                'sunat_tipo'            => $tipo,
                'sunat_serie'           => $docData['serie']       ?? $serieDefault,
                'sunat_numero'          => $docData['correlativo'] ?? '',
                'sunat_estado'          => $estado,
                'sunat_cdr_descripcion' => $docData['sunat_description'] ?? $docData['cdr_description'] ?? null,
                'sunat_emitido_en'      => date('Y-m-d H:i:s'),
            ]);

            Response::json([
                'message'            => ucfirst($tipo) . ' emitida y enviada a SUNAT',
                'venta_id'           => $ventaId,
                'sunat_documento_id' => $docId,
                'serie'              => $docData['serie']       ?? $serieDefault,
                'numero'             => $docData['correlativo'] ?? '',
                'estado'             => $estado,
                'cdr_descripcion'    => $docData['sunat_description'] ?? $docData['cdr_description'] ?? null,
            ], 201);

        } catch (RuntimeException $e) {
            $code = (int)$e->getCode();
            Response::error($e->getMessage(), $code >= 400 ? $code : 502);
        }
    }

    private function getVenta(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT
                v.*,
                comp.razon_social    AS comprador,
                comp.ruc_dni,
                comp.pais_destino,
                comp.email           AS email_comprador,
                comp.telefono        AS telefono_comprador,
                comp.direccion       AS direccion_comprador,
                l.codigo             AS lote_codigo,
                l.variedad
            FROM ventas v
            JOIN clientes comp ON comp.id = v.comprador_id
            JOIN lotes    l    ON l.id    = v.lote_id
            WHERE v.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    private function actualizarVentaSunat(int $ventaId, array $campos): void
    {
        $sets   = [];
        $params = [':id' => $ventaId];

        foreach ($campos as $col => $val) {
            $sets[]          = "{$col} = :{$col}";
            $params[":{$col}"] = $val;
        }

        $sql  = 'UPDATE ventas SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /** Extrae el estado SUNAT normalizado desde la respuesta de conexion_sunat */
    private function extractEstado(array $docData): string
    {
        $raw = strtolower(
            $docData['sunat_status'] ?? $docData['estado'] ?? $docData['status'] ?? 'pendiente'
        );

        return match(true) {
            str_contains($raw, 'acept') => 'aceptado',
            str_contains($raw, 'rechaz') => 'rechazado',
            str_contains($raw, 'observ') => 'observado',
            str_contains($raw, 'anula') => 'anulado',
            default                     => 'pendiente',
        };
    }
}
