<?php
/**
 * HuBBS - 用户API控制器
 */

class UserApi {
    
    /**
     * 获取当前用户信息
     * GET /api/user
     */
    public function me() {
        ApiAuth::check();
        
        $user = Auth::user();
        
        return ApiResponse::success($this->formatUser($user));
    }
    
    /**
     * 获取用户详情
     * GET /api/users/{id}
     */
    public function show($id) {
        $user = User::findActive($id);
        
        if (!$user) {
            return ApiResponse::notFound('用户不存在');
        }
        
        return ApiResponse::success($this->formatUser($user, true));
    }
    
    /**
     * 获取用户帖子
     * GET /api/users/{id}/posts
     */
    public function posts($id) {
        $user = User::findActive($id);
        
        if (!$user) {
            return ApiResponse::notFound('用户不存在');
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        
        $result = Post::query()
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $page);
        
        $items = [];
        foreach ($result['data'] as $post) {
            $items[] = [
                'id' => $post->id,
                'title' => $post->title,
                'views' => $post->views,
                'replies' => $post->replies,
                'created_at' => $post->created_at
            ];
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
    
    /**
     * 获取用户回复
     * GET /api/users/{id}/replies
     */
    public function replies($id) {
        $user = User::findActive($id);
        
        if (!$user) {
            return ApiResponse::notFound('用户不存在');
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        
        $result = Reply::query()
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $page);
        
        $items = [];
        foreach ($result['data'] as $reply) {
            $items[] = [
                'id' => $reply->id,
                'post_id' => $reply->post_id,
                'content' => mb_substr($reply->content, 0, 100),
                'created_at' => $reply->created_at
            ];
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
    
    /**
     * 获取用户收藏
     * GET /api/users/{id}/favorites
     */
    public function favorites($id) {
        $user = User::findActive($id);
        
        if (!$user) {
            return ApiResponse::notFound('用户不存在');
        }
        
        // 只能查看自己的收藏
        if ($id != Auth::id()) {
            return ApiResponse::forbidden('只能查看自己的收藏');
        }
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        
        $result = PostFavorite::query()
            ->where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, $page);
        
        $items = [];
        foreach ($result['data'] as $favorite) {
            $post = Post::find($favorite->post_id);
            if ($post) {
                $items[] = [
                    'id' => $favorite->id,
                    'post' => [
                        'id' => $post->id,
                        'title' => $post->title,
                        'author_id' => $post->user_id
                    ],
                    'created_at' => $favorite->created_at
                ];
            }
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
    
    /**
     * 更新用户信息
     * PUT /api/user
     */
    public function update() {
        ApiAuth::check();
        
        $user = Auth::user();
        $data = $this->getJsonInput();
        
        // 验证
        $errors = [];
        if (isset($data['username'])) {
            if (empty($data['username'])) {
                $errors['username'] = '用户名不能为空';
            } elseif (!validate_username($data['username'])) {
                $errors['username'] = '用户名2-20位，支持中英文、数字、下划线';
            }
        }
        
        if (!empty($errors)) {
            return ApiResponse::validationError($errors);
        }
        
        $user->fill($data);
        $user->save();
        
        return ApiResponse::success($this->formatUser($user), '用户信息更新成功');
    }
    
    /**
     * 修改密码
     * PUT /api/user/password
     */
    public function password() {
        ApiAuth::check();
        
        $data = $this->getJsonInput();
        
        $errors = [];
        if (empty($data['current_password'])) {
            $errors['current_password'] = '请输入当前密码';
        }
        if (empty($data['new_password']) || strlen($data['new_password']) < 6) {
            $errors['new_password'] = '新密码至少6位';
        }
        if ($data['new_password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = '两次输入的新密码不一致';
        }
        
        if (!empty($errors)) {
            return ApiResponse::validationError($errors);
        }
        
        $user = Auth::user();
        $db = DB::getInstance();
        
        // 验证当前密码
        $currentUser = $db->fetch(
            "SELECT password FROM {$db->table('users')} WHERE id = ? LIMIT 1",
            [$user['id']]
        );
        
        if (!password_verify($data['current_password'] . HUBBS_SALT, $currentUser['password'])) {
            return ApiResponse::error('当前密码错误', 422);
        }
        
        // 更新密码
        $newPassword = password_hash($data['new_password'] . HUBBS_SALT, PASSWORD_BCRYPT);
        $db->update('users', ['password' => $newPassword], 'id = ?', [$user['id']]);
        
        return ApiResponse::success(null, '密码修改成功');
    }
    
    /**
     * 格式化用户数据
     */
    private function formatUser($user, $withStats = false) {
        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'avatar' => $user->avatar,
            'bio' => $user->bio,
            'is_admin' => (bool)$user->is_admin,
            'created_at' => $user->created_at
        ];
        
        if ($withStats) {
            $data['stats'] = [
                'posts' => Post::query()->where('user_id', $user->id)->count(),
                'replies' => Reply::query()->where('user_id', $user->id)->count(),
                'favorites' => PostFavorite::query()->where('user_id', $user->id)->count()
            ];
        }
        
        return $data;
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
