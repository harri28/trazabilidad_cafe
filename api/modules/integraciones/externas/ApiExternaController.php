<?php
/**
 * Módulo: Integraciones → APIs Externas
 * Conectores con sistemas externos: bolsas de café, tipos de cambio,
 * plataformas de trazabilidad internacional, etc.
 * TODO: Implementar
 */
class ApiExternaController
{
    public function tipoCambio(): void   { Response::json(['modulo' => 'externas', 'estado' => 'en desarrollo']); }
    public function precioMercado(): void { Response::json(['modulo' => 'externas', 'estado' => 'en desarrollo']); }
}
