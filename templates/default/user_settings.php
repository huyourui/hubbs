<?php
/**
 * HuBBS - 用户设置页面
 */
$pageTitle = '账号设置';
include __DIR__ . '/header.php';

$avatarMaxSize = intval(Settings::get('avatar_max_size', 2097152));
$avatarMaxSizeMB = round($avatarMaxSize / 1048576, 2);
?>

<div class="settings-container">
    <div class="settings-sidebar">
        <div class="settings-menu">
            <a href="#profile" class="menu-item active" data-tab="profile">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                个人资料
            </a>
            <a href="#password" class="menu-item" data-tab="password">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                修改密码
            </a>
        </div>
    </div>

    <div class="settings-content">
        <?php if ($error): ?>
        <div class="alert alert-error"><?php e($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?php e($success); ?></div>
        <?php endif; ?>

        <!-- 个人资料 -->
        <div class="settings-section" id="profile-section">
            <h2 class="section-title">个人资料</h2>
            <form method="post" class="settings-form">
                <?php csrf_field(); ?>
                <input type="hidden" name="form_type" value="profile">

                <!-- 头像设置 -->
                <div class="form-group avatar-form-group">
                    <label>头像</label>
                    <div class="avatar-upload-wrapper">
                        <div class="current-avatar" id="avatar-container">
                            <?php if ($user['avatar']): ?>
                            <img src="<?php e($user['avatar']); ?>" alt="当前头像" id="avatar-preview">
                            <?php else: ?>
                            <?php echo render_default_avatar($user['id'], $user['username'], 'xxlarge', 'avatar-default-preview'); ?>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-upload-info">
                            <p>支持 jpg、jpeg、png、gif、webp 格式</p>
                            <p>文件大小不超过 <?php echo $avatarMaxSizeMB; ?>MB</p>
                            <p>上传后会自动压缩到宽度 300px</p>
                            <div class="upload-actions">
                                <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                                <button type="button" class="btn-secondary" onclick="document.getElementById('avatar-input').click()">更换头像</button>
                            </div>
                            <div class="upload-progress" id="upload-progress" style="display: none;">
                                <div class="progress-bar">
                                    <div class="progress-fill"></div>
                                </div>
                                <span class="progress-text">上传中...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>用户名 <span class="required">*</span></label>
                    <input type="text" name="username" value="<?php e($user['username']); ?>" required maxlength="20">
                    <span class="form-hint">2-20位，支持中英文、数字、下划线</span>
                </div>

                <div class="form-group">
                    <label>邮箱</label>
                    <input type="email" value="<?php e($user['email']); ?>" disabled class="disabled-input">
                    <span class="form-hint">邮箱不可修改</span>
                </div>

                <div class="form-group">
                    <label>个人介绍</label>
                    <textarea name="bio" rows="4" maxlength="500" placeholder="介绍一下你自己..."><?php e($user['bio'] ?? ''); ?></textarea>
                    <span class="form-hint">最多500字</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">保存修改</button>
                </div>
            </form>
        </div>

        <!-- 修改密码 -->
        <div class="settings-section" id="password-section" style="display: none;">
            <h2 class="section-title">修改密码</h2>
            <form method="post" class="settings-form">
                <?php csrf_field(); ?>
                <input type="hidden" name="form_type" value="password">

                <div class="form-group">
                    <label>当前密码 <span class="required">*</span></label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>新密码 <span class="required">*</span></label>
                    <input type="password" name="new_password" required minlength="6">
                    <span class="form-hint">至少6位</span>
                </div>

                <div class="form-group">
                    <label>确认新密码 <span class="required">*</span></label>
                    <input type="password" name="confirm_password" required minlength="6">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">修改密码</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 900px;
    margin: 20px auto;
    display: flex;
    gap: 20px;
    padding: 0 15px;
}

.settings-sidebar {
    width: 200px;
    flex-shrink: 0;
}

.settings-menu {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    color: #666;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: all 0.2s;
}

.menu-item:hover {
    background: #f5f5f5;
    color: #333;
}

.menu-item.active {
    background: #fff2f0;
    color: #ff6b6b;
    border-left-color: #ff6b6b;
}

.menu-item svg {
    flex-shrink: 0;
}

.settings-content {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    padding: 30px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eee;
}

.settings-form .form-group {
    margin-bottom: 20px;
}

.settings-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.settings-form .required {
    color: #ff4d4f;
}

.settings-form input[type="text"],
.settings-form input[type="email"],
.settings-form input[type="password"],
.settings-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.settings-form input:focus,
.settings-form textarea:focus {
    outline: none;
    border-color: #ff6b6b;
}

