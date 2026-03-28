<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
$extraStyles = <<<CSS
.notification-item { padding: 1rem; border-bottom: 1px solid #dee2e6; transition: background-color 0.15s; }
.notification-item:hover { background-color: #f8f9fa; }
.notification-item.unread { background-color: #e7f1ff; border-left: 3px solid #0d6efd; }
.notification-item:last-child { border-bottom: none; }
.notification-type { font-size: 0.75rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; }
.notification-time { font-size: 0.875rem; color: #6c757d; }
CSS;
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">消息中心</h4>
                <div>
                    <?php if ($unreadCount > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/pages/notifications.php?mark_all_read=1" class="btn btn-sm btn-outline-primary">全部标为已读</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">暂无消息</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="notification-type bg-light text-dark"><?php echo getNotificationTypeLabel($notification['type']); ?></span>
                                <small class="notification-time"><?php echo formatTime($notification['created_at']); ?></small>
                            </div>
                            <h6 class="mb-1"><?php echo escape($notification['title']); ?></h6>
                            <?php if ($notification['content']): ?>
                                <p class="mb-2 text-muted small"><?php echo escape($notification['content']); ?></p>
                            <?php endif; ?>
                            <div class="mt-2">
                                <?php 
                                $data = $notification['data'] ? json_decode($notification['data'], true) : null;
                                if ($data && isset($data['post_id'])): 
                                ?>
                                    <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $data['post_id']; ?>" class="btn btn-sm btn-outline-primary">查看帖子</a>
                                <?php endif; ?>
                                <?php if (!$notification['is_read']): ?>
                                    <a href="<?php echo SITE_URL; ?>/pages/notifications.php?mark_read=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-secondary">标为已读</a>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/pages/notifications.php?delete=<?php echo $notification['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此通知吗？')">删除</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
