<?php
/**
 * HuBBS - 后台板块编辑模板
 */
$pageTitle = $isEdit ? '编辑分类' : '添加分类';
$action = 'forums';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-forum-edit">
    <h1 class="admin-title"><?php echo $isEdit ? '编辑分类' : '添加分类'; ?></h1>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?php e($error); ?></div>
    <?php endif; ?>
    
    <form method="post" class="admin-form">
        <?php csrf_field(); ?>
        
        <div class="form-group">
            <label>分类名称 <span class="required">*</span></label>
            <input type="text" name="name" value="<?php e($forum['name'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>父分类</label>
            <select name="parent_id">
                <option value="0">作为一级分类</option>
                <?php foreach ($parentForums as $parent): ?>
                <option value="<?php echo $parent['id']; ?>" <?php echo ($forum['parent_id'] ?? 0) == $parent['id'] ? 'selected' : ''; ?>>
                    <?php e($parent['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="form-hint">选择父分类则创建为二级分类</span>
        </div>
        
        <div class="form-group">
            <label>描述</label>
            <textarea name="description" rows="3"><?php e($forum['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>图标</label>
            <div class="icon-input-wrapper">
                <input type="text" name="icon" value="<?php e($forum['icon'] ?? ''); ?>" placeholder="例如：💬 或 chat" class="icon-input">
                <div class="icon-preview" id="iconPreview">
                    <?php echo !empty($forum['icon']) ? $forum['icon'] : '📁'; ?>
                </div>
            </div>
            <span class="form-hint">支持 Emoji 图标（如 💬 🔥 📚 等）或文字标识</span>
        </div>
        
        <div class="form-group">
            <label>排序</label>
            <input type="number" name="sort_order" value="<?php echo $forum['sort_order'] ?? 0; ?>" min="0">
            <span class="form-hint">数字越小排序越靠前</span>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary"><?php echo $isEdit ? '保存' : '添加'; ?></button>
            <a href="index.php?module=admin&action=forums" class="btn-secondary">取消</a>
        </div>
    </form>
</div>

<style>
.admin-form {
    max-width: 600px;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.admin-form .form-group {
    margin-bottom: 24px;
}

.admin-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.admin-form .required {
    color: #ff4d4f;
}

.admin-form input[type="text"],
.admin-form input[type="number"],
.admin-form select,
.admin-form textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    font-size: 14px;
}

.admin-form input:focus,
.admin-form select:focus,
.admin-form textarea:focus {
    outline: none;
    border-color: #1890ff;
}

.admin-form .form-hint {
    display: block;
    margin-top: 6px;
    font-size: 13px;
    color: #999;
}

.admin-form .form-actions {
    margin-top: 30px;
    display: flex;
    gap: 12px;
}

.admin-form .btn-secondary {
    padding: 10px 24px;
    background: #f0f0f0;
    color: #666;
    border-radius: 4px;
}

.admin-form .btn-secondary:hover {
    background: #e0e0e0;
}

/* 图标输入框样式 */
.icon-input-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.icon-input {
    flex: 1;
    font-size: 16px;
}

.icon-preview {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    font-size: 24px;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

/* Emoji 选择器 */
.emoji-picker {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.emoji-item {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s;
    background: #fff;
    border: 1px solid #e8e8e8;
}

.emoji-item:hover {
    background: #e6f7ff;
    border-color: #1890ff;
    transform: scale(1.1);
}

.emoji-label {
    font-size: 12px;
    color: #999;
    margin-top: 8px;
    margin-bottom: 4px;
}
</style>

<script>
// 图标预览实时更新
document.addEventListener('DOMContentLoaded', function() {
    const iconInput = document.querySelector('.icon-input');
    const iconPreview = document.getElementById('iconPreview');
    
    if (iconInput && iconPreview) {
        iconInput.addEventListener('input', function() {
            const value = this.value.trim();
            iconPreview.textContent = value || '📁';
        });
    }
});
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
