<?php
/**
 * HuBBS - 后台友情链接表单
 * 
 * @package HuBBS
 * @version 1.7.5
 */
$pageTitle = $link ? '编辑友情链接' : '添加友情链接';
$action = 'links';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1><?php echo $link ? '编辑友情链接' : '添加友情链接'; ?></h1>
        <a href="<?php echo base_url('index.php?module=admin&action=links'); ?>" class="btn-secondary">
            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            返回列表
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
        <?php e($error); ?>
    </div>
    <?php endif; ?>
    
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-icon">
                <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
            </div>
            <div class="form-title"><?php echo $link ? '编辑链接信息' : '填写链接信息'; ?></div>
            <div class="form-subtitle">请填写友情链接的基本信息，带 <span class="required">*</span> 的为必填项</div>
        </div>
        
        <form method="post" class="link-form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="form-row">
                <div class="form-group form-group-large">
                    <label for="name">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                        链接名称 <span class="required">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-input" 
                           value="<?php echo $link ? e($link['name']) : ''; ?>" 
                           placeholder="例如：HuBBS官网" required>
                    <span class="form-hint">显示在网站上的链接文字</span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group form-group-large">
                    <label for="url">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
                        链接地址 <span class="required">*</span>
                    </label>
                    <div class="input-with-prefix">
                        <span class="input-prefix">https://</span>
                        <input type="url" id="url" name="url" class="form-input" 
                               value="<?php echo $link ? e($link['url']) : ''; ?>" 
                               placeholder="example.com" required>
                    </div>
                    <span class="form-hint">请填写完整的URL地址，包含 http:// 或 https://</span>
                </div>
            </div>
            
            <div class="form-row two-columns">
                <div class="form-group">
                    <label for="description">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                        链接描述
                    </label>
                    <input type="text" id="description" name="description" class="form-input" 
                           value="<?php echo $link ? e($link['description']) : ''; ?>" 
                           placeholder="简短描述该网站内容">
                    <span class="form-hint">鼠标悬停时显示的提示文字</span>
                </div>
                
                <div class="form-group">
                    <label for="sort_order">
                        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 18h6v-2H3v2zM3 6v2h18V6H3zm0 7h12v-2H3v2z"/></svg>
                        排序顺序
                    </label>
                    <input type="number" id="sort_order" name="sort_order" class="form-input" 
                           value="<?php echo $link ? $link['sort_order'] : '0'; ?>" 
                           placeholder="0" min="0">
                    <span class="form-hint">数字越小排序越靠前</span>
                </div>
            </div>
            
            <div class="form-divider"></div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    <?php echo $link ? '保存修改' : '添加链接'; ?>
                </button>
                <a href="<?php echo base_url('index.php?module=admin&action=links'); ?>" class="btn-cancel">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                    取消
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* 内容头部 */
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.content-header h1 {
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 18px;
    background: #fff;
    border: 1px solid #d9d9d9;
    border-radius: 8px;
    color: #595959;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    border-color: #ff6b6b;
    color: #ff6b6b;
}

/* 提示框 */
.alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 14px;
}

.alert svg {
    flex-shrink: 0;
}

.alert-error {
    background: linear-gradient(135deg, #fff2f0 0%, #fff5f5 100%);
    border: 1px solid #ffccc7;
    color: #cf1322;
}

.alert-success {
    background: linear-gradient(135deg, #f6ffed 0%, #f0f9eb 100%);
    border: 1px solid #b7eb8f;
    color: #389e0d;
}

/* 表单卡片 */
.form-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    overflow: hidden;
}

.form-card-header {
    padding: 32px 32px 24px;
    background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
    border-bottom: 1px solid #f0f0f0;
    text-align: center;
}

.form-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.form-title {
    font-size: 18px;
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 6px;
}

.form-subtitle {
    font-size: 13px;
    color: #8c8c8c;
}

.form-subtitle .required {
    color: #ff4d4f;
}

/* 表单主体 */
.link-form {
    padding: 32px;
}

.form-row {
    margin-bottom: 24px;
}

.form-row.two-columns {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group-large {
    max-width: 100%;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 10px;
    font-size: 14px;
    font-weight: 500;
    color: #262626;
}

.form-group label svg {
    color: #8c8c8c;
}

.required {
    color: #ff4d4f;
}

/* 输入框 */
.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    font-size: 14px;
    color: #262626;
    background: #fff;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-input:hover {
    border-color: #bfbfbf;
}

.form-input:focus {
    border-color: #ff6b6b;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
}

.form-input::placeholder {
    color: #bfbfbf;
}

/* 带前缀的输入框 */
.input-with-prefix {
    display: flex;
    align-items: center;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    background: #fff;
    transition: all 0.2s ease;
}

.input-with-prefix:focus-within {
    border-color: #ff6b6b;
    box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
}

.input-prefix {
    padding: 12px 0 12px 16px;
    color: #8c8c8c;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
}

.input-with-prefix .form-input {
    border: none;
    box-shadow: none;
    padding-left: 4px;
}

.input-with-prefix .form-input:focus {
    box-shadow: none;
}

/* 提示文字 */
.form-hint {
    margin-top: 8px;
    font-size: 12px;
    color: #8c8c8c;
    line-height: 1.5;
}

/* 分隔线 */
.form-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, #f0f0f0 20%, #f0f0f0 80%, transparent 100%);
    margin: 32px 0;
}

/* 表单操作按钮 */
.form-actions {
    display: flex;
    align-items: center;
    gap: 16px;
}

.btn-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 32px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
}

.btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(255, 107, 107, 0.4);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    background: #f5f5f5;
    border: 1px solid #d9d9d9;
    border-radius: 10px;
    color: #595959;
    font-size: 15px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: #fafafa;
    border-color: #bfbfbf;
    color: #262626;
}

/* 响应式 */
@media (max-width: 768px) {
    .form-row.two-columns {
        grid-template-columns: 1fr;
    }
    
    .link-form {
        padding: 24px 20px;
    }
    
    .form-card-header {
        padding: 24px 20px 20px;
    }
    
    .form-actions {
        flex-direction: column-reverse;
    }
    
    .btn-submit,
    .btn-cancel {
        width: 100%;
    }
    
    .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<?php include __DIR__ . '/admin_footer.php'; ?>
