<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * 核心函数库
 * 包含所有业务逻辑函数
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */

/* 引入系统引导文件 */
require_once __DIR__ . '/core/bootstrap.php';

/**
 * 检查remember_token并自动登录
 * 
 * @return void
 */
function checkRememberToken(): void {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
        global $pdo;
        $token = $_COOKIE['remember_token'];
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_expires_at > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
        }
    }
}

/* 调用函数检查remember_token并自动登录 */
checkRememberToken();

/**
 * 判断用户是否已登录
 * 
 * @return bool 已登录返回true，否则返回false
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function redirect(string $url): void {
    /* 如果URL不是绝对路径且不以ROOT_PATH开头，则添加ROOT_PATH */
    if (!preg_match('/^https?:\/\//i', $url) && defined('ROOT_PATH') && strpos($url, ROOT_PATH) !== 0) {
        $url = ROOT_PATH . '/' . ltrim($url, '/');
    }
    header("Location: $url");
    exit;
}

/**
 * 从 Gitee 获取最新版本信息
 * 
 * @return array|null 版本信息数组，失败返回 null
 */
function getLatestVersion(): ?array {
    $cacheKey = 'gitee_latest_version';
    $cached = cacheGet($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    try {
        /* 首先尝试从 Release API 获取 */
        $url = 'https://gitee.com/api/v5/repos/youruihu/hubbs/releases/latest';
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => "User-Agent: HuBBS/" . HUBBS_VERSION . "\r\n"
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            if (isset($data['tag_name'])) {
                $version = ltrim($data['tag_name'], 'vV');
                $result = [
                    'version' => $version,
                    'name' => $data['name'] ?? '',
                    'body' => $data['body'] ?? '',
                    'published_at' => $data['published_at'] ?? '',
                    'html_url' => $data['html_url'] ?? '',
                    'source' => 'release'
                ];
                cacheSet($cacheKey, $result, 3600);
                return $result;
            }
        }
        
        /* 如果没有 Release，尝试获取仓库信息 */
        $repoUrl = 'https://gitee.com/api/v5/repos/youruihu/hubbs';
        $repoResponse = @file_get_contents($repoUrl, false, $context);
        
        if ($repoResponse !== false) {
            $repoData = json_decode($repoResponse, true);
            if (isset($repoData['default_branch'])) {
                $result = [
                    'version' => HUBBS_VERSION, /* 没有发布版本时，使用当前版本 */
                    'name' => $repoData['name'] ?? 'HuBBS',
                    'body' => '请查看仓库获取最新更新',
                    'published_at' => $repoData['updated_at'] ?? '',
                    'html_url' => 'https://gitee.com/youruihu/hubbs',
                    'source' => 'repo',
                    'has_release' => false
                ];
                cacheSet($cacheKey, $result, 3600);
                return $result;
            }
        }
        
        return null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * 检查本地代码是否与远程同步
 * 通过比较本地和远程的 commit hash
 * 
 * @return array 同步状态
 */
function checkGitSync(): array {
    $rootPath = dirname(__FILE__);
    
    /* 检查是否是 git 仓库 */
    if (!is_dir($rootPath . '/.git')) {
        return [
            'is_git' => false,
            'synced' => true,
            'message' => '不是 Git 仓库'
        ];
    }
    
    /* 获取本地最新 commit */
    $localCommit = trim(shell_exec('cd ' . escapeshellarg($rootPath) . ' && git rev-parse HEAD 2>/dev/null') ?? '');
    
    /* 获取远程最新 commit */
    shell_exec('cd ' . escapeshellarg($rootPath) . ' && git fetch origin 2>/dev/null');
    $remoteCommit = trim(shell_exec('cd ' . escapeshellarg($rootPath) . ' && git rev-parse origin/main 2>/dev/null') ?? '');
    
    if (empty($localCommit) || empty($remoteCommit)) {
        return [
            'is_git' => true,
            'synced' => true,
            'message' => '无法获取版本信息'
        ];
    }
    
    $synced = ($localCommit === $remoteCommit);
    
    return [
        'is_git' => true,
        'synced' => $synced,
        'local_commit' => $localCommit,
        'remote_commit' => $remoteCommit,
        'message' => $synced ? '代码已是最新' : '有新的更新可用'
    ];
}

/**
 * 比较版本号
 * 
 * @param string $version1 版本1
 * @param string $version2 版本2
 * @return int 1表示version1>version2，-1表示<，0表示相等
 */
function compareVersions(string $version1, string $version2): int {
    return version_compare($version1, $version2);
}

/**
 * 检查是否有新版本
 * 
 * @return array 检查结果
 */
function checkForUpdate(): array {
    $currentVersion = HUBBS_VERSION;
    
    /* 首先检查 Git 同步状态 */
    $gitSync = checkGitSync();
    
    /* 如果是 Git 仓库，优先使用 Git 同步状态判断 */
    if ($gitSync['is_git']) {
        return [
            'has_update' => !$gitSync['synced'],
            'current_version' => $currentVersion,
            'latest_version' => $currentVersion,
            'release_info' => [
                'html_url' => 'https://gitee.com/youruihu/hubbs',
                'published_at' => '',
                'body' => $gitSync['message']
            ],
            'git_sync' => $gitSync,
            'source' => 'git'
        ];
    }
    
    /* 如果不是 Git 仓库，尝试从 Gitee API 获取版本信息 */
    $latestInfo = getLatestVersion();
    
    if ($latestInfo === null) {
        return [
            'has_update' => false,
            'current_version' => $currentVersion,
            'latest_version' => $currentVersion,
            'release_info' => null,
            'error' => '无法获取版本信息',
            'source' => 'api'
        ];
    }
    
    /* 如果没有发布版本，显示为最新 */
    if (isset($latestInfo['has_release']) && !$latestInfo['has_release']) {
        return [
            'has_update' => false,
            'current_version' => $currentVersion,
            'latest_version' => $currentVersion,
            'release_info' => $latestInfo,
            'source' => 'repo'
        ];
    }
    
    $latestVersion = $latestInfo['version'];
    $hasUpdate = compareVersions($latestVersion, $currentVersion) > 0;
    
    return [
        'has_update' => $hasUpdate,
        'current_version' => $currentVersion,
        'latest_version' => $latestVersion,
        'release_info' => $latestInfo,
        'source' => 'release'
    ];
}

/**
 * 执行系统更新（git pull）
 * 
 * @return array 更新结果
 */
function executeUpdate(): array {
    $rootPath = dirname(__FILE__);
    
    /* 检查是否是 git 仓库 */
    if (!is_dir($rootPath . '/.git')) {
        return [
            'success' => false,
            'error' => '不是 Git 仓库，无法自动更新'
        ];
    }
    
    /* 执行 git fetch 和 git pull */
    $commands = [
        'cd ' . escapeshellarg($rootPath) . ' && git fetch origin 2>&1',
        'cd ' . escapeshellarg($rootPath) . ' && git reset --hard origin/main 2>&1'
    ];
    
    $output = [];
    foreach ($commands as $command) {
        $result = shell_exec($command);
        $output[] = $result;
    }
    
    /* 检查是否成功 */
    $lastOutput = implode("\n", $output);
    if (strpos($lastOutput, 'fatal:') !== false || strpos($lastOutput, 'error:') !== false) {
        return [
            'success' => false,
            'error' => '更新失败：' . $lastOutput,
            'output' => $output
        ];
    }
    
    /* 清理缓存 */
    clearAllCache();
    
    /* 清除版本缓存 */
    cacheDelete('gitee_latest_version');
    
    return [
        'success' => true,
        'message' => '更新成功',
        'output' => $output
    ];
}

/**
 * 清理所有缓存
 * 
 * @return bool 是否成功
 */
function clearAllCache(): bool {
    $cacheDir = dirname(__FILE__) . '/cache';
    
    if (!is_dir($cacheDir)) {
        return true;
    }
    
    $files = glob($cacheDir . '/*.cache');
    $success = true;
    
    foreach ($files as $file) {
        if (!@unlink($file)) {
            $success = false;
        }
    }
    
    return $success;
}

/**
 * 解析IP地址获取地理位置
 * 使用免费的IP地址解析API
 * 
 * @param string $ip IP地址
 * @param bool $cityOnly 是否只返回省一级信息
 * @return string 地理位置信息（省市）
 */
function parseIpAddress(string $ip, bool $cityOnly = false): string {
    /* 过滤无效IP */
    if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0 || strpos($ip, '172.') === 0) {
        return '本地网络';
    }
    
    /* 检查缓存 */
    $cacheKey = 'ip_location_' . md5($ip);
    $cached = cacheGet($cacheKey);
    if ($cached !== null) {
        /* 如果只需要省一级，从缓存中提取 */
        if ($cityOnly && strpos($cached, ' ') !== false) {
            $parts = explode(' ', $cached);
            return $parts[0];
        }
        return $cached;
    }
    
    /* 使用免费API解析IP地址 */
    $location = '未知';
    
    try {
        /* 使用 ip-api.com 免费API（每分钟45次请求限制） */
        $url = "http://ip-api.com/json/{$ip}?lang=zh-CN&fields=status,country,regionName,city";
        $response = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET'
            ]
        ]));
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success') {
                $parts = [];
                if (!empty($data['country']) && $data['country'] !== 'China') {
                    $parts[] = $data['country'];
                }
                if (!empty($data['regionName'])) {
                    $parts[] = $data['regionName'];
                }
                if (!empty($data['city']) && $data['city'] !== $data['regionName']) {
                    $parts[] = $data['city'];
                }
                $location = implode(' ', $parts) ?: '未知';
            }
        }
    } catch (Exception $e) {
        /* 解析失败，返回默认值 */
    }
    
    /* 缓存结果24小时 */
    cacheSet($cacheKey, $location, 86400);
    
    /* 如果只需要省一级，提取省份信息 */
    if ($cityOnly && strpos($location, ' ') !== false) {
        $parts = explode(' ', $location);
        return $parts[0];
    }
    
    return $location;
}

