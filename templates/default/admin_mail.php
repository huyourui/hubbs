<?php
/**
 * HuBBS - 后台邮件设置模板
 */
$pageTitle = '邮件设置';
$action = 'mail';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-mail">
    <h1 class="admin-title">邮件设置</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php e($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?php e($error); ?></div>
    <?php endif; ?>
    
    <form method="post" class="settings-form">
        <?php csrf_field(); ?>
        
        <div class="settings-section">
            <h3>基本设置</h3>
            
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="mail_enabled" value="1" <?php echo ($settings['mail_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    <span class="checkbox-text">启用邮件功能</span>
                </label>
                <span class="form-hint">开启后可以使用邮件发送功能</span>
            </div>
            
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="mail_verify_register" value="1" <?php echo ($settings['mail_verify_register'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    <span class="checkbox-text">注册需要邮箱验证</span>
                </label>
                <span class="form-hint">开启后用户注册需要先验证邮箱</span>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>SMTP设置</h3>
            
            <div class="form-group">
                <label>邮件发送方式</label>
                <select name="mail_method" id="mailMethod">
                    <option value="smtp" <?php echo ($settings['mail_method'] ?? 'smtp') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                    <option value="sendmail" <?php echo ($settings['mail_method'] ?? '') === 'sendmail' ? 'selected' : ''; ?>>Sendmail</option>
                    <option value="mail" <?php echo ($settings['mail_method'] ?? '') === 'mail' ? 'selected' : ''; ?>>PHP Mail()</option>
                </select>
            </div>
            
            <div class="form-group" id="smtpProvider">
                <label>邮件服务商</label>
                <select name="mail_provider" id="mailProvider">
                    <option value="">自定义配置</option>
                    <option value="qq" <?php echo ($settings['mail_provider'] ?? '') === 'qq' ? 'selected' : ''; ?>>QQ邮箱</option>
                    <option value="163" <?php echo ($settings['mail_provider'] ?? '') === '163' ? 'selected' : ''; ?>>163邮箱</option>
                    <option value="126" <?php echo ($settings['mail_provider'] ?? '') === '126' ? 'selected' : ''; ?>>126邮箱</option>
                    <option value="gmail" <?php echo ($settings['mail_provider'] ?? '') === 'gmail' ? 'selected' : ''; ?>>Gmail</option>
                    <option value="outlook" <?php echo ($settings['mail_provider'] ?? '') === 'outlook' ? 'selected' : ''; ?>>Outlook</option>
                    <option value="yahoo" <?php echo ($settings['mail_provider'] ?? '') === 'yahoo' ? 'selected' : ''; ?>>Yahoo</option>
                </select>
                <span class="form-hint">选择服务商可自动填充SMTP配置</span>
            </div>
            
            <div class="smtp-settings" id="smtpSettings">
                <div class="form-row">
                    <div class="form-group half">
                        <label>SMTP服务器</label>
                        <input type="text" name="mail_host" id="mailHost" value="<?php e($settings['mail_host'] ?? ''); ?>" placeholder="如：smtp.qq.com">
                    </div>
                    <div class="form-group half">
                        <label>SMTP端口</label>
                        <input type="number" name="mail_port" id="mailPort" value="<?php e($settings['mail_port'] ?? '587'); ?>" placeholder="如：587">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>加密方式</label>
                    <select name="mail_encryption" id="mailEncryption">
                        <option value="tls" <?php echo ($settings['mail_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo ($settings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo ($settings['mail_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>无加密</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label>邮箱账号</label>
                        <input type="text" name="mail_username" value="<?php e($settings['mail_username'] ?? ''); ?>" placeholder="如：yourname@qq.com">
                    </div>
                    <div class="form-group half">
                        <label>邮箱密码/授权码</label>
                        <input type="password" name="mail_password" value="<?php e($settings['mail_password'] ?? ''); ?>" placeholder="邮箱密码或授权码">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>发件人设置</h3>
            
            <div class="form-row">
                <div class="form-group half">
                    <label>发件人邮箱</label>
                    <input type="email" name="mail_from_address" value="<?php e($settings['mail_from_address'] ?? ''); ?>" placeholder="如：noreply@example.com">
                </div>
                <div class="form-group half">
                    <label>发件人名称</label>
                    <input type="text" name="mail_from_name" value="<?php e($settings['mail_from_name'] ?? ''); ?>" placeholder="如：HuBBS论坛">
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>邮件测试</h3>
            
            <div class="form-group">
                <label>测试邮箱</label>
                <div class="test-mail-row">
                    <input type="email" name="test_email" id="testEmail" placeholder="输入邮箱地址进行测试">
                    <button type="button" class="btn-secondary" id="testMailBtn">发送测试邮件</button>
                </div>
                <span class="form-hint">保存设置前可以先发送测试邮件验证配置是否正确</span>
            </div>
            
            <div id="testResult" class="test-result" style="display: none;"></div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">保存设置</button>
        </div>
    </form>
</div>

<style>
/* 复选框样式 */
.checkbox-group {
    margin-bottom: 20px;
}

.checkbox-label {
    display: flex !important;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    margin-bottom: 5px;
}

.checkbox-label input[type="checkbox"] {
    width: 18px !important;
    height: 18px;
    margin: 0;
    cursor: pointer;
}

.checkbox-text {
    font-size: 14px;
    color: #333;
}

/* 表单行 */
.form-row {
    display: flex;
    gap: 20px;
}

.form-row .form-group {
    flex: 1;
}

.form-row .form-group.half {
    flex: 0 0 calc(50% - 10px);
}

.form-row input {
    width: 100%;
}

/* 测试邮件 */
.test-mail-row {
    display: flex;
    gap: 12px;
}

.test-mail-row input {
    flex: 1;
}

.test-mail-row .btn-secondary {
    padding: 10px 20px;
    background: #f0f0f0;
    color: #666;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
}

.test-mail-row .btn-secondary:hover {
    background: #e0e0e0;
}

.test-result {
    margin-top: 15px;
    padding: 12px 16px;
    border-radius: 4px;
    font-size: 14px;
}

.test-result.success {
    background: #f6ffed;
    border: 1px solid #b7eb8f;
    color: #52c41a;
}

.test-result.error {
    background: #fff2f0;
    border: 1px solid #ffccc7;
    color: #ff4d4f;
}
</style>

<script>
// 邮件服务商配置
const mailProviders = {
    'qq': { host: 'smtp.qq.com', port: '465', encryption: 'ssl' },
    '163': { host: 'smtp.163.com', port: '465', encryption: 'ssl' },
    '126': { host: 'smtp.126.com', port: '465', encryption: 'ssl' },
    'gmail': { host: 'smtp.gmail.com', port: '587', encryption: 'tls' },
    'outlook': { host: 'smtp.office365.com', port: '587', encryption: 'tls' },
    'yahoo': { host: 'smtp.mail.yahoo.com', port: '587', encryption: 'tls' }
};

// 切换邮件发送方式
document.getElementById('mailMethod').addEventListener('change', function() {
    const isSmtp = this.value === 'smtp';
    document.getElementById('smtpProvider').style.display = isSmtp ? 'block' : 'none';
    document.getElementById('smtpSettings').style.display = isSmtp ? 'block' : 'none';
});

// 选择邮件服务商自动填充配置
document.getElementById('mailProvider').addEventListener('change', function() {
    const provider = this.value;
    if (provider && mailProviders[provider]) {
        const config = mailProviders[provider];
        document.getElementById('mailHost').value = config.host;
        document.getElementById('mailPort').value = config.port;
        document.getElementById('mailEncryption').value = config.encryption;
    }
});

// 初始化显示状态
document.addEventListener('DOMContentLoaded', function() {
    const mailMethod = document.getElementById('mailMethod').value;
    const isSmtp = mailMethod === 'smtp';
    document.getElementById('smtpProvider').style.display = isSmtp ? 'block' : 'none';
    document.getElementById('smtpSettings').style.display = isSmtp ? 'block' : 'none';
});

// 发送测试邮件
document.getElementById('testMailBtn').addEventListener('click', function() {
    const testEmail = document.getElementById('testEmail').value;
    const resultDiv = document.getElementById('testResult');
    
    if (!testEmail) {
        resultDiv.className = 'test-result error';
        resultDiv.textContent = '请输入测试邮箱地址';
        resultDiv.style.display = 'block';
        return;
    }
    
    this.disabled = true;
    this.textContent = '发送中...';
    
    // 使用 fetch 发送 AJAX 请求
    fetch('index.php?module=admin&action=mail&sub=test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'email=' + encodeURIComponent(testEmail) + '&csrf_token=' + encodeURIComponent(document.querySelector('input[name="csrf_token"]').value)
    })
    .then(response => response.json())
    .then(data => {
        resultDiv.className = 'test-result ' + (data.success ? 'success' : 'error');
        resultDiv.textContent = data.message;
        resultDiv.style.display = 'block';
    })
    .catch(error => {
        resultDiv.className = 'test-result error';
        resultDiv.textContent = '请求失败：' + error.message;
        resultDiv.style.display = 'block';
    })
    .finally(() => {
        this.disabled = false;
        this.textContent = '发送测试邮件';
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
