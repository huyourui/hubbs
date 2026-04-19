<?php
/**
 * HuBBS - 通知服务层
 * 
 * 负责处理消息通知相关的业务逻辑
 * 
 * @package HuBBS
 * @version 1.7.3
 */

class NotificationService 
{
    /**
     * @var DB 数据库实例
     */
    private $db;
    
    /**
     * 构造函数
     */
    public function __construct() 
    {
        $this->db = DB::getInstance();
    }
    
    /**
     * 发送帖子回复通知
     * 
     * @param int $postId 帖子ID
     * @param int $toUserId 接收者用户ID
     * @param int $fromUserId 发送者用户ID
     * @param string $fromUsername 发送者用户名
     * @param string $postTitle 帖子标题
     * @return bool 发送结果
     */
    public function sendPostReply(int $postId, int $toUserId, int $fromUserId, string $fromUsername, string $postTitle): bool
    {
        if ($toUserId === $fromUserId) {
            return false;
        }
        
        try {
            // 创建通知
            $this->db->insert('notifications', [
                'user_id' => $toUserId,
                'sender_id' => $fromUserId,
                'type' => 'reply_post',
                'title' => '帖子回复',
                'content' => "{$fromUsername} 回复了你的帖子《{$postTitle}》",
                'target_id' => $postId,
                'target_type' => 'post',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // 增加未读计数
            $this->incrementUnreadCount($toUserId);
            
            return true;
        } catch (Exception $e) {
            error_log("发送帖子回复通知失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 发送评论回复通知
     * 
     * @param int $replyId 回复ID
     * @param int $toUserId 接收者用户ID
     * @param int $fromUserId 发送者用户ID
     * @param string $fromUsername 发送者用户名
     * @param string $postTitle 帖子标题
     * @return bool 发送结果
     */
    public function sendCommentReply(int $replyId, int $toUserId, int $fromUserId, string $fromUsername, string $postTitle): bool
    {
        if ($toUserId === $fromUserId) {
            return false;
        }
        
        try {
            $this->db->insert('notifications', [
                'user_id' => $toUserId,
                'sender_id' => $fromUserId,
                'type' => 'reply_comment',
                'title' => '评论回复',
                'content' => "{$fromUsername} 回复了你在《{$postTitle}》中的评论",
                'target_id' => $replyId,
                'target_type' => 'reply',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->incrementUnreadCount($toUserId);
            
            return true;
        } catch (Exception $e) {
            error_log("发送评论回复通知失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 发送帖子点赞通知
     * 
     * @param int $postId 帖子ID
     * @param int $toUserId 接收者用户ID
     * @param int $fromUserId 发送者用户ID
     * @param string $fromUsername 发送者用户名
     * @param string $postTitle 帖子标题
     * @return bool 发送结果
     */
    public function sendPostLike(int $postId, int $toUserId, int $fromUserId, string $fromUsername, string $postTitle): bool
    {
        if ($toUserId === $fromUserId) {
            return false;
        }
        
        try {
            $this->db->insert('notifications', [
                'user_id' => $toUserId,
                'sender_id' => $fromUserId,
                'type' => 'like_post',
                'title' => '帖子点赞',
                'content' => "{$fromUsername} 赞了你的帖子《{$postTitle}》",
                'target_id' => $postId,
                'target_type' => 'post',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->incrementUnreadCount($toUserId);
            
            return true;
        } catch (Exception $e) {
            error_log("发送点赞通知失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 发送帖子收藏通知
     * 
     * @param int $postId 帖子ID
     * @param int $toUserId 接收者用户ID
     * @param int $fromUserId 发送者用户ID
     * @param string $fromUsername 发送者用户名
     * @param string $postTitle 帖子标题
     * @return bool 发送结果
     */
    public function sendPostFavorite(int $postId, int $toUserId, int $fromUserId, string $fromUsername, string $postTitle): bool
    {
        if ($toUserId === $fromUserId) {
            return false;
        }
        
        try {
            $this->db->insert('notifications', [
                'user_id' => $toUserId,
                'sender_id' => $fromUserId,
                'type' => 'favorite_post',
                'title' => '帖子收藏',
                'content' => "{$fromUsername} 收藏了你的帖子《{$postTitle}》",
                'target_id' => $postId,
                'target_type' => 'post',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->incrementUnreadCount($toUserId);
            
            return true;
        } catch (Exception $e) {
            error_log("发送收藏通知失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 发送系统通知
     * 
     * @param int $toUserId 接收者用户ID
     * @param string $title 通知标题
     * @param string $content 通知内容
     * @return bool 发送结果
     */
    public function sendSystemNotification(int $toUserId, string $title, string $content): bool
    {
        try {
            $this->db->insert('notifications', [
                'user_id' => $toUserId,
                'sender_id' => 0,
                'type' => 'system',
                'title' => $title,
                'content' => $content,
                'target_id' => 0,
                'target_type' => null,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->incrementUnreadCount($toUserId);
            
            return true;
        } catch (Exception $e) {
            error_log("发送系统通知失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取用户通知列表
     * 
     * @param int $userId 用户ID
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array 通知列表和分页信息
     */
    public function getNotifications(int $userId, int $page = 1, int $perPage = 20): array
    {
        $total = $this->db->count('notifications', 'user_id = ?', [$userId]);
        $offset = ($page - 1) * $perPage;
        
        $notifications = $this->db->fetchAll(
            "SELECT n.*, u.username as sender_name, u.avatar as sender_avatar 
             FROM {$this->db->table('notifications')} n 
             LEFT JOIN {$this->db->table('users')} u ON n.sender_id = u.id 
             WHERE n.user_id = ? 
             ORDER BY n.created_at DESC 
             LIMIT {$offset}, {$perPage}",
            [$userId]
        );
        
        return [
            'notifications' => $notifications,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取未读通知列表
     * 
     * @param int $userId 用户ID
     * @param int $limit 限制数量
     * @return array 未读通知列表
     */
    public function getUnreadNotifications(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT n.*, u.username as sender_name, u.avatar as sender_avatar 
             FROM {$this->db->table('notifications')} n 
             LEFT JOIN {$this->db->table('users')} u ON n.sender_id = u.id 
             WHERE n.user_id = ? AND n.is_read = 0 
             ORDER BY n.created_at DESC 
             LIMIT {$limit}",
            [$userId]
        );
    }
    
    /**
     * 标记通知为已读
     * 
     * @param int $notificationId 通知ID
     * @param int $userId 用户ID（权限验证）
     * @return bool 操作结果
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->db->fetch(
            "SELECT * FROM {$this->db->table('notifications')} WHERE id = ? AND user_id = ? LIMIT 1",
            [$notificationId, $userId]
        );
        
        if (!$notification || $notification['is_read']) {
            return false;
        }
        
        try {
            $this->db->update('notifications', [
                'is_read' => 1,
                'read_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$notificationId]);
            
            // 减少未读计数
            $this->decrementUnreadCount($userId);
            
            return true;
        } catch (Exception $e) {
            error_log("标记通知已读失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 标记所有通知为已读
     * 
     * @param int $userId 用户ID
     * @return bool 操作结果
     */
    public function markAllAsRead(int $userId): bool
    {
        try {
            $this->db->query(
                "UPDATE {$this->db->table('notifications')} 
                 SET is_read = 1, read_at = ? 
                 WHERE user_id = ? AND is_read = 0",
                [date('Y-m-d H:i:s'), $userId]
            );
            
            // 重置未读计数
            $this->db->update('users', ['unread_count' => 0], 'id = ?', [$userId]);
            
            return true;
        } catch (Exception $e) {
            error_log("标记所有通知已读失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取未读通知数量
     * 
     * @param int $userId 用户ID
     * @return int 未读数量
     */
    public function getUnreadCount(int $userId): int
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as cnt FROM {$this->db->table('notifications')} WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        
        return (int) ($result['cnt'] ?? 0);
    }
    
    /**
     * 增加用户未读计数
     * 
     * @param int $userId 用户ID
     */
    private function incrementUnreadCount(int $userId): void
    {
        $this->db->query(
            "UPDATE {$this->db->table('users')} SET unread_count = unread_count + 1 WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * 减少用户未读计数
     * 
     * @param int $userId 用户ID
     */
    private function decrementUnreadCount(int $userId): void
    {
        $this->db->query(
            "UPDATE {$this->db->table('users')} SET unread_count = GREATEST(unread_count - 1, 0) WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * 删除通知
     * 
     * @param int $notificationId 通知ID
     * @param int $userId 用户ID（权限验证）
     * @return bool 操作结果
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        $notification = $this->db->fetch(
            "SELECT * FROM {$this->db->table('notifications')} WHERE id = ? AND user_id = ? LIMIT 1",
            [$notificationId, $userId]
        );
        
        if (!$notification) {
            return false;
        }
        
        try {
            $this->db->delete('notifications', 'id = ?', [$notificationId]);
            
            // 如果是未读通知，减少计数
            if (!$notification['is_read']) {
                $this->decrementUnreadCount($userId);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("删除通知失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 清理旧通知
     * 
     * @param int $days 保留天数（默认30天）
     * @return int 删除的通知数量
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        try {
            $result = $this->db->query(
                "DELETE FROM {$this->db->table('notifications')} WHERE created_at < ?",
                [$date]
            );
            
            return $result->rowCount();
        } catch (Exception $e) {
            error_log("清理旧通知失败: " . $e->getMessage());
            return 0;
        }
    }
}
