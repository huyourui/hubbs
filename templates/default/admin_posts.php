<?php
/**
 * HuBBS - 后台帖子管理模板
 */
$pageTitle = '帖子管理';
$action = 'posts';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-posts">
    <div class="admin-header-bar">
        <h1 class="admin-title">帖子管理</h1>
    </div>
    
    <form method="post" action="index.php?module=admin&action=posts&sub=batch" id="batchForm">
        <?php csrf_field(); ?>
        
        <div class="batch-actions">
            <select name="batch_action" id="batchAction">
                <option value="">批量操作</option>
                <option value="top">置顶</option>
                <option value="untop">取消置顶</option>
                <option value="essence">加精</option>
                <option value="unessence">取消加精</option>
                <option value="lock">锁定</option>
                <option value="unlock">解锁</option>
                <option value="delete" style="color: #ff4d4f;">删除</option>
            </select>
            <button type="submit" class="btn-primary btn-small" onclick="return confirmBatch()">执行</button>
        </div>
        
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="selectAll" onclick="toggleSelectAll()">
                        </th>
                        <th>ID</th>
                        <th>标题</th>
                        <th>作者</th>
                        <th>板块</th>
                        <th>状态</th>
                        <th>发布时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="post_ids[]" value="<?php echo $post['id']; ?>" class="post-checkbox">
                        </td>
                        <td><?php echo $post['id']; ?></td>
                        <td>
                            <a href="index.php?module=post&action=view&id=<?php echo $post['id']; ?>" target="_blank">
                                <?php e(mb_substr($post['title'], 0, 30)); ?><?php echo mb_strlen($post['title']) > 30 ? '...' : ''; ?>
                            </a>
                        </td>
                        <td><?php e($post['username']); ?></td>
                        <td><?php e($post['forum_name']); ?></td>
                        <td>
                            <?php if ($post['is_top']): ?>
                            <span class="badge badge-top">置顶</span>
                            <?php endif; ?>
                            <?php if ($post['is_essence']): ?>
                            <span class="badge badge-essence">精</span>
                            <?php endif; ?>
                            <?php if ($post['is_locked']): ?>
                            <span class="badge badge-locked">锁</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                        <td class="actions">
                            <?php if ($post['is_top']): ?>
                            <a href="index.php?module=admin&action=posts&sub=top&id=<?php echo $post['id']; ?>&is_top=0" class="btn-small btn-secondary">取消置顶</a>
                            <?php else: ?>
                            <a href="index.php?module=admin&action=posts&sub=top&id=<?php echo $post['id']; ?>&is_top=1" class="btn-small">置顶</a>
                            <?php endif; ?>
                            
                            <?php if ($post['is_essence']): ?>
                            <a href="index.php?module=admin&action=posts&sub=essence&id=<?php echo $post['id']; ?>&is_essence=0" class="btn-small btn-secondary">取消加精</a>
                            <?php else: ?>
                            <a href="index.php?module=admin&action=posts&sub=essence&id=<?php echo $post['id']; ?>&is_essence=1" class="btn-small btn-success">加精</a>
                            <?php endif; ?>
                            
                            <?php if ($post['is_locked']): ?>
                            <a href="index.php?module=admin&action=posts&sub=lock&id=<?php echo $post['id']; ?>&is_locked=0" class="btn-small btn-secondary">解锁</a>
                            <?php else: ?>
                            <a href="index.php?module=admin&action=posts&sub=lock&id=<?php echo $post['id']; ?>&is_locked=1" class="btn-small btn-danger">锁定</a>
                            <?php endif; ?>
                            
                            <a href="index.php?module=admin&action=posts&sub=delete&id=<?php echo $post['id']; ?>" 
                               class="btn-small btn-danger" 
                               onclick="return confirm('确定要删除此帖子吗？此操作不可恢复！')">删除</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    
    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrapper">
        <?php echo pagination($total, $page, 20, 'index.php?module=admin&action=posts&page='); ?>
    </div>
    <?php endif; ?>
</div>

<style>
.batch-actions {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
}

.batch-actions select {
    padding: 8px 12px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    font-size: 14px;
}

.badge-top {
    background: #ff6b6b;
    color: #fff;
}

.badge-essence {
    background: #52c41a;
    color: #fff;
}

.badge-locked {
    background: #faad14;
    color: #fff;
}

.btn-secondary {
    background: #999;
}

.btn-secondary:hover {
    background: #bbb;
}

.actions {
    white-space: nowrap;
}

.actions .btn-small {
    margin-right: 4px;
    margin-bottom: 4px;
}
</style>

<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.post-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function confirmBatch() {
    const action = document.getElementById('batchAction').value;
    if (!action) {
        alert('请选择批量操作类型');
        return false;
    }
    
    const checked = document.querySelectorAll('.post-checkbox:checked');
    if (checked.length === 0) {
        alert('请选择要操作的帖子');
        return false;
    }
    
    let msg = '确定要执行此批量操作吗？';
    if (action === 'delete') {
        msg = '确定要批量删除选中的帖子吗？此操作不可恢复！';
    }
    
    return confirm(msg);
}
</script>

<?php include __DIR__ . '/admin_footer.php'; ?>
