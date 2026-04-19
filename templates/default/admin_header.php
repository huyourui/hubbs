<?php
/**
 * HuBBS - 后台管理头部模板
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($pageTitle) ? h($pageTitle) . ' - ' : ''; ?>后台管理 - HuBBS</title>
    <link rel="stylesheet" href="<?php echo base_url('static/css/style.css?v=' . HUBBS_VERSION); ?>">
    <link rel="stylesheet" href="<?php echo base_url('static/css/admin.css?v=' . HUBBS_VERSION); ?>">
</head>
<body class="admin-body">
    <header class="admin-header">
        <div class="container">
            <div class="admin-logo">
                <a href="<?php echo base_url('index.php?module=admin'); ?>">
                    <span class="logo-icon">Hu</span><span class="logo-text">BBS</span>
                    <span class="admin-badge">后台</span>
                </a>
            </div>
            <nav class="admin-nav">
                <a href="<?php echo base_url('index.php?module=admin'); ?>" class="nav-link<?php echo $action === 'dashboard' ? ' active' : ''; ?>">概览</a>
                <a href="<?php echo base_url('index.php?module=admin&action=posts'); ?>" class="nav-link<?php echo $action === 'posts' ? ' active' : ''; ?>">帖子</a>
                <a href="<?php echo base_url('index.php?module=admin&action=users'); ?>" class="nav-link<?php echo $action === 'users' ? ' active' : ''; ?>">用户</a>
                <a href="<?php echo base_url('index.php?module=admin&action=forums'); ?>" class="nav-link<?php echo $action === 'forums' ? ' active' : ''; ?>">板块</a>
                <a href="<?php echo base_url('index.php?module=admin&action=links'); ?>" class="nav-link<?php echo $action === 'links' ? ' active' : ''; ?>">友链</a>
                <a href="<?php echo base_url('index.php?module=admin&action=mail'); ?>" class="nav-link<?php echo $action === 'mail' ? ' active' : ''; ?>">邮件</a>
                <a href="<?php echo base_url('index.php?module=admin&action=settings'); ?>" class="nav-link<?php echo $action === 'settings' ? ' active' : ''; ?>">设置</a>
                <a href="<?php echo base_url('index.php?module=admin&action=update'); ?>" class="nav-link<?php echo $action === 'update' ? ' active' : ''; ?>">更新<?php echo isset($hasUpdate) && $hasUpdate ? ' <span class="update-dot">●</span>' : ''; ?></a>
            </nav>
            <div class="admin-user">
                <span><?php e(Auth::user()['username']); ?></span>
                <a href="<?php echo base_url('index.php'); ?>" class="nav-link">前台</a>
                <a href="<?php echo base_url('index.php?module=user&action=logout'); ?>" class="nav-link">退出</a>
            </div>
        </div>
    </header>
    
    <main class="admin-main">
        <div class="container">
