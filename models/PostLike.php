<?php
/**
 * HuBBS - 点赞模型
 */

class PostLike extends Model {
    protected static $table = 'post_likes';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'post_id', 'user_id', 'created_at'
    ];
    
    /**
     * 获取点赞所属的帖子
     */
    public function post() {
        return $this->belongsTo('Post', 'post_id');
    }
    
    /**
     * 获取点赞的用户
     */
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 点赞帖子
     */
    public static function like($postId, $userId) {
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
        $db->query("UPDATE {$db->table('posts')} SET likes = likes + 1 WHERE id = ?", [$postId]);
        
        return true;
    }
    
    /**
     * 取消点赞
     */
    public static function unlike($postId, $userId) {
        $like = static::query()
            ->where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();
        
        if (!$like) {
            return false;
        }
        
        $like->delete();
        
        $db = self::getDb();
        $db->query("UPDATE {$db->table('posts')} SET likes = GREATEST(likes - 1, 0) WHERE id = ?", [$postId]);
        
        return true;
    }
}
