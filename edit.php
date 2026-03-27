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
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    flashMessage('请先登录', 'info');
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    redirect('index.php');
}

$post = getPostById($id);

if (!$post) {
    flashMessage('帖子不存在', 'error');
    redirect('index.php');
}

if ($_SESSION['user_id'] != $post['user_id'] && !isAdmin()) {
    flashMessage('您没有权限编辑此帖子', 'error');
    redirect('post.php?id=' . $id);
}

if ($post['is_locked'] && !isAdmin()) {
    flashMessage('此帖子已被锁定，无法编辑', 'error');
    redirect('post.php?id=' . $id);
}

$errors = [];
$title = $post['title'];
$content = $post['content'];
$categoryId = $post['category_id'];

$categories = getCategories();
$maxPostLength = (int)getSetting('max_post_length', '10000');
$postImages = getPostImages($id);
$postAttachments = getPostAttachments($id);
$attachmentMaxCount = getAttachmentMaxCount();
$attachmentMaxSize = getAttachmentMaxSize();
$attachmentAllowedExts = getAttachmentAllowedExts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $imageIds = isset($_POST['image_ids']) ? array_filter(array_map('intval', explode(',', $_POST['image_ids']))) : [];
    $insertedImages = isset($_POST['inserted_images']) ? array_filter(array_map('intval', explode(',', $_POST['inserted_images']))) : [];
    $attachmentIds = isset($_POST['attachment_ids']) ? array_filter(array_map('intval', explode(',', $_POST['attachment_ids']))) : [];
    
    $originalCategoryId = $post['category_id'];
    $lostCategoryPermission = $originalCategoryId && !canUserPostInCategory($originalCategoryId, $_SESSION['user_id']);
    
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
    
    if ($lostCategoryPermission && empty($categoryId)) {
        $errors[] = '您已失去原分类的发布权限，请重新选择分类';
    }
    
    if ($categoryId && !canUserPostInCategory($categoryId, $_SESSION['user_id'])) {
        $errors[] = '您没有权限在该分类发布帖子';
    }
    
    if (empty($errors)) {
        $content = filterSensitiveWords($content);
        $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ? WHERE id = ?");
        if ($stmt->execute([$title, $content, $categoryId ?: null, $id])) {
            $stmt = $pdo->prepare("UPDATE post_images SET post_id = NULL WHERE post_id = ?");
            $stmt->execute([$id]);
            
            if (!empty($imageIds)) {
                $order = 0;
                foreach ($imageIds as $imageId) {
                    $stmt = $pdo->prepare("UPDATE post_images SET post_id = ?, sort_order = ?, is_inserted = ? WHERE id = ?");
                    $isInserted = in_array($imageId, $insertedImages) ? 1 : 0;
                    $stmt->execute([$id, $order, $isInserted]);
                    $order++;
                }
            }
            
            $stmt = $pdo->prepare("UPDATE attachments SET post_id = NULL WHERE post_id = ?");
            $stmt->execute([$id]);
            
            if (!empty($attachmentIds)) {
                foreach ($attachmentIds as $attachmentId) {
                    $stmt = $pdo->prepare("UPDATE attachments SET post_id = ? WHERE id = ?");
                    $stmt->execute([$id, $attachmentId]);
                }
            }
            
            flashMessage('帖子更新成功', 'success');
            redirect('post.php?id=' . $id);
        } else {
            $errors[] = '更新失败，请稍后重试';
        }
    }
}

render('edit', [
    'errors' => $errors,
    'title' => $title,
    'content' => $content,
    'categoryId' => $categoryId,
    'categories' => $categories,
    'postImages' => $postImages,
    'postAttachments' => $postAttachments,
    'attachmentMaxCount' => $attachmentMaxCount,
    'attachmentMaxSize' => $attachmentMaxSize,
    'attachmentAllowedExts' => $attachmentAllowedExts,
    'id' => $id,
    'pageTitle' => '编辑帖子 - ' . getSetting('site_title', SITE_NAME)
]);
