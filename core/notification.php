<?php
/**
 * HuBBS - 消息通知类
 * 统一处理消息发送和管理
 */

class Notification {
    
    // 消息类型常量
    const TYPE_REPLY_POST = 'reply_post';       // 回复帖子
    const TYPE_REPLY_COMMENT = 'reply_comment'; // 回复评论
    const TYPE_LIKE_POST = 'like_post';         // 点赞帖子
    const TYPE_FAVORITE_POST = 'favorite_post'; // 收藏帖子
    const TYPE_SYSTEM = 'system';               // 系统消息
    
    /**
     * 发送消息通知
     * 
     * @param int $userId 接收消息的用户ID
     * @param string $type 消息类型
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @param int $senderId 发送者ID，0表示系统
     * @param int $targetId 关联目标ID
     * @param string $targetType 关联目标类型
     * @return bool|int 成功返回消息ID，失败返回false
     */
    public static function send($userId, $type, $title, $content, $senderId = 0, $targetId = 0, $targetType = null) {
        // 不能给自己发消息
        if ($senderId > 0 && $senderId == $userId) {
            return false;
        }
        
        $db = DB::getInstance();
        
        // 插入消息记录
        $notificationId = $db->insert('notifications', [
            'user_id' => $userId,
            'sender_id' => $senderId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($notificationId) {
            // 增加用户未读消息数
            $db->query("UPDATE {$db->table('users')} SET unread_count = unread_count + 1 WHERE id = ?", [$userId]);
        }
        
        return $notificationId;
    }
    
    /**
     * 发送帖子被回复通知
     * 
     * @param int $postId 帖子ID
     * @param int $postUserId 帖子作者ID
     * @param int $replyUserId 回复者ID
     * @param string $replyUsername 回复者用户名
     * @param string $postTitle 帖子标题
     * @return bool|int
     */
    public static function sendPostReply($postId, $postUserId, $replyUserId, $replyUsername, $postTitle) {
        $title = '你的帖子收到新回复';
        $content = "{$replyUsername} 回复了你的帖子《{$postTitle}》";
        
        return self::send(
            $postUserId,
            self::TYPE_REPLY_POST,
            $title,
            $content,
            $replyUserId,
            $postId,
            'post'
        );
    }
    
    /**
     * 发送评论被回复通知
     * 
     * @param int $replyId 一级回复ID
     * @param int $commentUserId 被回复的评论作者ID
     * @param int $senderUserId 发送者ID
     * @param string $senderUsername 发送者用户名
     * @param string $postTitle 帖子标题
     * @return bool|int
     */
    public static function sendCommentReply($replyId, $commentUserId, $senderUserId, $senderUsername, $postTitle) {
        $title = '你的评论收到新回复';
        $content = "{$senderUsername} 回复了你在《{$postTitle}》中的评论";
        
        return self::send(
            $commentUserId,
            self::TYPE_REPLY_COMMENT,
            $title,
            $content,
            $senderUserId,
            $replyId,
            'reply'
        );
    }
    
    /**
     * 发送帖子被点赞通知
     * 
     * @param int $postId 帖子ID
     * @param int $postUserId 帖子作者ID
     * @param int $likeUserId 点赞者ID
     * @param string $likeUsername 点赞者用户名
     * @param string $postTitle 帖子标题
     * @return bool|int
     */
    public static function sendPostLike($postId, $postUserId, $likeUserId, $likeUsername, $postTitle) {
        $title = '你的帖子收到新点赞';
        $content = "{$likeUsername} 点赞了你的帖子《{$postTitle}》";
        
        return self::send(
            $postUserId,
            self::TYPE_LIKE_POST,
            $title,
            $content,
            $likeUserId,
            $postId,
            'post'
        );
    }
    
    /**
     * 发送帖子被收藏通知
     * 
     * @param int $postId 帖子ID
     * @param int $postUserId 帖子作者ID
     * @param int $favoriteUserId 收藏者ID
     * @param string $favoriteUsername 收藏者用户名
     * @param string $postTitle 帖子标题
     * @return bool|int
     */
    public static function sendPostFavorite($postId, $postUserId, $favoriteUserId, $favoriteUsername, $postTitle) {
        $title = '你的帖子被收藏';
        $content = "{$favoriteUsername} 收藏了你的帖子《{$postTitle}》";
        
        return self::send(
            $postUserId,
            self::TYPE_FAVORITE_POST,
            $title,
            $content,
            $favoriteUserId,
            $postId,
            'post'
        );
    }
    
    /**
     * 发送系统消息
     * 
     * @param int $userId 用户ID，0表示发送给所有用户
     * @param string $title 消息标题
     * @param string $content 消息内容
     * @return bool|int|array
     */
    public static function sendSystem($userId, $title, $content) {
        if ($userId > 0) {
            return self::send($userId, self::TYPE_SYSTEM, $title, $content, 0);
        } else {
            // 发送给所有用户
            $db = DB::getInstance();
            $users = $db->fetchAll("SELECT id FROM {$db->table('users')}");
            $results = [];
            foreach ($users as $user) {
                $results[] = self::send($user['id'], self::TYPE_SYSTEM, $title, $content, 0);
            }
            return $results;
        }
    }
    
    /**
     * 获取用户消息列表
     * 
     * @param int $userId 用户ID
     * @param string $type 消息类型，null表示所有
     * @param int $isRead 是否已读，null表示所有，0-未读，1-已读
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array
     */
    public static function getUserNotifications($userId, $type = null, $isRead = null, $page = 1, $perPage = 20) {
        $db = DB::getInstance();
        
        $where = 'user_id = ?';
        $params = [$userId];
        
        if ($type !== null) {
            $where .= ' AND type = ?';
            $params[] = $type;
        }
        
        if ($isRead !== null) {
            $where .= ' AND is_read = ?';
            $params[] = $isRead;
        }
        
        $offset = ($page - 1) * $perPage;
        
        $notifications = $db->fetchAll(
            "SELECT n.*, u.username as sender_name, u.avatar as sender_avatar 
             FROM {$db->table('notifications')} n 
             LEFT JOIN {$db->table('users')} u ON n.sender_id = u.id 
             WHERE {$where} 
             ORDER BY n.created_at DESC 
             LIMIT {$offset}, {$perPage}",
            $params
        );
        
        return $notifications;
    }
    
    /**
     * 获取用户未读消息数
     * 
     * @param int $userId 用户ID
     * @return int
     */
    public static function getUnreadCount($userId) {
        $db = DB::getInstance();
        
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM {$db->table('notifications')} WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * 标记消息为已读
     * 
     * @param int $notificationId 消息ID
     * @param int $userId 用户ID（用于验证）
     * @return bool
     */
    public static function markAsRead($notificationId, $userId) {
        $db = DB::getInstance();
        
        // 验证消息是否属于该用户
        $notification = $db->fetch(
            "SELECT id, is_read FROM {$db->table('notifications')} WHERE id = ? AND user_id = ? LIMIT 1",
            [$notificationId, $userId]
        );
        
        if (!$notification) {
            return false;
        }
        
        // 如果已经是已读，直接返回
        if ($notification['is_read']) {
            return true;
        }
        
        // 更新消息状态
        $db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$notificationId]);
        
        // 减少用户未读消息数
        $db->query("UPDATE {$db->table('users')} SET unread_count = GREATEST(unread_count - 1, 0) WHERE id = ?", [$userId]);
        
        return true;
    }
    
    /**
     * 标记所有消息为已读
     * 
     * @param int $userId 用户ID
     * @return bool
     */
    public static function markAllAsRead($userId) {
        $db = DB::getInstance();
        
        $db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ], 'user_id = ? AND is_read = 0', [$userId]);
        
