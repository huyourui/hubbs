<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * 系统引导文件
 * 负责系统初始化、数据库连接、自动迁移等核心功能
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */

/* 开启会话 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 定义系统版本信息 */
if (!defined('HUBBS_VERSION')) {
    define('HUBBS_VERSION', '1.1.0');
}
if (!defined('HUBBS_NAME')) {
    define('HUBBS_NAME', 'HuBBS');
}

/* 定义系统根路径 */
if (!defined('ROOT_PATH')) {
    $rootPath = str_replace('\\', '/', dirname(__DIR__));
    $documentRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $rootPath = str_replace($documentRoot, '', $rootPath);
    $rootPath = rtrim($rootPath, '/');
    define('ROOT_PATH', $rootPath);
}

/* 定义站点URL */
if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITE_URL', $protocol . '://' . $host . ROOT_PATH);
}

/* 定义站点基本信息 */
define('SITE_NAME', 'HuBBS Forum');
define('SITE_DESC', 'An Open Source Forum System');

/* 定义系统常量 */
define('POSTS_PER_PAGE', 10);
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 20);
define('MIN_PASSWORD_LENGTH', 6);

/* 设置时区 */
date_default_timezone_set('Asia/Shanghai');

/**
 * 确保数据表存在
 * 如果表不存在则自动创建，实现无感迁移
 * 
 * @param PDO    $pdo       数据库连接对象
 * @param string $table     表名
 * @param string $createSql 创建表的SQL语句
 * 
 * @return void
 */
function ensureTableExists(PDO $pdo, string $table, string $createSql): void {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        $stmt->fetchAll();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        try {
            $pdo->exec($createSql);
        } catch (PDOException $ex) {
            // 表已存在或创建失败，忽略错误
        }
    }
}

/**
 * 确保数据表字段存在
 * 如果字段不存在则自动添加，实现无感迁移
 * 
 * @param PDO    $pdo       数据库连接对象
 * @param string $table     表名
 * @param string $column    字段名
 * @param string $alterSql  添加字段的SQL语句
 * 
 * @return void
 */
function ensureColumnExists(PDO $pdo, string $table, string $column, string $alterSql): void {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        if ($stmt->fetch() === false) {
            $pdo->exec($alterSql);
        }
    } catch (PDOException $e) {
        try {
            $pdo->exec($alterSql);
        } catch (PDOException $ex) {
            // 字段已存在或添加失败，忽略错误
        }
    }
}

/**
 * 初始化数据库表结构
 * 自动创建所需的数据表和字段，实现无感迁移
 * 
 * @param PDO $pdo 数据库连接对象
 * 
 * @return void
 */
