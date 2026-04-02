<?php
/**
 * HuBBS - API响应类
 * 统一API响应格式
 */

class ApiResponse {
    
    /**
     * 成功响应
     */
    public static function success($data = null, $message = 'success') {
        $response = [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
        
        return self::json($response);
    }
    
    /**
     * 失败响应
     */
    public static function error($message = 'error', $code = 400, $data = null) {
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ];
        
        return self::json($response, $code);
    }
    
    /**
     * 分页响应
     */
    public static function paginate($items, $total, $page, $perPage) {
        $response = [
            'success' => true,
            'code' => 200,
            'message' => 'success',
            'data' => [
                'items' => $items,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => max(1, ceil($total / $perPage))
                ]
            ],
            'timestamp' => time()
        ];
        
        return self::json($response);
    }
    
    /**
     * 未授权响应
     */
    public static function unauthorized($message = '未授权访问') {
        return self::error($message, 401);
    }
    
    /**
     * 禁止访问响应
     */
    public static function forbidden($message = '禁止访问') {
        return self::error($message, 403);
    }
    
    /**
     * 未找到响应
     */
    public static function notFound($message = '资源不存在') {
        return self::error($message, 404);
    }
    
    /**
     * 验证错误响应
     */
    public static function validationError($errors, $message = '验证失败') {
        return self::error($message, 422, ['errors' => $errors]);
    }
    
    /**
     * 服务器错误响应
     */
    public static function serverError($message = '服务器内部错误') {
        return self::error($message, 500);
    }
    
    /**
     * 输出JSON
     */
    private static function json($data, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        
        // 支持JSONP
        if (isset($_GET['callback'])) {
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['callback']);
            echo $callback . '(' . json_encode($data) . ');';
        } else {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        exit;
    }
}
