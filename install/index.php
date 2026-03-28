<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
session_start();

define('HUBBS_VERSION', '1.4.1');
define('HUBBS_NAME', 'HuBBS');

/* 安装程序根目录绝对路径 */
$installRootPath = dirname(__DIR__);

/* 检查是否已安装：install.lock 文件存在 */
if (file_exists($installRootPath . '/install.lock')) {
    die('HuBBS 已经安装。如需重新安装，请删除 install.lock 文件。');
}

/* 
 * 注意：删除 install.lock 后，安装程序会从第一步开始
 * 在步骤2提交数据库配置时，会自动删除旧表并重新创建（覆盖安装）
 */

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        
        if (empty($dbName) || empty($dbUser)) {
            $errors[] = '数据库名称和用户名不能为空';
        } else {
            try {
                $dsn = "mysql:host=$dbHost;charset=utf8mb4";
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                /* 创建或使用数据库 */
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$dbName`");
                
                /* 重装时删除所有旧表，确保干净安装 */
                $tables = [
                    'post_images', 'likes', 'invite_codes', 'point_logs', 
                    'user_levels', 'point_rules', 'attachments', 'announcements',
                    'links', 'notifications', 'favorites', 'settings',
                    'comments', 'posts', 'categories', 'users'
                ];
                
                /* 禁用外键检查以便删除有外键关联的表 */
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                foreach ($tables as $table) {
                    try {
                        $pdo->exec("DROP TABLE IF EXISTS `$table`");
                    } catch (PDOException $e) {
                        /* 忽略删除错误 */
                    }
                }
                
                /* 重新启用外键检查 */
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                /* 执行数据库结构SQL */
                $sql = file_get_contents($installRootPath . '/core/database.sql');
                $pdo->exec($sql);
                
                $configContent = <<<'PHP'
<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * 数据库配置文件
 * 此文件由安装程序自动生成，请勿手动修改
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */

/* 数据库主机地址 */
define('DB_HOST', '{DB_HOST}');

/* 数据库名称 */
define('DB_NAME', '{DB_NAME}');

/* 数据库用户名 */
define('DB_USER', '{DB_USER}');

/* 数据库密码 */
define('DB_PASS', '{DB_PASS}');

/* 数据库字符集 */
define('DB_CHARSET', 'utf8mb4');
PHP;
                
                $configContent = str_replace(
                    ['{DB_HOST}', '{DB_NAME}', '{DB_USER}', '{DB_PASS}'],
                    [$dbHost, $dbName, $dbUser, $dbPass],
                    $configContent
                );
                
                file_put_contents($installRootPath . '/config.php', $configContent);
                
                $step = 3;
            } catch (PDOException $e) {
                $errors[] = '数据库连接失败: ' . $e->getMessage();
            }
        }
    }
    
    if ($step === 3 && isset($_POST['create_admin'])) {
        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass = $_POST['admin_pass'] ?? '';
        
        if (strlen($adminUser) < 3) {
            $errors[] = '用户名至少需要3个字符';
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = '请输入有效的邮箱地址';
        }
        if (strlen($adminPass) < 6) {
            $errors[] = '密码至少需要6个字符';
        }
        
        if (empty($errors)) {
            /* 引入配置文件和引导文件以获取数据库连接 */
            require_once $installRootPath . '/config.php';
            require_once $installRootPath . '/core/bootstrap.php';
            
            /* 执行数据库迁移，确保所有字段都存在 */
            initDatabase($pdo);
            
            $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE username = 'admin'");
            if ($stmt->execute([$adminUser, $adminEmail, $hashedPass])) {
                /* 生成安装锁定文件 */
                $lockContent = date('Y-m-d H:i:s');
                $lockFile = $installRootPath . '/install.lock';
                
                /* 尝试写入锁定文件 */
                $written = @file_put_contents($lockFile, $lockContent);
                
                /* 如果写入失败，尝试修改权限后重试 */
                if ($written === false) {
                    @chmod($installRootPath, 0777);
                    $written = @file_put_contents($lockFile, $lockContent);
                }
                
                if ($written !== false) {
                    $step = 4;
                } else {
                    $errors[] = '无法创建安装锁定文件，请检查目录权限';
                }
            } else {
                $errors[] = '创建管理员账号失败';
            }
        }
    }
}

$rootPathWeb = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$rootPathWeb = preg_replace('#/install$#', '', $rootPathWeb);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装 HuBBS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .install-container { background: #fff; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-width: 600px; width: 100%; }
        .logo { text-align: center; font-size: 36px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .version { text-align: center; color: #666; margin-bottom: 30px; }
        .steps { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .step-item { flex: 1; text-align: center; padding: 10px; position: relative; }
        .step-item::after { content: ''; position: absolute; top: 50%; right: -50%; width: 100%; height: 2px; background: #ddd; z-index: 0; }
        .step-item:last-child::after { display: none; }
        .step-number { width: 30px; height: 30px; border-radius: 50%; background: #ddd; color: #666; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; position: relative; z-index: 1; }
        .step-item.active .step-number { background: #667eea; color: #fff; }
        .step-item.completed .step-number { background: #27ae60; color: #fff; }
        .step-item.completed::after { background: #27ae60; }
        .step-label { font-size: 12px; color: #666; margin-top: 5px; }
        .form-title { font-size: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn { display: block; width: 100%; padding: 12px; background: #667eea; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5a6fd6; }
        .errors { background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .errors ul { list-style: none; }
        .success-box { background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; text-align: center; }
        .success-box h3 { margin-bottom: 10px; }
        .success-box a { display: inline-block; margin-top: 15px; padding: 10px 30px; background: #27ae60; color: #fff; border-radius: 5px; text-decoration: none; }
        .success-box a:hover { background: #219a52; }
        .requirements { list-style: none; }
        .requirements li { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
        .requirements li:last-child { border-bottom: none; }
        .status-ok { color: #27ae60; }
        .status-error { color: #e74c3c; }
        .info-text { color: #666; font-size: 14px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo"><?php echo HUBBS_NAME; ?></div>
        <div class="version">Version <?php echo HUBBS_VERSION; ?></div>
        
        <div class="steps">
            <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-number">1</div>
                <div class="step-label">环境检测</div>
            </div>
            <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-number">2</div>
                <div class="step-label">数据库配置</div>
            </div>
            <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                <div class="step-number">3</div>
                <div class="step-label">管理员设置</div>
            </div>
            <div class="step-item <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-number">4</div>
                <div class="step-label">安装完成</div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <h2 class="form-title">环境检测</h2>
            <ul class="requirements">
                <li>
                    <span>PHP 版本 >= 7.4</span>
                    <span class="<?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'status-ok' : 'status-error'; ?>">
                        <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? '✓ ' . PHP_VERSION : '✗ ' . PHP_VERSION; ?>
                    </span>
                </li>
                <li>
                    <span>PDO MySQL 扩展</span>
                    <span class="<?php echo extension_loaded('pdo_mysql') ? 'status-ok' : 'status-error'; ?>">
                        <?php echo extension_loaded('pdo_mysql') ? '✓ 已安装' : '✗ 未安装'; ?>
                    </span>
                </li>
                <li>
                    <span>config.php 可写</span>
                    <span class="<?php echo is_writable($installRootPath) ? 'status-ok' : 'status-error'; ?>">
                        <?php echo is_writable($installRootPath) ? '✓ 可写' : '✗ 不可写'; ?>
                    </span>
                </li>
                <li>
                    <span>上传目录</span>
                    <span class="<?php echo is_writable($installRootPath) || is_dir($installRootPath . '/uploads') ? 'status-ok' : 'status-error'; ?>">
                        <?php echo is_writable($installRootPath) || is_dir($installRootPath . '/uploads') ? '✓ 可写' : '✗ 需要创建'; ?>
                    </span>
                </li>
            </ul>
            <a href="?step=2" class="btn" style="margin-top: 20px; text-align: center; text-decoration: none;">下一步</a>

        <?php elseif ($step === 2): ?>
            <h2 class="form-title">数据库配置</h2>
            <p class="info-text">请输入您的 MySQL 数据库连接信息</p>
            <form method="POST" action="?step=2">
                <div class="form-group">
                    <label for="db_host">数据库主机</label>
                    <input type="text" id="db_host" name="db_host" value="localhost" required>
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名称</label>
                    <input type="text" id="db_name" name="db_name" placeholder="hubbs" required>
                </div>
                <div class="form-group">
                    <label for="db_user">数据库用户名</label>
                    <input type="text" id="db_user" name="db_user" placeholder="root" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">数据库密码</label>
                    <input type="password" id="db_pass" name="db_pass" placeholder="留空表示无密码">
                </div>
                <button type="submit" class="btn">测试连接并安装</button>
            </form>

        <?php elseif ($step === 3): ?>
            <h2 class="form-title">创建管理员账号</h2>
            <p class="info-text">请设置您的管理员账号信息</p>
            <form method="POST" action="?step=3">
                <div class="form-group">
                    <label for="admin_user">管理员用户名</label>
                    <input type="text" id="admin_user" name="admin_user" placeholder="admin" required>
                </div>
                <div class="form-group">
                    <label for="admin_email">管理员邮箱</label>
                    <input type="email" id="admin_email" name="admin_email" placeholder="admin@example.com" required>
                </div>
                <div class="form-group">
                    <label for="admin_pass">管理员密码</label>
                    <input type="password" id="admin_pass" name="admin_pass" placeholder="至少6个字符" required>
                </div>
                <button type="submit" name="create_admin" class="btn">创建管理员</button>
            </form>

        <?php elseif ($step === 4): ?>
            <div class="success-box">
                <h3>🎉 安装成功！</h3>
                <p>HuBBS 论坛已成功安装，您现在可以开始使用了。</p>
                <p style="margin-top: 10px; font-size: 14px; color: #666;">
                    请使用刚才设置的管理员账号登录
                </p>
                <a href="<?php echo $rootPathWeb; ?>/index.php">访问论坛首页</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
