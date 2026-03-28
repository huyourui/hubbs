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

// 清除remember_token
if (isLoggedIn() && isset($pdo)) {
    $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, remember_expires_at = NULL WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// 清除cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

session_destroy();
session_start();
flashMessage('您已成功退出登录', 'info');
redirect('pages/login.php');
