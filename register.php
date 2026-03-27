<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
require_once __DIR__ . '/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

if (getSetting('allow_register', '1') !== '1') {
    flashMessage('系统已关闭新用户注册', 'error');
    redirect('login.php');
}

$emailVerifyEnabled = getSetting('email_verify_register', '0') === '1';
$inviteOnly = getSetting('invite_only', '0') === '1';
$errors = [];
$username = '';
$email = '';
$verifyCode = '';
$inviteCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_code' && $emailVerifyEnabled) {
        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? '');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址']);
            exit;
        }
        
        if (!isEmailDomainAllowed($email)) {
            $allowedDomains = getAllowedEmailDomainsList();
            if (empty($allowedDomains)) {
                echo json_encode(['success' => false, 'message' => '当前不允许任何邮箱注册']);
            } else {
                echo json_encode(['success' => false, 'message' => '仅允许以下邮箱后缀注册： ' . implode(', ', $allowedDomains)]);
            }
            exit;
        }
        
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => '该邮箱已被注册']);
            exit;
        }
        
        if (!isSmtpEnabled()) {
            echo json_encode(['success' => false, 'message' => '邮件服务未启用，请联系管理员']);
            exit;
        }
        
        $code = generateVerifyCode(4);
        $_SESSION['register_verify_code'] = $code;
        $_SESSION['register_verify_email'] = $email;
        $_SESSION['register_verify_time'] = time();
        
        $result = sendRegisterVerifyCodeEmail($email, $code);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => '验证码已发送，请查收邮件']);
        } else {
            echo json_encode(['success' => false, 'message' => '验证码发送失败: ' . $result['error']]);
        }
        exit;
    }
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $verifyCode = trim($_POST['verify_code'] ?? '');
    $inviteCode = trim($_POST['invite_code'] ?? '');
    
    if (mb_strlen($username) < MIN_USERNAME_LENGTH) {
        $errors[] = '用户名至少需要 ' . MIN_USERNAME_LENGTH . ' 个字符';
    }
    
    if (mb_strlen($username) > MAX_USERNAME_LENGTH) {
        $errors[] = '用户名不能超过 ' . MAX_USERNAME_LENGTH . ' 个字符';
    }
    
    if (!preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]+$/u', $username)) {
        $errors[] = '用户名只能包含中文、字母、数字、下划线';
    }
    
    if (containsForbiddenUsername($username)) {
        $errors[] = '该用户名包含禁止使用的字符';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '请输入有效的邮箱地址';
    } elseif (!isEmailDomainAllowed($email)) {
        $allowedDomains = getAllowedEmailDomainsList();
        if (empty($allowedDomains)) {
            $errors[] = '当前不允许任何邮箱注册';
        } else {
            $errors[] = '仅允许以下邮箱后缀注册: ' . implode(', ', $allowedDomains);
        }
    }
    
    if ($emailVerifyEnabled) {
        if (empty($verifyCode)) {
            $errors[] = '请输入邮箱验证码';
        } elseif (!isset($_SESSION['register_verify_code']) || 
                  !isset($_SESSION['register_verify_email']) ||
                  $_SESSION['register_verify_email'] !== $email) {
            $errors[] = '验证码无效，请重新获取';
        } elseif (!isset($_SESSION['register_verify_time']) || 
                  time() - $_SESSION['register_verify_time'] > 600) {
            $errors[] = '验证码已过期，请重新获取';
        } elseif ($verifyCode !== $_SESSION['register_verify_code']) {
            $errors[] = '验证码错误';
        }
    }
    
    if ($inviteOnly) {
        if (empty($inviteCode)) {
            $errors[] = '请输入邀请码';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM invite_codes WHERE code = ? AND is_used = 0");
            $stmt->execute([$inviteCode]);
            if (!$stmt->fetch()) {
                $errors[] = '邀请码无效或已被使用';
            }
        }
    }
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = '密码至少需要 ' . MIN_PASSWORD_LENGTH . ' 个字符';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = '两次输入的密码不一致';
    }
    
    if (empty($errors)) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = '用户名已被使用';
        }
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = '邮箱已被注册';
        }
    }
    
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $hashedPassword])) {
            $userId = $pdo->lastInsertId();
            
            if ($inviteOnly && !empty($inviteCode)) {
                $stmt = $pdo->prepare("DELETE FROM invite_codes WHERE code = ? AND is_used = 0");
                $stmt->execute([$inviteCode]);
            }
            
            unset($_SESSION['register_verify_code']);
            unset($_SESSION['register_verify_email']);
            unset($_SESSION['register_verify_time']);
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['user_role'] = 'user';
            
            flashMessage('注册成功，欢迎加入！', 'success');
            redirect('index.php');
        } else {
            $errors[] = '注册失败，请稍后重试';
        }
    }
}

renderAuth('register', [
    'errors' => $errors,
    'username' => $username,
    'email' => $email,
    'verifyCode' => $verifyCode,
    'inviteCode' => $inviteCode,
    'emailVerifyEnabled' => $emailVerifyEnabled,
    'pageTitle' => '注册 - ' . getSetting('site_title', SITE_NAME)
]);
