<?php
/**
 * HuBBS - 后台设置模板（Tab选项卡版）
 */
$pageTitle = '系统设置';
$action = 'settings';
include __DIR__ . '/admin_header.php';

// 获取当前选中的tab
$currentTab = $_GET['tab'] ?? 'basic';
$validTabs = ['basic', 'user', 'content', 'display', 'upload'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'basic';
}
?>

<div class="admin-settings">
    <h1 class="admin-title">系统设置</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success" id="setting-message"><?php e($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error" id="setting-message"><?php e($error); ?></div>
    <?php endif; ?>
    
    <!-- Tab选项卡 -->
    <div class="settings-tabs">
        <a href="?module=admin&action=settings&tab=basic" class="tab-item<?php echo $currentTab === 'basic' ? ' active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
            </svg>
            网站信息
        </a>
        <a href="?module=admin&action=settings&tab=user" class="tab-item<?php echo $currentTab === 'user' ? ' active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            用户注册
        </a>
        <a href="?module=admin&action=settings&tab=content" class="tab-item<?php echo $currentTab === 'content' ? ' active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            内容限制
        </a>
        <a href="?module=admin&action=settings&tab=display" class="tab-item<?php echo $currentTab === 'display' ? ' active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            显示设置
        </a>
        <a href="?module=admin&action=settings&tab=upload" class="tab-item<?php echo $currentTab === 'upload' ? ' active' : ''; ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            上传设置
        </a>
    </div>
    
    <form method="post" class="settings-form" action="?module=admin&action=settings&tab=<?php echo $currentTab; ?>">
        <?php csrf_field(); ?>
        
        <?php if ($currentTab === 'basic'): ?>
        <!-- 网站信息 -->
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
                <span class="form-hint">网站底部版权信息</span>
            </div>
            
            <div class="form-group">
                <label>网站URL</label>
                <input type="url" name="site_url" value="<?php e($settings['site_url'] ?? ''); ?>" placeholder="https://example.com">
                <span class="form-hint">网站完整URL地址，留空则自动检测</span>
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
        <?php endif; ?>
        
        <?php if ($currentTab === 'user'): ?>
        <!-- 用户注册 -->
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
                <span class="form-hint">多个关键词用逗号分隔</span>
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
        <?php endif; ?>
        
        <?php if ($currentTab === 'content'): ?>
        <!-- 内容限制 -->
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
            <h3>发布限制</h3>

            <div class="form-row">
                <div class="form-group half">
                    <label>发帖间隔时间（秒）</label>
                    <input type="number" name="post_interval" value="<?php e($settings['post_interval'] ?? '0'); ?>" min="0" max="3600">
                    <span class="form-hint">0表示不限制，建议30-60秒</span>
                </div>
                <div class="form-group half">
                    <label>评论间隔时间（秒）</label>
                    <input type="number" name="reply_interval" value="<?php e($settings['reply_interval'] ?? '0'); ?>" min="0" max="3600">
                    <span class="form-hint">0表示不限制，建议10-30秒</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($currentTab === 'display'): ?>
        <!-- 显示设置 -->
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

            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="post_list_show_excerpt" value="1" <?php echo ($settings['post_list_show_excerpt'] ?? '0') === '1' ? 'checked' : ''; ?>>
                    <span class="checkbox-text">帖子列表显示摘要</span>
                </label>
                <span class="form-hint">开启后在帖子列表显示帖子内容摘要</span>
            </div>

            <div class="form-group">
                <label>摘要字符数</label>
                <input type="number" name="post_list_excerpt_length" value="<?php e($settings['post_list_excerpt_length'] ?? '100'); ?>" min="20" max="500">
                <span class="form-hint">默认100个字符，建议50-200个字符</span>
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
        <?php endif; ?>
        
        <?php if ($currentTab === 'upload'): ?>
        <!-- 上传设置 -->
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
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn-primary">保存设置</button>
        </div>
    </form>
</div>

<style>
/* Tab选项卡样式 */
.settings-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid #e8e8e8;
    padding-bottom: 0;
    flex-wrap: wrap;
}

.tab-item {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 12px 20px;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
    border-radius: 4px 4px 0 0;
}

.tab-item:hover {
    color: #ff6b6b;
    background: #fff5f5;
}

.tab-item.active {
    color: #ff6b6b;
    border-bottom-color: #ff6b6b;
    background: #fff5f5;
}

.tab-item svg {
    flex-shrink: 0;
}

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

/* 设置区块 */
.settings-section {
    background: #fff;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
    border: 1px solid #e8e8e8;
}

.settings-section h3 {
    margin-top: 0;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 16px;
    color: #333;
}

/* 响应式 */
@media (max-width: 768px) {
    .settings-tabs {
        gap: 4px;
    }
    
    .tab-item {
        padding: 10px 14px;
        font-size: 13px;
    }
    
    .tab-item svg {
        display: none;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .form-row .form-group.half {
        flex: 1;
    }
}
</style>

<script>
// 消息提示自动消失
setTimeout(function() {
    var msg = document.getElementById('setting-message');
    if (msg) {
        msg.style.transition = 'opacity 0.5s ease';
        msg.style.opacity = '0';
        setTimeout(function() {
            msg.style.display = 'none';
        }, 500);
    }
}, 3000);
</script>

<?php include __DIR__ . '/footer.php'; ?>
