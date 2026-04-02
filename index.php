<?php
/**
 * HuBBS - 轻量级开源论坛系统
 */

define('HUBBS_ROOT', __DIR__ . '/');

// 检查是否已安装
if (!file_exists(HUBBS_ROOT . 'install.lock')) {
    header('Location: install/');
    exit;
}

// 加载核心文件
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

// 执行数据库迁移（无感）
Migrate::run();

// 初始化认证
Auth::init();

// 路由处理
$module = $_GET['module'] ?? 'post';
$action = $_GET['action'] ?? 'list';

// 加载模块
$moduleFile = HUBBS_ROOT . 'modules/' . $module . '.php';
if (file_exists($moduleFile)) {
    require_once $moduleFile;
    $className = ucfirst($module) . 'Module';
    if (class_exists($className)) {
        $moduleObj = new $className();
        $result = $moduleObj->handle();
        
        if (is_array($result) && isset($result['template'])) {
            // 渲染模板
            $templateData = $result['data'] ?? [];
            extract($templateData);
            $templateFile = HUBBS_ROOT . 'templates/default/' . $result['template'] . '.php';
            if (file_exists($templateFile)) {
                include $templateFile;
            } else {
                die('模板不存在: ' . $result['template']);
            }
        }
    }
} else {
    // 默认首页
    require_once HUBBS_ROOT . 'modules/post.php';
    $moduleObj = new PostModule();
    $result = $moduleObj->handle();
    if (is_array($result) && isset($result['template'])) {
        $templateData = $result['data'] ?? [];
        extract($templateData);
        include HUBBS_ROOT . 'templates/default/' . $result['template'] . '.php';
    }
}
