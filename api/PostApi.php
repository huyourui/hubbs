<?php
/**
 * HuBBS - 帖子API控制器
 * RESTful API接口
 */

class PostApi {
    
    /**
     * 获取帖子列表
     * GET /api/posts
     */
    public function index() {
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        $forumId = intval($_GET['forum_id'] ?? 0);
        
        $query = Post::query();
        
        if ($forumId > 0) {
            $query->where('forum_id', $forumId);
        }
        
        $result = $query->orderBy('is_top', 'desc')
                       ->orderBy('last_reply_at', 'desc')
                       ->paginate($perPage, $page);
        
        // 转换模型为数组
        $items = [];
        foreach ($result['data'] as $post) {
            $items[] = $this->formatPost($post);
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
    
    /**
     * 获取帖子详情
     * GET /api/posts/{id}
     */
    public function show($id) {
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        // 增加浏览数
        $post->incrementViews();
        
        return ApiResponse::success($this->formatPost($post, true));
    }
    
    /**
     * 创建帖子
     * POST /api/posts
     */
    public function create() {
        ApiAuth::check();
        
        $data = $this->getJsonInput();
        
        // 验证
        $errors = $this->validatePost($data);
        if (!empty($errors)) {
            return ApiResponse::validationError($errors);
        }
        
        // 创建帖子
        $post = Post::create([
            'forum_id' => $data['forum_id'],
            'user_id' => Auth::id(),
            'title' => $data['title'],
            'content' => $data['content'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'last_reply_at' => date('Y-m-d H:i:s')
        ]);
        
        return ApiResponse::success($this->formatPost($post), '帖子创建成功');
    }
    
    /**
     * 更新帖子
     * PUT /api/posts/{id}
     */
    public function update($id) {
        ApiAuth::check();
        
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        // 检查权限
        if ($post->user_id != Auth::id() && !Auth::isAdmin()) {
            return ApiResponse::forbidden('无权编辑此帖子');
        }
        
        $data = $this->getJsonInput();
        
        // 验证
        $errors = $this->validatePost($data, true);
        if (!empty($errors)) {
            return ApiResponse::validationError($errors);
        }
        
        // 更新
        $post->fill($data);
        $post->edit_count = $post->edit_count + 1;
        $post->last_edit_at = date('Y-m-d H:i:s');
        $post->save();
        
        return ApiResponse::success($this->formatPost($post), '帖子更新成功');
    }
    
    /**
     * 删除帖子
     * DELETE /api/posts/{id}
     */
    public function delete($id) {
        ApiAuth::check();
        
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        // 检查权限
        if ($post->user_id != Auth::id() && !Auth::isAdmin()) {
            return ApiResponse::forbidden('无权删除此帖子');
        }
        
        $post->delete();
        
        return ApiResponse::success(null, '帖子删除成功');
    }
    
    /**
     * 点赞帖子
     * POST /api/posts/{id}/like
     */
    public function like($id) {
        ApiAuth::check();
        
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        $liked = PostLike::like($id, Auth::id());
        
        return ApiResponse::success([
            'liked' => $liked,
            'likes_count' => $post->likes + ($liked ? 1 : 0)
        ]);
    }
    
    /**
     * 取消点赞
     * DELETE /api/posts/{id}/like
     */
    public function unlike($id) {
        ApiAuth::check();
        
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        $unliked = PostLike::unlike($id, Auth::id());
        
        return ApiResponse::success([
            'liked' => !$unliked,
            'likes_count' => $post->likes - ($unliked ? 1 : 0)
        ]);
    }
    
    /**
     * 收藏帖子
     * POST /api/posts/{id}/favorite
     */
    public function favorite($id) {
        ApiAuth::check();
        
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        $favorited = PostFavorite::favorite($id, Auth::id());
        
        return ApiResponse::success([
            'favorited' => $favorited,
            'favorites_count' => $post->favorites + ($favorited ? 1 : 0)
        ]);
    }
    
    /**
     * 取消收藏
     * DELETE /api/posts/{id}/favorite
     */
    public function unfavorite($id) {
        ApiAuth::check();
        
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        $unfavorited = PostFavorite::unfavorite($id, Auth::id());
        
        return ApiResponse::success([
            'favorited' => !$unfavorited,
            'favorites_count' => $post->favorites - ($unfavorited ? 1 : 0)
        ]);
    }
    
    /**
     * 获取帖子回复
     * GET /api/posts/{id}/replies
     */
    public function replies($id) {
        $post = Post::find($id);
        
        if (!$post) {
            return ApiResponse::notFound('帖子不存在');
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        
        $result = Reply::query()
            ->where('post_id', $id)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, $page);
        
        $items = [];
        foreach ($result['data'] as $reply) {
            $items[] = $this->formatReply($reply);
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
    
    /**
     * 格式化帖子数据
     */
    private function formatPost($post, $withContent = false) {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'forum_id' => $post->forum_id,
            'user_id' => $post->user_id,
            'views' => $post->views,
            'replies' => $post->replies,
            'likes' => $post->likes,
            'favorites' => $post->favorites,
            'is_top' => (bool)$post->is_top,
            'is_essence' => (bool)$post->is_essence,
            'is_locked' => (bool)$post->is_locked,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
            'last_reply_at' => $post->last_reply_at
        ];
        
        if ($withContent) {
            $data['content'] = $post->content;
            $data['edit_count'] = $post->edit_count;
            $data['last_edit_at'] = $post->last_edit_at;
        }
        
        return $data;
    }
    
    /**
     * 格式化回复数据
     */
    private function formatReply($reply) {
        return [
            'id' => $reply->id,
            'post_id' => $reply->post_id,
            'user_id' => $reply->user_id,
            'content' => $reply->content,
            'created_at' => $reply->created_at,
            'edit_count' => $reply->edit_count,
            'last_edit_at' => $reply->last_edit_at
        ];
    }
    
    /**
     * 验证帖子数据
     */
    private function validatePost($data, $isUpdate = false) {
        $errors = [];
        
        if (!$isUpdate || isset($data['title'])) {
            if (empty($data['title'])) {
                $errors['title'] = '标题不能为空';
            } elseif (mb_strlen($data['title']) < 2) {
                $errors['title'] = '标题至少2个字';
            }
        }
        
        if (!$isUpdate || isset($data['content'])) {
            if (empty($data['content'])) {
                $errors['content'] = '内容不能为空';
            } elseif (mb_strlen($data['content']) < 5) {
                $errors['content'] = '内容至少5个字';
            }
        }
        
        if (!$isUpdate && empty($data['forum_id'])) {
            $errors['forum_id'] = '请选择板块';
        }
        
        return $errors;
    }
    
    /**
     * 获取JSON输入
     */
    private function getJsonInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        return $data ?: $_POST;
    }
}