/**
 * 生成页面URL
 * 自动处理pages目录下的文件路径
 * 
 * @param string $page 页面名称（不含.php扩展名）
 * @param array $params 查询参数
 * @return string 完整URL
 */
function pageUrl(string $page, array $params = []): string {
    $url = SITE_URL . '/pages/' . $page . '.php';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * 生成站点URL
 * 自动处理根目录下的文件路径
 * 
 * @param string $path 相对路径
 * @return string 完整URL
 */
function siteUrl(string $path = ''): string {
    $path = ltrim($path, '/');
    return SITE_URL . ($path ? '/' . $path : '');
}

function escape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function getClientIP(): string {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function maskIP(string $ip): string {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.*.*';
        }
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        if (count($parts) >= 4) {
            return $parts[0] . ':' . $parts[1] . ':****:****';
        }
    }
    return '***.***.***.***';
}

function formatTime(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' 分钟前';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' 小时前';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' 天前';
    } else {
        return date('Y-m-d', $time);
    }
}

function cacheGet(string $key) {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $cacheFile = __DIR__ . '/cache/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        $data = @unserialize(file_get_contents($cacheFile));
        if ($data && isset($data['expire']) && $data['expire'] > time()) {
            $cache[$key] = $data['value'];
            return $data['value'];
        }
        @unlink($cacheFile);
    }
    return null;
}

function cacheSet(string $key, $value, int $ttl = 300): void {
    static $cache = [];
    $cache[$key] = $value;
    $cacheDir = __DIR__ . '/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    $data = ['value' => $value, 'expire' => time() + $ttl];
    @file_put_contents($cacheFile, serialize($data));
}

function cacheDelete(string $key): void {
    $cacheFile = __DIR__ . '/cache/' . md5($key) . '.cache';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

function cacheFlush(): void {
    $cacheDir = __DIR__ . '/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

function getCategories(): array {
    $cacheKey = 'categories_all';
    $cached = cacheGet($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
    $result = $stmt->fetchAll();
    cacheSet($cacheKey, $result, 600);
    return $result;
}

function getCategoryTree(): array {
    $categories = getCategories();
    $tree = [];
    $lookup = [];
    
    foreach ($categories as $cat) {
        $lookup[$cat['id']] = $cat;
        $lookup[$cat['id']]['children'] = [];
    }
    
    foreach ($categories as $cat) {
        $parentId = $cat['parent_id'] ?? null;
        if ($parentId === null) {
            $tree[] = &$lookup[$cat['id']];
        } else {
            if (isset($lookup[$parentId])) {
                $lookup[$parentId]['children'][] = &$lookup[$cat['id']];
            }
        }
    }
    
    return $tree;
}

function canUserPostInCategory(int $categoryId, ?int $userId): bool
{
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT allowed_users FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        return false;
    }
    
    $allowedUsers = $category['allowed_users'];
    
    if (empty($allowedUsers)) {
        return true;
    }
    
    if ($userId === null) {
        return false;
    }
    
    $allowedIds = array_map('intval', array_filter(array_map('trim', explode(',', $allowedUsers))));
    
    return in_array($userId, $allowedIds);
}

function isCategoryVisibleToUser(int $categoryId, ?int $userId): bool
{
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT allowed_users FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        return false;
    }
    
    $allowedUsers = $category['allowed_users'];
    
    if (empty($allowedUsers)) {
        return true;
    }
    
    if ($userId === null) {
        return false;
    }
    
    $allowedIds = array_map('intval', array_filter(array_map('trim', explode(',', $allowedUsers))));
    
    return in_array($userId, $allowedIds);
}

function getCategoryTreeForUser(?int $userId): array
{
    $categories = getCategories();
    $tree = [];
    $lookup = [];
    
    foreach ($categories as $cat) {
        if (!isCategoryVisibleToUser($cat['id'], $userId)) {
            continue;
        }
        $lookup[$cat['id']] = $cat;
        $lookup[$cat['id']]['children'] = [];
    }
    
    foreach ($categories as $cat) {
        if (!isset($lookup[$cat['id']])) {
            continue;
        }
        $parentId = $cat['parent_id'] ?? null;
        if ($parentId === null) {
            $tree[] = &$lookup[$cat['id']];
        } else {
            if (isset($lookup[$parentId])) {
                $lookup[$parentId]['children'][] = &$lookup[$cat['id']];
            }
        }
    }
    
    return $tree;
}

function getParentCategoriesForUser(?int $userId): array
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC");
        $categories = $stmt->fetchAll();
        
        return array_filter($categories, function($cat) use ($userId) {
            return isCategoryVisibleToUser($cat['id'], $userId);
        });
    } catch (PDOException $e) {
        return [];
    }
}

