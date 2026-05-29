<?php
/**
 * Cliente HTTP para la API de Facturación Electrónica SUNAT
 * Conecta trazabilidad_cafe con el servicio conexion_sunat (Laravel/Greenter)
 *
 * URL base: http://localhost/conexion_sunat/public
 * Credenciales: configurar SUNAT_API_EMAIL y SUNAT_API_PASSWORD abajo
 */
class SunatApiClient
{
    // ── Configuración ──────────────────────────────────────────
    private const BASE_URL     = 'http://localhost/conexion_sunat/public';

    /**
     * Credenciales del usuario API de conexion_sunat (Sanctum).
     * Deben coincidir con el usuario creado en conexion_sunat
     * (via php artisan db:seed --class=RolesAndPermissionsSeeder
     *  o el email/contraseña ingresado en POST /auth/initialize).
     */
    private const API_EMAIL    = 'admin@trazabilidad.com';
    private const API_PASSWORD = 'Admin123!';

    /**
     * Empresa emisora configurada en conexion_sunat.
     * RUC: 10734630549 — clave SOL configurada en CompanySeeder.
     * Ejecutar: php artisan db:seed --class=CompanySeeder
     */
    public const COMPANY_ID = 1;
    public const BRANCH_ID  = 1;

    /** Serie por defecto para facturas y boletas */
    public const SERIE_FACTURA = 'F001';
    public const SERIE_BOLETA  = 'B001';

    // ── Código de producto SUNAT para café ────────────────────
    private const CODIGO_PRODUCTO_CAFE = '01010000';  // Café sin tostar
    private const UNIDAD_KG            = 'KGM';        // Kilogramos

    // ── Token cache ───────────────────────────────────────────
    private string  $tokenFile;
    private ?string $token = null;

    public function __construct()
    {
        $this->tokenFile = __DIR__ . '/sunat_token_cache.json';
    }

    // ──────────────────────────────────────────────────────────
    //  MÉTODOS PÚBLICOS
    // ──────────────────────────────────────────────────────────

    /** Crea una factura en conexion_sunat. Devuelve el array de respuesta. */
    public function crearFactura(array $payload): array
    {
        return $this->post('/api/v1/invoices', $payload);
    }

    /** Envía la factura (id) a SUNAT y devuelve la respuesta con CDR. */
    public function enviarFacturaSunat(int $id): array
    {
        return $this->post("/api/v1/invoices/{$id}/send-sunat", []);
    }

    /** Crea una boleta en conexion_sunat. */
    public function crearBoleta(array $payload): array
    {
        return $this->post('/api/v1/boletas', $payload);
    }

    /** Envía la boleta (id) a SUNAT. */
    public function enviarBoletaSunat(int $id): array
    {
        return $this->post("/api/v1/boletas/{$id}/send-sunat", []);
    }

    /** Consulta datos de RUC/DNI en SUNAT (proxy a conexion_sunat). */
    public function consultarRuc(string $numero): array
    {
        return $this->get('/consulta-ruc?numero=' . urlencode($numero));
    }

    /** Obtiene el estado de un documento en conexion_sunat. */
    public function obtenerFactura(int $id): array
    {
        return $this->get("/api/v1/invoices/{$id}");
    }

    public function obtenerBoleta(int $id): array
    {
        return $this->get("/api/v1/boletas/{$id}");
    }

    // ──────────────────────────────────────────────────────────
    //  MAPEO VENTA → PAYLOAD SUNAT
    // ──────────────────────────────────────────────────────────

    /**
     * Construye el payload para crear una factura/boleta
     * a partir de los datos de la venta de trazabilidad_cafe.
     *
     * $venta debe contener los campos de VentaController::show():
     *   id, numero_contrato, comprador_id, lote_id, comprador, ruc_dni,
     *   pais_destino, email_comprador, cantidad_kg, precio_usd_kg,
     *   tipo_cambio, moneda_factura, total_usd, lote_codigo, variedad
     */
    public function buildFacturaPayload(array $venta): array
    {
        $esExportacion = !empty($venta['pais_destino'])
            && strtoupper(trim($venta['pais_destino'])) !== 'PERU'
            && strtoupper(trim($venta['pais_destino'])) !== 'PERÚ';

        $moneda = $venta['moneda_factura'] ?? 'USD';

        // Precio unitario en la moneda de factura
        $precioUnitario = $esExportacion
            ? (float)$venta['precio_usd_kg']
            : round((float)$venta['precio_usd_kg'] * (float)$venta['tipo_cambio'], 4);

        // IGV: exportación → 0%, doméstico → 18%
        $pctIgv    = $esExportacion ? 0 : 18;
        $tipAfeIgv = $esExportacion ? '40' : '10';  // 40=Exportación, 10=Gravado

        $tipoOp = $esExportacion ? '0200' : '0101';

        $descripcionItem = sprintf(
            'Café %s - Lote %s - %s kg',
            $venta['variedad'] ?? '',
            $venta['lote_codigo'] ?? '',
            number_format((float)$venta['cantidad_kg'], 3)
        );

        return [
            'company_id'          => self::COMPANY_ID,
            'branch_id'           => self::BRANCH_ID,
            'serie'               => self::SERIE_FACTURA,
            'fecha_emision'       => date('Y-m-d'),
            'fecha_vencimiento'   => date('Y-m-d', strtotime('+30 days')),
            'moneda'              => $moneda,
            'tipo_operacion'      => $tipoOp,
            'forma_pago_tipo'     => 'Contado',
            'client'              => $this->buildClientPayload($venta, false),
            'detalles'            => [
                [
                    'codigo'                => $venta['lote_codigo'] ?? 'CAFE001',
                    'descripcion'           => $descripcionItem,
                    'unidad'                => self::UNIDAD_KG,
                    'cantidad'              => (float)$venta['cantidad_kg'],
                    'mto_valor_unitario'    => $precioUnitario,
                    'porcentaje_igv'        => $pctIgv,
                    'tip_afe_igv'           => $tipAfeIgv,
                    'codigo_producto_sunat' => self::CODIGO_PRODUCTO_CAFE,
                ],
            ],
            'usuario_creacion'    => $venta['usuario'] ?? 'sistema',
            'observaciones'       => 'Contrato: ' . ($venta['numero_contrato'] ?? '') . '. Incoterm: ' . ($venta['incoterm'] ?? 'FOB'),
        ];
    }

