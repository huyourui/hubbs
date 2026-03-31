<?php
/**
 * HuBBS - 消息中心模板
 */
$pageTitle = '消息中心';
include __DIR__ . '/header.php';

// 获取数据库实例
$db = DB::getInstance();

// 消息类型名称
$typeNames = [
    'reply_post' => '回复帖子',
    'reply_comment' => '回复评论',
    'like_post' => '点赞帖子',
    'favorite_post' => '收藏帖子',
    'system' => '系统消息'
];

// 消息类型图标
$typeIcons = [
    'reply_post' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/></svg>',
    'reply_comment' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>',
    'like_post' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
    'favorite_post' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
    'system' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>'
];

// 消息类型颜色
$typeColors = [
    'reply_post' => '#1890ff',
    'reply_comment' => '#52c41a',
    'like_post' => '#ff6b6b',
    'favorite_post' => '#ffa726',
    'system' => '#722ed1'
];
?>

<div class="notification-page">
    <div class="container">
        <div class="notification-layout">
            <!-- 左侧筛选栏 -->
            <div class="notification-sidebar">
                <div class="sidebar-card">
                    <div class="sidebar-header">
                        <h3>消息筛选</h3>
                    </div>
                    <div class="filter-list">
                        <a href="index.php?module=notification&action=list" class="filter-item <?php echo $currentType === null && $currentIsRead === null ? 'active' : ''; ?>">
                            <span class="filter-icon all">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            </span>
                            <span class="filter-name">全部消息</span>
                            <span class="filter-count"><?php echo $typeCounts['total']; ?></span>
                        </a>
                        <a href="index.php?module=notification&action=list&is_read=0" class="filter-item <?php echo $currentIsRead === 0 ? 'active' : ''; ?>">
                            <span class="filter-icon unread">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/></svg>
                            </span>
                            <span class="filter-name">未读消息</span>
                            <?php if ($typeCounts['unread'] > 0): ?>
                            <span class="filter-count badge"><?php echo $typeCounts['unread']; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="filter-divider"></div>
                        
                        <?php foreach ($typeNames as $type => $name): ?>
                        <?php if ($typeCounts[$type] > 0): ?>
                        <a href="index.php?module=notification&action=list&type=<?php echo $type; ?>" class="filter-item <?php echo $currentType === $type ? 'active' : ''; ?>">
                            <span class="filter-icon" style="color: <?php echo $typeColors[$type]; ?>">
                                <?php echo $typeIcons[$type]; ?>
                            </span>
                            <span class="filter-name"><?php echo $name; ?></span>
                            <span class="filter-count"><?php echo $typeCounts[$type]; ?></span>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- 右侧消息列表 -->
            <div class="notification-main">
                <div class="notification-card">
                    <div class="card-header">
                        <h2>
                            <?php 
                            if ($currentType) {
                                echo $typeNames[$currentType] ?? '消息列表';
                            } elseif ($currentIsRead === 0) {
                                echo '未读消息';
                            } else {
                                echo '全部消息';
                            }
                            ?>
                        </h2>
                        <?php if ($unreadCount > 0): ?>
                        <button type="button" class="btn-mark-all" onclick="markAllRead()">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                            全部已读
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-list">
                        <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" width="64" height="64"><path fill="currentColor" d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/></svg>
                            <p>暂无消息</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>" data-id="<?php echo $notification['id']; ?>">
                            <div class="notification-icon" style="background: <?php echo $typeColors[$notification['type']] ?? '#999'; ?>20; color: <?php echo $typeColors[$notification['type']] ?? '#999'; ?>">
                                <?php echo $typeIcons[$notification['type']] ?? $typeIcons['system']; ?>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title">
                                    <?php if (!$notification['is_read']): ?>
                                    <span class="unread-dot"></span>
                                    <?php endif; ?>
                                    <?php echo h($notification['title']); ?>
                                </div>
                                <div class="notification-body">
                                    <?php echo h($notification['content']); ?>
                                </div>
                                <div class="notification-meta">
                                    <span class="notification-time"><?php echo time_ago($notification['created_at']); ?></span>
                                    <?php if ($notification['target_id'] > 0): ?>
                                    <?php
                                    // 根据消息类型构建正确的URL
                                    if ($notification['type'] === 'reply_post') {
                                        // 帖子回复：target_id 是帖子ID
                                        $targetUrl = 'index.php?module=post&action=view&id=' . $notification['target_id'];
                                    } elseif ($notification['type'] === 'reply_comment') {
                                        // 评论回复：需要获取帖子ID
                                        $replyInfo = $db->fetch("SELECT post_id FROM {$db->table('replies')} WHERE id = ?", [$notification['target_id']]);
                                        $postId = $replyInfo ? $replyInfo['post_id'] : $notification['target_id'];
                                        $targetUrl = 'index.php?module=post&action=view&id=' . $postId . '#reply-' . $notification['target_id'];
                                    } elseif ($notification['type'] === 'like_post' || $notification['type'] === 'favorite_post') {
                                        // 点赞/收藏：target_id 是帖子ID
                                        $targetUrl = 'index.php?module=post&action=view&id=' . $notification['target_id'];
                                    } else {
                                        $targetUrl = 'index.php?module=post&action=view&id=' . $notification['target_id'];
                                    }
                                    ?>
                                    <a href="<?php echo $targetUrl; ?>" class="view-link" onclick="markAsRead(<?php echo $notification['id']; ?>, event)">
                                        查看详情
                                        <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <?php if (!$notification['is_read']): ?>
                                <button type="button" class="action-btn mark-read" onclick="markAsRead(<?php echo $notification['id']; ?>)" title="标记为已读">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                </button>
                                <?php endif; ?>
                                <button type="button" class="action-btn delete" onclick="deleteNotification(<?php echo $notification['id']; ?>)" title="删除">
                                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <?php 
                        $urlPrefix = 'index.php?module=notification&action=list';
                        if ($currentType) $urlPrefix .= '&type=' . $currentType;
                        if ($currentIsRead !== null) $urlPrefix .= '&is_read=' . $currentIsRead;
                        echo pagination($typeCounts['total'], $page, 20, $urlPrefix . '&page='); 
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 标记单条消息为已读
function markAsRead(id, event) {
    if (event) {
        event.preventDefault();
        var href = event.currentTarget.href;
    }
    
    fetch('index.php?module=notification&action=markRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + id + '&csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 更新UI
            var item = document.querySelector('.notification-item[data-id="' + id + '"]');
            if (item) {
                item.classList.remove('unread');
                item.classList.add('read');
                var dot = item.querySelector('.unread-dot');
                if (dot) dot.remove();
                var btn = item.querySelector('.mark-read');
                if (btn) btn.remove();
            }
            // 更新未读数
            updateUnreadBadge(data.unreadCount);
        }
        // 跳转
        if (href) {
            window.location.href = href;
        }
    });
}