function checkPostInterval(int $userId): int
{
    $interval = (int)getSetting('post_interval', '0');
    if ($interval <= 0) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $lastPost = $stmt->fetch();
    
    if (!$lastPost) {
        return 0;
    }
    
    $lastTime = strtotime($lastPost['created_at']);
    $now = time();
    $elapsed = $now - $lastTime;
    
    if ($elapsed < $interval) {
        return $interval - $elapsed;
    }
    
    return 0;
}

function checkCommentInterval(int $userId): int
{
    $interval = (int)getSetting('comment_interval', '0');
    if ($interval <= 0) {
        return 0;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT created_at FROM comments WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $lastComment = $stmt->fetch();
    
    if (!$lastComment) {
        return 0;
    }
    
    $lastTime = strtotime($lastComment['created_at']);
    $now = time();
    $elapsed = $now - $lastTime;
    
    if ($elapsed < $interval) {
        return $interval - $elapsed;
    }
    
    return 0;
}

function getParentCategories(): array {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    }
}

function getUserLevels(): array
{
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM user_levels ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getUserLevel(int $points): array
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM user_levels WHERE min_points <= ? AND max_points >= ? ORDER BY sort_order ASC LIMIT 1");
        $stmt->execute([$points, $points]);
        $level = $stmt->fetch();
        
        if (!$level) {
            $stmt = $pdo->query("SELECT * FROM user_levels ORDER BY sort_order ASC LIMIT 1");
            $level = $stmt->fetch();
        }
        
        return $level ?: ['name' => '未定级', 'min_points' => 0, 'max_points' => 0];
    } catch (PDOException $e) {
        return ['name' => '未定级', 'min_points' => 0, 'max_points' => 0];
    }
}

function getUserLevelName(int $points): string
{
    $level = getUserLevel($points);
    return $level['name'];
}

function getPointsName(): string
{
    return getSetting('points_name', '积分');
}

function getChildCategories(int $parentId): array {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function hasChildCategories(int $categoryId): bool {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $stmt->execute([$categoryId]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function canPostInCategory(int $categoryId): bool {
    return !hasChildCategories($categoryId);
}

function getTodayStats(): array
{
    global $pdo;
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $posts = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE DATE(created_at) = ?");
    $stmt->execute([$today]);
    $comments = (int)$stmt->fetchColumn();
    
    return [
        'posts' => $posts,
        'comments' => $comments,
        'total' => $posts + $comments
    ];
}

function getPostCount(): int {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    return (int)$stmt->fetchColumn();
}

function getUserCount(): int {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return (int)$stmt->fetchColumn();
}

function getCommentCount(): int {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM comments");
    return (int)$stmt->fetchColumn();
}

function getPostById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.content, p.user_id, p.category_id, p.views, p.is_sticky, p.is_locked, p.is_digest, p.created_at, p.updated_at, p.ip_address,
               u.username, u.avatar, u.points,
               c.name as category_name, c.slug as category_slug,
               (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function incrementPostViews(int $postId): void {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
    $stmt->execute([$postId]);
}

function getCommentsByPostId(int $postId): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.avatar, ru.username as reply_to_username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN users ru ON c.reply_to_user_id = ru.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function buildCommentTree(array $comments): array {
    $tree = [];
    $lookup = [];
    
    foreach ($comments as $comment) {
        $lookup[$comment['id']] = $comment;
        $lookup[$comment['id']]['children'] = [];
    }
    
    foreach ($comments as $comment) {
        if ($comment['parent_id'] === null) {
            $tree[] = &$lookup[$comment['id']];
        } else {
            $lookup[$comment['parent_id']]['children'][] = &$lookup[$comment['id']];
        }
    }
    
    return $tree;
}

function hasUserRepliedToPost(int $postId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    return $stmt->fetchColumn() > 0;
}

function processHiddenContent(string $content, int $postId, ?int $userId, int $postAuthorId): string {
    $pattern = '/\[hide\](.*?)\[\/hide\]/s';
    
    if ($userId && ($userId == $postAuthorId || hasUserRepliedToPost($postId, $userId))) {
        return preg_replace($pattern, '<div class="hidden-content-revealed"><strong>隐藏内容：</strong><div class="hidden-content-inner">$1</div></div>', $content);
    }
    
    return preg_replace($pattern, '<div class="hidden-content-locked"><span class="lock-icon">🔒</span> <strong>回复可见</strong><br><small>回复本帖后可查看隐藏内容</small></div>', $content);
}

function renderComments(array $comments, int $postId, bool $isLocked = false): string {
    $html = '';
    foreach ($comments as $comment) {
        $html .= '<div class="comment-main" id="comment-' . $comment['id'] . '">';
        $html .= '<div class="comment-author">';
        $html .= '<a href="' . SITE_URL . '/pages/profile.php?user=' . $comment['user_id'] . '">' . escape($comment['username']) . '</a>';
        $html .= '<span class="comment-time">' . formatTime($comment['created_at']) . '</span>';
        $html .= '</div>';
        $html .= '<div class="comment-text">' . nl2br(escape($comment['content'])) . '</div>';
        $html .= '<div class="comment-actions">';
        if (isLoggedIn() && (!$isLocked || isAdmin())) {
            $html .= '<a href="#" class="reply-btn" data-comment-id="' . $comment['id'] . '" data-username="' . escape($comment['username']) . '">回复</a>';
        }
        if (isLoggedIn() && ($_SESSION['user_id'] == $comment['user_id'] || isAdmin())) {
            $html .= '<a href="' . SITE_URL . '/pages/post.php?id=' . $postId . '&delete_comment=' . $comment['id'] . '" onclick="return confirm(\'确定要删除此评论吗？\')">删除</a>';
        }
        $html .= '</div>';
        $html .= '<div class="reply-form" id="reply-form-' . $comment['id'] . '">';
        $html .= '<form method="POST" action="' . SITE_URL . '/pages/post.php?id=' . $postId . '">';
        $html .= '<input type="hidden" name="parent_id" value="' . $comment['id'] . '">';
        $html .= '<input type="hidden" name="reply_to_user_id" value="' . $comment['user_id'] . '">';
        $html .= '<textarea name="content" placeholder="回复 @' . escape($comment['username']) . '..." required></textarea>';
        $html .= '<button type="submit" name="submit_comment">回复</button>';
        $html .= '</form></div>';
        if (!empty($comment['children'])) {
            $html .= '<div class="comment-replies">';
            foreach ($comment['children'] as $reply) {
                $html .= '<div class="reply-item" id="comment-' . $reply['id'] . '">';
                $html .= '<div class="reply-header">';
                $html .= '<span class="reply-author"><a href="' . SITE_URL . '/pages/profile.php?user=' . $reply['user_id'] . '">' . escape($reply['username']) . '</a></span>';
                if (!empty($reply['reply_to_username'])) {
                    $html .= '<span class="reply-to">回复 <a href="' . SITE_URL . '/pages/profile.php?user=' . $reply['reply_to_user_id'] . '">@' . escape($reply['reply_to_username']) . '</a></span>';
                }
                $html .= '</div>';
                $html .= '<div class="reply-content">' . nl2br(escape($reply['content'])) . '</div>';
                $html .= '<div class="reply-footer">';
                $html .= '<span class="reply-time">' . formatTime($reply['created_at']) . '</span>';
                $html .= '<span class="reply-actions">';
                if (isLoggedIn() && (!$isLocked || isAdmin())) {
                    $html .= '<a href="#" class="reply-btn" data-comment-id="' . $comment['id'] . '" data-username="' . escape($reply['username']) . '" data-reply-to-id="' . $reply['user_id'] . '">回复</a>';
                }
                if (isLoggedIn() && ($_SESSION['user_id'] == $reply['user_id'] || isAdmin())) {
                    $html .= '<a href="' . SITE_URL . '/pages/post.php?id=' . $postId . '&delete_comment=' . $reply['id'] . '" onclick="return confirm(\'确定要删除此评论吗？\')">删除</a>';
                }
                $html .= '</span></div></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
    }
    return $html;
}

function uploadAvatar(array $file): ?string {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowedTypes)) {
        return null;
    }
    
    if ($file['size'] > $maxSize) {
        return null;
    }
    
    $uploadDir = __DIR__ . '/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/avatars/' . $filename;
    }
    
    return null;
}

function generateCsrfToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function flashMessage(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function getSetting(string $key, string $default = ''): string {
    global $pdo;
    static $settings = null;
    
    if ($settings === null) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'] ?? '';
            }
        } catch (PDOException $e) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(50) NOT NULL UNIQUE,
                `setting_value` TEXT DEFAULT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $settings = [];
        }
    }
    
    return $settings[$key] ?? $default;
}

