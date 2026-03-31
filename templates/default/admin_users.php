<?php
/**
 * HuBBS - 后台用户管理模板
 */
$pageTitle = '用户管理';
$action = 'users';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-users">
    <div class="admin-header-bar">
        <h1 class="admin-title">用户管理</h1>
    </div>
    
    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>状态</th>
                    <th>角色</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php e($user['username']); ?></td>
                    <td><?php e($user['email']); ?></td>
                    <td>
                        <?php if (!empty($user['deleted_at'])): ?>
                        <span class="badge badge-gray">已注销</span>
                        <?php elseif ($user['status'] == 1): ?>
                        <span class="badge badge-success">正常</span>
                        <?php else: ?>
                        <span class="badge badge-danger">封禁</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user['is_admin']): ?>
                        <span class="badge badge-primary">管理员</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">普通用户</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                    <td class="actions">
                        <?php if (empty($user['deleted_at'])): ?>
                            <a href="index.php?module=admin&action=users&sub=edit&id=<?php echo $user['id']; ?>" class="btn-small">编辑</a>
                            <?php if ($user['id'] != Auth::id()): ?>
                                <?php if ($user['status'] == 1): ?>
                                <a href="index.php?module=admin&action=users&sub=ban&id=<?php echo $user['id']; ?>&status=0" 
                                   class="btn-small btn-warning" 
                                   onclick="return confirm('确定要封禁该用户吗？')">封禁</a>
                                <?php else: ?>
                                <a href="index.php?module=admin&action=users&sub=ban&id=<?php echo $user['id']; ?>&status=1" 
                                   class="btn-small btn-success">解封</a>
                                <?php endif; ?>
                                <a href="index.php?module=admin&action=users&sub=delete&id=<?php echo $user['id']; ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirm('确定要注销该用户吗？用户将不能登录，但其发布的内容仍会保留。')">注销</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="index.php?module=admin&action=users&sub=restore&id=<?php echo $user['id']; ?>" 
                               class="btn-small btn-success">恢复</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrapper">
        <?php echo pagination($total, $page, 20, 'index.php?module=admin&action=users&page='); ?>
    </div>
    <?php endif; ?>
</div>

<style>
.badge-success {
    background: #f6ffed;
    color: #52c41a;
}

.badge-danger {
    background: #fff2f0;
    color: #ff4d4f;
}

.badge-primary {
    background: #e6f7ff;
    color: #1890ff;
}

.badge-secondary {
    background: #f5f5f5;
    color: #666;
}

.btn-warning {
    background: #faad14;
}

.btn-warning:hover {
    background: #ffc53d;
}

.btn-success {
    background: #52c41a;
}

.btn-success:hover {
    background: #73d13d;
}

.badge-gray {
    background: #f5f5f5;
    color: #999;
}
</style>

<?php include __DIR__ . '/admin_footer.php'; ?>
