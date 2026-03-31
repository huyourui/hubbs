<?php
/**
 * HuBBS - 后台友情链接管理
 */
$pageTitle = '友情链接管理';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1>友情链接管理</h1>
        <a href="index.php?module=admin&action=links&sub=add" class="btn-primary">
            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            添加链接
        </a>
    </div>
    
    <?php $msg = get_message(); if ($msg): ?>
    <div class="alert alert-<?php echo $msg['type']; ?>"><?php e($msg['text']); ?></div>
    <?php endif; ?>
    
    <div class="links-container">
        <?php if (empty($links)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" width="64" height="64"><path fill="#ddd" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
            <p>暂无友情链接</p>
            <a href="index.php?module=admin&action=links&sub=add" class="btn-primary">添加第一个链接</a>
        </div>
        <?php else: ?>
        <form action="index.php?module=admin&action=links&sub=sort" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            
            <div class="links-list">
                <?php foreach ($links as $index => $link): ?>
                <div class="link-card">
                    <div class="link-sort">
                        <input type="number" name="sort_order[<?php echo $link['id']; ?>]" value="<?php echo $link['sort_order']; ?>" class="sort-input" min="0" title="排序">
                    </div>
                    <div class="link-info">
                        <div class="link-name">
                            <?php e($link['name']); ?>
                            <?php if ($link['is_visible']): ?>
                            <span class="status-badge status-visible">显示</span>
                            <?php else: ?>
                            <span class="status-badge status-hidden">隐藏</span>
                            <?php endif; ?>
                        </div>
                        <div class="link-meta">
                            <a href="<?php e($link['url']); ?>" target="_blank" class="link-url">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z"/></svg>
                                <?php e($link['url']); ?>
                            </a>
                            <?php if ($link['description']): ?>
                            <span class="link-desc"><?php e($link['description']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="link-actions">
                        <a href="index.php?module=admin&action=links&sub=edit&id=<?php echo $link['id']; ?>" class="btn-action btn-edit" title="编辑">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </a>
                        <a href="index.php?module=admin&action=links&sub=delete&id=<?php echo $link['id']; ?>" class="btn-action btn-delete" title="删除" onclick="return confirm('确定要删除这个友情链接吗？')">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-save-sort">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                    保存排序
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.content-header h1 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.links-container {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

/* 空状态 */
.empty-state {
    text-align: center;
    padding: 80px 20px;
}

.empty-state svg {
    margin-bottom: 16px;
}

.empty-state p {
    color: #999;
    font-size: 16px;
    margin-bottom: 24px;
}

/* 链接列表 */
.links-list {
    padding: 8px;
}

.link-card {
    display: flex;
    align-items: center;
    padding: 16px;
    margin-bottom: 8px;
    background: #fafafa;
    border-radius: 8px;
    border: 1px solid #f0f0f0;
    transition: all 0.2s;
}

.link-card:hover {
    background: #f5f5f5;
    border-color: #e0e0e0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
}

.link-card:last-child {
    margin-bottom: 0;
}

/* 排序输入 */
.link-sort {
    margin-right: 16px;
}

.sort-input {
    width: 50px;
    height: 36px;
    text-align: center;
    border: 1px solid #d9d9d9;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    transition: all 0.2s;
}

.sort-input:focus {
    border-color: #ff6b6b;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
}

/* 链接信息 */
.link-info {
    flex: 1;
    min-width: 0;
}

.link-name {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 500;
    color: #333;
    margin-bottom: 6px;
}

.link-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.link-url {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #666;
    font-size: 13px;
    text-decoration: none;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: color 0.2s;
}

.link-url:hover {
    color: #ff6b6b;
}

.link-desc {
    color: #999;
    font-size: 13px;
}

/* 状态标签 */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: normal;
}

.status-visible {
    background: #f6ffed;
    color: #52c41a;
}

.status-hidden {
    background: #f5f5f5;
    color: #999;
}

/* 操作按钮 */
.link-actions {
    display: flex;
    gap: 8px;
    margin-left: 16px;
}

.btn-action {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-edit {
    background: #e6f7ff;
    color: #1890ff;
}

.btn-edit:hover {
    background: #1890ff;
    color: #fff;
}

.btn-delete {
    background: #fff2f0;
    color: #ff4d4f;
}

.btn-delete:hover {
    background: #ff4d4f;
    color: #fff;
}

/* 保存排序按钮 */
.form-actions {
    padding: 16px;
    background: #fafafa;
    border-top: 1px solid #f0f0f0;
}

.btn-save-sort {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    background: #ff6b6b;
    color: #fff;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-save-sort:hover {
    background: #ff5252;
}

/* 响应式 */
@media (max-width: 768px) {
    .link-card {
        flex-wrap: wrap;
    }
    
    .link-actions {
        width: 100%;
        margin-left: 0;
        margin-top: 12px;
        justify-content: flex-end;
    }
    
    .link-url {
        max-width: 200px;
    }
}
</style>

<?php include __DIR__ . '/admin_footer.php'; ?>
