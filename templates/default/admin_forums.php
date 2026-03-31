<?php
/**
 * HuBBS - 后台板块管理模板
 */
$pageTitle = '板块管理';
$action = 'forums';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-forums">
    <div class="admin-header-bar">
        <h1 class="admin-title">板块管理</h1>
        <a href="index.php?module=admin&action=forums&sub=add" class="btn-primary">+ 添加分类</a>
    </div>
    
    <form method="post" action="index.php?module=admin&action=forums&sub=sort" class="forums-sort-form">
        <?php csrf_field(); ?>
        
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">排序</th>
                        <th>分类名称</th>
                        <th>类型</th>
                        <th>描述</th>
                        <th>帖子数</th>
                        <th style="width: 150px;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forums as $forum): ?>
                    <!-- 一级分类 -->
                    <tr class="forum-parent">
                        <td>
                            <input type="number" name="sort_order[<?php echo $forum['id']; ?>]" 
                                   value="<?php echo $forum['sort_order']; ?>" class="sort-input">
                        </td>
                        <td>
                            <strong><?php e($forum['name']); ?></strong>
                        </td>
                        <td><span class="badge badge-primary">一级分类</span></td>
                        <td><?php echo $forum['description'] ? e(mb_substr($forum['description'], 0, 30)) : '-'; ?></td>
                        <td><?php echo $forum['post_count']; ?></td>
                        <td class="actions">
                            <a href="index.php?module=admin&action=forums&sub=edit&id=<?php echo $forum['id']; ?>" class="btn-small">编辑</a>
                            <a href="index.php?module=admin&action=forums&sub=delete&id=<?php echo $forum['id']; ?>" 
                               class="btn-small btn-danger" 
                               onclick="return confirm('确定要删除此分类吗？')">删除</a>
                        </td>
                    </tr>
                    
                    <!-- 二级分类 -->
                    <?php if (isset($children[$forum['id']])): ?>
                        <?php foreach ($children[$forum['id']] as $child): ?>
                        <tr class="forum-child">
                            <td>
                                <input type="number" name="sort_order[<?php echo $child['id']; ?>]" 
                                       value="<?php echo $child['sort_order']; ?>" class="sort-input">
                            </td>
                            <td>
                                <span class="child-indent">└─ <?php e($child['name']); ?></span>
                            </td>
                            <td><span class="badge badge-secondary">二级分类</span></td>
                            <td><?php echo $child['description'] ? e(mb_substr($child['description'], 0, 30)) : '-'; ?></td>
                            <td><?php echo $child['post_count']; ?></td>
                            <td class="actions">
                                <a href="index.php?module=admin&action=forums&sub=edit&id=<?php echo $child['id']; ?>" class="btn-small">编辑</a>
                                <a href="index.php?module=admin&action=forums&sub=delete&id=<?php echo $child['id']; ?>" 
                                   class="btn-small btn-danger" 
                                   onclick="return confirm('确定要删除此分类吗？')">删除</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="btn-primary">保存排序</button>
        </div>
    </form>
</div>

<style>
.admin-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.forum-parent {
    background: #fafafa;
}

.forum-child {
    background: #fff;
}

.child-indent {
    padding-left: 20px;
    color: #666;
}

.sort-input {
    width: 60px;
    padding: 6px;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    text-align: center;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
}

.badge-primary {
    background: #e6f7ff;
    color: #1890ff;
}

.badge-secondary {
    background: #f6ffed;
    color: #52c41a;
}

.btn-small {
    display: inline-block;
    padding: 4px 12px;
    background: #1890ff;
    color: #fff;
    border-radius: 4px;
    font-size: 13px;
    margin-right: 5px;
}

.btn-small:hover {
    background: #40a9ff;
    color: #fff;
}

.btn-danger {
    background: #ff4d4f;
}

.btn-danger:hover {
    background: #ff7875;
}
</style>

<?php include __DIR__ . '/admin_footer.php'; ?>
