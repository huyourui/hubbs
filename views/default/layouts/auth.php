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
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .auth-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .auth-card {
            width: 100%;
            max-width: 400px;
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .auth-header .logo {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            text-decoration: none;
        }
    </style>
    <?php if (isset($extraStyles)): ?>
    <style><?php echo $extraStyles; ?></style>
    <?php endif; ?>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="<?php echo SITE_URL; ?>/index.php" class="logo"><?php echo escape(getSetting('site_title', SITE_NAME)); ?></a>
            </div>
            <?php $flash = getFlashMessage(); if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'info'); ?> alert-dismissible fade show" role="alert">
                    <?php echo escape($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php echo $viewContent; ?>
        </div>
    </div>

    <footer class="bg-white py-4 mt-auto border-top">
        <div class="container">
            <div class="text-center text-muted">
                <p class="mb-0">
                    Powered by <a href="https://huyourui.com" class="text-decoration-none" target="_blank">HuBBS</a> 
                    <span class="mx-2">|</span> 
                    &copy; <?php echo date('Y'); ?> <?php echo escape(getSetting('site_title', SITE_NAME)); ?>
                </p>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/public/assets/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraScripts)): ?>
    <script><?php echo $extraScripts; ?></script>
    <?php endif; ?>
</body>
</html>
