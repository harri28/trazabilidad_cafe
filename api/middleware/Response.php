<?php
/**
 * Helpers para respuestas JSON estandarizadas
 */
class Response {
    public static function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        echo json_encode([
            'success' => $code < 400,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function error(string $message, int $code = 400, array $details = []): void {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error'   => $message,
            'details' => $details,
        ], JSON_UNESCAPED_UNICODE);
    }

    public static function paginated(array $items, int $total, int $page, int $per_page): void {
        http_response_code(200);
        echo json_encode([
            'success'    => true,
            'data'       => $items,
            'pagination' => [
                'total'    => $total,
                'page'     => $page,
                'per_page' => $per_page,
                'pages'    => (int)ceil($total / $per_page),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }
}
