<?php
/**
 * HuBBS - 帖子服务层
 * 
 * 负责处理帖子相关的业务逻辑，与数据访问层分离
 * 
 * @package HuBBS
 * @version 1.7.3
 */

class PostService 
{
    /**
     * @var DB 数据库实例
     */
    private $db;
    
    /**
     * @var NotificationService 通知服务
     */
    private $notificationService;
    
    /**
     * 构造函数
     */
    public function __construct() 
    {
        $this->db = DB::getInstance();
        $this->notificationService = new NotificationService();
    }
    
    /**
     * 获取帖子列表
     * 
     * @param int $forumId 板块ID（0表示全部）
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array 包含帖子列表和分页信息
     */
    public function getPostList(int $forumId = 0, int $page = 1, int $perPage = 20): array
    {
        $where = '1';
        $params = [];
        
        if ($forumId > 0) {
            $where = 'forum_id = ?';
            $params[] = $forumId;
        }
        
        $total = $this->db->count('posts', $where, $params);
        $offset = ($page - 1) * $perPage;
        
        $posts = $this->db->fetchAll(
            "SELECT p.*, u.username, u.avatar, f.name as forum_name 
             FROM {$this->db->table('posts')} p 
             LEFT JOIN {$this->db->table('users')} u ON p.user_id = u.id 
             LEFT JOIN {$this->db->table('forums')} f ON p.forum_id = f.id 
             WHERE {$where} 
             ORDER BY p.is_top DESC, p.last_reply_at DESC 
             LIMIT {$offset}, {$perPage}",
            $params
        );
        
        return [
            'posts' => $posts,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage)
        ];
    }
    
    /**
     * 获取帖子详情
     * 
     * @param int $postId 帖子ID
     * @param bool $incrementViews 是否增加浏览数
     * @return array|null 帖子详情
     */
    public function getPostDetail(int $postId, bool $incrementViews = true): ?array
    {
        $post = $this->db->fetch(
            "SELECT p.*, u.username, u.avatar, f.name as forum_name 
             FROM {$this->db->table('posts')} p 
             LEFT JOIN {$this->db->table('users')} u ON p.user_id = u.id 
             LEFT JOIN {$this->db->table('forums')} f ON p.forum_id = f.id 
             WHERE p.id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            return null;
        }
        
        if ($incrementViews) {
            $this->incrementViews($postId);
            $post['views']++;
        }
        
        return $post;
    }
    
    /**
     * 创建帖子
     * 
     * @param int $userId 用户ID
     * @param array $data 帖子数据
     * @return array 包含success和postId或message
     */
    public function createPost(int $userId, array $data): array
    {
        // 验证数据
        $validation = $this->validatePostData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        // 检查发帖间隔
        $intervalCheck = $this->checkPostInterval($userId);
        if (!$intervalCheck['allowed']) {
            return ['success' => false, 'message' => $intervalCheck['message']];
        }
        
        try {
            $postId = $this->db->insert('posts', [
                'forum_id' => $data['forum_id'],
                'user_id' => $userId,
                'title' => $data['title'],
                'content' => $data['content'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'last_reply_at' => date('Y-m-d H:i:s')
            ]);
            
            // 关联上传文件
            Upload::linkToPost($userId, $postId);
            
            // 更新板块帖子数
            $this->db->query(
                "UPDATE {$this->db->table('forums')} SET post_count = post_count + 1 WHERE id = ?",
                [$data['forum_id']]
            );
            
            return ['success' => true, 'post_id' => $postId];
        } catch (Exception $e) {
            error_log("创建帖子失败: " . $e->getMessage());
            return ['success' => false, 'message' => '发布失败，请稍后重试'];
        }
    }
    
    /**
     * 更新帖子
     * 
     * @param int $postId 帖子ID
     * @param int $userId 当前用户ID（用于权限验证）
     * @param array $data 更新数据
     * @return array 操作结果
     */
    public function updatePost(int $postId, int $userId, array $data): array
    {
        // 获取帖子信息
        $post = $this->db->fetch(
            "SELECT * FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            return ['success' => false, 'message' => '帖子不存在'];
        }
        
        // 权限检查
        if ($post['user_id'] != $userId) {
            return ['success' => false, 'message' => '无权编辑此帖子'];
        }
        
        // 验证数据
        $validation = $this->validatePostData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }
        
        try {
            $this->db->update('posts', [
                'forum_id' => $data['forum_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'updated_at' => date('Y-m-d H:i:s'),
                'edit_count' => $post['edit_count'] + 1,
                'last_edit_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$postId]);
            
            // 关联上传文件
            Upload::linkToPost($userId, $postId);
            
            return ['success' => true, 'post_id' => $postId];
        } catch (Exception $e) {
            error_log("更新帖子失败: " . $e->getMessage());
            return ['success' => false, 'message' => '更新失败，请稍后重试'];
        }
    }
    
    /**
     * 删除帖子
     * 
     * @param int $postId 帖子ID
     * @param int $userId 当前用户ID
     * @param bool $isAdmin 是否为管理员
     * @return array 操作结果
     */
    public function deletePost(int $postId, int $userId, bool $isAdmin = false): array
    {
        $post = $this->db->fetch(
            "SELECT * FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            return ['success' => false, 'message' => '帖子不存在'];
        }
        
        // 权限检查：只有作者或管理员可以删除
        if ($post['user_id'] != $userId && !$isAdmin) {
            return ['success' => false, 'message' => '无权删除此帖子'];
        }
        
        try {
            // 删除关联的点赞和收藏
            $this->db->delete('post_likes', 'post_id = ?', [$postId]);
            $this->db->delete('post_favorites', 'post_id = ?', [$postId]);
            
            // 删除关联的回复
            $this->db->delete('replies', 'post_id = ?', [$postId]);
            
            // 删除帖子
            $this->db->delete('posts', 'id = ?', [$postId]);
            
            // 更新板块帖子数
            $this->db->query(
                "UPDATE {$this->db->table('forums')} SET post_count = post_count - 1 WHERE id = ? AND post_count > 0",
                [$post['forum_id']]
            );
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("删除帖子失败: " . $e->getMessage());
            return ['success' => false, 'message' => '删除失败，请稍后重试'];
        }
    }
    
    /**
     * 获取回复列表
     * 
     * @param int $postId 帖子ID
     * @param int $page 页码
     * @param int $perPage 每页数量
     * @return array 回复列表和分页信息
     */
    public function getReplies(int $postId, int $page = 1, int $perPage = 20): array
    {
        $total = $this->db->count('replies', 'post_id = ?', [$postId]);
        $offset = ($page - 1) * $perPage;
        
        $replies = $this->db->fetchAll(
            "SELECT r.*, u.username, u.avatar 
             FROM {$this->db->table('replies')} r 
             LEFT JOIN {$this->db->table('users')} u ON r.user_id = u.id 
             WHERE r.post_id = ? 
             ORDER BY r.created_at ASC 
             LIMIT {$offset}, {$perPage}",
            [$postId]
        );
        
        // 获取楼中楼评论
        $replyIds = array_column($replies, 'id');
        $replyComments = [];
        $totalComments = 0;
        
        if (!empty($replyIds)) {
            $placeholders = implode(',', array_fill(0, count($replyIds), '?'));
            $comments = $this->db->fetchAll(
                "SELECT rc.*, u.id as user_id, u.username, u.avatar, 
                        tu.id as to_user_id, tu.username as to_username 
                 FROM {$this->db->table('reply_comments')} rc 
                 LEFT JOIN {$this->db->table('users')} u ON rc.user_id = u.id 
                 LEFT JOIN {$this->db->table('users')} tu ON rc.to_user_id = tu.id 
                 WHERE rc.reply_id IN ({$placeholders}) 
                 ORDER BY rc.created_at ASC",
                $replyIds
            );
            
            foreach ($comments as $comment) {
                $replyComments[$comment['reply_id']][] = $comment;
                $totalComments++;
            }
        }
        
        return [
            'replies' => $replies,
            'replyComments' => $replyComments,
            'total' => $total + $totalComments,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage)
        ];
    }
    
    /**
     * 创建回复
     * 
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @param string $content 回复内容
     * @return array 操作结果
     */
    public function createReply(int $postId, int $userId, string $content): array
    {
        // 验证内容
        if (empty($content) || mb_strlen($content) < 2) {
            return ['success' => false, 'message' => '回复内容至少2个字'];
        }
        
        // 检查回复间隔
        $intervalCheck = $this->checkReplyInterval($userId);
        if (!$intervalCheck['allowed']) {
            return ['success' => false, 'message' => $intervalCheck['message']];
        }
        
        // 检查帖子是否存在
        $post = $this->db->fetch(
            "SELECT id, user_id, title FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            return ['success' => false, 'message' => '帖子不存在'];
        }
        
        try {
            // 创建回复
            $this->db->insert('replies', [
                'post_id' => $postId,
                'user_id' => $userId,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // 更新帖子回复数
            $replyCount = $this->db->count('replies', 'post_id = ?', [$postId]);
            $this->db->update('posts', [
                'replies' => $replyCount,
                'last_reply_at' => date('Y-m-d H:i:s'),
                'last_reply_user_id' => $userId
            ], 'id = ?', [$postId]);
            
            // 发送通知给帖子作者
            if ($post['user_id'] != $userId) {
                $user = Auth::user();
                $this->notificationService->sendPostReply(
                    $postId,
                    $post['user_id'],
                    $userId,
                    $user['username'] ?? '',
                    $post['title']
                );
            }
            
            return ['success' => true];
        } catch (Exception $e) {
            error_log("创建回复失败: " . $e->getMessage());
            return ['success' => false, 'message' => '回复失败，请稍后重试'];
        }
    }
    
    /**
     * 点赞/取消点赞
     * 
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return array 包含操作结果和最新点赞数
     */
    public function toggleLike(int $postId, int $userId): array
    {
        $post = $this->db->fetch(
            "SELECT id, user_id, title FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            return ['success' => false, 'message' => '帖子不存在'];
        }
        
        $existing = $this->db->fetch(
            "SELECT id FROM {$this->db->table('post_likes')} WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );
        
        if ($existing) {
            // 取消点赞
            $this->db->delete('post_likes', 'id = ?', [$existing['id']]);
            $this->db->query(
                "UPDATE {$this->db->table('posts')} SET likes = likes - 1 WHERE id = ?",
                [$postId]
            );
            $liked = false;
        } else {
            // 添加点赞
            $this->db->insert('post_likes', [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->db->query(
                "UPDATE {$this->db->table('posts')} SET likes = likes + 1 WHERE id = ?",
                [$postId]
            );
            $liked = true;
            
            // 发送通知
            if ($post['user_id'] != $userId) {
                $user = Auth::user();
                $this->notificationService->sendPostLike(
                    $postId,
                    $post['user_id'],
                    $userId,
                    $user['username'] ?? '',
                    $post['title']
                );
            }
        }
        
        $post = $this->db->fetch(
            "SELECT likes FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        return [
            'success' => true,
            'liked' => $liked,
            'count' => $post['likes'] ?? 0
        ];
    }
    
    /**
     * 收藏/取消收藏
     * 
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return array 操作结果
     */
    public function toggleFavorite(int $postId, int $userId): array
    {
        $post = $this->db->fetch(
            "SELECT id, user_id, title FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            return ['success' => false, 'message' => '帖子不存在'];
        }
        
        // 不能收藏自己的帖子
        if ($post['user_id'] == $userId) {
            return ['success' => false, 'message' => '不能收藏自己的帖子'];
        }
        
        $existing = $this->db->fetch(
            "SELECT id FROM {$this->db->table('post_favorites')} WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );
        
        if ($existing) {
            // 取消收藏
            $this->db->delete('post_favorites', 'id = ?', [$existing['id']]);
            $this->db->query(
                "UPDATE {$this->db->table('posts')} SET favorites = favorites - 1 WHERE id = ?",
                [$postId]
            );
            $favorited = false;
        } else {
            // 添加收藏
            $this->db->insert('post_favorites', [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->db->query(
                "UPDATE {$this->db->table('posts')} SET favorites = favorites + 1 WHERE id = ?",
                [$postId]
            );
            $favorited = true;
            
            // 发送通知
            $user = Auth::user();
            $this->notificationService->sendPostFavorite(
                $postId,
                $post['user_id'],
                $userId,
                $user['username'] ?? '',
                $post['title']
            );
        }
        
        $post = $this->db->fetch(
            "SELECT favorites FROM {$this->db->table('posts')} WHERE id = ? LIMIT 1",
            [$postId]
        );
        
        return [
            'success' => true,
            'favorited' => $favorited,
            'count' => $post['favorites'] ?? 0
        ];
    }
    
    /**
     * 验证帖子数据
     * 
     * @param array $data 帖子数据
     * @return array 验证结果
     */
    private function validatePostData(array $data): array
    {
        // 检查是否强制选择分类
        if (Settings::get('is_force_forum', '1') === '1' && empty($data['forum_id'])) {
            return ['valid' => false, 'message' => '请选择板块'];
        }
        
        // 验证标题
        $titleMinLength = (int) Settings::get('title_min_length', '2');
        if (empty($data['title']) || mb_strlen($data['title']) < $titleMinLength) {
            return ['valid' => false, 'message' => "标题至少{$titleMinLength}个字"];
        }
        
        // 验证内容
        $contentMinLength = (int) Settings::get('post_min_length', '5');
        if (empty($data['content']) || mb_strlen($data['content']) < $contentMinLength) {
            return ['valid' => false, 'message' => "内容至少{$contentMinLength}个字"];
        }
        
        return ['valid' => true];
    }
    
    /**
     * 检查发帖间隔
     * 
     * @param int $userId 用户ID
     * @return array 检查结果
     */
    private function checkPostInterval(int $userId): array
    {
        $postInterval = (int) Settings::get('post_interval', '0');
        
        if ($postInterval <= 0) {
            return ['allowed' => true];
        }
        
        $lastPost = $this->db->fetch(
            "SELECT created_at FROM {$this->db->table('posts')} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        if ($lastPost) {
            $lastTime = strtotime($lastPost['created_at']);
            $currentTime = time();
            $diff = $currentTime - $lastTime;
            
            if ($diff < $postInterval) {
                $waitTime = $postInterval - $diff;
                return [
                    'allowed' => false,
                    'message' => "发帖太频繁，请等待 {$waitTime} 秒后再试"
                ];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * 检查回复间隔
     * 
     * @param int $userId 用户ID
     * @return array 检查结果
     */
    private function checkReplyInterval(int $userId): array
    {
        $replyInterval = (int) Settings::get('reply_interval', '0');
        
        if ($replyInterval <= 0) {
            return ['allowed' => true];
        }
        
        $lastReply = $this->db->fetch(
            "SELECT created_at FROM {$this->db->table('replies')} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
            [$userId]
        );
        
        if ($lastReply) {
            $lastTime = strtotime($lastReply['created_at']);
            $currentTime = time();
            $diff = $currentTime - $lastTime;
            
            if ($diff < $replyInterval) {
                $waitTime = $replyInterval - $diff;
                return [
                    'allowed' => false,
                    'message' => "回复太频繁，请等待 {$waitTime} 秒后再试"
                ];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * 增加浏览数
     * 
     * @param int $postId 帖子ID
     */
    private function incrementViews(int $postId): void
    {
        $this->db->query(
            "UPDATE {$this->db->table('posts')} SET views = views + 1 WHERE id = ?",
            [$postId]
        );
    }
    
    /**
     * 检查用户是否已点赞
     * 
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return bool
     */
    public function isLikedByUser(int $postId, int $userId): bool
    {
        $existing = $this->db->fetch(
            "SELECT id FROM {$this->db->table('post_likes')} WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );
        
        return $existing !== false;
    }
    
    /**
     * 检查用户是否已收藏
     * 
     * @param int $postId 帖子ID
     * @param int $userId 用户ID
     * @return bool
     */
    public function isFavoritedByUser(int $postId, int $userId): bool
    {
        $existing = $this->db->fetch(
            "SELECT id FROM {$this->db->table('post_favorites')} WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );
        
        return $existing !== false;
    }
}
