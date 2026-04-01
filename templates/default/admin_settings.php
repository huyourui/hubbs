<?php
/**
 * HuBBS - 后台设置模板
 */
$pageTitle = '系统设置';
$action = 'settings';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-settings">
    <h1 class="admin-title">系统设置</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php e($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?php e($error); ?></div>
    <?php endif; ?>
    
    <form method="post" class="settings-form">
        <?php csrf_field(); ?>
        
        <div class="settings-section">
            <h3>网站信息</h3>
            
            <div class="form-group">
                <label>网站标题</label>
                <input type="text" name="site_title" value="<?php e($settings['site_title'] ?? 'HuBBS'); ?>" required>
                <span class="form-hint">显示在浏览器标签和页面头部</span>
            </div>
            
            <div class="form-group">
                <label>网站副标题</label>
                <input type="text" name="site_subtitle" value="<?php e($settings['site_subtitle'] ?? ''); ?>">
                <span class="form-hint">显示在网站标题后面，格式：标题 - 副标题</span>
            </div>
            
            <div class="form-group">
                <label>关键词</label>
                <input type="text" name="site_keywords" value="<?php e($settings['site_keywords'] ?? ''); ?>">
                <span class="form-hint">多个关键词用英文逗号分隔，用于SEO</span>
            </div>
            
            <div class="form-group">
                <label>网站描述</label>
                <textarea name="site_description" rows="3"><?php e($settings['site_description'] ?? ''); ?></textarea>
                <span class="form-hint">网站简介，用于SEO和首页展示</span>
            </div>
            
            <div class="form-group">
                <label>版权信息</label>
                <input type="text" name="site_copyright" value="<?php e($settings['site_copyright'] ?? 'HuBBS - 开源论坛程序 v' . HUBBS_VERSION); ?>">
                <span class="form-hint">网站底部版权信息，如：HuBBS - 开源论坛程序 v<?php echo HUBBS_VERSION; ?></span>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>功能设置</h3>
            
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_register" value="1" <?php echo ($settings['enable_register'] ?? '1') === '1' ? 'checked' : ''; ?>>
                    <span class="checkbox-text">开放用户注册</span>
                </label>
                <span class="form-hint">关闭后新用户将无法注册</span>
            </div>
            
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_force_forum" value="1" <?php echo ($settings['is_force_forum'] ?? '1') === '1' ? 'checked' : ''; ?>>
                    <span class="checkbox-text">强制选择分类</span>
                </label>
                <span class="form-hint">开启后用户发帖必须选择分类</span>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>注册限制</h3>
            
            <div class="form-group">
                <label>允许注册的邮箱后缀</label>
                <input type="text" name="register_email_suffix" value="<?php e($settings['register_email_suffix'] ?? ''); ?>">
                <span class="form-hint">多个后缀用逗号分隔，如：qq.com,gmail.com。留空表示不限制</span>
            </div>
            
            <div class="form-group">
                <label>禁止注册的用户名关键词</label>
                <input type="text" name="register_banned_words" value="<?php e($settings['register_banned_words'] ?? 'admin,root,system,管理员'); ?>">
                <span class="form-hint">多个关键词用逗号分隔，用户名包含这些词将被禁止注册</span>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label>用户名最短字符数</label>
                    <input type="number" name="username_min_length" value="<?php e($settings['username_min_length'] ?? '2'); ?>" min="1" max="10">
                </div>
                <div class="form-group half">
                    <label>用户名最长字符数</label>
                    <input type="number" name="username_max_length" value="<?php e($settings['username_max_length'] ?? '20'); ?>" min="5" max="50">
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>内容限制</h3>
            
            <div class="form-row">
                <div class="form-group half">
                    <label>帖子最小字符数</label>
                    <input type="number" name="post_min_length" value="<?php e($settings['post_min_length'] ?? '5'); ?>" min="1">
                </div>
                <div class="form-group half">
                    <label>帖子最大字符数</label>
                    <input type="number" name="post_max_length" value="<?php e($settings['post_max_length'] ?? '10000'); ?>" min="100">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group half">
                    <label>回复最小字符数</label>
                    <input type="number" name="reply_min_length" value="<?php e($settings['reply_min_length'] ?? '2'); ?>" min="1">
                </div>
                <div class="form-group half">
                    <label>回复最大字符数</label>
                    <input type="number" name="reply_max_length" value="<?php e($settings['reply_max_length'] ?? '5000'); ?>" min="100">
                </div>
            </div>
        </div>
        
        <div class="settings-section">
            <h3>显示设置</h3>

            <div class="form-row">
                <div class="form-group half">
                    <label>首页每页帖子数</label>
                    <input type="number" name="posts_per_page" value="<?php e($settings['posts_per_page'] ?? '20'); ?>" min="5" max="100">
                </div>
                <div class="form-group half">
                    <label>帖子页每页回复数</label>
                    <input type="number" name="replies_per_page" value="<?php e($settings['replies_per_page'] ?? '20'); ?>" min="5" max="100">
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>发布限制</h3>

            <div class="form-row">
                <div class="form-group half">
                    <label>发帖间隔时间（秒）</label>
                    <input type="number" name="post_interval" value="<?php e($settings['post_interval'] ?? '0'); ?>" min="0" max="3600">
                    <span class="form-hint">0表示不限制，建议设置为30-60秒防止刷屏</span>
                </div>
                <div class="form-group half">
                    <label>评论间隔时间（秒）</label>
                    <input type="number" name="reply_interval" value="<?php e($settings['reply_interval'] ?? '0'); ?>" min="0" max="3600">
                    <span class="form-hint">0表示不限制，建议设置为10-30秒防止刷屏</span>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>图片上传设置</h3>

            <div class="form-group">
                <label>允许的图片后缀</label>
                <input type="text" name="upload_image_exts" value="<?php e($settings['upload_image_exts'] ?? 'jpg,jpeg,png,gif,webp'); ?>">
                <span class="form-hint">多个后缀用英文逗号分隔，如：jpg,png,gif</span>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>单张图片最大大小（MB）</label>
                    <input type="number" name="upload_image_max_size_mb" value="<?php echo intval(($settings['upload_image_max_size'] ?? 5242880) / 1048576); ?>" min="1" max="50">
                    <span class="form-hint">默认5MB，最大50MB</span>
                </div>
                <div class="form-group half">
                    <label>单篇帖子最多上传图片数</label>
                    <input type="number" name="upload_image_max_count" value="<?php e($settings['upload_image_max_count'] ?? '10'); ?>" min="1" max="50">
                    <span class="form-hint">建议设置为10-20张</span>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>附件上传设置</h3>

            <div class="form-group">
                <label>允许的附件类型</label>
                <input type="text" name="upload_attachment_exts" value="<?php e($settings['upload_attachment_exts'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,md'); ?>">
                <span class="form-hint">多个后缀用英文逗号分隔，如：pdf,doc,zip</span>
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <label>单个附件最大大小（MB）</label>
                    <input type="number" name="upload_attachment_max_size_mb" value="<?php echo intval(($settings['upload_attachment_max_size'] ?? 10485760) / 1048576); ?>" min="1" max="100">
                    <span class="form-hint">默认10MB，最大100MB</span>
                </div>
                <div class="form-group half">
                    <label>单篇帖子最多上传附件数</label>
                    <input type="number" name="upload_attachment_max_count" value="<?php e($settings['upload_attachment_max_count'] ?? '5'); ?>" min="1" max="20">
                    <span class="form-hint">建议设置为5-10个</span>
                </div>
            </div>
        </div>

        <div class="settings-section">
            <h3>头像设置</h3>

            <div class="form-group">
                <label>头像最大大小（MB）</label>
                <input type="number" name="avatar_max_size_mb" value="<?php echo intval(($settings['avatar_max_size'] ?? 2097152) / 1048576); ?>" min="1" max="10">
                <span class="form-hint">默认2MB，最大10MB。上传后会自动压缩到宽度300px</span>
            </div>
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
</style>

<?php include __DIR__ . '/footer.php'; ?>
