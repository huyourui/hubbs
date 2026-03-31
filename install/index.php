<?php
/**
 * HuBBS - 安装程序
 */

define('HUBBS_ROOT', dirname(__DIR__) . '/');
$lockFile = HUBBS_ROOT . 'install.lock';

// 加载版本号定义
require_once HUBBS_ROOT . 'core/config.php';

/**
 * 安全处理SQL标识符（数据库名、表名、字段名等）
 * 防止SQL注入攻击
 * @param string $identifier 标识符
 * @return string 处理后的标识符
 */
function backquoteIdentifier($identifier) {
    // 移除反引号防止注入
    $identifier = str_replace('`', '', $identifier);
    // 只允许字母数字下划线
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $identifier)) {
        throw new Exception('非法的数据库标识符，只允许字母、数字和下划线');
    }
    return '`' . $identifier . '`';
}

/**
 * 验证表前缀（只允许字母数字下划线）
 * @param string $prefix 前缀
 * @return string 验证后的前缀
 */
function validatePrefix($prefix) {
    if (!preg_match('/^[a-zA-Z0-9_]*$/', $prefix)) {
        throw new Exception('非法的表前缀，只允许字母、数字和下划线');
    }
    return $prefix;
}

// 检查是否已安装
if (file_exists($lockFile)) {
    die('系统已安装！如需重新安装，请删除 install.lock 文件。');
}

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = '';

