<?php
/**
 * HuBBS - 用户模块
 * 处理注册、登录、个人中心
 */

class UserModule {
    
    public function handle() {
        $action = $_GET['action'] ?? 'profile';

        switch ($action) {
            case 'register':
                return $this->register();
            case 'verify':
                return $this->verifyEmail();
            case 'resend':
                return $this->resendCode();
            case 'login':
                return $this->login();
            case 'logout':
                return $this->logout();
            case 'profile':
                return $this->profile();
            case 'check_email':
                return $this->checkEmail();
            default:
                redirect('index.php');
        }
    }
    
    private function register() {
        if (Auth::check()) {
            redirect('index.php');
        }
        
        // 检查是否开放注册
        if (!Settings::isRegisterEnabled()) {
            set_message('网站已关闭注册', 'error');
            redirect('index.php?module=user&action=login');
        }
        
        $error = '';
        $step = $_SESSION['register_step'] ?? 1;
        $registerData = $_SESSION['register_data'] ?? [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                // 检查是否需要邮箱验证
                $needVerify = Settings::get('mail_verify_register', '0') === '1' &&
                              Mailer::getInstance()->isEnabled();

                // 第一步：提交注册信息（有step字段是验证模式，无step字段是直接注册模式）
                if (!$needVerify || (isset($_POST['step']) && $_POST['step'] === '1')) {
                    $username = trim($_POST['username'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $password2 = $_POST['password2'] ?? '';

                    // 验证
                    if (!validate_username($username)) {
                        $error = '用户名2-20位，支持中英文、数字、下划线';
                    } elseif (!validate_email($email)) {
                        $error = '邮箱格式不正确';
                    } elseif (strlen($password) < 6) {
                        $error = '密码至少6位';
                    } elseif ($password !== $password2) {
                        $error = '两次密码不一致';
                    } else {
                        if ($needVerify) {
                            // 发送验证码
                            $code = $this->generateVerifyCode($email);
                            $mailer = Mailer::getInstance();

                            if ($mailer->sendRegisterCode($email, $code)) {
                                // 保存注册数据到 session
                                $_SESSION['register_data'] = [
                                    'username' => $username,
                                    'email' => $email,
                                    'password' => $password,
                                ];
                                $_SESSION['register_step'] = 2;
                                $_SESSION['register_email'] = $email;
                                $_SESSION['register_code_time'] = time();

                                set_message('验证码已发送到您的邮箱，请查收');
                                redirect('index.php?module=user&action=register&step=2');
                            } else {
                                $error = '验证码发送失败，请检查邮箱配置或稍后重试';
                            }
                        } else {
                            // 直接注册
                            $result = Auth::register([
                                'username' => $username,
                                'email' => $email,
                                'password' => $password
                            ]);

                            if ($result['success']) {
                                Auth::login($username, $password, false);
                                set_message('注册成功，欢迎！');
                                redirect('index.php');
                            } else {
                                $error = $result['message'];
                            }
                        }
                    }
                }
                // 第二步：验证邮箱
                elseif (isset($_POST['step']) && $_POST['step'] === '2') {
                    $code = trim($_POST['code'] ?? '');
                    
                    if (empty($code)) {
                        $error = '请输入验证码';
                    } elseif (strlen($code) !== 4 || !ctype_digit($code)) {
                        $error = '验证码为4位数字';
                    } else {
                        // 验证验证码
                        if ($this->verifyCode($_SESSION['register_email'] ?? '', $code, 'register')) {
                            // 验证码正确，完成注册
                            $data = $_SESSION['register_data'] ?? [];
                            $result = Auth::register([
                                'username' => $data['username'],
                                'email' => $data['email'],
                                'password' => $data['password']
                            ]);
                            
                            if ($result['success']) {
                                // 清除 session
                                unset($_SESSION['register_step']);
                                unset($_SESSION['register_data']);
                                unset($_SESSION['register_email']);
                                unset($_SESSION['register_code_time']);
                                
                                Auth::login($data['username'], $data['password'], false);
                                set_message('注册成功，欢迎！');
                                redirect('index.php');
                            } else {
                                $error = $result['message'];
                            }
                        } else {
                            $error = '验证码错误或已过期';
                        }
                    }
                }
            }
        }
        
        // 检查步骤参数
        $step = isset($_GET['step']) && $_GET['step'] === '2' ? 2 : 1;
        
        // 如果第二步但没有 session 数据，回到第一步
        if ($step === 2 && empty($_SESSION['register_data'])) {
            redirect('index.php?module=user&action=register');
        }
        
        return [
            'template' => 'user_register',
            'data' => [
                'error' => $error,
                'step' => $step,
                'email' => $_SESSION['register_email'] ?? ''
            ]
        ];
    }
    
    /**
     * 重新发送验证码
     */
    private function resendCode() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        
        $email = $_SESSION['register_email'] ?? '';
        $lastTime = $_SESSION['register_code_time'] ?? 0;
        
        // 限制发送频率（60秒）
        if (time() - $lastTime < 60) {
            set_message('请稍后再试', 'error');
            redirect('index.php?module=user&action=register&step=2');
        }
        
        if (empty($email)) {
            redirect('index.php?module=user&action=register');
        }
        
        // 发送新验证码
        $code = $this->generateVerifyCode($email);
        $mailer = Mailer::getInstance();
        
        if ($mailer->sendRegisterCode($email, $code)) {
            $_SESSION['register_code_time'] = time();
            set_message('验证码已重新发送');
        } else {
            set_message('验证码发送失败，请稍后重试', 'error');
        }
        
        redirect('index.php?module=user&action=register&step=2');
    }
    
