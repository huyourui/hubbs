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
<div class="card">
    <div class="card-body">
        <h4 class="card-title text-center mb-4">登录</h4>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo escape($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">用户名 / 邮箱</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo escape($username); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">保持登录</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">登录</button>
        </form>

        <div class="text-center mt-3">
            <?php if (getSetting('allow_register', '1') === '1'): ?>
                还没有账号？ <a href="<?php echo SITE_URL; ?>/pages/register.php">立即注册</a>
            <?php endif; ?>
        </div>
    </div>
</div>
