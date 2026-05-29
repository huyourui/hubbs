<?php
/**
 * HuBBS - 模板头部
 */
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="format-detection" content="telephone=no">
    <meta name="keywords" content="<?php e(Settings::get('site_keywords', 'HuBBS,论坛,开源,PHP')); ?>">
    <meta name="description" content="<?php e(Settings::get('site_description', 'HuBBS是一款轻量级开源论坛程序')); ?>">
    <title><?php echo isset($pageTitle) && $pageTitle ? h($pageTitle) . ' - ' : ''; ?><?php e(Settings::getFullTitle()); ?></title>
    <link rel="stylesheet" href="<?php echo base_url('static/css/style.css?v=' . HUBBS_VERSION); ?>">
    <style>
        /* 搜索框样式 */
        .search-box {
            margin: 0 20px;
            flex: 1;
            max-width: 400px;
        }
        
        .search-form {
            display: flex;
            align-items: center;
            background: #f5f5f5;
            border-radius: 25px;
            padding: 4px;
            transition: all 0.2s;
        }
        
        .search-form:focus-within {
            background: #fff;
            box-shadow: 0 0 0 2px rgba(255, 107, 107, 0.2);
        }
        
        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 8px 15px;
            font-size: 14px;
            outline: none;
        }
        
        .search-input::placeholder {
            color: #999;
        }
        
        .search-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: #ff6b6b;
            color: #fff;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .search-btn:hover {
            background: #ff5252;
            transform: scale(1.05);
        }
        
        @media (max-width: 768px) {
            .search-box {
                order: 3;
                width: 100%;
                max-width: none;
                margin: 10px 0 0 0;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <a href="<?php echo base_url(); ?>">
                    <span class="logo-icon">Hu</span><span class="logo-text">BBS</span>
                </a>
            </div>
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="菜单">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <nav class="nav" id="mobileNav">
                <a href="<?php echo base_url(); ?>" class="nav-link<?php echo (isset($action) && $action === 'list' && empty($forumId)) ? ' active' : ''; ?>">首页</a>
                <a href="<?php echo base_url('index.php?module=post&action=create'); ?>" class="nav-link">发帖</a>
            </nav>
            
            <!-- 搜索框 -->
            <div class="search-box">
                <form action="<?php echo base_url('index.php?module=search&action=index'); ?>" method="get" class="search-form">
                    <input type="hidden" name="module" value="search">
                    <input type="hidden" name="action" value="index">
                    <input type="text" name="keyword" class="search-input" placeholder="搜索帖子..." value="<?php echo isset($_GET['keyword']) ? h($_GET['keyword']) : ''; ?>" required>
                    <button type="submit" class="search-btn" aria-label="搜索">
                        <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                    </button>
                </form>
            </div>
            <div class="user-nav">
                <?php if (Auth::check()): 
                    $currentUser = Auth::user();
                    $unreadCount = Notification::getUnreadCount($currentUser['id']);
                ?>
                    <!-- 消息通知图标 -->
                    <a href="<?php echo base_url('index.php?module=notification&action=list'); ?>" class="notification-bell <?php echo $unreadCount > 0 ? 'has-unread' : ''; ?>">
                        <svg viewBox="0 0 24 24" width="22" height="22">
                            <path fill="currentColor" d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                        <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <span class="user-name"><?php echo $currentUser ? e($currentUser['username']) : '用户'; ?></span>
                    <?php if (Auth::isAdmin()): ?>
                    <a href="<?php echo base_url('index.php?module=admin'); ?>" class="nav-link" style="color: #ff6b6b;">后台</a>
                    <?php endif; ?>
                    <a href="<?php echo base_url('index.php?module=user&action=profile'); ?>" class="nav-link">个人中心</a>
                    <a href="<?php echo base_url('index.php?module=user&action=settings'); ?>" class="nav-link">账号设置</a>
                    <a href="<?php echo base_url('index.php?module=user&action=logout'); ?>" class="nav-link">退出</a>
                <?php else: ?>
                    <a href="<?php echo base_url('index.php?module=user&action=login'); ?>" class="nav-link">登录</a>
                    <a href="<?php echo base_url('index.php?module=user&action=register'); ?>" class="nav-link btn-small">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <?php $msg = get_message(); if ($msg): ?>
    <div class="message message-<?php echo $msg['type']; ?>">
        <div class="container"><?php e($msg['text']); ?></div>
    </div>
    <?php endif; ?>
    
    <main class="main">
        <div class="container">

<script>
// 移动端菜单切换
function toggleMobileMenu() {
    const nav = document.getElementById('mobileNav');
    nav.classList.toggle('active');
}

// 点击页面其他地方关闭菜单
document.addEventListener('click', function(e) {
    const nav = document.getElementById('mobileNav');
    const btn = document.querySelector('.mobile-menu-btn');
    if (!nav.contains(e.target) && !btn.contains(e.target)) {
        nav.classList.remove('active');
    }
});

// 窗口大小改变时关闭菜单
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        document.getElementById('mobileNav').classList.remove('active');
    }
});
</script>
