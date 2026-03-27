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

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'profile.php';
    flashMessage('请先登录', 'info');
    redirect('login.php');
}

$currentUserId = $_SESSION['user_id'];
$viewUserId = isset($_GET['user']) ? (int)$_GET['user'] : $currentUserId;
$isOwnProfile = ($viewUserId === $currentUserId);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$viewUserId]);
$viewUser = $stmt->fetch();

if (!$viewUser) {
    flashMessage('用户不存在', 'error');
    redirect('index.php');
}

$errors = [];
$success = false;

if ($isOwnProfile && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim($_POST['bio'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (mb_strlen($bio) > 500) {
        $errors[] = '个人简介不能超过500个字符';
    }
    
    if (!empty($newPassword)) {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $userPassword = $stmt->fetchColumn();
        
        if (!password_verify($currentPassword, $userPassword)) {
            $errors[] = '当前密码错误';
        } elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
            $errors[] = '新密码至少需要 ' . MIN_PASSWORD_LENGTH . ' 个字符';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = '两次输入的新密码不一致';
        }
    }
    
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarPath = uploadAvatar($_FILES['avatar']);
        if ($avatarPath) {
            $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
            $stmt->execute([$avatarPath, $_SESSION['user_id']]);
        } else {
            $errors[] = '头像上传失败，请检查文件格式和大小';
        }
    }
    
    if (empty($errors)) {
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET bio = ?, password = ? WHERE id = ?");
            $stmt->execute([$bio, $hashedPassword, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
            $stmt->execute([$bio, $_SESSION['user_id']]);
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $viewUser = $stmt->fetch();
        $success = true;
        flashMessage('个人资料已更新', 'success');
    }
}

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 10");
$stmt->execute([$viewUserId]);
$myPosts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT c.*, p.title as post_title FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 10");
$stmt->execute([$viewUserId]);
$myComments = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
$stmt->execute([$viewUserId]);
$postCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
$stmt->execute([$viewUserId]);
$commentCount = $stmt->fetchColumn();

$myFavorites = $isOwnProfile ? getUserFavorites($currentUserId, 10) : [];
$favoriteCount = $isOwnProfile ? getUserFavoriteCount($currentUserId) : 0;

$pointLogs = $isOwnProfile ? getUserPointLogs($currentUserId, 20) : [];
$pointLogCount = $isOwnProfile ? getUserPointLogCount($currentUserId) : 0;

render('profile', [
    'user' => $viewUser,
    'errors' => $errors,
    'myPosts' => $myPosts,
    'myComments' => $myComments,
    'myFavorites' => $myFavorites,
    'postCount' => $postCount,
    'commentCount' => $commentCount,
    'favoriteCount' => $favoriteCount,
    'pointLogs' => $pointLogs,
    'pointLogCount' => $pointLogCount,
    'isOwnProfile' => $isOwnProfile,
    'pageTitle' => $viewUser['username'] . ' 的个人中心 - ' . getSetting('site_title', SITE_NAME)
]);