    /**
     * 生成验证码
     */
    private function generateVerifyCode($email) {
        $db = DB::getInstance();
        
        // 生成4位随机数字
        $code = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // 删除该邮箱的旧验证码
        $db->query("DELETE FROM {$db->table('email_codes')} WHERE email = ? AND type = 'register'", [$email]);
        
        // 保存新验证码
        $db->insert('email_codes', [
            'email' => $email,
            'code' => $code,
            'type' => 'register',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
            'used' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return $code;
    }
    
    /**
     * 验证验证码
     */
    private function verifyCode($email, $code, $type) {
        $db = DB::getInstance();
        
        $record = $db->fetch(
            "SELECT * FROM {$db->table('email_codes')} 
             WHERE email = ? AND code = ? AND type = ? AND used = 0 AND expires_at > NOW() 
             LIMIT 1",
            [$email, $code, $type]
        );
        
        if ($record) {
            // 标记为已使用
            $db->update('email_codes', ['used' => 1], 'id = ?', [$record['id']]);
            return true;
        }
        
        return false;
    }
    
    private function verifyEmail() {
        // 邮箱验证页面（备用）
        redirect('index.php?module=user&action=register&step=2');
    }
    
    private function login() {
        if (Auth::check()) {
            redirect('index.php');
        }
        
        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf($_POST['csrf_token'] ?? '')) {
                $error = '安全验证失败';
            } else {
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $remember = isset($_POST['remember']);
                
                if (Auth::login($username, $password, $remember)) {
                    set_message('登录成功');
                    redirect('index.php');
                } else {
                    $error = '用户名或密码错误';
                }
            }
        }
        
        return [
            'template' => 'user_login',
            'data' => ['error' => $error]
        ];
    }
    
    private function logout() {
        Auth::logout();
        set_message('已退出登录');
        redirect('index.php');
    }
    
