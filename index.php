<?php
/**
 * HuBBS - 轻量级开源论坛系统
 * 
 * 入口文件 - 加载核心组件并路由请求
 */

define('HUBBS_ROOT', __DIR__ . '/');

// 检查是否已安装
if (!file_exists(HUBBS_ROOT . 'install.lock')) {
    // 构建安装页面URL（处理IP访问和子目录部署）
    $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    
    // 获取当前脚本所在目录
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $basePath = dirname($scriptName);
    if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
        $basePath = '';
    }
    
    // 构建完整的安装URL
    $installUrl = $scheme . '://' . $host . $basePath . '/install/';
    header('Location: ' . $installUrl);
    exit;
}

// ==================== 加载核心文件 ====================
require_once HUBBS_ROOT . 'core/config.php';
require_once HUBBS_ROOT . 'core/db.php';
require_once HUBBS_ROOT . 'core/functions.php';
require_once HUBBS_ROOT . 'core/Utils.php';
require_once HUBBS_ROOT . 'core/View.php';
require_once HUBBS_ROOT . 'core/Pagination.php';
require_once HUBBS_ROOT . 'core/Auth.php';
require_once HUBBS_ROOT . 'core/migrate.php';
require_once HUBBS_ROOT . 'core/Settings.php';
require_once HUBBS_ROOT . 'core/notification.php';
require_once HUBBS_ROOT . 'core/Upload.php';
require_once HUBBS_ROOT . 'core/Updater.php';

// ==================== 加载ORM系统 ====================
require_once HUBBS_ROOT . 'core/Model.php';

// 自动加载模型类
function autoloadModels($class) {
    $modelFile = HUBBS_ROOT . 'models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
    }
}
spl_autoload_register('autoloadModels');

// ==================== 加载路由系统 ====================
require_once HUBBS_ROOT . 'core/Router.php';

// ==================== 加载API组件 ====================
require_once HUBBS_ROOT . 'api/ApiResponse.php';
require_once HUBBS_ROOT . 'api/ApiAuth.php';

// 自动加载API控制器
function autoloadApi($class) {
    $apiFile = HUBBS_ROOT . 'api/' . $class . '.php';
    if (file_exists($apiFile)) {
        require_once $apiFile;
    }
}
spl_autoload_register('autoloadApi');

// ==================== 加载服务层 ====================
function autoloadServices($class) {
    $serviceFile = HUBBS_ROOT . 'services/' . $class . '.php';
    if (file_exists($serviceFile)) {
        require_once $serviceFile;
    }
}
spl_autoload_register('autoloadServices');

// ==================== 执行数据库迁移（无感） ====================
Migrate::run();

// ==================== 初始化认证 ====================
Auth::init();

// ==================== 路由处理 ====================
// 加载API路由配置
require_once HUBBS_ROOT . 'api/routes.php';

// 执行路由调度
Router::dispatch();
