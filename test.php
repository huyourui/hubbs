<?php
/**
 * HuBBS - 系统测试脚本
 * 用于验证所有功能是否正常运行
 */

define('HUBBS_ROOT', __DIR__ . '/');

// 加载核心文件
require_once HUBBS_ROOT . 'core/config.php';
require_once HUBBS_ROOT . 'core/db.php';
require_once HUBBS_ROOT . 'core/functions.php';
require_once HUBBS_ROOT . 'core/Model.php';
require_once HUBBS_ROOT . 'core/Router.php';
require_once HUBBS_ROOT . 'core/migrate.php';

// 执行数据库迁移
Migrate::run();

// 自动加载模型类
function autoloadModelsTest($class) {
    $modelFile = HUBBS_ROOT . 'models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
    }
}
spl_autoload_register('autoloadModelsTest');

// 加载API组件
require_once HUBBS_ROOT . 'api/ApiResponse.php';
require_once HUBBS_ROOT . 'api/ApiAuth.php';

// 自动加载API控制器
function autoloadApiTest($class) {
    $apiFile = HUBBS_ROOT . 'api/' . $class . '.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
    }
}
spl_autoload_register('autoloadApiTest');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HuBBS 系统测试</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5; 
            padding: 20px;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header { 
            background: #333; 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 { font-size: 24px; }
        .header p { color: #aaa; margin-top: 5px; }
        .test-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 4px;
        }
        .test-item.success { background: #d4edda; }
        .test-item.error { background: #f8d7da; }
        .test-item.warning { background: #fff3cd; }
        .test-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .test-item.success .test-icon { background: #28a745; color: white; }
        .test-item.error .test-icon { background: #dc3545; color: white; }
        .test-item.warning .test-icon { background: #ffc107; color: #333; }
        .test-name { flex: 1; }
        .test-message { color: #666; font-size: 14px; }
        .summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary h2 { margin-bottom: 15px; }
        .stats { display: flex; gap: 20px; }
        .stat {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat.success { background: #d4edda; }
        .stat.error { background: #f8d7da; }
        .stat.warning { background: #fff3cd; }
        .stat-number { font-size: 32px; font-weight: bold; }
        .stat-label { color: #666; margin-top: 5px; }
        code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 14px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>HuBBS 系统测试报告</h1>
            <p>版本: <?php echo HUBBS_VERSION; ?> | 测试时间: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>

        <?php
        $tests = [];
        $passed = 0;
        $failed = 0;
        $warnings = 0;

        function addTest($name, $status, $message = '') {
            global $tests, $passed, $failed, $warnings;
            $tests[] = ['name' => $name, 'status' => $status, 'message' => $message];
            if ($status === 'success') $passed++;
            elseif ($status === 'error') $failed++;
            elseif ($status === 'warning') $warnings++;
        }

        // ==================== 核心功能测试 ====================
        echo '<div class="test-section">';
        echo '<h2>核心功能测试</h2>';

        // 测试配置文件
        if (defined('HUBBS_VERSION') && defined('HUBBS_SALT')) {
            addTest('配置文件加载', 'success', '版本: ' . HUBBS_VERSION);
        } else {
            addTest('配置文件加载', 'error', '常量未定义');
        }

        // 测试数据库连接
        try {
            $db = DB::getInstance();
            addTest('数据库连接', 'success', '连接正常');
        } catch (Exception $e) {
            addTest('数据库连接', 'error', $e->getMessage());
        }

        // 测试ORM系统
        if (class_exists('Model')) {
            addTest('ORM系统', 'success', 'Model类已加载');
        } else {
            addTest('ORM系统', 'error', 'Model类未找到');
        }

        // 测试路由系统
        if (class_exists('Router')) {
            addTest('路由系统', 'success', 'Router类已加载');
        } else {
            addTest('路由系统', 'error', 'Router类未找到');
        }

        // 测试自动加载
        if (class_exists('User') && class_exists('Post')) {
            addTest('模型自动加载', 'success', 'User, Post等模型已加载');
        } else {
            addTest('模型自动加载', 'warning', '部分模型未加载');
        }

        foreach (array_slice($tests, -5) as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<div class="test-icon">' . ($test['status'] === 'success' ? '✓' : ($test['status'] === 'error' ? '✗' : '!')) . '</div>';
            echo '<div class="test-name">' . htmlspecialchars($test['name']) . '</div>';
            if ($test['message']) {
                echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        // ==================== API功能测试 ====================
        echo '<div class="test-section">';
        echo '<h2>API功能测试</h2>';

        // 测试API响应类
        if (class_exists('ApiResponse')) {
            addTest('API响应类', 'success', 'ApiResponse已加载');
        } else {
            addTest('API响应类', 'error', 'ApiResponse未找到');
        }

        // 测试API认证类
        if (class_exists('ApiAuth')) {
            addTest('API认证类', 'success', 'ApiAuth已加载');
        } else {
            addTest('API认证类', 'error', 'ApiAuth未找到');
        }

        // 测试API控制器
        $apiControllers = ['PostApi', 'UserApi', 'ForumApi', 'NotificationApi'];
        foreach ($apiControllers as $controller) {
            if (class_exists($controller)) {
                addTest("API控制器: {$controller}", 'success', '已加载');
            } else {
                addTest("API控制器: {$controller}", 'error', '未找到');
            }
        }

        foreach (array_slice($tests, -5) as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<div class="test-icon">' . ($test['status'] === 'success' ? '✓' : ($test['status'] === 'error' ? '✗' : '!')) . '</div>';
            echo '<div class="test-name">' . htmlspecialchars($test['name']) . '</div>';
            if ($test['message']) {
                echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        // ==================== 数据库表测试 ====================
        echo '<div class="test-section">';
        echo '<h2>数据库表测试</h2>';

        $requiredTables = [
            'users', 'posts', 'replies', 'forums',
            'reply_comments', 'post_likes', 'post_favorites',
            'notifications', 'links', 'settings', 'migrations',
            'uploads', 'email_codes', 'remember_tokens'
        ];

        foreach ($requiredTables as $table) {
            try {
                $db->query("SELECT 1 FROM {$db->table($table)} LIMIT 1");
                addTest("数据表: {$table}", 'success', '存在');
            } catch (Exception $e) {
                addTest("数据表: {$table}", 'error', '不存在');
            }
        }

        foreach (array_slice($tests, -count($requiredTables)) as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<div class="test-icon">' . ($test['status'] === 'success' ? '✓' : ($test['status'] === 'error' ? '✗' : '!')) . '</div>';
            echo '<div class="test-name">' . htmlspecialchars($test['name']) . '</div>';
            if ($test['message']) {
                echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        // ==================== 目录权限测试 ====================
        echo '<div class="test-section">';
        echo '<h2>目录权限测试</h2>';

        $directories = [
            'uploads' => '上传目录',
            'data' => '数据目录',
            'templates/default' => '模板目录',
            'static' => '静态资源目录'
        ];

        foreach ($directories as $dir => $name) {
            $path = HUBBS_ROOT . $dir;
            if (is_dir($path)) {
                if (is_writable($path)) {
                    addTest($name, 'success', '可读写');
                } else {
                    addTest($name, 'warning', '不可写');
                }
            } else {
                addTest($name, 'error', '目录不存在');
            }
        }

        foreach (array_slice($tests, -count($directories)) as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<div class="test-icon">' . ($test['status'] === 'success' ? '✓' : ($test['status'] === 'error' ? '✗' : '!')) . '</div>';
            echo '<div class="test-name">' . htmlspecialchars($test['name']) . '</div>';
            if ($test['message']) {
                echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        // ==================== PHP扩展测试 ====================
        echo '<div class="test-section">';
        echo '<h2>PHP扩展测试</h2>';

        $extensions = [
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'gd' => 'GD图像处理',
            'openssl' => 'OpenSSL',
            'mbstring' => '多字节字符串',
            'json' => 'JSON',
            'session' => 'Session'
        ];

        foreach ($extensions as $ext => $name) {
            if (extension_loaded($ext)) {
                addTest($name, 'success', '已安装');
            } else {
                addTest($name, 'error', '未安装');
            }
        }

        foreach (array_slice($tests, -count($extensions)) as $test) {
            echo '<div class="test-item ' . $test['status'] . '">';
            echo '<div class="test-icon">' . ($test['status'] === 'success' ? '✓' : ($test['status'] === 'error' ? '✗' : '!')) . '</div>';
            echo '<div class="test-name">' . htmlspecialchars($test['name']) . '</div>';
            if ($test['message']) {
                echo '<div class="test-message">' . htmlspecialchars($test['message']) . '</div>';
            }
            echo '</div>';
        }
        echo '</div>';

        // ==================== 汇总 ====================
        echo '<div class="summary">';
        echo '<h2>测试汇总</h2>';
        echo '<div class="stats">';
        echo '<div class="stat success"><div class="stat-number">' . $passed . '</div><div class="stat-label">通过</div></div>';
        echo '<div class="stat error"><div class="stat-number">' . $failed . '</div><div class="stat-label">失败</div></div>';
        echo '<div class="stat warning"><div class="stat-number">' . $warnings . '</div><div class="stat-label">警告</div></div>';
        echo '</div>';
        echo '</div>';

        // API测试示例
        echo '<div class="test-section">';
        echo '<h2>API测试示例</h2>';
        echo '<p>以下是可用的API端点示例：</p>';
        echo '<pre>';
        echo "GET  /api/posts           - 获取帖子列表\n";
        echo "GET  /api/posts/{id}      - 获取帖子详情\n";
        echo "POST /api/posts           - 创建帖子\n";
        echo "GET  /api/forums          - 获取板块列表\n";
        echo "GET  /api/user            - 获取当前用户信息\n";
        echo "GET  /api/notifications   - 获取通知列表\n";
        echo '</pre>';
        echo '</div>';
        ?>

    </div>
</body>
</html>
