<?php
/**
 * Router minimalista para la API REST
 */
class Router {
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void {
        $this->routes[] = compact('method', 'pattern', 'handler');
    }

    public function get(string $p, callable $h): void    { $this->add('GET',    $p, $h); }
    public function post(string $p, callable $h): void   { $this->add('POST',   $p, $h); }
    public function put(string $p, callable $h): void    { $this->add('PUT',    $p, $h); }
    public function delete(string $p, callable $h): void { $this->add('DELETE', $p, $h); }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        // Soporta _method override para clientes que no soportan PUT/DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Quitar prefijo /api, con o sin /trazabilidad_cafe delante
        // (local: /trazabilidad_cafe/api/... — VPS con dominio propio: /api/...)
        $uri = preg_replace('#^(/trazabilidad_cafe)?/api#', '', $uri);
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            if (preg_match("#^{$pattern}$#", $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($route['handler'], $params);
                return;
            }
        }

        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada', 'uri' => $uri, 'method' => $method]);
    }
}
