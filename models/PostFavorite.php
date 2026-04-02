<?php
/**
 * HuBBS - 收藏模型
 */

class PostFavorite extends Model {
    protected static $table = 'post_favorites';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'post_id', 'user_id', 'created_at'
    ];
    
    /**
     * 获取收藏所属的帖子
     */
    public function post() {
        return $this->belongsTo('Post', 'post_id');
    }
    
    /**
     * 获取收藏的用户
     */
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 收藏帖子
     */
    public static function favorite($postId, $userId) {
        $existing = static::query()
            ->where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();
        
        if ($existing) {
            return false;
        }
        
        static::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db = self::getDb();
        $db->query("UPDATE {$db->table('posts')} SET favorites = favorites + 1 WHERE id = ?", [$postId]);
        
        return true;
    }
    
    /**
     * 取消收藏
     */
    public static function unfavorite($postId, $userId) {
        $favorite = static::query()
            ->where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();
        
        if (!$favorite) {
            return false;
        }
        
        $favorite->delete();
        
        $db = self::getDb();
        $db->query("UPDATE {$db->table('posts')} SET favorites = GREATEST(favorites - 1, 0) WHERE id = ?", [$postId]);
        
        return true;
    }
    
    /**
     * 获取用户的收藏列表
     */
    public static function getByUser($userId, $limit = 20) {
        return static::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
