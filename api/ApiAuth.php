<?php
/**
 * HuBBS - API认证中间件
 */

class ApiAuth {
    
    /**
     * 验证API请求
     */
    public static function check() {
        // 检查是否登录
        if (Auth::guest()) {
            ApiResponse::unauthorized('请先登录');
        }
        
        return true;
    }
    
    /**
     * 验证管理员权限
     */
    public static function admin() {
        self::check();
        
        if (!Auth::isAdmin()) {
            ApiResponse::forbidden('需要管理员权限');
        }
        
        return true;
    }
    
    /**
     * 验证CSRF Token（用于非GET请求）
     */
    public static function csrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }
        
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        
        if (!verify_csrf($token)) {
            ApiResponse::error('CSRF验证失败', 403);
        }
        
        return true;
    }
}
