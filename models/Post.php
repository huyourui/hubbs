<?php
/**
 * HuBBS - 帖子模型
 */

class Post extends Model {
    protected static $table = 'posts';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'forum_id', 'user_id', 'title', 'content', 'views', 'replies',
        'likes', 'favorites', 'is_top', 'is_essence', 'is_locked',
        'created_at', 'updated_at', 'edit_count', 'last_edit_at',
        'last_reply_at', 'last_reply_user_id'
    ];
    
    protected static $defaults = [
        'views' => 0,
        'replies' => 0,
        'likes' => 0,
        'favorites' => 0,
        'is_top' => 0,
        'is_essence' => 0,
        'is_locked' => 0,
        'edit_count' => 0
    ];
    
    /**
     * 获取帖子的作者
     */
    public function author() {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取帖子所属板块
     */
    public function forum() {
        return $this->belongsTo('Forum', 'forum_id');
    }
    
    /**
     * 获取帖子的所有回复
     */
    public function replies() {
        return $this->hasMany('Reply');
    }
    
    /**
     * 获取帖子的所有点赞
     */
    public function likes() {
        return $this->hasMany('PostLike');
    }
    
    /**
     * 获取帖子的所有收藏
     */
    public function favorites() {
        return $this->hasMany('PostFavorite');
    }
    
    /**
     * 检查用户是否已点赞
     */
    public function isLikedBy($userId) {
        return PostLike::query()
            ->where('post_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }
    
    /**
     * 检查用户是否已收藏
     */
    public function isFavoritedBy($userId) {
        return PostFavorite::query()
            ->where('post_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }
    
    /**
     * 增加浏览数
     */
    public function incrementViews() {
        $db = self::getDb();
        $db->query("UPDATE {$db->table('posts')} SET views = views + 1 WHERE id = ?", [$this->id]);
        $this->views++;
    }
    
    /**
     * 获取帖子的回复数（包含楼中楼）
     */
    public function getTotalRepliesCount() {
        $db = self::getDb();
        
        $repliesCount = $db->count('replies', 'post_id = ?', [$this->id]);
        
        $replyIds = $db->fetchAll(
            "SELECT id FROM {$db->table('replies')} WHERE post_id = ?",
            [$this->id]
        );
        
        if (empty($replyIds)) {
            return $repliesCount;
        }
        
        $ids = array_column($replyIds, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $commentsCount = $db->count('reply_comments', "reply_id IN ({$placeholders})", $ids);
        
        return $repliesCount + $commentsCount;
    }
    
    /**
     * 获取置顶帖子
     */
    public static function getTopPosts() {
        return static::query()
            ->where('is_top', 1)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * 获取精华帖子
     */
    public static function getEssencePosts($limit = 20) {
        return static::query()
            ->where('is_essence', 1)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * 获取板块的最新帖子
     */
    public static function getByForum($forumId, $limit = 20) {
        return static::query()
            ->where('forum_id', $forumId)
            ->orderBy('is_top', 'desc')
            ->orderBy('last_reply_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * 搜索帖子
     */
    public static function search($keyword, $limit = 20) {
        return static::query()
            ->select('*')
            ->where('title', 'LIKE', "%{$keyword}%")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
