<?php
/**
 * HuBBS - 用户模型
 * 使用ORM基类提供便捷的数据库操作
 */

class User extends Model {
    protected static $table = 'users';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'username', 'email', 'original_email', 'password', 'avatar', 'bio',
        'is_admin', 'status', 'unread_count', 'deleted_at', 'created_at',
        'last_login', 'last_ip'
    ];
    
    protected static $hidden = ['password'];
    
    protected static $defaults = [
        'is_admin' => 0,
        'status' => 1,
        'unread_count' => 0,
        'avatar' => '',
        'deleted_at' => null
    ];
    
    /**
     * 获取用户的所有帖子
     */
    public function posts() {
        return $this->hasMany('Post');
    }
    
    /**
     * 获取用户的所有回复
     */
    public function replies() {
        return $this->hasMany('Reply');
    }
    
    /**
     * 获取用户的所有收藏
     */
    public function favorites() {
        return $this->hasMany('PostFavorite');
    }
    
    /**
     * 获取用户的所有点赞
     */
    public function likes() {
        return $this->hasMany('PostLike');
    }
    
    /**
     * 获取用户的所有通知
     */
    public function notifications() {
        return $this->hasMany('Notification');
    }
    
    /**
     * 获取未读通知数
     */
    public function getUnreadNotificationsCount() {
        return Notification::query()
            ->where('user_id', $this->id)
            ->where('is_read', 0)
            ->count();
    }
    
    /**
     * 检查是否是管理员
     */
    public function isAdmin() {
        return (bool)$this->is_admin;
    }
    
    /**
     * 检查是否被封禁
     */
    public function isBanned() {
        return $this->status === 0;
    }
    
    /**
     * 检查是否已删除
     */
    public function isDeleted() {
        return $this->deleted_at !== null;
    }
    
    /**
     * 查找活跃用户
     */
    public static function findActive($id) {
        return static::query()
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();
    }
    
    /**
     * 获取最新注册的用户
     */
    public static function getLatest($limit = 10) {
        return static::query()
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * 搜索用户
     */
    public static function search($keyword, $limit = 20) {
        return static::query()
            ->whereNull('deleted_at')
            ->where('username', 'LIKE', "%{$keyword}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