function clearSettingsCache(): void {
    $GLOBALS['settings_cache_cleared'] = true;
}

function updateSetting(string $key, string $value): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(50) NOT NULL UNIQUE,
            `setting_value` TEXT DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
}

function isFavorited(int $postId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    return $stmt->fetch() !== false;
}

function toggleFavorite(int $postId, int $userId): bool {
    global $pdo;
    if (isFavorited($postId, $userId)) {
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE post_id = ? AND user_id = ?");
        return $stmt->execute([$postId, $userId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favorites (post_id, user_id) VALUES (?, ?)");
        return $stmt->execute([$postId, $userId]);
    }
}

function getFavoriteCount(int $postId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE post_id = ?");
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

function getUserFavorites(int $userId, int $limit = 10): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, c.name as category_name, f.created_at as favorited_at
        FROM favorites f
        JOIN posts p ON f.post_id = p.id
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function getUserFavoriteCount(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function isLiked(int $postId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    return $stmt->fetch() !== false;
}

function toggleLike(int $postId, int $userId): bool {
    global $pdo;
    if (isLiked($postId, $userId)) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        return $stmt->execute([$postId, $userId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        return $stmt->execute([$postId, $userId]);
    }
}

function getLikeCount(int $postId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

function getUserLikes(int $userId, int $limit = 10): array {
    global $pdo;
    $stmt = $pdo->prepare("    SELECT p.*, u.username, c.name as category_name, l.created_at as liked_at
        FROM likes l
        JOIN posts p ON l.post_id = p.id
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function createNotification(int $userId, string $type, string $title, ?string $content = null, ?array $data = null): bool {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, content, data) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$userId, $type, $title, $content, $data ? json_encode($data) : null]);
}

function getUnreadNotificationCount(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function getUserNotifications(int $userId, int $limit = 20, int $offset = 0): array {
    global $pdo;
    /* 未读消息排在前面，然后按时间倒序 */
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$userId, $limit, $offset]);
    return $stmt->fetchAll();
}

function markNotificationAsRead(int $notificationId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}

function markAllNotificationsAsRead(int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    return $stmt->execute([$userId]);
}

function deleteNotification(int $notificationId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    return $stmt->execute([$notificationId, $userId]);
}

/**
 * 标记与特定帖子相关的所有未读通知为已读
 * 
 * @param int $postId 帖子ID
 * @param int $userId 用户ID
 * @return bool 是否成功
 */
function markPostNotificationsAsRead(int $postId, int $userId): bool {
    global $pdo;
    
    /* 查找与该帖子相关的未读通知 */
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? 
        AND is_read = 0 
        AND data LIKE ?
    ");
    $postIdStr = '%\"post_id\":' . $postId . '%';
    return $stmt->execute([$userId, $postIdStr]);
}

function getNotificationTypeLabel(string $type): string {
    $labels = [
        'post_reply' => '帖子回复',
        'comment_reply' => '评论回复',
        'post_favorited' => '帖子被收藏',
        'post_liked' => '帖子被点赞',
        'post_sticky' => '帖子置顶',
        'post_unsticky' => '取消置顶',
        'post_locked' => '帖子锁定',
        'post_unlocked' => '帖子解锁',
        'post_deleted' => '帖子删除',
        'post_digest' => '帖子精华',
        'post_undigest' => '取消精华',
        'system' => '系统通知'
    ];
    return $labels[$type] ?? '通知';
}

function getLinks(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM links WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll();
}

function getAllLinks(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM links ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll();
}

function getLinkById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM links WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function addLink(string $name, string $url, ?string $description = null, int $sortOrder = 0): bool {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO links (name, url, description, sort_order) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$name, $url, $description, $sortOrder]);
}

function updateLink(int $id, string $name, string $url, ?string $description = null, int $sortOrder = 0, int $isVisible = 1): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE links SET name = ?, url = ?, description = ?, sort_order = ?, is_visible = ? WHERE id = ?");
    return $stmt->execute([$name, $url, $description, $sortOrder, $isVisible, $id]);
}

function deleteLink(int $id): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM links WHERE id = ?");
    return $stmt->execute([$id]);
}

