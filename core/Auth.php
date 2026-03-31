<?php
/**
 * HuBBS - 用户认证类
 */

class Auth {
    private static $user = null;
    
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 检查记住登录
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['hubbs_remember'])) {
            self::checkRememberToken();
        }
        
        // 加载当前用户
        if (isset($_SESSION['user_id'])) {
            self::$user = self::getUserById($_SESSION['user_id']);
        }
    }
    
    public static function user() {
        return self::$user;
    }
    
    public static function id() {
        return self::$user ? self::$user['id'] : null;
    }
    
    public static function check() {
        return self::$user !== null && self::$user !== false;
    }
    
    public static function guest() {
        return !self::check();
    }
    
    public static function isAdmin() {
        return self::check() && !empty(self::$user['is_admin']);
    }
    
    public static function login($username, $password, $remember = false) {
        $db = DB::getInstance();
        
        // 支持用户名或邮箱登录，排除已删除用户
        $user = $db->fetch(
            "SELECT * FROM {$db->table('users')} WHERE (username = ? OR email = ?) AND deleted_at IS NULL LIMIT 1",
            [$username, $username]
        );
        
        if (!$user || !password_verify_hubbs($password, $user['password'])) {
            return false;
        }
        
        // 更新登录信息
        $db->update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => get_client_ip()
        ], 'id = ?', [$user['id']]);
        
        // 设置Session
        $_SESSION['user_id'] = $user['id'];
        self::$user = $user;
        
        // 记住登录
        if ($remember) {
            self::setRememberToken($user['id']);
        }
        
        return true;
    }
    
    public static function logout() {
        // 清除记住登录
        if (isset($_COOKIE['hubbs_remember'])) {
            $db = DB::getInstance();
            $db->delete('remember_tokens', 'token = ?', [$_COOKIE['hubbs_remember']]);
            setcookie('hubbs_remember', '', time() - 3600, '/');
        }
        
        session_destroy();
        self::$user = null;
    }
    
    public static function register($data) {
        $db = DB::getInstance();
        
        // 检查用户名（排除已删除用户）
        if ($db->count('users', 'username = ? AND deleted_at IS NULL', [$data['username']]) > 0) {
            return ['success' => false, 'message' => '用户名已被注册'];
        }

        // 检查禁止注册的关键词
        $bannedWords = Settings::get('register_banned_words', '');
        if (!empty($bannedWords)) {
            $words = array_map('trim', explode(',', $bannedWords));
            $usernameLower = mb_strtolower($data['username']);
            foreach ($words as $word) {
                if (empty($word)) continue;
                if (mb_stripos($usernameLower, mb_strtolower($word)) !== false) {
                    return ['success' => false, 'message' => '用户名包含禁止使用的词汇'];
                }
            }
        }

        // 检查邮箱（排除已删除用户，允许重新注册）
        if ($db->count('users', 'email = ? AND deleted_at IS NULL', [$data['email']]) > 0) {
            return ['success' => false, 'message' => '邮箱已被注册'];
        }
        
        // 创建用户
        $userId = $db->insert('users', [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash_hubbs($data['password']),
            'avatar' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => date('Y-m-d H:i:s'),
            'last_ip' => get_client_ip()
        ]);
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    private static function getUserById($id) {
        $db = DB::getInstance();
        return $db->fetch(
            "SELECT id, username, email, avatar, is_admin, created_at FROM {$db->table('users')} WHERE id = ? AND deleted_at IS NULL LIMIT 1",
            [$id]
        );
    }
    
    private static function setRememberToken($userId) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $db = DB::getInstance();
        $db->insert('remember_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'expires' => $expires
        ]);
        
        setcookie('hubbs_remember', $token, strtotime('+30 days'), '/', '', false, true);
    }
    
    private static function checkRememberToken() {
        $token = $_COOKIE['hubbs_remember'];
        $db = DB::getInstance();
        
        $record = $db->fetch(
            "SELECT user_id FROM {$db->table('remember_tokens')} WHERE token = ? AND expires > NOW() LIMIT 1",
            [$token]
        );
        
        if ($record) {
            $_SESSION['user_id'] = $record['user_id'];
        } else {
            setcookie('hubbs_remember', '', time() - 3600, '/');
        }
    }
}
