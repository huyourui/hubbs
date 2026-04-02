<?php
/**
 * HuBBS - 楼中楼评论模型
 */

class ReplyComment extends Model {
    protected static $table = 'reply_comments';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'reply_id', 'user_id', 'to_user_id', 'content', 'created_at'
    ];
    
    /**
     * 获取评论所属的一级回复
     */
    public function reply() {
        return $this->belongsTo('Reply', 'reply_id');
    }
    
    /**
     * 获取评论的作者
     */
    public function author() {
        return $this->belongsTo('User', 'user_id');
    }
    
    /**
     * 获取被回复的用户
     */
    public function toUser() {
        return $this->belongsTo('User', 'to_user_id');
    }
    
    /**
     * 获取一级回复的所有评论
     */
    public static function getByReply($replyId) {
        return static::query()
            ->where('reply_id', $replyId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