function getAvailableThemes(): array {
    $themes = [];
    $viewsPath = __DIR__ . '/views';
    $dirs = scandir($viewsPath);
    foreach ($dirs as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir($viewsPath . '/' . $dir) && file_exists($viewsPath . '/' . $dir . '/layouts/header.php')) {
            $themes[] = $dir;
        }
    }
    return $themes;
}

function getCurrentTheme(): string {
    return getSetting('site_theme', 'default');
}

function render(string $view, array $data = []): void {
    extract($data);
    $theme = getCurrentTheme();
    $themePath = __DIR__ . '/views/' . $theme;
    
    if (!is_dir($themePath)) {
        $theme = 'default';
        $themePath = __DIR__ . '/views/default';
    }
    
    $GLOBALS['extraStyles'] = '';
    $GLOBALS['extraScripts'] = '';
    
    ob_start();
    require $themePath . '/' . $view . '.php';
    $viewContent = ob_get_clean();
    
    $extraStyles = $GLOBALS['extraStyles'];
    $extraScripts = $GLOBALS['extraScripts'];
    
    require $themePath . '/layouts/header.php';
    echo $viewContent;
    require $themePath . '/layouts/footer.php';
}

function renderAuth(string $view, array $data = []): void {
    extract($data);
    $theme = getCurrentTheme();
    $themePath = __DIR__ . '/views/' . $theme;
    
    if (!is_dir($themePath)) {
        $theme = 'default';
        $themePath = __DIR__ . '/views/default';
    }
    
    ob_start();
    require_once $themePath . '/' . $view . '.php';
    $viewContent = ob_get_clean();
    
    require_once $themePath . '/layouts/auth.php';
}

function getPointRule(string $action): ?array {
    global $pdo;
    static $rules = null;
    
    if ($rules === null) {
        $stmt = $pdo->query("SELECT * FROM point_rules");
        $rules = [];
        while ($row = $stmt->fetch()) {
            $rules[$row['action']] = $row;
        }
    }
    
    return $rules[$action] ?? null;
}

function getAllPointRules(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM point_rules ORDER BY id ASC");
    return $stmt->fetchAll();
}

function getUserPoints(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function checkDailyLimit(int $userId, string $action, int $dailyLimit): bool {
    if ($dailyLimit <= 0) {
        return true;
    }
    
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM point_logs WHERE user_id = ? AND action = ? AND DATE(created_at) = ?");
    $stmt->execute([$userId, $action, $today]);
    return (int)$stmt->fetchColumn() < $dailyLimit;
}

function addPoints(int $userId, string $action, ?string $relatedType = null, ?int $relatedId = null, ?string $remark = null): bool {
    global $pdo;
    
    $rule = getPointRule($action);
    if (!$rule || !$rule['is_enabled']) {
        return false;
    }
    
    if ($rule['points'] > 0 && !checkDailyLimit($userId, $action, $rule['daily_limit'])) {
        return false;
    }
    
    $points = $rule['points'];
    
    $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentBalance = (int)$stmt->fetchColumn();
    $newBalance = $currentBalance + $points;
    
    if ($newBalance < 0) {
        $newBalance = 0;
    }
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE users SET points = ? WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
        
        $stmt = $pdo->prepare("INSERT INTO point_logs (user_id, action, points, balance, related_type, related_id, remark) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $action, $points, $newBalance, $relatedType, $relatedId, $remark]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        return false;
    }
}

function getUserPointLogs(int $userId, int $limit = 20, int $offset = 0): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM point_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$userId, $limit, $offset]);
    return $stmt->fetchAll();
}

function getUserPointLogCount(int $userId): int {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM point_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function updatePointRule(int $id, int $points, int $isEnabled, int $dailyLimit): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE point_rules SET points = ?, is_enabled = ?, daily_limit = ? WHERE id = ?");
    return $stmt->execute([$points, $isEnabled, $dailyLimit, $id]);
}

function getPointActionLabel(string $action): string {
    $rule = getPointRule($action);
    return $rule ? $rule['name'] : $action;
}

function isEmailDomainAllowed(string $email): bool {
    if (getSetting('restrict_email_domain', '0') !== '1') {
        return true;
    }
    
    $allowedDomains = getSetting('allowed_email_domains', '');
    if (empty($allowedDomains)) {
        return false;
    }
    
    $emailDomain = strtolower(substr(strrchr($email, '@'), 1));
    if (!$emailDomain) {
        return false;
    }
    
    $domains = array_map('trim', array_map('strtolower', explode(',', $allowedDomains)));
    
    return in_array($emailDomain, $domains, true);
}

function getAllowedEmailDomainsList(): array {
    $allowedDomains = getSetting('allowed_email_domains', '');
    if (empty($allowedDomains)) {
        return [];
    }
    return array_map('trim', array_map('strtolower', explode(',', $allowedDomains)));
}

function isSmtpEnabled(): bool {
    return getSetting('smtp_enabled', '0') === '1';
}

function getSmtpConfig(): array {
    return [
        'host' => getSetting('smtp_host', ''),
        'port' => (int)getSetting('smtp_port', '465'),
        'secure' => getSetting('smtp_secure', 'ssl'),
        'username' => getSetting('smtp_username', ''),
        'password' => getSetting('smtp_password', ''),
        'from_email' => getSetting('smtp_from_email', ''),
        'from_name' => getSetting('smtp_from_name', 'HuBBS Forum'),
    ];
}

function sendEmail(string $to, string $subject, string $body, ?string $toName = null): array {
    if (!isSmtpEnabled()) {
        return ['success' => false, 'error' => 'SMTP服务未启用'];
    }
    
    $config = getSmtpConfig();
    
    if (empty($config['host']) || empty($config['username']) || empty($config['from_email'])) {
        return ['success' => false, 'error' => 'SMTP配置不完整'];
    }
    
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . mb_encode_mimeheader($config['from_name']) . ' <' . $config['from_email'] . '>';
    
    if ($toName) {
        $to = mb_encode_mimeheader($toName) . ' <' . $to . '>';
    }
    
    $subject = mb_encode_mimeheader($subject);
    
    $socket = @fsockopen(
        ($config['secure'] === 'ssl' ? 'ssl://' : '') . $config['host'],
        $config['port'],
        $errno,
        $errstr,
        10
    );
    
    if (!$socket) {
        return ['success' => false, 'error' => "连接失败: {$errstr} ({$errno})"];
    }
    
    $readResponse = function() use ($socket) {
        $response = '';
        while ($line = fgets($socket)) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };
    
    $response = $readResponse();
    if (strpos($response, '220') !== 0) {
        fclose($socket);
        return ['success' => false, 'error' => '服务器响应异常: ' . $response];
    }
    
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    fputs($socket, "EHLO {$host}\r\n");
    $response = $readResponse();
    
    if ($config['secure'] === 'tls') {
        fputs($socket, "STARTTLS\r\n");
        $response = $readResponse();
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fputs($socket, "EHLO {$host}\r\n");
        $response = $readResponse();
    }
    
    fputs($socket, "AUTH LOGIN\r\n");
    $response = $readResponse();
    
    fputs($socket, base64_encode($config['username']) . "\r\n");
    $response = $readResponse();
    
    fputs($socket, base64_encode($config['password']) . "\r\n");
    $response = $readResponse();
    
    if (strpos($response, '235') !== 0) {
        fclose($socket);
        return ['success' => false, 'error' => '认证失败: ' . trim($response)];
    }
    
    fputs($socket, "MAIL FROM: <" . $config['from_email'] . ">\r\n");
    $response = $readResponse();
    
    fputs($socket, "RCPT TO: <" . $to . ">\r\n");
    $response = $readResponse();
    
    fputs($socket, "DATA\r\n");
    $response = $readResponse();
    
    $email = "To: {$to}\r\n";
    $email .= "Subject: {$subject}\r\n";
    $email .= implode("\r\n", $headers) . "\r\n";
    $email .= "\r\n";
    $email .= $body;
    $email .= "\r\n.\r\n";
    
    fputs($socket, $email);
    $response = $readResponse();
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    if (strpos($response, '250') !== 0) {
        return ['success' => false, 'error' => '发送失败: ' . trim($response)];
    }
    
    return ['success' => true, 'error' => null];
}

