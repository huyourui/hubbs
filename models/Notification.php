<?php
/**
 * HuBBS - 通知模型
 */

class Notification extends Model {
    protected static $table = 'notifications';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'user_id', 'sender_id', 'type', 'title', 'content', 'target_id',
        'target_type', 'is_read', 'created_at', 'read_at'
    ];
    
    protected static $defaults = [
        'sender_id' => 0,
        'is_read' => 0,
        'target_id' => 0
    ];
    
    // 通知类型常量
    const TYPE_REPLY_POST = 'reply_post';
    const TYPE_REPLY_COMMENT = 'reply_comment';
    const TYPE_LIKE_POST = 'like_post';
    const TYPE_FAVORITE_POST = 'favorite_post';
    const TYPE_SYSTEM = 'system';
    
    /**
     * 获取通知的接收者
     */
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取通知的发送者
     */
    public function sender() {
        return $this->belongsTo('User', 'sender_id');
    }
    
    /**
     * 获取关联目标
     */
    public function target() {
        if (!$this->target_type || !$this->target_id) {
            return null;
        }
        
        switch ($this->target_type) {
            case 'post':
                return Post::find($this->target_id);
            case 'reply':
                return Reply::find($this->target_id);
            default:
                return null;
        }
    }
    
    /**
     * 标记为已读
     */
    public function markAsRead() {
        if ($this->is_read) {
            return true;
        }
        
        $this->is_read = 1;
        $this->read_at = date('Y-m-d H:i:s');
        $this->save();
        
        $db = self::getDb();
        $db->query(
            "UPDATE {$db->table('users')} SET unread_count = GREATEST(unread_count - 1, 0) WHERE id = ?",
            [$this->user_id]
        );
        
        return true;
    }
    
    /**
     * 获取用户的消息列表
     */
    public static function getByUser($userId, $limit = 20) {
        return static::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * 获取用户的未读消息数
     */
    public static function getUnreadCount($userId) {
        return static::query()
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }
    
    /**
     * 获取通知类型名称
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
}
