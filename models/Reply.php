<?php
/**
 * HuBBS - 回复模型
 */

class Reply extends Model {
    protected static $table = 'replies';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'post_id', 'user_id', 'content', 'created_at', 'edit_count', 'last_edit_at'
    ];
    
    protected static $defaults = [
        'edit_count' => 0
    ];
    
    /**
     * 获取回复所属的帖子
     */
    public function post() {
        return $this->belongsTo('Post', 'post_id');
    }
    
    /**
     * 获取回复的作者
     */
    public function author() {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取楼中楼评论
     */
    public function comments() {
        return $this->hasMany('ReplyComment');
    }
    
    /**
     * 获取评论数
     */
    public function getCommentsCount() {
        return ReplyComment::query()
            ->where('reply_id', $this->id)
            ->count();
    }
    
    /**
     * 获取帖子的所有回复
     */
    public static function getByPost($postId, $limit = 100) {
        return static::query()
            ->where('post_id', $postId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }
}