// 标记所有消息为已读
function markAllRead() {
    fetch('index.php?module=notification&action=markAllRead', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 更新所有消息为已读状态
            document.querySelectorAll('.notification-item.unread').forEach(function(item) {
                item.classList.remove('unread');
                item.classList.add('read');
                var dot = item.querySelector('.unread-dot');
                if (dot) dot.remove();
                var btn = item.querySelector('.mark-read');
                if (btn) btn.remove();
            });
            // 更新未读数
            updateUnreadBadge(0);
            // 隐藏"全部已读"按钮
            var markAllBtn = document.querySelector('.btn-mark-all');
            if (markAllBtn) markAllBtn.style.display = 'none';
        }
    });
}

// 删除消息
function deleteNotification(id) {
    if (!confirm('确定要删除这条消息吗？')) {
        return;
    }
    
    fetch('index.php?module=notification&action=delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'id=' + id + '&csrf_token=<?php echo csrf_token(); ?>'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 移除消息项
            var item = document.querySelector('.notification-item[data-id="' + id + '"]');
            if (item) {
                item.style.opacity = '0';
                setTimeout(function() {
                    item.remove();
                    // 如果没有消息了，显示空状态
                    if (document.querySelectorAll('.notification-item').length === 0) {
                        location.reload();
                    }
                }, 300);
            }
            // 更新未读数
            updateUnreadBadge(data.unreadCount);
        }
    });
}