function sendTestEmail(string $testTo): array {
    $subject = 'HuBBS 邮件测试';
    $body = '<html><body>';
    $body .= '<h2>邮件测试成功</h2>';
    $body .= '<p>这是一封测试邮件，如果您收到此邮件，说明SMTP配置正确。</p>';
    $body .= '<p>发送时间: ' . date('Y-m-d H:i:s') . '</p>';
    $body .= '<hr><p style="color:#999;font-size:12px;">此邮件由系统自动发送，请勿回复</p>';
    $body .= '</body></html>';
    
    return sendEmail($testTo, $subject, $body);
}

function generateVerifyCode(int $length = 4): string {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendRegisterVerifyCodeEmail(string $to, string $code): array {
    $siteName = getSetting('site_title', 'HuBBS Forum');
    $subject = "【{$siteName}】验证码";
    $body = '<html><body>';
    $body .= '<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">';
    $body .= '<h2 style="color:#333;">' . escape($siteName) . ' 邮箱验证</h2>';
    $body .= '<p style="font-size:16px;color:#666;margin-top:20px;">您好！</p>';
    $body .= '<p style="font-size:18px;color:#333;margin-top:20px;">验证码为<strong style="font-size:24px;color:#007bff;margin:0 5px;">' . escape($code) . '</strong></p>';
    $body .= '<p style="font-size:14px;color:#999;margin-top:20px;">验证码有效期为10分钟，请尽快完成注册。</p>';
    $body .= '<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">';
    $body .= '<p style="color:#999;font-size:12px;">此邮件由系统自动发送，请勿回复</p>';
    $body .= '</div>';
    $body .= '</body></html>';
    
    return sendEmail($to, $subject, $body);
}

function renderPostContent(string $content, int $postId, ?int $userId, int $postAuthorId): string
{
    if ($content === '' || $content === null) {
        return '';
    }
    
    $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);
    
    $hidePattern = '/\[hide\](.*?)\[\/hide\]/s';
    
    $hideBlocks = [];
    $index = 0;
    $content = preg_replace_callback($hidePattern, function($matches) use (&$hideBlocks, &$index) {
        $placeholder = '[[HIDE_BLOCK_' . $index . ']]';
        $hideBlocks[$index] = $matches[1];
        $index++;
        return $placeholder;
    }, $content);
    
    $allowedTags = '<strong><em><del><u><b><i><s><img><br><p><div><span><ul><ol><li><blockquote><pre><code><a><h1><h2><h3><h4><h5><h6><font>';
    $content = strip_tags($content, $allowedTags);
    
    $content = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
    $content = preg_replace('/javascript\s*:/i', '', $content);
    $content = preg_replace('/<a\s+([^>]*?)href=["\']javascript:[^"\']*["\']([^>]*?)>/i', '<a $1$2>', $content);
    
    $content = preg_replace('/\[img\](https?:\/\/[^\s\[\<\>]+?)\[\/img\]/i', '<img src="$1" alt="图片" class="img-fluid" style="max-width:100%;height:auto;">', $content);
    
    $content = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $content);
    $content = preg_replace('/\[i\](.*?)\[\/i\]/is', '<em>$1</em>', $content);
    $content = preg_replace('/\[s\](.*?)\[\/s\]/is', '<del>$1</del>', $content);
    $content = preg_replace('/\[u\](.*?)\[\/u\]/is', '<u>$1</u>', $content);
    
    $content = preg_replace('/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i', '<img src="$2" alt="图片" class="img-fluid" style="max-width:100%;height:auto;">', $content);
    
    if (strpos($content, '<br') === false && strpos($content, '<p>') === false && strpos($content, '<div>') === false && strpos($content, '[[HIDE_BLOCK_') === false) {
        $content = nl2br($content);
    }
    
    foreach ($hideBlocks as $i => $hiddenText) {
        $placeholder = '[[HIDE_BLOCK_' . $i . ']]';
        
        if ($userId && ($userId == $postAuthorId || hasUserRepliedToPost($postId, $userId))) {
            $cleanedHidden = strip_tags($hiddenText, $allowedTags);
            $cleanedHidden = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $cleanedHidden);
            $replacement = '<div class="hidden-content-revealed"><strong>隐藏内容：</strong><div class="hidden-content-inner">' . $cleanedHidden . '</div></div>';
        } else {
            $replacement = '<div class="hidden-content-locked"><span class="lock-icon">🔒</span> <strong>回复可见</strong><br><small>回复本帖后可查看隐藏内容</small></div>';
        }
        
        $content = str_replace($placeholder, $replacement, $content);
    }
    
    return $content;
}

function processPostContent(string $content, int $postId, ?int $userId, int $postAuthorId): string
{
    if ($content === '' || $content === null) {
        return '';
    }
    
    $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $content);
    
    $pattern = '/\[hide\](.*?)\[\/hide\]/s';
    
    if ($userId && ($userId == $postAuthorId || hasUserRepliedToPost($postId, $userId))) {
        $content = preg_replace_callback($pattern, function($matches) {
            $hiddenContent = nl2br(escape($matches[1]));
            return '<div class="hidden-content-revealed"><strong>隐藏内容：</strong><div class="hidden-content-inner">' . $hiddenContent . '</div></div>';
        }, $content);
    } else {
        $content = preg_replace($pattern, '<div class="hidden-content-locked"><span class="lock-icon">🔒</span> <strong>回复可见</strong><br><small>回复本帖后可查看隐藏内容</small></div>', $content);
    }
    
    $content = nl2br(escape($content));
    
    $content = str_replace(['&lt;div class="hidden-content-', '&lt;/div&gt;', '&lt;strong&gt;', '&lt;/strong&gt;', '&lt;small&gt;', '&lt;/small&gt;', '&lt;br', '&lt;span class="lock-icon"&gt;', '&lt;/span&gt;', '&lt;div class="hidden-content-inner"&gt;'], 
        ['<div class="hidden-content-', '</div>', '<strong>', '</strong>', '<small>', '</small>', '<br', '<span class="lock-icon">', '</span>', '<div class="hidden-content-inner">'], $content);
    
    $content = preg_replace('/\[img\](https?:\/\/[^\s\[\<\>]+?)\[\/img\]/i', '<img src="$1" alt="图片" class="img-fluid" style="max-width:100%;height:auto;">', $content);
    
    return $content;
}

