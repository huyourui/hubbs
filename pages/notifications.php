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
    $_SESSION['redirect_after_login'] = 'notifications.php';
    flashMessage('请先登录', 'info');
    redirect('pages/login.php');
}

if (isset($_GET['mark_read'])) {
    $notificationId = (int)$_GET['mark_read'];
    markNotificationAsRead($notificationId, $_SESSION['user_id']);
    flashMessage('已标记为已读', 'success');
    redirect('pages/notifications.php');
}

if (isset($_GET['mark_all_read'])) {
    markAllNotificationsAsRead($_SESSION['user_id']);
    flashMessage('已全部标记为已读', 'success');
    redirect('pages/notifications.php');
}

if (isset($_GET['delete'])) {
    $notificationId = (int)$_GET['delete'];
    deleteNotification($notificationId, $_SESSION['user_id']);
    flashMessage('通知已删除', 'success');
    redirect('pages/notifications.php');
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$notifications = getUserNotifications($_SESSION['user_id'], $perPage, $offset);
$unreadCount = getUnreadNotificationCount($_SESSION['user_id']);

render('notifications', [
    'notifications' => $notifications,
    'unreadCount' => $unreadCount,
    'page' => $page,
    'pageTitle' => '消息中心 - ' . getSetting('site_title', SITE_NAME)
]);
