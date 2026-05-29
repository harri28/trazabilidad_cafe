<?php
/**
 * Conexión PDO a PostgreSQL
 */
class Database
{
    private string  $host    = 'localhost';
    private int     $port    = 5432;
    private string  $db_name = 'trazabilidad_cafe';
    private string  $username = 'postgresql';
    private string  $password = '1234';
    private ?PDO    $conn    = null;

    public function getConnection(): PDO
    {
        if ($this->conn !== null) return $this->conn;

        try {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->db_name}";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // UTF-8 explícito
            $this->conn->exec("SET client_encoding = 'UTF8'");
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]));
        }

        return $this->conn;
    }

    /**
     * Devuelve el último ID insertado para una tabla dada.
     * En PostgreSQL el nombre de secuencia es: {tabla}_id_seq
     */
    public static function lastId(PDO $db, string $tabla): int
    {
        return (int) $db->lastInsertId("{$tabla}_id_seq");
    }
}
