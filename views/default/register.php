<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
$inviteOnly = getSetting('invite_only', '0') === '1';
?>
<div class="card">
    <div class="card-body">
        <h4 class="card-title text-center mb-4">注册账号</h4>

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
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo escape($username); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">邮箱</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($email); ?>" required>
            </div>
            <?php if ($emailVerifyEnabled): ?>
            <div class="mb-3">
                <label for="verify_code" class="form-label">邮箱验证码</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="verify_code" name="verify_code" maxlength="4" placeholder="请输入4位验证码" value="<?php echo escape($verifyCode ?? ''); ?>" required>
                    <button class="btn btn-outline-primary" type="button" id="sendCodeBtn">发送验证码</button>
                </div>
                <small class="text-muted">验证码有效期为10分钟</small>
            </div>
            <?php endif; ?>
            <?php if ($inviteOnly): ?>
            <div class="mb-3">
                <label for="invite_code" class="form-label">邀请码 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="invite_code" name="invite_code" value="<?php echo escape($inviteCode ?? ''); ?>" placeholder="请输入邀请码" required>
                <small class="text-muted">本站开启邀请码注册，请输入有效的邀请码</small>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">确认密码</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">注册</button>
        </form>

        <div class="text-center mt-3">
            已有账号？ <a href="<?php echo SITE_URL; ?>/pages/login.php">立即登录</a>
        </div>
    </div>
</div>

<?php if ($emailVerifyEnabled): ?>
<?php
$extraScripts = <<<'JS'
document.getElementById('sendCodeBtn').addEventListener('click', function() {
    var btn = this;
    var emailInput = document.getElementById('email');
    var email = emailInput.value.trim();
    
    if (!email) {
        alert('请先输入邮箱地址');
        emailInput.focus();
        return;
    }
    
    btn.disabled = true;
    btn.textContent = '发送中...';
    
    var formData = new FormData();
    formData.append('action', 'send_code');
    formData.append('email', email);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            var countdown = 60;
            btn.textContent = countdown + '秒后重试';
            var timer = setInterval(function() {
                countdown--;
                if (countdown <= 0) {
                    clearInterval(timer);
                    btn.disabled = false;
                    btn.textContent = '发送验证码';
                } else {
                    btn.textContent = countdown + '秒后重试';
                }
            }, 1000);
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.textContent = '发送验证码';
        }
    })
    .catch(function() {
        alert('发送失败，请稍后重试');
        btn.disabled = false;
        btn.textContent = '发送验证码';
    });
});
JS;
?>
<?php endif; ?>