// 步骤处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // 测试数据库连接
            $config = [
                'host' => $_POST['db_host'] ?? 'localhost',
                'port' => intval($_POST['db_port'] ?? 3306),
                'user' => $_POST['db_user'] ?? '',
                'pass' => $_POST['db_pass'] ?? '',
                'name' => $_POST['db_name'] ?? 'hubbs',
                'charset' => 'utf8mb4',
                'prefix' => $_POST['db_prefix'] ?? 'hubbs_',
                'engine' => 'InnoDB'
            ];
            
            try {
                $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
                $pdo = new PDO($dsn, $config['user'], $config['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // 创建数据库 - 使用反引号包裹标识符防止SQL注入
                $dbName = backquoteIdentifier($config['name']);
                $charset = backquoteIdentifier($config['charset']);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbName} CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
                $pdo->exec("USE {$dbName}");
                
                // 创建表
                createTables($pdo, $config);
                
                // 创建管理员
                $adminUser = $_POST['admin_user'] ?? 'admin';
                $adminPass = $_POST['admin_pass'] ?? '';
                $adminEmail = $_POST['admin_email'] ?? '';
                
                if (strlen($adminPass) < 6) {
                    throw new Exception('管理员密码至少6位');
                }
                
                $salt = bin2hex(random_bytes(16));
                $password = password_hash($adminPass . $salt, PASSWORD_BCRYPT);
                
                $prefix = $config['prefix'];
                $stmt = $pdo->prepare("INSERT INTO {$prefix}users (username, email, password, is_admin, created_at, last_login) VALUES (?, ?, ?, 1, NOW(), NOW())");
                $stmt->execute([$adminUser, $adminEmail, $password]);
                
                // 保存配置
                saveConfig($config, $salt);
                
                // 创建锁文件
                file_put_contents($lockFile, date('Y-m-d H:i:s'));
                
                $success = '安装成功！';
                $step = 3;
            } catch (PDOException $e) {
                // 数据库错误处理
                $errorCode = $e->getCode();
                $errorMsg = $e->getMessage();
                
                if (strpos($errorMsg, 'Access denied') !== false) {
                    $error = '数据库连接失败：用户名或密码错误';
                } elseif (strpos($errorMsg, 'Unknown database') !== false) {
                    $error = '数据库不存在，请检查数据库名称';
                } elseif (strpos($errorMsg, 'Connection refused') !== false || strpos($errorMsg, 'No such host') !== false) {
                    $error = '无法连接到数据库服务器，请检查主机地址和端口';
                } elseif (strpos($errorMsg, 'Duplicate entry') !== false) {
                    $error = '数据重复：' . $errorMsg;
                } else {
                    $error = '数据库错误：' . $errorMsg;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

function createTables($pdo, $config) {
    // 验证表前缀安全性
    $prefix = validatePrefix($config['prefix']);
    $engine = backquoteIdentifier($config['engine']);
    $charset = backquoteIdentifier($config['charset']);
    
    // 开始事务
    $pdo->beginTransaction();
    
    // 禁用外键检查，避免删除表时因外键约束而失败
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // 删除已存在的表（确保使用最新表结构）
    // 注意：删除顺序需要考虑外键依赖关系，先删除有外键依赖的表
    $tables = [
        // 关联表（有外键依赖的优先删除）
        'remember_tokens', 'post_likes', 'post_favorites', 
        'reply_comments', 'replies', 'uploads', 'notifications',
        'email_codes', 'links', 'settings', 'migrations',
        // 主表
        'posts', 'forums', 'users'
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS {$prefix}{$table}");
        } catch (PDOException $e) {
            // 忽略删除不存在的表的错误
            if ($e->getCode() != '42S02') { // 42S02 = 表不存在
                throw $e;
            }
        }
    }
    
    // 重新启用外键检查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 用户表 - 支持亿级数据
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        username VARCHAR(20) NOT NULL COMMENT '用户名',
        email VARCHAR(100) NOT NULL COMMENT '邮箱',
        original_email VARCHAR(100) DEFAULT NULL COMMENT '注销前的原始邮箱',
        password VARCHAR(255) NOT NULL COMMENT '密码',
        avatar VARCHAR(255) DEFAULT '' COMMENT '头像',
        is_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否管理员',
        status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '用户状态：0-封禁，1-正常',
        unread_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '未读消息数',
        deleted_at DATETIME DEFAULT NULL COMMENT '删除时间，NULL表示未删除',
        created_at DATETIME NOT NULL COMMENT '注册时间',
        last_login DATETIME DEFAULT NULL COMMENT '最后登录',
        last_ip VARCHAR(45) DEFAULT '' COMMENT '最后登录IP',
        PRIMARY KEY (id),
        UNIQUE KEY uk_username (username),
        UNIQUE KEY uk_email (email),
        KEY idx_created (created_at)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='用户表'");
    
    // 记住登录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}remember_tokens (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_token (token),
        KEY idx_expires (expires)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='记住登录'");
    
    // 板块表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}forums (
        id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
        parent_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID',
        name VARCHAR(50) NOT NULL COMMENT '板块名称',
        description VARCHAR(255) DEFAULT '' COMMENT '描述',
        icon VARCHAR(50) DEFAULT '' COMMENT '图标',
        sort_order SMALLINT UNSIGNED DEFAULT 0 COMMENT '排序',
        post_count INT UNSIGNED DEFAULT 0 COMMENT '帖子数',
        PRIMARY KEY (id),
        KEY idx_sort (sort_order)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='板块表'");
    
    // 帖子表 - 分表设计，支持亿级数据
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}posts (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        forum_id SMALLINT UNSIGNED NOT NULL COMMENT '板块ID',
        user_id INT UNSIGNED NOT NULL COMMENT '作者ID',
        title VARCHAR(100) NOT NULL COMMENT '标题',
        content TEXT NOT NULL COMMENT '内容',
        views INT UNSIGNED DEFAULT 0 COMMENT '浏览数',
        replies INT UNSIGNED DEFAULT 0 COMMENT '回复数',
        likes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数',
        favorites INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数',
        is_top TINYINT(1) DEFAULT 0 COMMENT '置顶',
        is_essence TINYINT(1) DEFAULT 0 COMMENT '精华',
        is_locked TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否锁定',
        created_at DATETIME NOT NULL COMMENT '发布时间',
        updated_at DATETIME DEFAULT NULL COMMENT '更新时间',
        last_reply_at DATETIME DEFAULT NULL COMMENT '最后回复时间',
        last_reply_user_id INT UNSIGNED DEFAULT 0 COMMENT '最后回复用户',
        PRIMARY KEY (id),
        KEY idx_forum_time (forum_id, created_at),
        KEY idx_user (user_id),
        KEY idx_top_time (is_top, created_at),
        KEY idx_last_reply (last_reply_at),
        FULLTEXT KEY ft_title (title)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='帖子表'");
    
    // 回复表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}replies (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL COMMENT '帖子ID',
        user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
        content TEXT NOT NULL COMMENT '内容',
        created_at DATETIME NOT NULL COMMENT '回复时间',
        PRIMARY KEY (id),
        KEY idx_post (post_id),
        KEY idx_user (user_id),
        KEY idx_created (created_at)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='回复表'");
    
    // 楼中楼回复表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}reply_comments (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        reply_id INT UNSIGNED NOT NULL COMMENT '一级回复ID',
        user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
        to_user_id INT UNSIGNED DEFAULT 0 COMMENT '回复给哪个用户',
        content TEXT NOT NULL COMMENT '内容',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_reply (reply_id),
        KEY idx_user (user_id)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='楼中楼回复'");
    
    // 点赞表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}post_likes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL COMMENT '帖子ID',
        user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_post_user (post_id, user_id),
        KEY idx_post (post_id),
        KEY idx_user (user_id)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='帖子点赞表'");
    
    // 收藏表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}post_favorites (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id INT UNSIGNED NOT NULL COMMENT '帖子ID',
        user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_post_user (post_id, user_id),
        KEY idx_post (post_id),
        KEY idx_user (user_id)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='帖子收藏表'");
    
    // 消息通知表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}notifications (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL COMMENT '接收消息的用户ID',
        sender_id INT UNSIGNED DEFAULT 0 COMMENT '发送者用户ID，0表示系统',
        type VARCHAR(30) NOT NULL COMMENT '消息类型',
        title VARCHAR(200) DEFAULT NULL COMMENT '消息标题',
        content TEXT COMMENT '消息内容',
        target_id INT UNSIGNED DEFAULT 0 COMMENT '关联目标ID',
        target_type VARCHAR(20) DEFAULT NULL COMMENT '关联目标类型',
        is_read TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读',
        created_at DATETIME NOT NULL,
        read_at DATETIME DEFAULT NULL COMMENT '阅读时间',
        PRIMARY KEY (id),
        KEY idx_user (user_id),
        KEY idx_user_read (user_id, is_read),
        KEY idx_type (type),
        KEY idx_created (created_at)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='消息通知表'");
    
    // 友情链接表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}links (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL COMMENT '链接名称',
        url VARCHAR(500) NOT NULL COMMENT '链接地址',
        description VARCHAR(255) DEFAULT NULL COMMENT '链接描述',
        sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序顺序',
        is_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否显示',
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_sort (sort_order),
        KEY idx_visible (is_visible)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='友情链接表'");
    
    // 邮件验证码表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}email_codes (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL COMMENT '邮箱地址',
        code VARCHAR(10) NOT NULL COMMENT '验证码',
        type VARCHAR(20) NOT NULL DEFAULT 'register' COMMENT '验证码类型',
        expires_at DATETIME NOT NULL COMMENT '过期时间',
        used TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已使用',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_email (email),
        KEY idx_code (code),
        KEY idx_expires (expires_at)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='邮件验证码表'");
    
    // 上传文件表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}uploads (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL COMMENT '上传用户ID',
        file_name VARCHAR(255) NOT NULL COMMENT '原始文件名',
        file_path VARCHAR(500) NOT NULL COMMENT '文件存储路径',
        file_type VARCHAR(50) NOT NULL COMMENT '文件类型',
        file_size INT UNSIGNED NOT NULL COMMENT '文件大小',
        mime_type VARCHAR(100) DEFAULT NULL COMMENT 'MIME类型',
        post_id INT UNSIGNED DEFAULT NULL COMMENT '关联的帖子ID',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user (user_id),
        KEY idx_post (post_id),
        KEY idx_type (file_type),
        KEY idx_created (created_at)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='上传文件表'");
    
    // 设置表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}settings (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(50) NOT NULL COMMENT '设置键',
        setting_value TEXT COMMENT '设置值',
        description VARCHAR(255) DEFAULT NULL COMMENT '描述',
        PRIMARY KEY (id),
        UNIQUE KEY uk_key (setting_key)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='系统设置表'");
    
    // 迁移记录表
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        version INT UNSIGNED NOT NULL,
        executed_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uk_version (version)
    ) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='迁移记录表'");
    
    // 插入默认设置
    $defaultSettings = [
        ['site_title', 'HuBBS', '网站标题'],
        ['site_subtitle', '开源论坛程序', '网站副标题'],
        ['site_keywords', 'HuBBS,论坛,开源,PHP', '网站关键词'],
        ['site_description', 'HuBBS是一款轻量级开源论坛程序', '网站描述'],
        ['site_copyright', 'HuBBS - 开源论坛程序 v' . HUBBS_VERSION, '版权信息'],
        ['enable_register', '1', '是否开放注册'],
        ['is_force_forum', '1', '是否强制选择分类'],
        ['register_email_suffix', '', '注册邮箱后缀限制'],
        ['register_banned_words', 'admin,root,system,管理员', '禁用用户名'],
        ['username_min_length', '2', '用户名最小长度'],
        ['username_max_length', '20', '用户名最大长度'],
        ['post_min_length', '5', '帖子最小长度'],
        ['post_max_length', '10000', '帖子最大长度'],
        ['reply_min_length', '2', '回复最小长度'],
        ['reply_max_length', '5000', '回复最大长度'],
        ['posts_per_page', '20', '每页帖子数'],
        ['replies_per_page', '20', '每页回复数'],
        ['post_interval', '0', '发帖间隔(秒)'],
        ['reply_interval', '0', '回复间隔(秒)'],
        ['mail_enabled', '0', '是否启用邮件'],
        ['mail_method', 'smtp', '邮件发送方式'],
        ['mail_host', '', '邮件服务器'],
        ['mail_port', '587', '邮件端口'],
        ['mail_encryption', 'tls', '邮件加密方式'],
        ['mail_username', '', '邮件用户名'],
        ['mail_password', '', '邮件密码'],
        ['mail_from_address', '', '发件人地址'],
        ['mail_from_name', '', '发件人名称'],
        ['mail_verify_register', '0', '注册是否需要验证邮箱'],
        ['upload_image_exts', 'jpg,jpeg,png,gif,webp', '允许的图片后缀'],
        ['upload_image_max_size', '5242880', '图片最大大小(字节)'],
        ['upload_image_max_count', '10', '最大图片数量'],
        ['upload_attachment_exts', 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,md', '允许的附件后缀'],
        ['upload_attachment_max_size', '10485760', '附件最大大小(字节)'],
        ['upload_attachment_max_count', '5', '最大附件数量'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    
    // 插入默认板块 - 只保留一个默认板块
    $stmt = $pdo->prepare("INSERT INTO {$prefix}forums (name, description, icon, sort_order) VALUES (?, ?, ?, ?)");
    $stmt->execute(['默认板块', '默认讨论板块', 'chat', 1]);

    // 插入默认友情链接
    $stmt = $pdo->prepare("INSERT INTO {$prefix}links (name, url, description, sort_order, is_visible, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute(['HuBBS官网', 'https://bbs.huyourui.com', 'HuBBS开源论坛程序官方网站', 1, 1]);
    
    // 记录迁移版本（安装时已完成所有迁移）
    $pdo->exec("INSERT INTO {$prefix}migrations (version, executed_at) VALUES (17, NOW())");
    
    // 提交事务
    $pdo->commit();
}

function saveConfig($config, $salt) {
    // 生成加密密钥（基于安装时的随机盐值）
    $encryptionKey = hash('sha256', $salt . 'hubbs_encryption_key', true);
    
    // 加密数据库密码
    $encryptedPass = encryptData($config['pass'], $encryptionKey);
    
    $content = "<?php\n";
    $content .= "// HuBBS 配置文件 - 自动生成\n";
    $content .= "// 生成时间: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "\$db_config = [\n";
    $content .= "    'host'    => '{$config['host']}',\n";
    $content .= "    'port'    => {$config['port']},\n";
    $content .= "    'user'    => '{$config['user']}',\n";
    $content .= "    'pass'    => '{$encryptedPass}',\n";
    $content .= "    'pass_encrypted' => true,\n";
    $content .= "    'name'    => '{$config['name']}',\n";
    $content .= "    'charset' => '{$config['charset']}',\n";
    $content .= "    'prefix'  => '{$config['prefix']}',\n";
    $content .= "    'engine'  => '{$config['engine']}'\n";
    $content .= "];\n\n";
    $content .= "\$hubbs_salt = '{$salt}';\n";
    
    // 创建data目录
    $dataDir = HUBBS_ROOT . 'data';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    file_put_contents($dataDir . '/config.php', $content);
}

/**
 * 加密数据
 * @param string $data 要加密的数据
 * @param string $key 加密密钥
 * @return string 加密后的数据（base64编码）
 */
function encryptData($data, $key) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * 解密数据
 * @param string $data 加密的数据（base64编码）
 * @param string $key 解密密钥
 * @return string|false 解密后的数据或false
 */
function decryptData($data, $key) {
    $data = base64_decode($data);
    if ($data === false || strlen($data) < 16) {
        return false;
    }
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HuBBS 安装 - 步骤 <?php echo $step; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .install-box {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            width: 480px;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            font-size: 28px;
        }
        .logo span {
            color: #ff6b6b;
        }
        .version {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-bottom: 30px;
        }
        .step-bar {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin: 0 10px;
        }
        .step.active {
            background: #ff6b6b;
            color: #fff;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #ff6b6b;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #ff6b6b;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .btn:hover {
            background: #ff5252;
        }
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .success-icon {
            text-align: center;
            font-size: 60px;
            color: #4caf50;
            margin-bottom: 20px;
        }
        .success-text {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="install-box">
        <div class="logo">
            <h1>Hu<span>BBS</span></h1>
        </div>
        <div class="version">Version <?php echo HUBBS_VERSION; ?></div>
        
        <div class="step-bar">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($step == 1): ?>
        <form method="get">
            <input type="hidden" name="step" value="2">
            <h3 style="margin-bottom: 20px; color: #333;">环境检测</h3>
            <div style="margin-bottom: 20px; font-size: 14px; color: #666;">
                <p>✓ PHP 版本: <?php echo PHP_VERSION; ?> (需要 7.4+)</p>
                <p>✓ PDO 扩展: <?php echo extension_loaded('pdo') ? '已安装' : '未安装'; ?></p>
                <p>✓ PDO_MySQL: <?php echo extension_loaded('pdo_mysql') ? '已安装' : '未安装'; ?></p>
                <p>✓ 目录可写: <?php echo is_writable(HUBBS_ROOT) ? '是' : '否'; ?></p>
            </div>
            <button type="submit" class="btn">下一步</button>
        </form>
        
        <?php elseif ($step == 2): ?>
        <form method="post">
            <input type="hidden" name="step" value="2">
            <h3 style="margin-bottom: 20px; color: #333;">数据库配置</h3>
            <div style="margin-bottom: 20px; padding: 10px; background: #fff3cd; border-radius: 4px; font-size: 13px; color: #856404;">
                <strong>提示：</strong>如果数据库已存在，安装程序会自动删除旧表并重新创建。如需保留数据，请先备份。
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>数据库主机</label>
                    <input type="text" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label>端口</label>
                    <input type="text" name="db_port" value="3306" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>数据库名</label>
                <input type="text" name="db_name" value="hubbs" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="db_user" value="root" required>
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="db_pass">
                </div>
            </div>
            
            <div class="form-group">
                <label>表前缀</label>
                <input type="text" name="db_prefix" value="hubbs_">
            </div>
            
            <h3 style="margin: 25px 0 20px; color: #333;">管理员设置</h3>
            
            <div class="form-group">
                <label>管理员账号</label>
                <input type="text" name="admin_user" value="admin" required>
            </div>
            
            <div class="form-group">
                <label>管理员密码</label>
                <input type="password" name="admin_pass" required placeholder="至少6位">
            </div>
            
            <div class="form-group">
                <label>管理员邮箱</label>
                <input type="email" name="admin_email" required>
            </div>
            
            <button type="submit" class="btn">开始安装</button>
        </form>
        
        <?php elseif ($step == 3): ?>
        <div class="success-icon">✓</div>
        <div class="success-text">
            <p>恭喜！HuBBS 安装成功！</p>
            <p style="margin-top: 10px; font-size: 14px; color: #666;">
                <a href="../index.php" style="color: #ff6b6b;">访问首页</a> | 
                <a href="../index.php?module=user&action=login" style="color: #ff6b6b;">登录后台</a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
