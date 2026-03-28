<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
require_once __DIR__ . '/../functions.php';

if (isLoggedIn()) {
    redirect('');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username)) {
        $errors[] = '请输入用户名';
    }
    
    if (empty($password)) {
        $errors[] = '请输入密码';
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // 处理保持登录
            if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30天
                
                $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_expires_at = ? WHERE id = ?");
                $stmt->execute([$token, $expiresAt, $user['id']]);
                
                setcookie('remember_token', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
            }
            
            $redirectUrl = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            
            flashMessage('欢迎回来，' . $user['username'] . '！', 'success');
            redirect($redirectUrl);
        } else {
            $errors[] = '用户名或密码错误';
        }
    }
}

renderAuth('login', [
    'errors' => $errors,
    'username' => $username,
    'pageTitle' => '登录 - ' . getSetting('site_title', SITE_NAME)
]);