    public function buildBoletaPayload(array $venta): array
    {
        $moneda = $venta['moneda_factura'] ?? 'PEN';
        $precioUnitario = round(
            (float)$venta['precio_usd_kg'] * (float)$venta['tipo_cambio'],
            4
        );

        return [
            'company_id'        => self::COMPANY_ID,
            'branch_id'         => self::BRANCH_ID,
            'serie'             => self::SERIE_BOLETA,
            'fecha_emision'     => date('Y-m-d'),
            'moneda'            => $moneda,
            'tipo_operacion'    => '0101',
            'forma_pago_tipo'   => 'Contado',
            'client'            => $this->buildClientPayload($venta, true),
            'detalles'          => [
                [
                    'codigo'                => $venta['lote_codigo'] ?? 'CAFE001',
                    'descripcion'           => sprintf('Café %s - Lote %s', $venta['variedad'] ?? '', $venta['lote_codigo'] ?? ''),
                    'unidad'                => self::UNIDAD_KG,
                    'cantidad'              => (float)$venta['cantidad_kg'],
                    'mto_valor_unitario'    => $precioUnitario,
                    'porcentaje_igv'        => 18,
                    'tip_afe_igv'           => '10',
                    'codigo_producto_sunat' => self::CODIGO_PRODUCTO_CAFE,
                ],
            ],
            'usuario_creacion'  => $venta['usuario'] ?? 'sistema',
        ];
    }

    // ──────────────────────────────────────────────────────────
    //  HTTP HELPERS
    // ──────────────────────────────────────────────────────────

    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    private function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $token = $this->getToken();
        $url   = self::BASE_URL . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$token}",
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("Error de conexión con conexion_sunat: {$curlError}");
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new RuntimeException("Respuesta inválida de conexion_sunat (HTTP {$httpCode}): {$response}");
        }

        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? "Error HTTP {$httpCode}";
            throw new RuntimeException("conexion_sunat respondió con error: {$msg}", $httpCode);
        }

        return $decoded;
    }

    // ──────────────────────────────────────────────────────────
    //  AUTENTICACIÓN Y TOKEN
    // ──────────────────────────────────────────────────────────

    private function getToken(): string
    {
        if ($this->token !== null) return $this->token;

        // Intentar cargar desde caché
        if (file_exists($this->tokenFile)) {
            $cache = json_decode(file_get_contents($this->tokenFile), true);
            if (!empty($cache['token']) && !empty($cache['expires_at'])) {
                // Dejar 5 minutos de margen antes del vencimiento
                if (time() < ($cache['expires_at'] - 300)) {
                    $this->token = $cache['token'];
                    return $this->token;
                }
            }
        }

        // Obtener nuevo token
        $this->token = $this->login();
        return $this->token;
    }

    private function login(): string
    {
        $url = self::BASE_URL . '/auth/login';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => json_encode([
                'email'    => self::API_EMAIL,
                'password' => self::API_PASSWORD,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("No se pudo conectar con conexion_sunat: {$curlError}");
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200 || empty($data['token'])) {
            $msg = $data['message'] ?? "Login fallido (HTTP {$httpCode})";
            throw new RuntimeException("Autenticación con conexion_sunat falló: {$msg}");
        }

        // Cachear token por 23 horas (expira en 24h según .env)
        file_put_contents($this->tokenFile, json_encode([
            'token'      => $data['token'],
            'expires_at' => time() + (23 * 3600),
        ]));

        return $data['token'];
    }

    // ──────────────────────────────────────────────────────────
    //  HELPERS PRIVADOS
    // ──────────────────────────────────────────────────────────

    private function buildClientPayload(array $venta, bool $esBoleta): array
    {
        $rucDni = $venta['ruc_dni'] ?? '';
        $len    = strlen(preg_replace('/\D/', '', $rucDni));

        if ($esBoleta) {
            $tipoDoc = ($len === 8) ? '1' : '0';  // 1=DNI, 0=sin doc
        } else {
            if ($len === 11)      $tipoDoc = '6';  // RUC
            elseif ($len === 8)   $tipoDoc = '1';  // DNI (raro en facturas, pero posible)
            else                  $tipoDoc = '0';  // Extranjero / sin documento
        }

        return [
            'tipo_documento'  => $tipoDoc,
            'numero_documento'=> $rucDni ?: '00000000',
            'razon_social'    => $venta['comprador'] ?? 'Cliente SUNAT',
            'direccion'       => $venta['direccion_comprador'] ?? $venta['direccion'] ?? '',
            'email'           => $venta['email_comprador'] ?? '',
            'telefono'        => $venta['telefono_comprador'] ?? '',
        ];
    }
}