// 更新未读消息数徽章
function updateUnreadBadge(count) {
    // 更新头部铃铛图标
    var headerBadge = document.querySelector('.notification-badge');
    if (headerBadge) {
        if (count > 0) {
            headerBadge.textContent = count > 99 ? '99+' : count;
            headerBadge.style.display = 'flex';
        } else {
            headerBadge.style.display = 'none';
        }
    }
    // 更新侧边栏未读数
    var sidebarBadge = document.querySelector('.filter-item .filter-count.badge');
    if (sidebarBadge) {
        if (count > 0) {
            sidebarBadge.textContent = count;
            sidebarBadge.style.display = 'inline-flex';
        } else {
            sidebarBadge.style.display = 'none';
        }
    }
}
</script>

<style>
/* 消息中心页面 */
.notification-page {
    padding: 20px 0 40px;
    background: #f6f6f6;
    min-height: calc(100vh - 60px);
}

.notification-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 20px;
}

/* 侧边栏 */
.notification-sidebar {
    position: sticky;
    top: 80px;
    height: fit-content;
}

.sidebar-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #f0f0f0;
}

.sidebar-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.filter-list {
    padding: 10px 0;
}

.filter-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
}

.filter-item:hover {
    background: #f5f5f5;
    color: #333;
}

.filter-item.active {
    background: #fff2f0;
    color: #ff6b6b;
    border-right: 3px solid #ff6b6b;
}

.filter-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #f5f5f5;
}

.filter-icon.all {
    background: #e6f7ff;
    color: #1890ff;
}

.filter-icon.unread {
    background: #fff2f0;
    color: #ff6b6b;
}

.filter-name {
    flex: 1;
    font-size: 14px;
}

.filter-count {
    font-size: 12px;
    color: #999;
    background: #f5f5f5;
    padding: 2px 8px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
}

.filter-count.badge {
    background: #ff6b6b;
    color: #fff;
}

.filter-divider {
    height: 1px;
    background: #f0f0f0;
    margin: 10px 20px;
}

/* 主内容区 */
.notification-main {
    min-width: 0;
}

.notification-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
}

.card-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0;
}

.btn-mark-all {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f6ffed;
    border: 1px solid #b7eb8f;
    border-radius: 6px;
    color: #52c41a;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-mark-all:hover {
    background: #d9f7be;
}

/* 消息列表 */
.notification-list {
    min-height: 300px;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 20px 24px;
    border-bottom: 1px solid #f5f5f5;
    transition: all 0.2s;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background: #fafafa;
}

.notification-item.unread {
    background: #f0f7ff;
}

.notification-item.unread:hover {
    background: #e6f4ff;
}

.notification-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 500;
    color: #333;
    margin-bottom: 6px;
}

.unread-dot {
    width: 8px;
    height: 8px;
    background: #ff6b6b;
    border-radius: 50%;
    flex-shrink: 0;
}

.notification-body {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 8px;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    color: #999;
}

.view-link {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    color: #1890ff;
    text-decoration: none;
}

.view-link:hover {
    text-decoration: underline;
}

.notification-actions {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.2s;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #999;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #f0f0f0;
}

.action-btn.mark-read:hover {
    background: #f6ffed;
    color: #52c41a;
}

.action-btn.delete:hover {
    background: #fff2f0;
    color: #ff4d4f;
}

/* 空状态 */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
    color: #ccc;
}

.empty-state svg {
    margin-bottom: 16px;
}

.empty-state p {
    font-size: 15px;
    color: #999;
    margin: 0;
}

/* 分页 */
.pagination-wrapper {
    padding: 20px 24px;
    border-top: 1px solid #f0f0f0;
}

/* 响应式 */
@media (max-width: 768px) {
    .notification-layout {
        grid-template-columns: 1fr;
    }
    
    .notification-sidebar {
        position: static;
    }
    
    .filter-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 15px;
    }
    
    .filter-item {
        padding: 8px 12px;
        border-radius: 6px;
        background: #f5f5f5;
    }
    
    .filter-item.active {
        border-right: none;
        background: #ff6b6b;
        color: #fff;
    }
    
    .filter-divider {
        display: none;
    }
    
    .notification-item {
        padding: 15px;
    }
    
    .notification-actions {
        opacity: 1;
    }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
