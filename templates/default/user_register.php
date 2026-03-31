<?php
/**
 * HuBBS - 注册模板（支持邮箱验证）
 */
$pageTitle = '用户注册';
include __DIR__ . '/header.php';

// 检查是否开放注册
$registerEnabled = Settings::isRegisterEnabled();
$step = $step ?? 1;
$email = $email ?? '';

// 检查是否开启邮箱验证
$needEmailVerify = Settings::get('mail_verify_register', '0') === '1' && Mailer::getInstance()->isEnabled();
?>

<div class="form-container">
    <div class="form-box">
        <h2 class="form-title">注册</h2>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php e($error); ?></div>
        <?php endif; ?>

        <?php if (!$registerEnabled): ?>
        <div class="alert alert-error">网站已关闭注册</div>
        <div class="form-links">
            <p><a href="index.php?module=user&action=login">返回登录</a></p>
        </div>
        <?php else: ?>

        <?php if ($needEmailVerify): ?>
        <!-- 开启邮箱验证：显示步骤指示器 -->
        <div class="register-steps">
            <div class="step <?php echo $step === 1 ? 'active' : ($step > 1 ? 'completed' : ''); ?>">
                <span class="step-num">1</span>
                <span class="step-text">填写信息</span>
            </div>
            <div class="step-line"></div>
            <div class="step <?php echo $step === 2 ? 'active' : ''; ?>">
                <span class="step-num">2</span>
                <span class="step-text">验证邮箱</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$needEmailVerify || $step === 1): ?>
        <!-- 注册表单 -->
        <form method="post" class="auth-form">
            <?php csrf_field(); ?>
            <?php if ($needEmailVerify): ?>
            <input type="hidden" name="step" value="1">
            <?php endif; ?>

            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" placeholder="2-20位，支持中英文、数字、下划线" required>
            </div>

            <div class="form-group">
                <label>邮箱</label>
                <input type="email" name="email" id="emailInput" placeholder="请输入邮箱" required>
                <span class="email-check-status" id="emailStatus"></span>
            </div>

            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="至少6位" required>
            </div>

            <div class="form-group">
                <label>确认密码</label>
                <input type="password" name="password2" placeholder="再次输入密码" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary"><?php echo $needEmailVerify ? '下一步' : '立即注册'; ?></button>
            </div>
        </form>

        <div class="form-links">
            <p>已有账号？<a href="index.php?module=user&action=login">立即登录</a></p>
        </div>

        <?php else: ?>
        <!-- 第二步：邮箱验证 -->
        <div class="verify-info">
            <p>验证码已发送至：<strong><?php e($email); ?></strong></p>
            <p class="verify-hint">请输入4位验证码完成注册</p>
        </div>

        <form method="post" class="auth-form">
            <?php csrf_field(); ?>
            <input type="hidden" name="step" value="2">

            <div class="form-group">
                <label>验证码</label>
                <input type="text" name="code" placeholder="请输入4位验证码" maxlength="4" required class="code-input">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">完成注册</button>
            </div>
        </form>

        <div class="resend-section">
            <form method="post" action="index.php?module=user&action=resend" class="resend-form">
                <?php csrf_field(); ?>
                <button type="submit" class="btn-link" id="resendBtn">重新发送验证码</button>
                <span class="countdown" id="countdown"></span>
            </form>
        </div>

        <div class="form-links">
            <p><a href="index.php?module=user&action=register">返回修改邮箱</a></p>
        </div>

        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<style>
/* 步骤指示器 */
.register-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 30px;
    padding: 0 20px;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #999;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 600;
}

.step.active .step-num {
    background: #ff6b6b;
    color: #fff;
}

.step.completed .step-num {
    background: #52c41a;
    color: #fff;
}

.step-text {
    font-size: 12px;
    color: #999;
}

.step.active .step-text {
    color: #ff6b6b;
    font-weight: 500;
}

.step-line {
    flex: 1;
    height: 2px;
    background: #e0e0e0;
    margin: 0 15px;
    margin-bottom: 20px;
    max-width: 80px;
}

/* 验证信息 */
.verify-info {
    text-align: center;
    margin-bottom: 24px;
    padding: 20px;
    background: #f6ffed;
    border-radius: 8px;
    border: 1px solid #b7eb8f;
}

