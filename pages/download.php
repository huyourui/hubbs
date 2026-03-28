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

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    flashMessage('无效的附件ID', 'error');
    redirect('');
}

$attachment = getAttachmentById($id);

if (!$attachment) {
    flashMessage('附件不存在', 'error');
    redirect('');
}

if (!isLoggedIn() && !isAttachmentGuestDownload()) {
    flashMessage('请先登录', 'info');
    $_SESSION['redirect_after_login'] = 'post.php?id=' . $attachment['post_id'];
    redirect('pages/login.php');
}

if (!file_exists($attachment['file_path'])) {
    flashMessage('文件不存在', 'error');
    redirect('pages/post.php?id=' . $attachment['post_id']);
}

incrementAttachmentDownload($id);

header('Content-Description: File Transfer');
header('Content-Type: ' . $attachment['mime_type']);
header('Content-Disposition: attachment; filename="' . basename($attachment['original_name']) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $attachment['file_size']);

readfile($attachment['file_path']);
exit;
