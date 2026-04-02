<?php
/**
 * HuBBS - 工具类
 * 封装常用工具方法
 */

class Utils {
    
    /**
     * 获取基础 URL
     * @param string $path 可选路径
     * @return string
     */
    public static function baseUrl($path = '') {
        static $baseUrl = null;
        if ($baseUrl === null) {
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = dirname($scriptName);
            if ($dir === '/' || $dir === '\\') {
                $baseUrl = '';
            } else {
                $baseUrl = $dir;
            }
            $baseUrl = rtrim($baseUrl, '/');
        }
        if ($path) {
            return $baseUrl . '/' . ltrim($path, '/');
        }
        return $baseUrl;
    }
    
    /**
     * HTML 转义
     * @param string $str
     * @return string
     */
    public static function h($str) {
        if ($str === null) {
            return '';
        }
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 输出 HTML 转义后的字符串
     * @param string $str
     */
    public static function e($str) {
        echo self::h($str);
    }
    
    /**
     * 密码哈希
     * @param string $password
     * @return string
     */
    public static function passwordHash($password) {
        return password_hash($password . HUBBS_SALT, PASSWORD_BCRYPT);
    }
    
    /**
     * 密码验证
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function passwordVerify($password, $hash) {
        return password_verify($password . HUBBS_SALT, $hash);
    }
    
    /**
     * 获取 CSRF Token
     * @return string
     */
    public static function csrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 输出 CSRF 隐藏字段
     */
    public static function csrfField() {
        echo '<input type="hidden" name="csrf_token" value="' . self::csrfToken() . '">';
    }
    
    /**
     * 验证 CSRF Token
     * @param string $token
     * @return bool
     */
    public static function verifyCsrf($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * 跳转
     * @param string $url
     */
    public static function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    /**
     * 设置消息
     * @param string $msg
     * @param string $type
     */
    public static function setMessage($msg, $type = 'success') {
        $_SESSION['message'] = ['text' => $msg, 'type' => $type];
    }
    
    /**
     * 获取并清除消息
     * @return array|null
     */
    public static function getMessage() {
        if (isset($_SESSION['message'])) {
            $msg = $_SESSION['message'];
            unset($_SESSION['message']);
            return $msg;
        }
        return null;
    }
    
    /**
     * 获取客户端 IP
     * @return string
     */
    public static function getClientIp() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
    
    /**
     * 时间格式化（多久前）
     * @param string $time
     * @return string
     */
    public static function timeAgo($time) {
        $diff = time() - strtotime($time);
        
        if ($diff < 60) return '刚刚';
        if ($diff < 3600) return floor($diff / 60) . '分钟前';
        if ($diff < 86400) return floor($diff / 3600) . '小时前';
        if ($diff < 2592000) return floor($diff / 86400) . '天前';
        
        return date('Y-m-d', strtotime($time));
    }
    
    /**
     * 验证邮箱
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    /**
     * 验证用户名
     * @param string $username
     * @return bool
     */
    public static function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20}$/u', $username);
    }
    
    /**
     * 格式化文件大小
     * @param int $size
     * @return string
     */
    public static function formatSize($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}
