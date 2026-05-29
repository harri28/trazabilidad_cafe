<?php
/**
 * Módulo: Administrativo → Financiero / Contabilidad
 * Cuentas por cobrar/pagar, flujo de caja, estados financieros.
 * TODO: Implementar
 */
class FinancieroController
{
    public function index(): void  { Response::json(['modulo' => 'financiero', 'estado' => 'en desarrollo']); }
    public function flujoCaja(): void { Response::json(['modulo' => 'financiero', 'estado' => 'en desarrollo']); }
}
