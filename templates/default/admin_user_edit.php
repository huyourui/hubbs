<?php
/**
 * HuBBS - 后台用户编辑模板
 */
$pageTitle = '编辑用户';
$action = 'users';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-user-edit">
    <h1 class="admin-title">编辑用户</h1>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?php e($error); ?></div>
    <?php endif; ?>
    
    <form method="post" class="admin-form">
        <?php csrf_field(); ?>
        
        <div class="form-group">
            <label>用户ID</label>
            <input type="text" value="<?php echo $user['id']; ?>" disabled>
        </div>
        
        <div class="form-group">
            <label>用户名</label>
            <input type="text" name="username" value="<?php e($user['username']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>邮箱</label>
            <input type="email" name="email" value="<?php e($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>新密码</label>
            <input type="password" name="password" placeholder="不修改请留空">
            <span class="form-hint">如需修改密码请输入新密码</span>
        </div>
        
        <div class="form-group form-checkbox">
            <label>
                <input type="checkbox" name="is_admin" value="1" <?php echo $user['is_admin'] ? 'checked' : ''; ?>>
                <span>设为管理员</span>
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn-primary">保存</button>
            <a href="index.php?module=admin&action=users" class="btn-secondary">取消</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/admin_footer.php'; ?>