function getAttachmentMaxSize(): int {
    return (int)getSetting('attachment_max_size', '10') * 1024 * 1024;
}

function getAttachmentMaxCount(): int {
    return (int)getSetting('attachment_max_count', '5');
}

function getAttachmentAllowedExts(): array {
    $exts = getSetting('attachment_allowed_exts', 'zip,rar,7z,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4');
    return array_map('trim', array_filter(explode(',', strtolower($exts))));
}

function isAttachmentGuestDownload(): bool {
    return getSetting('attachment_guest_download', '0') === '1';
}

function formatFileSize(int $bytes): string {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function uploadAttachment(array $file, int $userId): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = '上传失败，错误码：' . $file['error'];
        return ['success' => false, 'errors' => $errors];
    }
    
    $maxSize = getAttachmentMaxSize();
    if ($file['size'] > $maxSize) {
        $errors[] = '附件大小超过限制（最大 ' . formatFileSize($maxSize) . '）';
        return ['success' => false, 'errors' => $errors];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = getAttachmentAllowedExts();
    if (!in_array($ext, $allowedExts)) {
        $errors[] = '不允许上传此类型的文件，允许的后缀：' . implode(', ', $allowedExts);
        return ['success' => false, 'errors' => $errors];
    }
    
    /* 按日期创建子目录 */
    $dateDir = date('Ymd');
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $uploadDir = __DIR__ . '/uploads/attachments/' . $dateDir;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    /* 检查目录是否可写 */
    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
    }
    
    $filePath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        $errors[] = '文件保存失败，请检查目录权限';
        return ['success' => false, 'errors' => $errors];
    }
    
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO attachments (user_id, filename, original_name, file_path, file_size, file_ext, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $filename,
        $file['name'],
        'uploads/attachments/' . $dateDir . '/' . $filename,
        $file['size'],
        $ext,
        $file['type']
    ]);
    
    $attachmentId = (int)$pdo->lastInsertId();
    
    return [
        'success' => true,
        'attachment' => [
            'id' => $attachmentId,
            'filename' => $filename,
            'original_name' => $file['name'],
            'file_size' => $file['size'],
            'file_ext' => $ext
        ]
    ];
}

function getPostAttachments(int $postId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE post_id = ? ORDER BY created_at ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function attachFilesToPost(int $postId, array $attachmentIds): void {
    global $pdo;
    foreach ($attachmentIds as $attachmentId) {
        $stmt = $pdo->prepare("UPDATE attachments SET post_id = ? WHERE id = ?");
        $stmt->execute([$postId, $attachmentId]);
    }
}

function getAttachmentById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function incrementAttachmentDownload(int $attachmentId): void {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE attachments SET download_count = download_count + 1 WHERE id = ?");
    $stmt->execute([$attachmentId]);
}

function deleteAttachment(int $attachmentId, int $userId): bool {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM attachments WHERE id = ?");
    $stmt->execute([$attachmentId]);
    $attachment = $stmt->fetch();
    
    if (!$attachment) {
        return false;
    }
    
    if (file_exists($attachment['file_path'])) {
        @unlink($attachment['file_path']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
    $stmt->execute([$attachmentId]);
    
    return true;
}

function cleanupOrphanAttachments(): void {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM attachments WHERE post_id IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $orphanAttachments = $stmt->fetchAll();
    
    foreach ($orphanAttachments as $attachment) {
        deleteAttachment($attachment['id'], $attachment['user_id']);
    }
}

function getAnnouncements(bool $enabledOnly = true): array {
    global $pdo;
    $sql = "SELECT * FROM announcements";
    if ($enabledOnly) {
        $sql .= " WHERE is_enabled = 1";
    }
    $sql .= " ORDER BY created_at DESC, sort_order ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getAnnouncementById(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

function createAnnouncement(string $content, string $bgColor = '#fff3cd', bool $isEnabled = true): int {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO announcements (content, bg_color, is_enabled) VALUES (?, ?, ?)");
    $stmt->execute([$content, $bgColor, $isEnabled ? 1 : 0]);
    return (int)$pdo->lastInsertId();
}

function updateAnnouncement(int $id, string $content, string $bgColor, bool $isEnabled): bool {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE announcements SET content = ?, bg_color = ?, is_enabled = ? WHERE id = ?");
    return $stmt->execute([$content, $bgColor, $isEnabled ? 1 : 0, $id]);
}

function isUsernameForbidden(string $username): bool {
    $forbidden = getSetting('forbidden_usernames', '');
    if (empty($forbidden)) {
        return false;
    }
    $forbiddenWords = array_map('trim', array_filter(explode(',', strtolower($forbidden))));
    $lowerUsername = strtolower($username);
    foreach ($forbiddenWords as $word) {
        if (strpos($lowerUsername, $word) !== false) {
            return true;
        }
    }
    return false;
}

function filterSensitiveWords(string $content): string {
    $sensitiveWords = getSetting('sensitive_words', '');
    if (empty($sensitiveWords)) {
        return $content;
    }
    $replacement = getSetting('sensitive_replacement', '***');
    $words = array_map('trim', array_filter(explode(',', $sensitiveWords)));
    foreach ($words as $word) {
        if (!empty($word)) {
            $content = str_ireplace($word, $replacement, $content);
        }
    }
    return $content;
}

function deleteAnnouncement(int $id): bool {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    return $stmt->execute([$id]);
}

function getAnnouncementColors(): array {
    return [
        '#fff3cd' => '淡黄色',
        '#cce5ff' => '淡蓝色',
        '#d4edda' => '淡绿色',
        '#f8d7da' => '淡红色',
        '#e2d5f1' => '淡紫色',
        '#d1ecf1' => '淡青色',
        '#fff5e6' => '淡橙色',
        '#ffe6e6' => '淡粉色',
        '#e6f3ff' => '天蓝色',
        '#ffe6cc' => '杏色',
    ];
}

function containsForbiddenUsername(string $username): bool {
    $forbidden = getSetting('forbidden_usernames', '');
    if (empty($forbidden)) {
        return false;
    }
    $forbiddenChars = array_map('trim', explode(',', $forbidden));
    $forbiddenChars = array_filter($forbiddenChars);
    $usernameLower = strtolower($username);
    foreach ($forbiddenChars as $char) {
        if (strpos($usernameLower, $char) !== false) {
            return true;
        }
    }
    return false;
}

function getMaxImageSize(): int {
    return (int)getSetting('max_image_size', '5') * 1024 * 1024;
}

function getMaxImageWidth(): int {
    return (int)getSetting('max_image_width', '1920');
}

function getImageQuality(): int {
    return (int)getSetting('image_quality', '85');
}

function getThumbWidth(): int {
    return (int)getSetting('thumb_width', '300');
}

function getAllowedImageTypes(): array {
    return ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

function generateImageFilename(string $originalName): string {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $ext = 'jpg';
    }
    return date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
}

function uploadImage(array $file, int $userId): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => '上传失败，错误码：' . $file['error']];
    }
    
    $maxSize = getMaxImageSize();
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => '图片大小超过限制（最大 ' . round($maxSize / 1024 / 1024, 1) . 'MB）'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['success' => false, 'error' => '不支持的图片格式，仅支持 JPG、PNG、GIF、WEBP'];
    }
    
    /* 按日期创建子目录 */
    $dateDir = date('Ymd');
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    
    /* 上传目录位于 uploads/images/日期 */
    $uploadDir = __DIR__ . '/uploads/images/' . $dateDir;
    $thumbDir = $uploadDir . '/thumbs';
    
    /* 如果目录不存在，尝试创建 */
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!is_dir($thumbDir)) {
        mkdir($thumbDir, 0755, true);
    }
    
    /* 检查目录是否可写，如果不可写则尝试修改权限 */
    if (!is_writable($uploadDir)) {
        @chmod($uploadDir, 0777);
    }
    if (!is_writable($thumbDir)) {
        @chmod($thumbDir, 0777);
    }
    
    $filepath = $uploadDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => '文件保存失败，请检查目录权限'];
    }
    
    $imageInfo = @getimagesize($filepath);
    $width = $imageInfo ? $imageInfo[0] : 0;
    $height = $imageInfo ? $imageInfo[1] : 0;
    $mimeType = $imageInfo ? $imageInfo['mime'] : 'image/jpeg';
    
    $thumbWidth = getThumbWidth();
    if ($width > 0 && $height > 0) {
        $thumbHeight = (int)($height * ($thumbWidth / $width));
        /* 缩略图保存到 thumbs 子目录 */
        $thumbFilename = $filename;
        $thumbPath = $thumbDir . '/' . $filename;
        
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg': $sourceImage = @imagecreatefromjpeg($filepath); break;
            case 'image/png': $sourceImage = @imagecreatefrompng($filepath); break;
            case 'image/gif': $sourceImage = @imagecreatefromgif($filepath); break;
            case 'image/webp': $sourceImage = @imagecreatefromwebp($filepath); break;
        }
        
        if ($sourceImage) {
            $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
            if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
            }
            imagecopyresampled($thumb, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
            
            switch ($mimeType) {
                case 'image/jpeg': imagejpeg($thumb, $thumbPath, 85); break;
                case 'image/png': imagepng($thumb, $thumbPath, 6); break;
                case 'image/gif': imagegif($thumb, $thumbPath); break;
                case 'image/webp': imagewebp($thumb, $thumbPath, 85); break;
            }
            imagedestroy($thumb);
            imagedestroy($sourceImage);
        }
    }
    
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO post_images (user_id, filename, original_name, filepath, thumbpath, filesize, width, height, mime_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $filename,
        $file['name'],
        'uploads/images/' . $dateDir . '/' . $filename,
        'uploads/images/' . $dateDir . '/thumbs/' . $filename,
        $file['size'],
        $width,
        $height,
        $mimeType
    ]);
    
    $imageId = (int)$pdo->lastInsertId();
    
    return [
        'success' => true,
        'image' => [
            'id' => $imageId,
            'filename' => $filename,
            'url' => SITE_URL . '/uploads/images/' . $dateDir . '/' . $filename,
            'thumb_url' => SITE_URL . '/uploads/images/' . $dateDir . '/thumbs/' . $filename,
            'width' => $width,
            'height' => $height
        ]
    ];
}

