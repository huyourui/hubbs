<?php
/**
 * HuBBS - 板块模型
 */

class Forum extends Model {
    protected static $table = 'forums';
    protected static $primaryKey = 'id';
    
    protected static $fillable = [
        'parent_id', 'name', 'description', 'icon', 'sort_order', 'post_count', 'allowed_users'
    ];
    
    protected static $defaults = [
        'parent_id' => 0,
        'post_count' => 0,
        'sort_order' => 0,
        'icon' => '',
        'description' => ''
    ];
    
    /**
     * 获取父板块
     */
    public function parent() {
        if ($this->parent_id == 0) {
            return null;
        }
        return self::find($this->parent_id);
    }
    
    /**
     * 获取子板块
     */
    public function children() {
        return self::query()
            ->where('parent_id', $this->id)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
    
    /**
     * 获取板块的所有帖子
     */
    public function posts() {
        return $this->hasMany('Post');
    }
    
    /**
     * 检查用户是否有发帖权限
     */
    public function canPostBy($userId) {
        if (empty($this->allowed_users)) {
            return true;
        }
        
        $allowedUsers = array_filter(array_map('trim', explode(',', $this->allowed_users)));
        return in_array($userId, $allowedUsers);
    }
    
    /**
     * 获取一级板块列表
     */
    public static function getParentForums() {
        return static::query()
            ->where('parent_id', 0)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
    
    /**
     * 获取所有板块（构建树形结构）
     */
    public static function getTree() {
        $all = static::query()
            ->orderBy('sort_order', 'asc')
            ->get();
        
        $parents = [];
        $children = [];
        
        foreach ($all as $forum) {
            if ($forum->parent_id == 0) {
                $parents[] = $forum;
            } else {
                $children[$forum->parent_id][] = $forum;
            }
        }
        
        return ['parents' => $parents, 'children' => $children];
    }
    
    /**
     * 获取可选择的板块（过滤掉有子分类的一级分类）
     */
    public static function getSelectableForums() {
        $all = static::query()
            ->orderBy('sort_order', 'asc')
            ->get();
        
        $childrenMap = [];
        foreach ($all as $forum) {
            if ($forum->parent_id > 0) {
                if (!isset($childrenMap[$forum->parent_id])) {
                    $childrenMap[$forum->parent_id] = [];
                }
                $childrenMap[$forum->parent_id][] = $forum;
            }
        }
        
        $selectable = [];
        foreach ($all as $forum) {
            if ($forum->parent_id > 0 || !isset($childrenMap[$forum->id])) {
                $selectable[] = $forum;
            }
        }
        
        return $selectable;
    }
    
    /**
     * 增加帖子数
     */
    public function incrementPostCount() {
        $db = self::getDb();
        $db->query("UPDATE {$db->table('forums')} SET post_count = post_count + 1 WHERE id = ?", [$this->id]);
        $this->post_count++;
    }
}