.settings-form .disabled-input {
    background: #f5f5f5;
    color: #999;
    cursor: not-allowed;
}

.settings-form .form-hint {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: #999;
}

.form-actions {
    margin-top: 30px;
}

.btn-primary {
    padding: 10px 24px;
    background: #ff6b6b;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-primary:hover {
    background: #ff5252;
}

/* 头像上传（整合到个人资料） */
.avatar-form-group {
    margin-bottom: 20px;
}

.avatar-upload-wrapper {
    display: flex;
    align-items: center;
    gap: 30px;
}

.current-avatar {
    flex-shrink: 0;
}

.current-avatar img,
.current-avatar svg.avatar-default-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.avatar-upload-info {
    flex: 1;
    color: #666;
    font-size: 13px;
    line-height: 1.8;
}

.avatar-upload-info p {
    margin: 4px 0;
}

.upload-actions {
    margin-top: 10px;
}

.btn-secondary {
    background: #f5f5f5;
    color: #666;
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-secondary:hover {
    background: #e8e8e8;
}

.upload-progress {
    margin-top: 10px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: #ff6b6b;
    width: 0%;
    transition: width 0.3s;
}

.progress-text {
    color: #666;
    font-size: 14px;
}

/* 响应式 */
@media (max-width: 768px) {
    .settings-container {
        flex-direction: column;
    }

    .settings-sidebar {
        width: 100%;
    }

    .settings-menu {
        display: flex;
        overflow-x: auto;
    }

    .menu-item {
        white-space: nowrap;
        border-left: none;
        border-bottom: 3px solid transparent;
    }

    .menu-item.active {
        border-left-color: transparent;
        border-bottom-color: #ff6b6b;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 标签切换
    const menuItems = document.querySelectorAll('.menu-item');
    const sections = {
        'profile': document.getElementById('profile-section'),
        'password': document.getElementById('password-section')
    };

    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.dataset.tab;

            // 更新菜单状态
            menuItems.forEach(m => m.classList.remove('active'));
            this.classList.add('active');

            // 显示对应内容
            Object.keys(sections).forEach(key => {
                sections[key].style.display = key === tab ? 'block' : 'none';
            });
        });
    });

    // 头像上传
    const avatarInput = document.getElementById('avatar-input');
    const avatarContainer = document.getElementById('avatar-container');
    const uploadProgress = document.getElementById('upload-progress');
    const progressFill = uploadProgress.querySelector('.progress-fill');
    const defaultAvatarSvg = `<?php echo $user['avatar'] ? '' : trim(preg_replace('/\s+/', ' ', render_default_avatar($user['id'], $user['username'], 'xxlarge', 'avatar-default-preview'))); ?>`;

    avatarInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        // 显示预览 - 如果是 SVG 默认头像，替换为 img
        const reader = new FileReader();
        reader.onload = function(e) {
            let avatarPreview = document.getElementById('avatar-preview');
            if (!avatarPreview) {
                // 移除 SVG，创建 img
                avatarContainer.innerHTML = '<img src="' + e.target.result + '" alt="当前头像" id="avatar-preview">';
            } else {
                avatarPreview.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);

        // 上传文件
        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('csrf_token', '<?php echo csrf_token(); ?>');

        uploadProgress.style.display = 'block';
        progressFill.style.width = '30%';

        fetch('index.php?module=user&action=upload_avatar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            progressFill.style.width = '100%';

            if (data.success) {
                setTimeout(() => {
                    uploadProgress.style.display = 'none';
                    progressFill.style.width = '0%';
                    // 更新页面上的所有头像
                    document.querySelectorAll('img[src*="avatar"]').forEach(img => {
                        img.src = data.avatar_url + '?t=' + Date.now();
                    });
                }, 500);
            } else {
                alert(data.message || '上传失败');
                uploadProgress.style.display = 'none';
                progressFill.style.width = '0%';
                // 恢复原头像
                <?php if ($user['avatar']): ?>
                document.getElementById('avatar-preview').src = '<?php e($user['avatar']); ?>';
                <?php else: ?>
                avatarContainer.innerHTML = defaultAvatarSvg;
                <?php endif; ?>
            }
        })
        .catch(error => {
            alert('上传失败，请重试');
            uploadProgress.style.display = 'none';
            progressFill.style.width = '0%';
            // 恢复原头像
            <?php if ($user['avatar']): ?>
            document.getElementById('avatar-preview').src = '<?php e($user['avatar']); ?>';
            <?php else: ?>
            avatarContainer.innerHTML = defaultAvatarSvg;
            <?php endif; ?>
        });
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