    private function profile() {
        $db = DB::getInstance();
        $page = max(1, intval($_GET['page'] ?? 1));
        $tab = $_GET['tab'] ?? 'posts';
        
        // 获取要查看的用户ID（如果有传入id参数，查看其他用户；否则查看当前登录用户）
        $viewUserId = intval($_GET['id'] ?? 0);
        $isOwnProfile = false;
        
        if ($viewUserId > 0) {
            // 查看其他用户
            $user = $db->fetch("SELECT * FROM {$db->table('users')} WHERE id = ? LIMIT 1", [$viewUserId]);
            if (!$user) {
                set_message('用户不存在', 'error');
                redirect('index.php');
            }
            // 检查是否是查看自己的主页
            $isOwnProfile = Auth::check() && Auth::id() === $viewUserId;
        } else {
            // 查看自己的主页，需要登录
            if (Auth::guest()) {
                redirect('index.php?module=user&action=login');
            }
            $user = Auth::user();
            $viewUserId = $user['id'];
            $isOwnProfile = true;
        }
        
        // 获取用户帖子数
        $postCount = $db->count('posts', 'user_id = ?', [$viewUserId]);
        
        // 获取用户回复数
        $replyCount = $db->count('replies', 'user_id = ?', [$viewUserId]);
        
        // 获取用户收藏数（始终查询，用于tab显示）
        $favoriteCount = $db->count('post_favorites', 'user_id = ?', [$viewUserId]);
        
        // 获取用户点赞数（始终查询，用于tab显示）
        $likeCount = $db->count('post_likes', 'user_id = ?', [$viewUserId]);
        
        // 获取用户的帖子列表
        $posts = [];
        $postTotalPages = 0;
        if ($tab === 'posts') {
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            $posts = $db->fetchAll(
                "SELECT p.*, f.name as forum_name 
                 FROM {$db->table('posts')} p 
                 LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
                 WHERE p.user_id = ? 
                 ORDER BY p.created_at DESC 
                 LIMIT {$offset}, {$perPage}",
                [$viewUserId]
            );
            $postTotalPages = ceil($postCount / $perPage);
        }
        
        // 获取用户的回复列表
        $replies = [];
        $replyTotalPages = 0;
        if ($tab === 'replies') {
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            $replies = $db->fetchAll(
                "SELECT r.*, p.title as post_title, p.id as post_id, f.name as forum_name 
                 FROM {$db->table('replies')} r 
                 LEFT JOIN {$db->table('posts')} p ON r.post_id = p.id 
                 LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
                 WHERE r.user_id = ? 
                 ORDER BY r.created_at DESC 
                 LIMIT {$offset}, {$perPage}",
                [$viewUserId]
            );
            $replyTotalPages = ceil($replyCount / $perPage);
        }
        
        // 获取用户的收藏列表
        $favorites = [];
        $favoriteTotalPages = 0;
        if ($tab === 'favorites') {
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            // 获取收藏列表
            $favorites = $db->fetchAll(
                "SELECT p.*, f.name as forum_name, pf.created_at as favorited_at, u.username as author_name 
                 FROM {$db->table('post_favorites')} pf 
                 LEFT JOIN {$db->table('posts')} p ON pf.post_id = p.id 
                 LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
                 LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
                 WHERE pf.user_id = ? 
                 ORDER BY pf.created_at DESC 
                 LIMIT {$offset}, {$perPage}",
                [$viewUserId]
            );
            $favoriteTotalPages = ceil($favoriteCount / $perPage);
        }
        
        // 获取用户的点赞列表
        $likes = [];
        $likeTotalPages = 0;
        if ($tab === 'likes') {
            $perPage = 10;
            $offset = ($page - 1) * $perPage;
            
            // 获取点赞列表
            $likes = $db->fetchAll(
                "SELECT p.*, f.name as forum_name, pl.created_at as liked_at, u.username as author_name 
                 FROM {$db->table('post_likes')} pl 
                 LEFT JOIN {$db->table('posts')} p ON pl.post_id = p.id 
                 LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
                 LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
                 WHERE pl.user_id = ? 
                 ORDER BY pl.created_at DESC 
                 LIMIT {$offset}, {$perPage}",
                [$viewUserId]
            );
            $likeTotalPages = ceil($likeCount / $perPage);
        }
        
        return [
            'template' => 'user_profile',
            'data' => [
                'user' => $user,
                'postCount' => $postCount,
                'replyCount' => $replyCount,
                'favoriteCount' => $favoriteCount,
                'likeCount' => $likeCount,
                'posts' => $posts,
                'replies' => $replies,
                'favorites' => $favorites,
                'likes' => $likes,
                'tab' => $tab,
                'page' => $page,
                'postTotalPages' => $postTotalPages,
                'replyTotalPages' => $replyTotalPages,
                'favoriteTotalPages' => $favoriteTotalPages,
                'likeTotalPages' => $likeTotalPages,
                'isOwnProfile' => $isOwnProfile
            ]
        ];
    }

    /**
     * AJAX 检查邮箱是否已注册
     */
    private function checkEmail() {
        // 设置 JSON 响应头
        header('Content-Type: application/json');

        $email = $_GET['email'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['valid' => false, 'message' => '邮箱格式不正确']);
            return;
        }

        $db = DB::getInstance();
        $exists = $db->count('users', 'email = ? AND deleted_at IS NULL', [$email]) > 0;

        if ($exists) {
            echo json_encode(['valid' => false, 'message' => '该邮箱已被注册']);
        } else {
            echo json_encode(['valid' => true, 'message' => '该邮箱可用']);
        }
    }
}