function initDatabase(PDO $pdo): void {
    /* 创建用户表 */
    ensureTableExists($pdo, 'users', "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('user', 'admin') DEFAULT 'user',
        `avatar` VARCHAR(255) DEFAULT NULL,
        `bio` TEXT DEFAULT NULL,
        `remember_token` VARCHAR(255) DEFAULT NULL,
        `remember_expires_at` DATETIME DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_username` (`username`),
        INDEX `idx_email` (`email`),
        INDEX `idx_remember_token` (`remember_token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建分类表 */
    ensureTableExists($pdo, 'categories', "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `parent_id` INT UNSIGNED DEFAULT NULL,
        `name` VARCHAR(50) NOT NULL,
        `description` VARCHAR(255) DEFAULT NULL,
        `slug` VARCHAR(50) NOT NULL UNIQUE,
        `sort_order` INT DEFAULT 0,
        `allowed_users` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_slug` (`slug`),
        INDEX `idx_parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建帖子表 */
    ensureTableExists($pdo, 'posts', "CREATE TABLE IF NOT EXISTS `posts` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `category_id` INT UNSIGNED DEFAULT NULL,
        `views` INT UNSIGNED DEFAULT 0,
        `is_sticky` TINYINT(1) DEFAULT 0,
        `is_locked` TINYINT(1) DEFAULT 0,
        `is_digest` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_category_id` (`category_id`),
        INDEX `idx_created_at` (`created_at`),
        INDEX `idx_is_digest` (`is_digest`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建评论表 */
    ensureTableExists($pdo, 'comments', "CREATE TABLE IF NOT EXISTS `comments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED NOT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `content` TEXT NOT NULL,
        `parent_id` INT UNSIGNED DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_post_id` (`post_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 确保评论表有 reply_to_user_id 字段 */
    ensureColumnExists($pdo, 'comments', 'reply_to_user_id', "ALTER TABLE comments ADD COLUMN reply_to_user_id INT UNSIGNED DEFAULT NULL AFTER parent_id");
    
    /* 确保分类表有 allowed_users 字段 */
    ensureColumnExists($pdo, 'categories', 'allowed_users', "ALTER TABLE categories ADD COLUMN allowed_users TEXT DEFAULT NULL AFTER sort_order");
    
    /* 创建系统设置表 */
    ensureTableExists($pdo, 'settings', "CREATE TABLE IF NOT EXISTS `settings` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `setting_key` VARCHAR(50) NOT NULL UNIQUE,
        `setting_value` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建收藏表 */
    ensureTableExists($pdo, 'favorites', "CREATE TABLE IF NOT EXISTS `favorites` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `post_id` INT UNSIGNED NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_post` (`user_id`, `post_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_post_id` (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建通知表 */
    ensureTableExists($pdo, 'notifications', "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `type` VARCHAR(50) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `content` TEXT DEFAULT NULL,
        `data` JSON DEFAULT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_is_read` (`is_read`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建友情链接表 */
    ensureTableExists($pdo, 'links', "CREATE TABLE IF NOT EXISTS `links` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `url` VARCHAR(255) NOT NULL,
        `description` VARCHAR(255) DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `is_visible` TINYINT(1) DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_sort_order` (`sort_order`),
        INDEX `idx_is_visible` (`is_visible`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建公告表 */
    ensureTableExists($pdo, 'announcements', "CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `content` TEXT NOT NULL,
        `bg_color` VARCHAR(20) DEFAULT '#fff3cd',
        `is_enabled` TINYINT(1) DEFAULT 1,
        `sort_order` INT UNSIGNED DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_is_enabled` (`is_enabled`),
        INDEX `idx_sort_order` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 移除公告表的旧字段 title（如果存在） */
    try {
        $pdo->exec("ALTER TABLE `announcements` DROP COLUMN `title`");
    } catch (PDOException $e) {
        // 字段不存在，忽略错误
    }
    
    /* 创建附件表 */
    ensureTableExists($pdo, 'attachments', "CREATE TABLE IF NOT EXISTS `attachments` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED DEFAULT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `original_name` VARCHAR(255) NOT NULL,
        `file_path` VARCHAR(500) NOT NULL,
        `file_size` INT UNSIGNED NOT NULL,
        `file_ext` VARCHAR(20) NOT NULL,
        `mime_type` VARCHAR(100) NOT NULL,
        `download_count` INT UNSIGNED DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_post_id` (`post_id`),
        INDEX `idx_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 确保附件表的 post_id 字段允许 NULL */
    try {
        $pdo->exec("ALTER TABLE `attachments` MODIFY COLUMN `post_id` INT UNSIGNED DEFAULT NULL");
    } catch (PDOException $e) {
        // 修改失败，忽略错误
    }
    
    /* 创建积分规则表 */
    ensureTableExists($pdo, 'point_rules', "CREATE TABLE IF NOT EXISTS `point_rules` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `action` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `points` INT NOT NULL DEFAULT 0,
        `description` VARCHAR(255) DEFAULT NULL,
        `is_enabled` TINYINT(1) DEFAULT 1,
        `daily_limit` INT UNSIGNED DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX `idx_action` (`action`),
        INDEX `idx_is_enabled` (`is_enabled`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建用户等级表 */
    ensureTableExists($pdo, 'user_levels', "CREATE TABLE IF NOT EXISTS `user_levels` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(50) NOT NULL,
        `min_points` INT UNSIGNED NOT NULL DEFAULT 0,
        `max_points` INT UNSIGNED NOT NULL DEFAULT 0,
        `sort_order` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_points` (`min_points`, `max_points`),
        INDEX `idx_sort_order` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 初始化默认用户等级 */
    $stmt = $pdo->query("SELECT COUNT(*) FROM user_levels");
    if ($stmt->fetchColumn() == 0) {
        $defaultLevels = [
            ['江湖新手', 0, 99],
            ['初窥门径', 100, 299],
            ['小试锋芒', 300, 599],
            ['武林新秀', 600, 999],
            ['江湖侠士', 1000, 1999],
            ['一代大侠', 2000, 4999],
            ['武林至尊', 5000, 999999]
        ];
        $stmt = $pdo->prepare("INSERT INTO user_levels (name, min_points, max_points, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($defaultLevels as $i => $level) {
            $stmt->execute([$level[0], $level[1], $level[2], $i]);
        }
    }
    
    /* 创建积分日志表 */
    ensureTableExists($pdo, 'point_logs', "CREATE TABLE IF NOT EXISTS `point_logs` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `points` INT NOT NULL,
        `balance` INT NOT NULL DEFAULT 0,
        `related_type` VARCHAR(50) DEFAULT NULL,
        `related_id` INT UNSIGNED DEFAULT NULL,
        `remark` VARCHAR(255) DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_action` (`action`),
        INDEX `idx_created_at` (`created_at`),
        INDEX `idx_related` (`related_type`, `related_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建邀请码表 */
    ensureTableExists($pdo, 'invite_codes', "CREATE TABLE IF NOT EXISTS `invite_codes` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `code` VARCHAR(32) NOT NULL UNIQUE,
        `created_by` INT UNSIGNED NOT NULL,
        `used_by` INT UNSIGNED DEFAULT NULL,
        `is_used` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `used_at` DATETIME DEFAULT NULL,
        INDEX `idx_code` (`code`),
        INDEX `idx_is_used` (`is_used`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建点赞表 */
    ensureTableExists($pdo, 'likes', "CREATE TABLE IF NOT EXISTS `likes` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT UNSIGNED NOT NULL,
        `post_id` INT UNSIGNED NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_post` (`user_id`, `post_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_post_id` (`post_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 创建帖子图片表 */
    ensureTableExists($pdo, 'post_images', "CREATE TABLE IF NOT EXISTS `post_images` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED DEFAULT NULL,
        `user_id` INT UNSIGNED NOT NULL,
        `filename` VARCHAR(255) NOT NULL,
        `original_name` VARCHAR(255) DEFAULT NULL,
        `filepath` VARCHAR(500) NOT NULL,
        `thumbpath` VARCHAR(500) DEFAULT NULL,
        `filesize` INT UNSIGNED NOT NULL DEFAULT 0,
        `width` INT UNSIGNED DEFAULT NULL,
        `height` INT UNSIGNED DEFAULT NULL,
        `mime_type` VARCHAR(50) DEFAULT NULL,
        `sort_order` INT UNSIGNED DEFAULT 0,
        `is_inserted` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_post_id` (`post_id`),
        INDEX `idx_user_id` (`user_id`),
        INDEX `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    /* 确保用户表有积分字段 */
    ensureColumnExists($pdo, 'users', 'points', "ALTER TABLE users ADD COLUMN points INT NOT NULL DEFAULT 0 AFTER bio");
    
    /* 确保用户表有记住登录令牌字段 */
    ensureColumnExists($pdo, 'users', 'remember_token', "ALTER TABLE users ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL AFTER points");
    ensureColumnExists($pdo, 'users', 'remember_expires_at', "ALTER TABLE users ADD COLUMN remember_expires_at DATETIME DEFAULT NULL AFTER remember_token");
    
    /* 确保帖子表有精华字段 */
    ensureColumnExists($pdo, 'posts', 'is_digest', "ALTER TABLE posts ADD COLUMN is_digest TINYINT(1) DEFAULT 0");
    
    /* 确保分类表有父级分类字段 */
    ensureColumnExists($pdo, 'categories', 'parent_id', "ALTER TABLE categories ADD COLUMN parent_id INT UNSIGNED DEFAULT NULL AFTER id");
    
    /* 确保帖子和评论表有IP地址字段 */
    ensureColumnExists($pdo, 'posts', 'ip_address', "ALTER TABLE posts ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER updated_at");
    ensureColumnExists($pdo, 'comments', 'ip_address', "ALTER TABLE comments ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL AFTER updated_at");
    
    /* 添加优化索引 */
    try { $pdo->exec("ALTER TABLE posts ADD INDEX idx_is_digest (is_digest)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE categories ADD INDEX idx_parent_id (parent_id)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE posts ADD INDEX idx_list_query (is_sticky DESC, created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE posts ADD INDEX idx_category_list (category_id, is_sticky DESC, created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE posts ADD INDEX idx_user_posts (user_id, created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE comments ADD INDEX idx_post_created (post_id, created_at ASC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE comments ADD INDEX idx_user_comments (user_id, created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE notifications ADD INDEX idx_user_read (user_id, is_read, created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE point_logs ADD INDEX idx_user_action_date (user_id, action, created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE post_images ADD INDEX idx_post_sort (post_id, sort_order)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD INDEX idx_role (role)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE users ADD INDEX idx_created (created_at DESC)"); } catch (PDOException $e) {}
    try { $pdo->exec("ALTER TABLE favorites ADD INDEX idx_user_created (user_id, created_at DESC)"); } catch (PDOException $e) {}
    
    /* 清理设置表重复数据 */
    try {
        $stmt = $pdo->query("SELECT setting_key, COUNT(*) as cnt FROM settings GROUP BY setting_key HAVING cnt > 1");
        $duplicates = $stmt->fetchAll();
        if (!empty($duplicates)) {
            foreach ($duplicates as $dup) {
                $stmt = $pdo->prepare("DELETE FROM settings WHERE setting_key = ? LIMIT 1");
                $stmt->execute([$dup['setting_key']]);
            }
        }
    } catch (PDOException $e) {}
    
    /* 确保设置表有唯一索引 */
    try {
        $pdo->exec("ALTER TABLE settings ADD UNIQUE INDEX idx_setting_key_unique (setting_key)");
    } catch (PDOException $e) {}
    
    /* 初始化默认系统设置 */
    $defaultSettings = [
        ['site_title', 'HuBBS'],
        ['site_subtitle', '一款基于MIT协议的开源论坛'],
        ['require_category', '0'],
        ['allow_register', '1'],
        ['max_post_length', '10000'],
        ['max_comment_length', '2000'],
        ['smtp_enabled', '0'],
        ['smtp_host', ''],
        ['smtp_port', '465'],
        ['smtp_secure', 'ssl'],
        ['smtp_username', ''],
        ['smtp_password', ''],
        ['smtp_from_email', ''],
        ['smtp_from_name', 'HuBBS Forum'],
        ['email_verify_register', '0'],
        ['email_notify_reply', '0'],
        ['restrict_email_domain', '0'],
        ['allowed_email_domains', ''],
        ['max_image_size', '5'],
        ['max_image_width', '1920'],
        ['image_quality', '85'],
        ['thumb_width', '300'],
        ['posts_per_page', '10'],
        ['forbidden_usernames', 'admin,administrator,root,system,管理员,系统'],
        ['sensitive_words', ''],
        ['sensitive_replacement', '***'],
        ['attachment_max_size', '10'],
        ['attachment_max_count', '5'],
        ['attachment_allowed_exts', 'zip,rar,7z,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4'],
        ['attachment_guest_download', '0'],
    ];
    
    foreach ($defaultSettings as $setting) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)");
            $stmt->execute($setting);
        } catch (PDOException $e) {}
    }
    
    /* 初始化默认积分规则 */
    $defaultPointRules = [
        ['create_post', '发帖', 5, '发布新帖子获得积分', 1, 10],
        ['create_comment', '发表评论', 2, '发表评论获得积分', 1, 20],
        ['like_post', '点赞帖子', 1, '点赞帖子获得积分', 1, 30],
        ['post_liked', '帖子被点赞', 1, '帖子被点赞获得积分', 1, 50],
        ['post_deleted', '帖子被删除', -10, '帖子被删除扣除积分', 1, 0],
        ['comment_deleted', '评论被删除', -5, '评论被删除扣除积分', 1, 0],
    ];
    
    foreach ($defaultPointRules as $rule) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `point_rules` (`action`, `name`, `points`, `description`, `is_enabled`, `daily_limit`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute($rule);
        } catch (PDOException $e) {}
    }
    
    /* 创建上传目录 */
    $uploadDir = dirname(__FILE__) . '/public/uploads';
    $uploadSubDirs = ['', 'images', 'images/original', 'images/thumbs'];
    foreach ($uploadSubDirs as $subDir) {
        $dir = $uploadDir . ($subDir ? '/' . $subDir : '');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

/**
 * 初始化数据库连接
 * 加载数据库配置并建立连接，自动执行数据库迁移
 * 
 * @return PDO|null 返回数据库连接对象，连接失败返回null
 */
function initDatabaseConnection(): ?PDO {
    global $pdo;
    
    /* 检查数据库配置是否已定义 */
    if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
        return null;
    }
    
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        /* 如果安装锁文件存在，执行数据库迁移 */
        if (file_exists(__DIR__ . '/../install.lock')) {
            initDatabase($pdo);
        }
        
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

/* 加载数据库配置文件（必须在调用 initDatabaseConnection 之前） */
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

/* 初始化数据库连接 */
$pdo = initDatabaseConnection();

/* 检查是否已安装（install.lock 文件存在） */
$installLockFile = __DIR__ . '/../install.lock';
$inInstallDir = strpos($_SERVER['PHP_SELF'], '/install/') !== false;

/* 如果 install.lock 不存在且不在安装目录，跳转到安装页面 */
if (!file_exists($installLockFile) && !$inInstallDir) {
    header('Location: ' . ROOT_PATH . '/install/index.php');
    exit;
}

/* 如果数据库连接失败且不在安装目录，跳转到安装页面 */
if ($pdo === null && !$inInstallDir) {
    header('Location: ' . ROOT_PATH . '/install/index.php');
    exit;
}