        // 重置用户未读消息数
        $db->update('users', ['unread_count' => 0], 'id = ?', [$userId]);
        
        return true;
    }
    
    /**
     * 删除消息
     * 
     * @param int $notificationId 消息ID
     * @param int $userId 用户ID（用于验证）
     * @return bool
     */
    public static function delete($notificationId, $userId) {
        $db = DB::getInstance();
        
        // 验证消息是否属于该用户
        $notification = $db->fetch(
            "SELECT id, is_read FROM {$db->table('notifications')} WHERE id = ? AND user_id = ? LIMIT 1",
            [$notificationId, $userId]
        );
        
        if (!$notification) {
            return false;
        }
        
        // 如果是未读消息，减少未读计数
        if (!$notification['is_read']) {
            $db->query("UPDATE {$db->table('users')} SET unread_count = GREATEST(unread_count - 1, 0) WHERE id = ?", [$userId]);
        }
        
        // 删除消息
        $db->query("DELETE FROM {$db->table('notifications')} WHERE id = ?", [$notificationId]);
        
        return true;
    }
    
    /**
     * 获取消息类型名称
     * 
     * @param string $type 消息类型
     * @return string
     */
    public static function getTypeName($type) {
        $names = [
            self::TYPE_REPLY_POST => '回复帖子',
            self::TYPE_REPLY_COMMENT => '回复评论',
            self::TYPE_LIKE_POST => '点赞帖子',
            self::TYPE_FAVORITE_POST => '收藏帖子',
            self::TYPE_SYSTEM => '系统消息'
        ];
        
        return $names[$type] ?? '其他';
    }
    
    /**
     * 获取消息类型图标
     * 
     * @param string $type 消息类型
     * @return string SVG图标
     */
    public static function getTypeIcon($type) {
        $icons = [
            self::TYPE_REPLY_POST => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/></svg>',
            self::TYPE_REPLY_COMMENT => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>',
            self::TYPE_LIKE_POST => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
            self::TYPE_FAVORITE_POST => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
            self::TYPE_SYSTEM => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>'
        ];
        
        return $icons[$type] ?? $icons[self::TYPE_SYSTEM];
    }
}