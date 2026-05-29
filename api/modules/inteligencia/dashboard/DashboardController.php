<?php
/**
 * Módulo: Inteligencia → Dashboard Gerencial (BI)
 * KPIs consolidados de producción, ventas, calidad y financiero.
 * Extrae y consolida métricas de todos los módulos operativos.
 * TODO: Migrar lógica desde VentaController::dashboard() y ampliar.
 */
class DashboardController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function index(): void
    {
        Response::json(['modulo' => 'dashboard', 'estado' => 'en desarrollo']);
    }
}
