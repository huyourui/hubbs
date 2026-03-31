<?php
/**
 * HuBBS - 后台友情链接表单
 */
$pageTitle = $link ? '编辑友情链接' : '添加友情链接';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1><?php echo $link ? '编辑友情链接' : '添加友情链接'; ?></h1>
        <a href="index.php?module=admin&action=links" class="btn-secondary">
            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
            返回列表
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?php e($error); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <form method="post" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="form-group">
                <label for="name">链接名称 <span class="required">*</span></label>
                <input type="text" id="name" name="name" class="form-input" 
                       value="<?php echo $link ? e($link['name']) : ''; ?>" 
                       placeholder="请输入链接名称" required>
            </div>
            
            <div class="form-group">
                <label for="url">链接地址 <span class="required">*</span></label>
                <input type="url" id="url" name="url" class="form-input" 
                       value="<?php echo $link ? e($link['url']) : ''; ?>" 
                       placeholder="https://example.com" required>
                <span class="form-hint">请填写完整的URL，包含 http:// 或 https://</span>
            </div>
            
            <div class="form-group">
                <label for="description">链接描述</label>
                <input type="text" id="description" name="description" class="form-input" 
                       value="<?php echo $link ? e($link['description']) : ''; ?>" 
                       placeholder="请输入链接描述（可选）">
            </div>
            
            <div class="form-group">
                <label for="sort_order">排序顺序</label>
                <input type="number" id="sort_order" name="sort_order" class="form-input" 
                       value="<?php echo $link ? $link['sort_order'] : '0'; ?>" 
                       placeholder="0" min="0">
                <span class="form-hint">数字越小排序越靠前，默认为0</span>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <?php echo $link ? '保存修改' : '添加链接'; ?>
                </button>
                <a href="index.php?module=admin&action=links" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.content-header h1 {
    font-size: 20px;
    font-weight: 600;
    margin: 0;
}

.form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group .required {
    color: #ff4d4f;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-input:focus {
    border-color: #ff6b6b;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
}

.form-hint {
    display: block;
    margin-top: 6px;
    font-size: 12px;
    color: #999;
}

.form-actions {
    display: flex;
    gap: 12px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fff2f0;
    border: 1px solid #ffccc7;
    color: #ff4d4f;
}

.alert-success {
    background: #f6ffed;
    border: 1px solid #b7eb8f;
    color: #52c41a;
}
</style>

<?php include __DIR__ . '/admin_footer.php'; ?>
