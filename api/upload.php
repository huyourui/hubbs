<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * 文件上传API接口
 * 处理图片和附件的上传、删除、列表等操作
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */

/* 引入核心函数库（包含会话和数据库初始化） */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

try {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => '请先登录']);
        exit;
    }

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'upload':
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $result = uploadImage($_FILES['image'], $_SESSION['user_id']);
                echo json_encode($result);
                exit;
            }
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $result = uploadAttachment($_FILES['attachment'], $_SESSION['user_id']);
                echo json_encode($result);
                exit;
            }
            
            echo json_encode(['success' => false, 'error' => '请选择要上传的文件']);
            exit;

        case 'delete':
            $imageId = (int)($_POST['image_id'] ?? 0);
            
            if ($imageId > 0) {
                $result = deleteImage($imageId, $_SESSION['user_id']);
                echo json_encode(['success' => $result]);
                exit;
            }
            
            $attachmentId = (int)($_POST['attachment_id'] ?? 0);
            if ($attachmentId > 0) {
                $result = deleteAttachment($attachmentId, $_SESSION['user_id']);
                echo json_encode(['success' => $result]);
                exit;
            }
            
            echo json_encode(['success' => false, 'error' => '无效的ID']);
            exit;

        case 'delete_attachment':
            $attachmentId = (int)($_POST['attachment_id'] ?? 0);
            
            if ($attachmentId <= 0) {
                echo json_encode(['success' => false, 'error' => '无效的附件ID']);
                exit;
            }
            
            $result = deleteAttachment($attachmentId, $_SESSION['user_id']);
            echo json_encode(['success' => $result]);
            exit;

        case 'list':
            $postId = (int)($_GET['post_id'] ?? 0);
            
            if ($postId > 0) {
                $images = getPostImages($postId);
                $result = [];
                foreach ($images as $img) {
                    $result[] = [
                        'id' => $img['id'],
                        'url' => SITE_URL . '/' . $img['filepath'],
                        'thumb_url' => SITE_URL . '/' . $img['thumbpath'],
                        'width' => $img['width'],
                        'height' => $img['height'],
                        'is_inserted' => $img['is_inserted']
                    ];
                }
                echo json_encode(['success' => true, 'images' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => '无效的帖子ID']);
            }
            exit;

        case 'config':
            echo json_encode([
                'success' => true,
                'config' => [
                    'max_size' => getMaxImageSize(),
                    'max_size_text' => round(getMaxImageSize() / 1024 / 1024, 1) . 'MB',
                    'max_width' => getMaxImageWidth(),
                    'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
                ]
            ]);
            exit;

        default:
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $result = uploadImage($_FILES['image'], $_SESSION['user_id']);
                echo json_encode($result);
                exit;
            }
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $result = uploadAttachment($_FILES['attachment'], $_SESSION['user_id']);
                echo json_encode($result);
                exit;
            }
            
            echo json_encode(['success' => false, 'error' => '未知操作']);
            exit;
    }
} catch (Exception $e) {
    error_log('Upload API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '服务器错误: ' . $e->getMessage()]);
}