function getPostImages(int $postId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM post_images WHERE post_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

function attachImagesToPost(int $postId, array $imageIds): void {
    global $pdo;
    $order = 0;
    foreach ($imageIds as $imageId) {
        $stmt = $pdo->prepare("UPDATE post_images SET post_id = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$postId, $order, $imageId]);
        $order++;
    }
}

function deleteImage(int $imageId, int $userId): bool {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM post_images WHERE id = ? AND user_id = ?");
    $stmt->execute([$imageId, $userId]);
    $image = $stmt->fetch();
    
    if (!$image) {
        return false;
    }
    
    $basePath = __DIR__ . '/';
    if (file_exists($basePath . $image['filepath'])) {
        @unlink($basePath . $image['filepath']);
    }
    if (file_exists($basePath . $image['thumbpath'])) {
        @unlink($basePath . $image['thumbpath']);
    }
    $originalPath = __DIR__ . '/uploads/images/original/' . $image['filename'];
    if (file_exists($originalPath)) {
        @unlink($originalPath);
    }
    
    $stmt = $pdo->prepare("DELETE FROM post_images WHERE id = ?");
    $stmt->execute([$imageId]);
    
    return true;
}

function cleanupOrphanImages(): void {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM post_images WHERE post_id IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $orphanImages = $stmt->fetchAll();
    
    foreach ($orphanImages as $image) {
        deleteImage($image['id'], $image['user_id']);
    }
}

function sendVerifyCodeEmail(string $to, string $code, string $purpose = '验证'): array {
    $siteName = getSetting('site_title', 'HuBBS Forum');
    $subject = "【{$siteName}】{$purpose}码";
    $body = '<html><body>';
    $body .= '<div style="max-width:600px;margin:0 auto;padding:20px;font-family:Arial,sans-serif;">';
    $body .= '<h2 style="color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;">' . escape($purpose) . '码</h2>';
    $body .= '<p style="font-size:16px;color:#666;">您好！</p>';
    $body .= '<p style="font-size:16px;color:#666;">您的' . escape($purpose) . '码是：</p>';
    $body .= '<div style="background:#f5f5f5;padding:20px;text-align:center;margin:20px 0;">';
    $body .= '<span style="font-size:32px;font-weight:bold;color:#007bff;letter-spacing:5px;">' . escape($code) . '</span>';
    $body .= '</div>';
    $body .= '<p style="font-size:14px;color:#999;">验证码有效期为10分钟，请尽快使用。</p>';
    $body .= '<p style="font-size:14px;color:#999;">如果您没有请求此验证码，请忽略此邮件。</p>';
    $body .= '<hr style="border:none;border-top:1px solid #eee;margin:20px 0;">';
    $body .= '<p style="color:#999;font-size:12px;">此邮件由系统自动发送，请勿回复</p>';
    $body .= '</div>';
    $body .= '</body></html>';
    
    return sendEmail($to, $subject, $body);
}
