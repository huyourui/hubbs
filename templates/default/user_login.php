<?php
/**
 * HuBBS - 登录模板
 */
$pageTitle = '用户登录';
include __DIR__ . '/header.php';
?>

<div class="form-container">
    <div class="form-box">
        <h2 class="form-title">登录</h2>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php e($error); ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <?php csrf_field(); ?>
            
            <div class="form-group">
                <label>用户名/邮箱</label>
                <input type="text" name="username" placeholder="请输入用户名或邮箱" required autofocus>
            </div>
            
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            
            <div class="form-group form-checkbox">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    <span>记住我</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">登录</button>
            </div>
        </form>
        
        <div class="form-links">
            <p>还没有账号？<a href="index.php?module=user&action=register">立即注册</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