.verify-info p {
    margin: 0;
    color: #333;
}

.verify-info strong {
    color: #ff6b6b;
}

.verify-hint {
    font-size: 13px;
    color: #666;
    margin-top: 8px !important;
}

/* 验证码输入 */
.code-input {
    text-align: center;
    font-size: 24px;
    letter-spacing: 8px;
    font-weight: 600;
}

/* 重新发送 */
.resend-section {
    text-align: center;
    margin-top: 20px;
}

.resend-form {
    display: inline-block;
}

.btn-link {
    background: none;
    border: none;
    color: #1890ff;
    cursor: pointer;
    font-size: 14px;
    padding: 0;
}

.btn-link:hover {
    color: #40a9ff;
    text-decoration: underline;
}

.btn-link:disabled {
    color: #999;
    cursor: not-allowed;
    text-decoration: none;
}

.countdown {
    color: #999;
    font-size: 13px;
    margin-left: 8px;
}

/* 邮箱检查状态 */
.email-check-status {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    min-height: 18px;
}

.email-check-status.checking {
    color: #666;
}

.email-check-status.valid {
    color: #52c41a;
}

.email-check-status.invalid {
    color: #ff4d4f;
}

.email-check-status .loading {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid #e0e0e0;
    border-top-color: #ff6b6b;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-right: 6px;
    vertical-align: middle;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<?php if ($step === 1): ?>
<script>
// 邮箱实时检查
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('emailInput');
    const emailStatus = document.getElementById('emailStatus');
    const form = document.querySelector('.auth-form');
    let checkTimeout = null;
    let isEmailValid = false;

    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        isEmailValid = false;

        // 清除之前的定时器
        if (checkTimeout) {
            clearTimeout(checkTimeout);
        }

        // 清空状态
        if (email === '') {
            emailStatus.textContent = '';
            emailStatus.className = 'email-check-status';
            return;
        }

        // 基本格式验证
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            emailStatus.innerHTML = '';
            emailStatus.className = 'email-check-status';
            return;
        }

        // 显示检查中状态
        emailStatus.innerHTML = '<span class="loading"></span>检查中...';
        emailStatus.className = 'email-check-status checking';

        // 延迟 500ms 后发送请求（防抖）
        checkTimeout = setTimeout(function() {
            fetch('index.php?module=user&action=check_email&email=' + encodeURIComponent(email))
                .then(response => response.json())
                .then(data => {
                    if (data.valid) {
                        emailStatus.textContent = '✓ ' + data.message;
                        emailStatus.className = 'email-check-status valid';
                        isEmailValid = true;
                    } else {
                        emailStatus.textContent = '✗ ' + data.message;
                        emailStatus.className = 'email-check-status invalid';
                        isEmailValid = false;
                    }
                })
                .catch(error => {
                    emailStatus.textContent = '';
                    emailStatus.className = 'email-check-status';
                    isEmailValid = false;
                });
        }, 500);
    });

    // 表单提交前验证邮箱
    form.addEventListener('submit', function(e) {
        const email = emailInput.value.trim();

        // 基本格式验证
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            emailStatus.textContent = '✗ 请输入正确的邮箱格式';
            emailStatus.className = 'email-check-status invalid';
            emailInput.focus();
            e.preventDefault();
            return false;
        }

        // 检查邮箱是否已被占用
        if (!isEmailValid) {
            emailStatus.textContent = '✗ 该邮箱已被注册，请更换其他邮箱';
            emailStatus.className = 'email-check-status invalid';
            emailInput.focus();
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?php endif; ?>

<?php if ($step === 2): ?>
<script>
// 重新发送倒计时
document.addEventListener('DOMContentLoaded', function() {
    const resendBtn = document.getElementById('resendBtn');
    const countdown = document.getElementById('countdown');
    let seconds = 60;

    // 禁用按钮并开始倒计时
    resendBtn.disabled = true;
    countdown.textContent = '(' + seconds + 's)';

    const timer = setInterval(function() {
        seconds--;
        if (seconds > 0) {
            countdown.textContent = '(' + seconds + 's)';
        } else {
            clearInterval(timer);
            resendBtn.disabled = false;
            countdown.textContent = '';
        }
    }, 1000);
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
