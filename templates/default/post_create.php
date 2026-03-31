<?php
/**
 * HuBBS - 发帖模板
 */
$pageTitle = '发布新帖';
include __DIR__ . '/header.php';

$isForceForum = Settings::get('is_force_forum', '1') === '1';
?>

<div class="post-create-container">
    <div class="post-create-box">
        <h2 class="post-create-title">发布新帖</h2>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php e($error); ?></div>
        <?php endif; ?>
        
        <form method="post" class="post-create-form">
            <?php csrf_field(); ?>
            
            <div class="form-group">
                <label>选择板块 <?php if ($isForceForum): ?><span class="required">*</span><?php endif; ?></label>
                <select name="forum_id" <?php echo $isForceForum ? 'required' : ''; ?>>
                    <option value="">请选择板块</option>
                    <?php foreach ($parentForums as $parent): ?>
                        <?php if (isset($childForums[$parent['id']])): ?>
                            <!-- 有子分类的一级分类，显示为分组 -->
                            <optgroup label="<?php e($parent['name']); ?>">
                                <?php foreach ($childForums[$parent['id']] as $child): ?>
                                <option value="<?php echo $child['id']; ?>" <?php echo ($formData['forum_id'] ?? '') == $child['id'] ? 'selected' : ''; ?>>
                                    └─ <?php e($child['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php else: ?>
                            <!-- 没有子分类的一级分类，可以直接选择 -->
                            <option value="<?php echo $parent['id']; ?>" <?php echo ($formData['forum_id'] ?? '') == $parent['id'] ? 'selected' : ''; ?>><?php e($parent['name']); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>标题 <span class="required">*</span></label>
                <input type="text" name="title" placeholder="请输入标题" required maxlength="100" value="<?php e($formData['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>内容 <span class="required">*</span></label>
                <div id="editor-container"></div>
                <textarea name="content" id="content-textarea" style="display:none;" required><?php e($formData['content'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">发布</button>
                <a href="index.php" class="btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

<style>
.required {
    color: #ff4d4f;
}

optgroup {
    font-weight: 600;
    color: #333;
}

optgroup option {
    font-weight: normal;
    color: #666;
    padding-left: 10px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.post-create-form');
    const textarea = document.getElementById('content-textarea');
    const savedContent = textarea.value;

    // 初始化编辑器
    const editor = new HubbsEditor('#editor-container', {
        placeholder: '请输入内容...',
        onChange: function(value) {
            textarea.value = value;
        }
    });

    // 如果有保存的内容，设置到编辑器
    if (savedContent) {
        editor.setValue(savedContent);
    }

    // 表单提交时同步内容
    form.addEventListener('submit', function() {
        textarea.value = editor.getValue();
    });

    // Ctrl+Enter 提交 - 监听编辑器的自定义提交事件
    const editorContainer = document.getElementById('editor-container');
    editorContainer.addEventListener('editorSubmit', function(e) {
        e.preventDefault();
        textarea.value = editor.getValue();
        form.submit();
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
