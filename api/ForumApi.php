<?php
/**
 * HuBBS - 板块API控制器
 */

class ForumApi {
    
    /**
     * 获取板块列表
     * GET /api/forums
     */
    public function index() {
        $tree = Forum::getTree();
        
        $forums = [];
        foreach ($tree['parents'] as $parent) {
            $forumData = [
                'id' => $parent->id,
                'name' => $parent->name,
                'description' => $parent->description,
                'icon' => $parent->icon,
                'post_count' => $parent->post_count,
                'children' => []
            ];
            
            if (isset($tree['children'][$parent->id])) {
                foreach ($tree['children'][$parent->id] as $child) {
                    $forumData['children'][] = [
                        'id' => $child->id,
                        'name' => $child->name,
                        'description' => $child->description,
                        'icon' => $child->icon,
                        'post_count' => $child->post_count
                    ];
                }
            }
            
            $forums[] = $forumData;
        }
        
        return ApiResponse::success($forums);
    }
    
    /**
     * 获取板块详情
     * GET /api/forums/{id}
     */
    public function show($id) {
        $forum = Forum::find($id);
        
        if (!$forum) {
            return ApiResponse::notFound('板块不存在');
        }
        
        return ApiResponse::success([
            'id' => $forum->id,
            'name' => $forum->name,
            'description' => $forum->description,
            'icon' => $forum->icon,
            'post_count' => $forum->post_count,
            'parent_id' => $forum->parent_id
        ]);
    }
    
    /**
     * 获取板块帖子
     * GET /api/forums/{id}/posts
     */
    public function posts($id) {
        $forum = Forum::find($id);
        
        if (!$forum) {
            return ApiResponse::notFound('板块不存在');
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        
        $result = Post::query()
            ->where('forum_id', $id)
            ->orderBy('is_top', 'desc')
            ->orderBy('last_reply_at', 'desc')
            ->paginate($perPage, $page);
        
        $items = [];
        foreach ($result['data'] as $post) {
            $items[] = [
                'id' => $post->id,
                'title' => $post->title,
                'user_id' => $post->user_id,
                'views' => $post->views,
                'replies' => $post->replies,
                'likes' => $post->likes,
                'is_top' => (bool)$post->is_top,
                'is_essence' => (bool)$post->is_essence,
                'created_at' => $post->created_at
            ];
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
}
