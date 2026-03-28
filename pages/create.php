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

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'create.php';
    flashMessage('请先登录', 'info');
    redirect('pages/login.php');
}

$errors = [];
$title = '';
$content = '';
$categoryId = '';

$categories = getCategories();
$maxPostLength = (int)getSetting('max_post_length', '10000');
$attachmentMaxCount = getAttachmentMaxCount();
$attachmentMaxSize = getAttachmentMaxSize();
$allowedAttachmentExts = getAttachmentAllowedExts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $imageIds = isset($_POST['image_ids']) ? array_filter(array_map('intval', explode(',', $_POST['image_ids']))) : [];
    $insertedImages = isset($_POST['inserted_images']) ? array_filter(array_map('intval', explode(',', $_POST['inserted_images']))) : [];
    $attachmentIds = isset($_POST['attachment_ids']) ? array_filter(array_map('intval', explode(',', $_POST['attachment_ids']))) : [];
    
    if (empty($title)) {
        $errors[] = '请输入标题';
    } elseif (mb_strlen($title) > 255) {
        $errors[] = '标题不能超过255个字符';
    }
    
    if (empty($content)) {
        $errors[] = '请输入内容';
    } elseif (mb_strlen(strip_tags($content)) > $maxPostLength) {
        $errors[] = '内容超过最大字数限制（' . $maxPostLength . '字）';
    }
    
    if (getSetting('require_category', '0') === '1' && empty($categoryId)) {
        $errors[] = '请选择分类';
    }
    
    if ($categoryId && !canPostInCategory($categoryId)) {
        $errors[] = '该分类有子分类，请选择具体的子分类';
    }
    
    if ($categoryId && !canUserPostInCategory($categoryId, $_SESSION['user_id'])) {
        $errors[] = '您没有权限在该分类发布帖子';
    }
    
    $remainingTime = checkPostInterval($_SESSION['user_id']);
    if ($remainingTime > 0) {
        $errors[] = '发帖过于频繁，请等待 ' . $remainingTime . ' 秒后再试';
    }
    
    if (count($attachmentIds) > $attachmentMaxCount) {
        $errors[] = '附件数量超过限制（最多 ' . $attachmentMaxCount . ' 个）';
    }
    
    if (empty($errors)) {
        $content = filterSensitiveWords($content);
        $ipAddress = getClientIP();
        $stmt = $pdo->prepare("INSERT INTO posts (title, content, user_id, category_id, ip_address) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$title, $content, $_SESSION['user_id'], $categoryId ?: null, $ipAddress])) {
            $postId = $pdo->lastInsertId();
            
            if (!empty($imageIds)) {
                attachImagesToPost($postId, $imageIds);
                
                if (!empty($insertedImages)) {
                    global $pdo;
                    $placeholders = implode(',', array_fill(0, count($insertedImages), '?'));
                    $stmt = $pdo->prepare("UPDATE post_images SET is_inserted = 1 WHERE id IN ($placeholders) AND post_id = ?");
                    $params = array_merge($insertedImages, [$postId]);
                    $stmt->execute($params);
                }
            }
            
            if (!empty($attachmentIds)) {
                attachFilesToPost($postId, $attachmentIds);
            }
            
            addPoints($_SESSION['user_id'], 'create_post', 'post', $postId, '发布帖子：' . mb_substr($title, 0, 50));
            flashMessage('帖子发布成功', 'success');
            redirect('pages/post.php?id=' . $postId);
        } else {
            $errors[] = '发布失败，请稍后重试';
        }
    }
}

render('create', [
    'errors' => $errors,
    'title' => $title,
    'content' => $content,
    'categoryId' => $categoryId,
    'categories' => $categories,
    'attachmentMaxCount' => $attachmentMaxCount,
    'attachmentMaxSize' => $attachmentMaxSize,
    'attachmentAllowedExts' => $attachmentAllowedExts,
    'pageTitle' => '发帖 - ' . getSetting('site_title', SITE_NAME)
]);
