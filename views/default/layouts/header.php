<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? escape($pageTitle) : escape(getSetting('site_title', SITE_NAME)); ?></title>
    <link href="<?php echo SITE_URL; ?>/public/assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/public/assets/css/style.css" rel="stylesheet">
    <?php if (!empty($extraStyles)): ?>
    <style><?php echo $extraStyles; ?></style>
    <?php endif; ?>
    <script>var SITE_URL = '<?php echo SITE_URL; ?>';</script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">
                <?php echo escape(getSetting('site_title', SITE_NAME)); ?>
                <?php $subtitle = getSetting('site_subtitle', ''); if ($subtitle): ?>
                    <span class="site-subtitle"><?php echo escape($subtitle); ?></span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/create.php">发帖</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/profile.php">个人中心</a>
                        </li>
                        <?php 
                        $unreadCount = getUnreadNotificationCount($_SESSION['user_id']);
                        ?>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/notifications.php">
                                消息
                                <?php if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/admin.php">管理后台</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/logout.php">退出</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">登录</a>
                        </li>
                        <?php if (getSetting('allow_register', '1') === '1'): ?>
                            <li class="nav-item">
                                <a class="btn btn-primary ms-2" href="<?php echo SITE_URL; ?>/register.php">注册</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php $flash = getFlashMessage(); if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info'); ?> alert-dismissible fade show flash-message" role="alert">
                <?php echo escape($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
