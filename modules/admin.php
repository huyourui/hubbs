<?php
/**
 * HuBBS - 后台管理模块
 */

class AdminModule {
    
    public function handle() {
        // 检查是否管理员
        if (!Auth::isAdmin()) {
            set_message('无权访问', 'error');
            redirect('index.php');
        }
        
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                return $this->dashboard();
            case 'posts':
                return $this->posts();
            case 'users':
                return $this->users();
            case 'forums':
                return $this->forums();
            case 'settings':
                return $this->settings();
            case 'mail':
                return $this->mail();
            case 'links':
                return $this->links();
            default:
                return $this->dashboard();
        }
    }
    
    private function dashboard() {
        $db = DB::getInstance();

        // 统计数据
        $stats = [
            'users' => $db->count('users'),
            'posts' => $db->count('posts'),
            'replies' => $db->count('replies'),
            'forums' => $db->count('forums'),
        ];

        // 今日新增
        $today = date('Y-m-d');
        $stats['today_users'] = $db->count('users', "DATE(created_at) = ?", [$today]);
        $stats['today_posts'] = $db->count('posts', "DATE(created_at) = ?", [$today]);

        // 服务器信息
        $serverInfo = [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'] ?? 'Unknown',
            'max_upload' => ini_get('upload_max_filesize'),
            'max_execution' => ini_get('max_execution_time') . 's',
            'memory_limit' => ini_get('memory_limit'),
            'os' => php_uname('s') . ' ' . php_uname('r'),
        ];

        // 论坛版本信息
        $appInfo = [
            'name' => 'HuBBS',
            'version' => HUBBS_VERSION,
            'release_date' => '2024-01-01',
        ];

        return [
            'template' => 'admin_dashboard',
            'data' => [
                'stats' => $stats,
                'serverInfo' => $serverInfo,
                'appInfo' => $appInfo,
            ]
        ];
    }
    
    private function posts() {
        $subAction = $_GET['sub'] ?? 'list';
        
        switch ($subAction) {
            case 'top':
                return $this->postTop();
            case 'essence':
                return $this->postEssence();
            case 'lock':
                return $this->postLock();
            case 'delete':
                return $this->postDelete();
            case 'batch':
                return $this->postBatch();
            default:
                return $this->postList();
        }
    }
    
    private function postList() {
        $db = DB::getInstance();
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20;
        
        $total = $db->count('posts');
        $offset = ($page - 1) * $perPage;
        
        $posts = $db->fetchAll(
            "SELECT p.*, u.username, f.name as forum_name 
             FROM {$db->table('posts')} p 
             LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
             LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
             ORDER BY p.created_at DESC 
             LIMIT {$offset}, {$perPage}"
        );
        
        return [
            'template' => 'admin_posts',
            'data' => [
                'posts' => $posts,
                'page' => $page,
                'total' => $total,
                'totalPages' => ceil($total / $perPage)
            ]
        ];
    }
    
    private function postTop() {
        $db = DB::getInstance();
        $postId = intval($_GET['id'] ?? 0);
        $isTop = intval($_GET['is_top'] ?? 0);
        
        if ($postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=posts');
        }
        
        $db->update('posts', ['is_top' => $isTop], 'id = ?', [$postId]);
        
        $msg = $isTop ? '帖子已置顶' : '帖子已取消置顶';
        set_message($msg);
        redirect('index.php?module=admin&action=posts');
    }
    
    private function postEssence() {
        $db = DB::getInstance();
        $postId = intval($_GET['id'] ?? 0);
        $isEssence = intval($_GET['is_essence'] ?? 0);
        
        if ($postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=posts');
        }
        
        $db->update('posts', ['is_essence' => $isEssence], 'id = ?', [$postId]);
        
        $msg = $isEssence ? '帖子已加精' : '帖子已取消加精';
        set_message($msg);
        redirect('index.php?module=admin&action=posts');
    }
    
    private function postLock() {
        $db = DB::getInstance();
        $postId = intval($_GET['id'] ?? 0);
        $isLocked = intval($_GET['is_locked'] ?? 0);
        
        if ($postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=posts');
        }
        
        $db->update('posts', ['is_locked' => $isLocked], 'id = ?', [$postId]);
        
        $msg = $isLocked ? '帖子已锁定' : '帖子已解锁';
        set_message($msg);
        redirect('index.php?module=admin&action=posts');
    }
    
    private function postDelete() {
        $db = DB::getInstance();
        $postId = intval($_GET['id'] ?? 0);
        
        if ($postId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=posts');
        }
        
        // 获取帖子信息，用于更新板块帖子数
        $post = $db->fetch("SELECT forum_id FROM {$db->table('posts')} WHERE id = ? LIMIT 1", [$postId]);
        
        // 删除帖子的回复
        $db->delete('replies', 'post_id = ?', [$postId]);
        
        // 删除帖子
        $db->delete('posts', 'id = ?', [$postId]);
        
        // 更新板块帖子数
        if ($post) {
            $count = $db->count('posts', 'forum_id = ?', [$post['forum_id']]);
            $db->update('forums', ['post_count' => $count], 'id = ?', [$post['forum_id']]);
        }
        
        set_message('帖子已删除');
        redirect('index.php?module=admin&action=posts');
    }
    
    private function postBatch() {
        $db = DB::getInstance();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?module=admin&action=posts');
        }
        
        if (!verify_csrf($_POST['csrf_token'] ?? '')) {
            set_message('安全验证失败', 'error');
            redirect('index.php?module=admin&action=posts');
        }
        
        $postIds = $_POST['post_ids'] ?? [];
        $action = $_POST['batch_action'] ?? '';
        
        if (empty($postIds)) {
            set_message('请选择要操作的帖子', 'error');
            redirect('index.php?module=admin&action=posts');
        }
        
        $ids = array_map('intval', $postIds);
        $idList = implode(',', $ids);
        
        switch ($action) {
            case 'delete':
                // 获取所有帖子的板块ID
                $posts = $db->fetchAll("SELECT id, forum_id FROM {$db->table('posts')} WHERE id IN ({$idList})");
                $forumIds = [];
                foreach ($posts as $post) {
                    $forumIds[] = $post['forum_id'];
                    // 删除回复
                    $db->delete('replies', 'post_id = ?', [$post['id']]);
                }
                // 删除帖子
                $db->query("DELETE FROM {$db->table('posts')} WHERE id IN ({$idList})");
                // 更新板块帖子数
                foreach (array_unique($forumIds) as $forumId) {
                    $count = $db->count('posts', 'forum_id = ?', [$forumId]);
                    $db->update('forums', ['post_count' => $count], 'id = ?', [$forumId]);
                }
                set_message('批量删除成功');
                break;
                
            case 'top':
                $db->query("UPDATE {$db->table('posts')} SET is_top = 1 WHERE id IN ({$idList})");
                set_message('批量置顶成功');
                break;
                
            case 'untop':
                $db->query("UPDATE {$db->table('posts')} SET is_top = 0 WHERE id IN ({$idList})");
                set_message('批量取消置顶成功');
                break;
                
            case 'essence':
                $db->query("UPDATE {$db->table('posts')} SET is_essence = 1 WHERE id IN ({$idList})");
                set_message('批量加精成功');
                break;
                
            case 'unessence':
                $db->query("UPDATE {$db->table('posts')} SET is_essence = 0 WHERE id IN ({$idList})");
                set_message('批量取消加精成功');
                break;
                
            case 'lock':
                $db->query("UPDATE {$db->table('posts')} SET is_locked = 1 WHERE id IN ({$idList})");
                set_message('批量锁定成功');
                break;
                
            case 'unlock':
                $db->query("UPDATE {$db->table('posts')} SET is_locked = 0 WHERE id IN ({$idList})");
                set_message('批量解锁成功');
                break;
                
            default:
                set_message('未知操作', 'error');
        }
        
        redirect('index.php?module=admin&action=posts');
    }
    
    private function users() {
        $db = DB::getInstance();
        $subAction = $_GET['sub'] ?? 'list';
        
        switch ($subAction) {
            case 'edit':
                return $this->userEdit();
            case 'ban':
                return $this->userBan();
            case 'delete':
                return $this->userDelete();
            case 'restore':
                return $this->userRestore();
            default:
                return $this->userList();
        }
    }
    
    private function userList() {
        $db = DB::getInstance();
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20;
        
        $total = $db->count('users');
        $offset = ($page - 1) * $perPage;
        
        $users = $db->fetchAll(
            "SELECT * FROM {$db->table('users')} 
             ORDER BY created_at DESC 
             LIMIT {$offset}, {$perPage}"
        );
        
        return [
            'template' => 'admin_users',
            'data' => [
                'users' => $users,
                'page' => $page,
                'total' => $total,
                'totalPages' => ceil($total / $perPage)
            ]
        ];
    }
    
    private function userEdit() {
        $db = DB::getInstance();
        $userId = intval($_GET['id'] ?? 0);
        $error = '';
        
        if ($userId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=users');
        }
        
        $user = $db->fetch(
            "SELECT * FROM {$db->table('users')} WHERE id = ? LIMIT 1",
            [$userId]
        );
        
        if (!$user) {
            set_message('用户不存在', 'error');
            redirect('index.php?module=admin&action=users');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $data = [
                    'username' => trim($_POST['username'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'is_admin' => isset($_POST['is_admin']) ? 1 : 0,
                ];
                
                // 如果设置了新密码
                if (!empty($_POST['password'])) {
                    $data['password'] = password_hash_hubbs($_POST['password']);
                }
                
                $db->update('users', $data, 'id = ?', [$userId]);
                set_message('用户更新成功');
                redirect('index.php?module=admin&action=users');
            }
        }
        
        return [
            'template' => 'admin_user_edit',
            'data' => [
                'user' => $user,
                'error' => $error
            ]
        ];
    }
    
    private function userBan() {
        $db = DB::getInstance();
        $userId = intval($_GET['id'] ?? 0);
        $status = intval($_GET['status'] ?? 0);
        
        if ($userId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=users');
        }
        
        // 不能封禁自己
        if ($userId == Auth::id()) {
            set_message('不能封禁自己', 'error');
            redirect('index.php?module=admin&action=users');
        }
        
        $db->update('users', ['status' => $status], 'id = ?', [$userId]);
        
        $msg = $status == 0 ? '用户已封禁' : '用户已解封';
        set_message($msg);
        redirect('index.php?module=admin&action=users');
    }
    
    private function userDelete() {
        $db = DB::getInstance();
        $userId = intval($_GET['id'] ?? 0);

        if ($userId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=users');
        }

        // 不能删除自己
        if ($userId == Auth::id()) {
            set_message('不能删除自己', 'error');
            redirect('index.php?module=admin&action=users');
        }

        // 获取用户信息
        $user = $db->fetch("SELECT email, username FROM {$db->table('users')} WHERE id = ?", [$userId]);
        if (!$user) {
            set_message('用户不存在', 'error');
            redirect('index.php?module=admin&action=users');
        }

        // 生成归档邮箱格式：deleted_{用户ID}_{时间戳}_{随机数}@archived.hubbs
        $archivedEmail = 'deleted_' . $userId . '_' . time() . '_' . mt_rand(1000, 9999) . '@archived.hubbs';

        // 软删除用户 - 设置删除时间戳，归档邮箱和用户名
        $db->update('users', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'original_email' => $user['email'],  // 保存原始邮箱
            'email' => $archivedEmail,           // 使用归档邮箱占位
            'username' => '[已注销_' . $userId . ']'  // 标记用户名已注销
        ], 'id = ?', [$userId]);

        // 清除用户的登录状态
        $db->delete('remember_tokens', 'user_id = ?', [$userId]);

        set_message('用户已注销，邮箱已释放可供重新注册');
        redirect('index.php?module=admin&action=users');
    }

    private function userRestore() {
        $db = DB::getInstance();
        $userId = intval($_GET['id'] ?? 0);

        if ($userId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=users');
        }

        // 获取被注销的用户信息
        $user = $db->fetch("SELECT * FROM {$db->table('users')} WHERE id = ? AND deleted_at IS NOT NULL", [$userId]);
        if (!$user) {
            set_message('用户不存在或未被注销', 'error');
            redirect('index.php?module=admin&action=users');
        }

        $originalEmail = $user['original_email'];
        $originalUsername = str_replace('[已注销_' . $userId . ']', '', $user['username']);

        // 检查原始邮箱是否已被其他用户使用
        $emailConflict = false;
        $usernameConflict = false;

        if ($originalEmail) {
            $emailExists = $db->fetch(
                "SELECT id FROM {$db->table('users')} WHERE email = ? AND deleted_at IS NULL AND id != ? LIMIT 1",
                [$originalEmail, $userId]
            );
            if ($emailExists) {
                $emailConflict = true;
            }
        }

        // 检查原始用户名是否已被其他用户使用
        $usernameExists = $db->fetch(
            "SELECT id FROM {$db->table('users')} WHERE username = ? AND deleted_at IS NULL AND id != ? LIMIT 1",
            [$originalUsername, $userId]
        );
        if ($usernameExists) {
            $usernameConflict = true;
        }

        // 如果有冲突，需要管理员处理
        if ($emailConflict || $usernameConflict) {
            $conflicts = [];
            if ($emailConflict) $conflicts[] = '邮箱已被其他用户使用';
            if ($usernameConflict) $conflicts[] = '用户名已被其他用户使用';

            set_message(
                '无法恢复用户：' . implode('、', $conflicts) . '。' .
                '请先在后台修改冲突的用户信息，或让用户使用新的邮箱/用户名注册。',
                'error'
            );
            redirect('index.php?module=admin&action=users');
        }

        // 恢复用户 - 清除删除时间戳，恢复原始邮箱和用户名
        $updateData = [
            'deleted_at' => null,
            'email' => $originalEmail,
            'original_email' => null,
            'username' => $originalUsername
        ];

        $db->update('users', $updateData, 'id = ?', [$userId]);

        set_message('用户已成功恢复，可以使用原邮箱和密码登录');
        redirect('index.php?module=admin&action=users');
    }

    private function forums() {
        $db = DB::getInstance();
        $subAction = $_GET['sub'] ?? 'list';
        
        switch ($subAction) {
            case 'add':
                return $this->forumAdd();
            case 'edit':
                return $this->forumEdit();
            case 'delete':
                return $this->forumDelete();
            case 'sort':
                return $this->forumSort();
            default:
                return $this->forumList();
        }
    }
    
    private function forumList() {
        $db = DB::getInstance();
        
        // 获取所有分类，按sort_order排序
        $allForums = $db->fetchAll(
            "SELECT * FROM {$db->table('forums')} ORDER BY sort_order ASC, id ASC"
        );
        
        // 构建树形结构
        $forums = [];
        $children = [];
        
        foreach ($allForums as $forum) {
            if ($forum['parent_id'] == 0) {
                $forums[] = $forum;
            } else {
                $children[$forum['parent_id']][] = $forum;
            }
        }
        
        return [
            'template' => 'admin_forums',
            'data' => [
                'forums' => $forums,
                'children' => $children
            ]
        ];
    }
    
    private function forumAdd() {
        $db = DB::getInstance();
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $parentId = intval($_POST['parent_id'] ?? 0);
                $icon = trim($_POST['icon'] ?? '');
                $sortOrder = intval($_POST['sort_order'] ?? 0);
                
                if (empty($name)) {
                    $error = '分类名称不能为空';
                } else {
                    $db->insert('forums', [
                        'parent_id' => $parentId,
                        'name' => $name,
                        'description' => $description,
                        'icon' => $icon,
                        'sort_order' => $sortOrder,
                        'post_count' => 0
                    ]);
                    
                    set_message('分类添加成功');
                    redirect('index.php?module=admin&action=forums');
                }
            }
        }
        
        // 获取一级分类作为父分类选项
        $parentForums = $db->fetchAll(
            "SELECT id, name FROM {$db->table('forums')} WHERE parent_id = 0 ORDER BY sort_order ASC"
        );
        
        return [
            'template' => 'admin_forum_edit',
            'data' => [
                'forum' => null,
                'parentForums' => $parentForums,
                'error' => $error,
                'isEdit' => false
            ]
        ];
    }
    
    private function forumEdit() {
        $db = DB::getInstance();
        $forumId = intval($_GET['id'] ?? 0);
        $error = '';
        
        if ($forumId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=forums');
        }
        
        $forum = $db->fetch(
            "SELECT * FROM {$db->table('forums')} WHERE id = ? LIMIT 1",
            [$forumId]
        );
        
        if (!$forum) {
            set_message('分类不存在', 'error');
            redirect('index.php?module=admin&action=forums');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $parentId = intval($_POST['parent_id'] ?? 0);
                $icon = trim($_POST['icon'] ?? '');
                $sortOrder = intval($_POST['sort_order'] ?? 0);
                
                // 检查是否将自己设为子分类（避免循环）
                if ($parentId == $forumId) {
                    $error = '不能将自己设为父分类';
                } elseif ($parentId > 0) {
                    // 检查父分类是否已经是当前分类的子分类
                    $isChild = $this->isChildForum($db, $forumId, $parentId);
                    if ($isChild) {
                        $error = '不能将子分类设为父分类';
                    }
                }
                
                if (empty($error)) {
                    if (empty($name)) {
                        $error = '分类名称不能为空';
                    } else {
                        $db->update('forums', [
                            'parent_id' => $parentId,
                            'name' => $name,
                            'description' => $description,
                            'icon' => $icon,
                            'sort_order' => $sortOrder
                        ], 'id = ?', [$forumId]);
                        
                        set_message('分类更新成功');
                        redirect('index.php?module=admin&action=forums');
                    }
                }
            }
        }
        
        // 获取一级分类作为父分类选项（排除自己）
        $parentForums = $db->fetchAll(
            "SELECT id, name FROM {$db->table('forums')} WHERE parent_id = 0 AND id != ? ORDER BY sort_order ASC",
            [$forumId]
        );
        
        return [
            'template' => 'admin_forum_edit',
            'data' => [
                'forum' => $forum,
                'parentForums' => $parentForums,
                'error' => $error,
                'isEdit' => true
            ]
        ];
    }
    
    private function isChildForum($db, $parentId, $checkId) {
        $children = $db->fetchAll(
            "SELECT id FROM {$db->table('forums')} WHERE parent_id = ?",
            [$parentId]
        );
        
        foreach ($children as $child) {
            if ($child['id'] == $checkId) {
                return true;
            }
            if ($this->isChildForum($db, $child['id'], $checkId)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function forumDelete() {
        $db = DB::getInstance();
        $forumId = intval($_GET['id'] ?? 0);
        
        if ($forumId <= 0) {
            set_message('参数错误', 'error');
            redirect('index.php?module=admin&action=forums');
        }
        
        // 检查是否有子分类
        $hasChildren = $db->count('forums', 'parent_id = ?', [$forumId]);
        if ($hasChildren > 0) {
            set_message('请先删除该分类下的子分类', 'error');
            redirect('index.php?module=admin&action=forums');
        }
        
        // 检查该分类下是否有帖子
        $postCount = $db->count('posts', 'forum_id = ?', [$forumId]);
        if ($postCount > 0) {
            set_message('该分类下还有帖子，无法删除', 'error');
            redirect('index.php?module=admin&action=forums');
        }
        
        $db->delete('forums', 'id = ?', [$forumId]);
        set_message('分类已删除');
        redirect('index.php?module=admin&action=forums');
    }
    
    private function forumSort() {
        $db = DB::getInstance();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orders = $_POST['sort_order'] ?? [];
            
            foreach ($orders as $id => $order) {
                $db->update('forums', [
                    'sort_order' => intval($order)
                ], 'id = ?', [intval($id)]);
            }
            
            set_message('排序已更新');
        }
        
        redirect('index.php?module=admin&action=forums');
    }
    
    private function settings() {
        $error = '';
        $success = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $data = [
                    'site_title' => trim($_POST['site_title'] ?? ''),
                    'site_subtitle' => trim($_POST['site_subtitle'] ?? ''),
                    'site_keywords' => trim($_POST['site_keywords'] ?? ''),
                    'site_description' => trim($_POST['site_description'] ?? ''),
                    'site_copyright' => trim($_POST['site_copyright'] ?? ''),
                    'enable_register' => isset($_POST['enable_register']) ? '1' : '0',
                    'is_force_forum' => isset($_POST['is_force_forum']) ? '1' : '0',
                    'register_email_suffix' => trim($_POST['register_email_suffix'] ?? ''),
                    'register_banned_words' => trim($_POST['register_banned_words'] ?? ''),
                    'username_min_length' => trim($_POST['username_min_length'] ?? '2'),
                    'username_max_length' => trim($_POST['username_max_length'] ?? '20'),
                    'post_min_length' => trim($_POST['post_min_length'] ?? '5'),
                    'post_max_length' => trim($_POST['post_max_length'] ?? '10000'),
                    'reply_min_length' => trim($_POST['reply_min_length'] ?? '2'),
                    'reply_max_length' => trim($_POST['reply_max_length'] ?? '5000'),
                    'posts_per_page' => trim($_POST['posts_per_page'] ?? '20'),
                    'replies_per_page' => trim($_POST['replies_per_page'] ?? '20'),
                    'post_interval' => trim($_POST['post_interval'] ?? '0'),
                    'reply_interval' => trim($_POST['reply_interval'] ?? '0'),
                    'upload_image_exts' => trim($_POST['upload_image_exts'] ?? 'jpg,jpeg,png,gif,webp'),
                    'upload_image_max_size' => intval(($_POST['upload_image_max_size_mb'] ?? 5) * 1048576),
                    'upload_image_max_count' => trim($_POST['upload_image_max_count'] ?? '10'),
                    'upload_attachment_exts' => trim($_POST['upload_attachment_exts'] ?? 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,md'),
                    'upload_attachment_max_size' => intval(($_POST['upload_attachment_max_size_mb'] ?? 10) * 1048576),
                    'upload_attachment_max_count' => trim($_POST['upload_attachment_max_count'] ?? '5'),
                ];
                
                Settings::setMultiple($data);
                $success = '设置已保存';
            }
        }
        
        $settings = Settings::getAll();
        
        return [
            'template' => 'admin_settings',
            'data' => [
                'settings' => $settings,
                'error' => $error,
                'success' => $success
            ]
        ];
    }
    
    private function mail() {
        $error = '';
        $success = '';
        $subAction = $_GET['sub'] ?? '';
        
        // 处理测试邮件 AJAX 请求
        if ($subAction === 'test' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            $email = $_POST['email'] ?? '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
                exit;
            }
            
            // 临时加载当前提交的设置
            $testSettings = [
                'mail_enabled' => '1',
                'mail_method' => $_POST['mail_method'] ?? 'smtp',
                'mail_host' => $_POST['mail_host'] ?? '',
                'mail_port' => $_POST['mail_port'] ?? '587',
                'mail_encryption' => $_POST['mail_encryption'] ?? 'tls',
                'mail_username' => $_POST['mail_username'] ?? '',
                'mail_password' => $_POST['mail_password'] ?? '',
                'mail_from_address' => $_POST['mail_from_address'] ?? '',
                'mail_from_name' => $_POST['mail_from_name'] ?? '测试',
            ];
            
            // 重新实例化 Mailer 使用临时配置
            $mailer = new class($testSettings) {
                private $config;
                private $lastError = '';
                
                public function __construct($config) {
                    $this->config = $config;
                }
                
                public function send($to, $subject, $body) {
                    // 简化的 SMTP 发送测试
                    $host = $this->config['mail_host'];
                    $port = $this->config['mail_port'];
                    $username = $this->config['mail_username'];
                    $password = $this->config['mail_password'];
                    
                    if (empty($host) || empty($username) || empty($password)) {
                        $this->lastError = 'SMTP 配置不完整';
                        return false;
                    }
                    
                    try {
                        $socket = @fsockopen('ssl://' . $host, $port, $errno, $errstr, 10);
                        if (!$socket) {
                            $this->lastError = "连接 SMTP 服务器失败: $errstr";
                            return false;
                        }
                        fclose($socket);
                        return true;
                    } catch (Exception $e) {
                        $this->lastError = $e->getMessage();
                        return false;
                    }
                }
                
                public function getLastError() {
                    return $this->lastError;
                }
            };
            
            // 实际使用 Mailer 类发送测试邮件
            $mailer = Mailer::getInstance();
            $siteTitle = Settings::get('site_title', 'HuBBS');
            
            $result = $mailer->send($email, "【{$siteTitle}】邮件测试", 
                "<h2>邮件测试</h2><p>如果您收到这封邮件，说明邮件配置正确！</p>");
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => '测试邮件发送成功，请查收']);
            } else {
                echo json_encode(['success' => false, 'message' => '发送失败：' . $mailer->getLastError()]);
            }
            exit;
        }
        
        // 处理保存设置
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $data = [
                    'mail_enabled' => isset($_POST['mail_enabled']) ? '1' : '0',
                    'mail_verify_register' => isset($_POST['mail_verify_register']) ? '1' : '0',
                    'mail_method' => trim($_POST['mail_method'] ?? 'smtp'),
                    'mail_provider' => trim($_POST['mail_provider'] ?? ''),
                    'mail_host' => trim($_POST['mail_host'] ?? ''),
                    'mail_port' => trim($_POST['mail_port'] ?? '587'),
                    'mail_encryption' => trim($_POST['mail_encryption'] ?? 'tls'),
                    'mail_username' => trim($_POST['mail_username'] ?? ''),
                    'mail_password' => trim($_POST['mail_password'] ?? ''),
                    'mail_from_address' => trim($_POST['mail_from_address'] ?? ''),
                    'mail_from_name' => trim($_POST['mail_from_name'] ?? ''),
                ];
                
                Settings::setMultiple($data);
                
                // 重新加载 Mailer 配置
                Mailer::getInstance()->reloadConfig();
                
                $success = '邮件设置已保存';
            }
        }
        
        $settings = Settings::getAll();
        
        return [
            'template' => 'admin_mail',
            'data' => [
                'settings' => $settings,
                'error' => $error,
                'success' => $success
            ]
        ];
    }
    
    /**
     * 友情链接管理
     */
    private function links() {
        $subAction = $_GET['sub'] ?? 'list';
        
        switch ($subAction) {
            case 'add':
                return $this->linkAdd();
            case 'edit':
                return $this->linkEdit();
            case 'delete':
                return $this->linkDelete();
            case 'sort':
                return $this->linkSort();
            default:
                return $this->linkList();
        }
    }
    
    /**
     * 友情链接列表
     */
    private function linkList() {
        $db = DB::getInstance();
        
        $links = $db->fetchAll(
            "SELECT * FROM {$db->table('links')} ORDER BY sort_order ASC, id ASC"
        );
        
        return [
            'template' => 'admin_links',
            'data' => [
                'links' => $links
            ]
        ];
    }
    
    /**
     * 添加友情链接
     */
    private function linkAdd() {
        $db = DB::getInstance();
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            $isVisible = 1; // 默认显示链接
            
            if (empty($name)) {
                $error = '链接名称不能为空';
            } elseif (empty($url)) {
                $error = '链接地址不能为空';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '链接地址格式不正确';
            } else {
                // 获取最大排序值
                $maxSort = $db->fetch("SELECT MAX(sort_order) as max_sort FROM {$db->table('links')}");
                $sortOrder = $sortOrder > 0 ? $sortOrder : ($maxSort['max_sort'] ?? 0) + 1;
                
                $db->insert('links', [
                    'name' => $name,
                    'url' => $url,
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'is_visible' => $isVisible,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                set_message('友情链接添加成功', 'success');
                redirect('index.php?module=admin&action=links');
            }
        }
        
        return [
            'template' => 'admin_link_form',
            'data' => [
                'link' => null,
                'error' => $error
            ]
        ];
    }
    
    /**
     * 编辑友情链接
     */
    private function linkEdit() {
        $db = DB::getInstance();
        $id = intval($_GET['id'] ?? 0);
        $error = '';
        $success = '';
        
        $link = $db->fetch("SELECT * FROM {$db->table('links')} WHERE id = ? LIMIT 1", [$id]);
        if (!$link) {
            set_message('友情链接不存在', 'error');
            redirect('index.php?module=admin&action=links');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            $isVisible = isset($_POST['is_visible']) ? 1 : 0;
            
            if (empty($name)) {
                $error = '链接名称不能为空';
            } elseif (empty($url)) {
                $error = '链接地址不能为空';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $error = '链接地址格式不正确';
            } else {
                $db->update('links', [
                    'name' => $name,
                    'url' => $url,
                    'description' => $description,
                    'sort_order' => $sortOrder,
                    'is_visible' => $isVisible,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$id]);
                
                $success = '友情链接更新成功';
                // 重新获取数据
                $link = $db->fetch("SELECT * FROM {$db->table('links')} WHERE id = ? LIMIT 1", [$id]);
            }
        }
        
        return [
            'template' => 'admin_link_form',
            'data' => [
                'link' => $link,
                'error' => $error,
                'success' => $success
            ]
        ];
    }
    
    /**
     * 删除友情链接
     */
    private function linkDelete() {
        $db = DB::getInstance();
        $id = intval($_GET['id'] ?? 0);
        
        if ($id > 0) {
            $db->query("DELETE FROM {$db->table('links')} WHERE id = ?", [$id]);
            set_message('友情链接已删除');
        }
        
        redirect('index.php?module=admin&action=links');
    }
    
    /**
     * 排序友情链接
     */
    private function linkSort() {
        $db = DB::getInstance();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orders = $_POST['sort_order'] ?? [];
            
            foreach ($orders as $id => $sortOrder) {
                $db->update('links', [
                    'sort_order' => intval($sortOrder)
                ], 'id = ?', [intval($id)]);
            }
            
            set_message('排序已更新');
        }
        
        redirect('index.php?module=admin&action=links');
    }
}
