<?php
/**
 * HuBBS - 帖子模块
 * 处理发帖、回帖、帖子详情
 */

class PostModule {
    
    public function handle() {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list':
                return $this->list();
            case 'create':
                return $this->create();
            case 'edit':
                return $this->edit();
            case 'view':
                return $this->view();
            case 'reply':
                return $this->reply();
            case 'editReply':
                return $this->editReply();
            case 'replyComment':
                return $this->replyComment();
            case 'like':
                return $this->like();
            case 'favorite':
                return $this->favorite();
            default:
                redirect('index.php');
        }
    }
    
    private function list() {
        $db = DB::getInstance();
        $forumId = intval($_GET['forum'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        
        // 获取板块列表，构建树形结构
        $allForums = $db->fetchAll("SELECT * FROM {$db->table('forums')} ORDER BY sort_order ASC");
        $forums = [];
        $children = [];
        
        foreach ($allForums as $forum) {
            if ($forum['parent_id'] == 0) {
                $forums[] = $forum;
            } else {
                $children[$forum['parent_id']][] = $forum;
            }
        }
        
        // 构建查询条件
        $where = '1';
        $params = [];
        if ($forumId > 0) {
            $where = 'forum_id = ?';
            $params[] = $forumId;
        }
        
        // 获取帖子总数
        $total = $db->count('posts', $where, $params);
        
        // 获取帖子列表
        $offset = ($page - 1) * POSTS_PER_PAGE;
        $posts = $db->fetchAll(
            "SELECT p.*, u.username, u.avatar, f.name as forum_name 
             FROM {$db->table('posts')} p 
             LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
             LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
             WHERE {$where} 
             ORDER BY p.is_top DESC, p.last_reply_at DESC 
             LIMIT {$offset}, " . POSTS_PER_PAGE,
            $params
        );
        
        return [
            'template' => 'post_list',
            'data' => [
                'forums' => $forums,
                'children' => $children,
                'posts' => $posts,
                'forumId' => $forumId,
                'page' => $page,
                'total' => $total,
                'totalPages' => ceil($total / POSTS_PER_PAGE)
            ]
        ];
    }
    
    private function create() {
        if (Auth::guest()) {
            redirect('index.php?module=user&action=login');
        }

        // 检查用户是否被禁言
        if (Auth::check()) {
            $currentUser = Auth::user();
            if ($currentUser && $currentUser['is_banned']) {
                set_message('您已被禁言，无法发帖', 'error');
                redirect('index.php');
            }
        }

        $db = DB::getInstance();
        $error = '';

        // 获取板块列表（构建树形结构）
        $allForums = $db->fetchAll("SELECT * FROM {$db->table('forums')} ORDER BY sort_order ASC");
        $parentForums = [];
        $childForums = [];

        foreach ($allForums as $forum) {
            if ($forum['parent_id'] == 0) {
                $parentForums[] = $forum;
            } else {
                $childForums[$forum['parent_id']][] = $forum;
            }
        }

        // 过滤掉有子分类的一级分类（只能选择二级分类）
        $selectableForums = [];
        foreach ($allForums as $forum) {
            // 如果是二级分类，或者是一级分类但没有子分类
            if ($forum['parent_id'] > 0 || !isset($childForums[$forum['id']])) {
                $selectableForums[] = $forum;
            }
        }

        // 初始化表单数据
        $formData = [
            'forum_id' => '',
            'title' => '',
            'content' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $forumId = intval($_POST['forum_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');

                // 保存表单数据用于回显
                $formData = [
                    'forum_id' => $forumId,
                    'title' => $title,
                    'content' => $content
                ];

                // 检查是否强制选择分类
                if (Settings::get('is_force_forum', '1') === '1' && $forumId <= 0) {
                    $error = '请选择板块';
                } elseif ($forumId > 0) {
                    // 检查选择的分类是否有效（不能是有子分类的一级分类）
                    $selectedForum = null;
                    foreach ($allForums as $f) {
                        if ($f['id'] == $forumId) {
                            $selectedForum = $f;
                            break;
                        }
                    }
                    if ($selectedForum && $selectedForum['parent_id'] == 0 && isset($childForums[$forumId])) {
                        $error = '请选择二级分类';
                    }

                    // 检查用户是否有权限在该板块发帖
                    if (empty($error) && $selectedForum) {
                        if (!$this->canPostInForum($selectedForum)) {
                            $error = '您没有权限在该板块发帖';
                        }
                    }
                }

                if (empty($error)) {
                    // 检查发帖间隔时间
                    $postInterval = intval(Settings::get('post_interval', '0'));
                    if ($postInterval > 0) {
                        $lastPost = $db->fetch("SELECT created_at FROM {$db->table('posts')} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [Auth::id()]);
                        if ($lastPost) {
                            $lastTime = strtotime($lastPost['created_at']);
                            $currentTime = time();
                            $diff = $currentTime - $lastTime;
                            if ($diff < $postInterval) {
                                $waitTime = $postInterval - $diff;
                                $error = "发帖太频繁，请等待 {$waitTime} 秒后再试";
                            }
                        }
                    }
                }

                if (empty($error)) {
                    if (empty($title) || mb_strlen($title) < 2) {
                        $error = '标题至少2个字';
                    } elseif (empty($content) || mb_strlen($content) < 5) {
                        $error = '内容至少5个字';
                    } else {
                        $postId = $db->insert('posts', [
                            'forum_id' => $forumId,
                            'user_id' => Auth::id(),
                            'title' => $title,
                            'content' => $content,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'last_reply_at' => date('Y-m-d H:i:s')
                        ]);

                        // 关联上传的文件到帖子
                        Upload::linkToPost(Auth::id(), $postId);

                        // 更新板块帖子数
                        $db->query("UPDATE {$db->table('forums')} SET post_count = post_count + 1 WHERE id = ?", [$forumId]);

                        set_message('发布成功');
                        redirect('index.php?module=post&action=view&id=' . $postId);
                    }
                }
            }
        }

        return [
            'template' => 'post_create',
            'data' => [
                'parentForums' => $parentForums,
                'childForums' => $childForums,
                'selectableForums' => $selectableForums,
                'error' => $error,
                'formData' => $formData
            ]
        ];
    }

    /**
     * 编辑帖子
     */
    private function edit() {
        if (Auth::guest()) {
            redirect('index.php?module=user&action=login');
        }

        $db = DB::getInstance();
        $postId = intval($_GET['id'] ?? 0);
        $error = '';

        if ($postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php');
        }

        // 获取帖子信息
        $post = $db->fetch("SELECT * FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        if (!$post) {
            set_message('帖子不存在', 'error');
            redirect('index.php');
        }

        // 检查权限（只能编辑自己的帖子）
        if ($post['user_id'] != Auth::id()) {
            set_message('无权编辑此帖子', 'error');
            redirect('index.php?module=post&action=view&id=' . $postId);
        }

        // 获取板块列表
        $allForums = $db->fetchAll("SELECT * FROM {$db->table('forums')} ORDER BY sort_order ASC");
        $parentForums = [];
        $childForums = [];

        foreach ($allForums as $forum) {
            if ($forum['parent_id'] == 0) {
                $parentForums[] = $forum;
            } else {
                $childForums[$forum['parent_id']][] = $forum;
            }
        }

        // 过滤掉有子分类的一级分类
        $selectableForums = [];
        foreach ($allForums as $forum) {
            if ($forum['parent_id'] > 0 || !isset($childForums[$forum['id']])) {
                $selectableForums[] = $forum;
            }
        }

        // 初始化表单数据
        $formData = [
            'forum_id' => $post['forum_id'],
            'title' => $post['title'],
            'content' => $post['content']
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $forumId = intval($_POST['forum_id'] ?? 0);
                $title = trim($_POST['title'] ?? '');
                $content = trim($_POST['content'] ?? '');

                // 保存表单数据用于回显
                $formData = [
                    'forum_id' => $forumId,
                    'title' => $title,
                    'content' => $content
                ];

                // 检查是否强制选择分类
                if (Settings::get('is_force_forum', '1') === '1' && $forumId <= 0) {
                    $error = '请选择板块';
                } elseif ($forumId > 0) {
                    $selectedForum = null;
                    foreach ($allForums as $f) {
                        if ($f['id'] == $forumId) {
                            $selectedForum = $f;
                            break;
                        }
                    }
                    if ($selectedForum && $selectedForum['parent_id'] == 0 && isset($childForums[$forumId])) {
                        $error = '请选择二级分类';
                    }
                }

                if (empty($error)) {
                    if (empty($title) || mb_strlen($title) < 2) {
                        $error = '标题至少2个字';
                    } elseif (empty($content) || mb_strlen($content) < 5) {
                        $error = '内容至少5个字';
                    } else {
                        // 更新帖子
                        $db->update('posts', [
                            'forum_id' => $forumId,
                            'title' => $title,
                            'content' => $content,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'edit_count' => $post['edit_count'] + 1,
                            'last_edit_at' => date('Y-m-d H:i:s')
                        ], 'id = ?', [$postId]);

                        // 关联上传的文件到帖子
                        Upload::linkToPost(Auth::id(), $postId);

                        set_message('编辑成功');
                        redirect('index.php?module=post&action=view&id=' . $postId);
                    }
                }
            }
        }

        return [
            'template' => 'post_edit',
            'data' => [
                'post' => $post,
                'parentForums' => $parentForums,
                'childForums' => $childForums,
                'selectableForums' => $selectableForums,
                'error' => $error,
                'formData' => $formData
            ]
        ];
    }
    
    private function view() {
        $db = DB::getInstance();
        $postId = intval($_GET['id'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        
        if ($postId <= 0) {
            redirect('index.php');
        }
        
        // 获取帖子详情
        $post = $db->fetch(
            "SELECT p.*, u.username, u.avatar, f.name as forum_name 
             FROM {$db->table('posts')} p 
             LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
             LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
             WHERE p.id = ? LIMIT 1",
            [$postId]
        );
        
        if (!$post) {
            redirect('index.php');
        }
        
        // 增加浏览数
        $db->query("UPDATE {$db->table('posts')} SET views = views + 1 WHERE id = ?", [$postId]);
        $post['views']++;
        
        // 获取回复列表
        $total = $db->count('replies', 'post_id = ?', [$postId]);
        $offset = ($page - 1) * REPLIES_PER_PAGE;
        
        $replies = $db->fetchAll(
            "SELECT r.*, u.username, u.avatar 
             FROM {$db->table('replies')} r 
             LEFT JOIN {$db->table('users')} u ON r.user_id = u.id 
             WHERE r.post_id = ? 
             ORDER BY r.created_at ASC 
             LIMIT {$offset}, " . REPLIES_PER_PAGE,
            [$postId]
        );
        
        // 获取楼中楼评论
        $replyIds = array_column($replies, 'id');
        $replyComments = [];
        $totalComments = 0;
        if (!empty($replyIds)) {
            $placeholders = implode(',', array_fill(0, count($replyIds), '?'));
            $comments = $db->fetchAll(
                "SELECT rc.*, u.id as user_id, u.username, u.avatar, tu.id as to_user_id, tu.username as to_username 
                 FROM {$db->table('reply_comments')} rc 
                 LEFT JOIN {$db->table('users')} u ON rc.user_id = u.id 
                 LEFT JOIN {$db->table('users')} tu ON rc.to_user_id = tu.id 
                 WHERE rc.reply_id IN ({$placeholders}) 
                 ORDER BY rc.created_at ASC",
                $replyIds
            );
            foreach ($comments as $comment) {
                $replyComments[$comment['reply_id']][] = $comment;
                $totalComments++;
            }
        }
        
        // 计算总回复数（一级回复 + 楼中楼评论）
        $totalReplies = $total + $totalComments;
        
        // 获取当前用户的点赞和收藏状态
        $userLiked = false;
        $userFavorited = false;
        
        if (Auth::check()) {
            $userId = Auth::id();
            
            // 检查是否已点赞
            $likeRecord = $db->fetch(
                "SELECT id FROM {$db->table('post_likes')} WHERE post_id = ? AND user_id = ? LIMIT 1",
                [$postId, $userId]
            );
            $userLiked = $likeRecord !== false;
            
            // 检查是否已收藏
            $favoriteRecord = $db->fetch(
                "SELECT id FROM {$db->table('post_favorites')} WHERE post_id = ? AND user_id = ? LIMIT 1",
                [$postId, $userId]
            );
            $userFavorited = $favoriteRecord !== false;
        }
        
        return [
            'template' => 'post_view',
            'data' => [
                'post' => $post,
                'replies' => $replies,
                'replyComments' => $replyComments,
                'page' => $page,
                'total' => $totalReplies,
                'totalPages' => ceil($total / REPLIES_PER_PAGE),
                'userLiked' => $userLiked,
                'userFavorited' => $userFavorited
            ]
        ];
    }
    
    private function reply() {
        if (Auth::guest()) {
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            set_message('安全验证失败', 'error');
            redirect('index.php');
        }
        
        $db = DB::getInstance();
        $postId = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        
        if ($postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php');
        }
        
        if (empty($content) || mb_strlen($content) < 2) {
            set_message('回复内容至少2个字', 'error');
            redirect('index.php?module=post&action=view&id=' . $postId);
        }

        // 检查评论间隔时间
        $replyInterval = intval(Settings::get('reply_interval', '0'));
        if ($replyInterval > 0) {
            $lastReply = $db->fetch("SELECT created_at FROM {$db->table('replies')} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [Auth::id()]);
            if ($lastReply) {
                $lastTime = strtotime($lastReply['created_at']);
                $currentTime = time();
                $diff = $currentTime - $lastTime;
                if ($diff < $replyInterval) {
                    $waitTime = $replyInterval - $diff;
                    set_message("评论太频繁，请等待 {$waitTime} 秒后再试", 'error');
                    redirect('index.php?module=post&action=view&id=' . $postId);
                }
            }
        }

        // 检查帖子是否存在
        $post = $db->fetch("SELECT id, user_id, title FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        if (!$post) {
            set_message('帖子不存在', 'error');
            redirect('index.php');
        }
        
        // 创建回复
        $db->insert('replies', [
            'post_id' => $postId,
            'user_id' => Auth::id(),
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // 更新帖子回复数和最后回复信息
        $db->update('posts', [
            'replies' => $db->count('replies', 'post_id = ?', [$postId]),
            'last_reply_at' => date('Y-m-d H:i:s'),
            'last_reply_user_id' => Auth::id()
        ], 'id = ?', [$postId]);
        
        // 发送消息通知给帖子作者
        if ($post['user_id'] != Auth::id()) {
            $currentUser = Auth::user();
            if ($currentUser) {
                Notification::sendPostReply(
                    $postId,
                    $post['user_id'],
                    Auth::id(),
                    $currentUser['username'],
                    $post['title']
                );
            }
        }
        
        set_message('回复成功');
        redirect('index.php?module=post&action=view&id=' . $postId);
    }

    /**
     * 编辑一级回复
     */
    private function editReply() {
        if (Auth::guest()) {
            redirect('index.php?module=user&action=login');
        }

        $db = DB::getInstance();
        $replyId = intval($_GET['id'] ?? 0);
        $postId = intval($_GET['post_id'] ?? 0);

        if ($replyId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php');
        }

        // 获取回复信息
        $reply = $db->fetch("SELECT * FROM {$db->table('replies')} WHERE id = ? LIMIT 1", [$replyId]);
        if (!$reply) {
            set_message('回复不存在', 'error');
            redirect('index.php');
        }

        // 检查权限（只能编辑自己的回复）
        if ($reply['user_id'] != Auth::id()) {
            set_message('无权编辑此回复', 'error');
            redirect('index.php?module=post&action=view&id=' . $reply['post_id']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                set_message('安全验证失败', 'error');
                redirect('index.php?module=post&action=view&id=' . $reply['post_id']);
            }

            $content = trim($_POST['content'] ?? '');

            if (empty($content) || mb_strlen($content) < 2) {
                set_message('回复内容至少2个字', 'error');
                redirect('index.php?module=post&action=view&id=' . $reply['post_id']);
            }

            // 更新回复
            $db->update('replies', [
                'content' => $content,
                'edit_count' => $reply['edit_count'] + 1,
                'last_edit_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$replyId]);

            set_message('编辑成功');
            redirect('index.php?module=post&action=view&id=' . $reply['post_id']);
        }

        // 如果是AJAX请求，返回JSON
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'reply' => $reply
            ]);
            exit;
        }

        // 非AJAX请求重定向到帖子页面
        redirect('index.php?module=post&action=view&id=' . $reply['post_id']);
    }

    private function replyComment() {
        if (Auth::guest()) {
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            set_message('安全验证失败', 'error');
            redirect('index.php');
        }
        
        $db = DB::getInstance();
        $replyId = intval($_POST['reply_id'] ?? 0);
        $postId = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $toUserId = intval($_POST['to_user_id'] ?? 0);
        
        if ($replyId <= 0 || $postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php');
        }
        
        if (empty($content) || mb_strlen($content) < 2) {
            set_message('回复内容至少2个字', 'error');
            redirect('index.php?module=post&action=view&id=' . $postId);
        }

        // 检查评论间隔时间（楼中楼也使用 reply_interval 设置）
        $replyInterval = intval(Settings::get('reply_interval', '0'));
        if ($replyInterval > 0) {
            $lastComment = $db->fetch("SELECT created_at FROM {$db->table('reply_comments')} WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [Auth::id()]);
            if ($lastComment) {
                $lastTime = strtotime($lastComment['created_at']);
                $currentTime = time();
                $diff = $currentTime - $lastTime;
                if ($diff < $replyInterval) {
                    $waitTime = $replyInterval - $diff;
                    set_message("评论太频繁，请等待 {$waitTime} 秒后再试", 'error');
                    redirect('index.php?module=post&action=view&id=' . $postId);
                }
            }
        }

        // 检查一级回复是否存在
        $reply = $db->fetch("SELECT id, user_id FROM {$db->table('replies')} WHERE id = ? LIMIT 1", [$replyId]);
        if (!$reply) {
            set_message('回复不存在', 'error');
            redirect('index.php');
        }
        
        // 获取帖子信息
        $post = $db->fetch("SELECT id, title FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        
        // 创建楼中楼评论
        $db->insert('reply_comments', [
            'reply_id' => $replyId,
            'user_id' => Auth::id(),
            'to_user_id' => $toUserId,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // 发送消息通知
        if ($post) {
            $currentUser = Auth::user();
            if ($currentUser) {
                // 如果指定了回复对象，通知被回复的人
                if ($toUserId > 0 && $toUserId != Auth::id()) {
                    Notification::sendCommentReply(
                        $replyId,
                        $toUserId,
                        Auth::id(),
                        $currentUser['username'],
                        $post['title']
                    );
                }
                // 同时通知一级回复的作者（如果不是自己）
                elseif ($reply['user_id'] != Auth::id() && $reply['user_id'] != $toUserId) {
                    Notification::sendCommentReply(
                        $replyId,
                        $reply['user_id'],
                        Auth::id(),
                        $currentUser['username'],
                        $post['title']
                    );
                }
            }
        }
        
        set_message('回复成功');
        redirect('index.php?module=post&action=view&id=' . $postId);
    }
    
    /**
     * 点赞/取消点赞
     */
    private function like() {
        if (Auth::guest()) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '请先登录']);
                exit;
            }
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        
        $db = DB::getInstance();
        $postId = intval($_POST['post_id'] ?? 0);
        
        if ($postId <= 0) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            redirect('index.php');
        }
        
        // 检查帖子是否存在
        $post = $db->fetch("SELECT id, user_id, title FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        if (!$post) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '帖子不存在']);
                exit;
            }
            redirect('index.php');
        }
        
        $userId = Auth::id();
        $currentUser = Auth::user();
        $username = $currentUser ? $currentUser['username'] : '';
        
        // 检查是否已经点赞
        $existing = $db->fetch(
            "SELECT id FROM {$db->table('post_likes')} WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );
        
        if ($existing) {
            // 取消点赞
            $db->query("DELETE FROM {$db->table('post_likes')} WHERE id = ?", [$existing['id']]);
            $db->query("UPDATE {$db->table('posts')} SET likes = likes - 1 WHERE id = ?", [$postId]);
            $liked = false;
        } else {
            // 添加点赞
            $db->insert('post_likes', [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->query("UPDATE {$db->table('posts')} SET likes = likes + 1 WHERE id = ?", [$postId]);
            $liked = true;
            
            // 发送点赞通知（不是自己的帖子）
            if ($post['user_id'] != $userId) {
                Notification::sendPostLike(
                    $postId,
                    $post['user_id'],
                    $userId,
                    $username,
                    $post['title']
                );
            }
        }
        
        // 获取最新点赞数
        $post = $db->fetch("SELECT likes FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        
        if ($this->isAjaxRequest()) {
            echo json_encode([
                'success' => true,
                'liked' => $liked,
                'count' => $post['likes']
            ]);
            exit;
        }
        
        redirect('index.php?module=post&action=view&id=' . $postId);
    }
    
    /**
     * 收藏/取消收藏
     */
    private function favorite() {
        if (Auth::guest()) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '请先登录']);
                exit;
            }
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        
        $db = DB::getInstance();
        $postId = intval($_POST['post_id'] ?? 0);
        
        if ($postId <= 0) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            redirect('index.php');
        }
        
        // 检查帖子是否存在
        $post = $db->fetch("SELECT id, user_id, title FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        if (!$post) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '帖子不存在']);
                exit;
            }
            redirect('index.php');
        }
        
        $userId = Auth::id();
        $currentUser = Auth::user();
        $username = $currentUser ? $currentUser['username'] : '';

        // 不能收藏自己的帖子
        if ($post['user_id'] == $userId) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '不能收藏自己的帖子']);
                exit;
            }
            set_message('不能收藏自己的帖子', 'error');
            redirect('index.php?module=post&action=view&id=' . $postId);
        }
        
        // 检查是否已经收藏
        $existing = $db->fetch(
            "SELECT id FROM {$db->table('post_favorites')} WHERE post_id = ? AND user_id = ? LIMIT 1",
            [$postId, $userId]
        );
        
        if ($existing) {
            // 取消收藏
            $db->query("DELETE FROM {$db->table('post_favorites')} WHERE id = ?", [$existing['id']]);
            $db->query("UPDATE {$db->table('posts')} SET favorites = favorites - 1 WHERE id = ?", [$postId]);
            $favorited = false;
        } else {
            // 添加收藏
            $db->insert('post_favorites', [
                'post_id' => $postId,
                'user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->query("UPDATE {$db->table('posts')} SET favorites = favorites + 1 WHERE id = ?", [$postId]);
            $favorited = true;
            
            // 发送收藏通知
            Notification::sendPostFavorite(
                $postId,
                $post['user_id'],
                $userId,
                $username,
                $post['title']
            );
        }
        
        // 获取最新收藏数
        $post = $db->fetch("SELECT favorites FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        
        if ($this->isAjaxRequest()) {
            echo json_encode([
                'success' => true,
                'favorited' => $favorited,
                'count' => $post['favorites']
            ]);
            exit;
        }
        
        redirect('index.php?module=post&action=view&id=' . $postId);
    }
    
    /**
     * 判断是否是AJAX请求
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * 检查用户是否有权限在指定板块发帖
     * @param array $forum 板块信息
     * @return bool 是否有权限
     */
    private function canPostInForum($forum) {
        // 如果没有设置允许发帖用户，则所有人都可以发帖
        if (empty($forum['allowed_users'])) {
            return true;
        }

        // 解析允许发帖的用户ID列表
        $allowedUserIds = array_filter(array_map('trim', explode(',', $forum['allowed_users'])));

        // 如果没有有效的用户ID，则所有人都可以发帖
        if (empty($allowedUserIds)) {
            return true;
        }

        // 检查当前用户是否在允许列表中
        return in_array(Auth::id(), $allowedUserIds);
    }
}
