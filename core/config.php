<?php
/**
 * HuBBS Forum - 核心配置文件
 * Version: 1.9.0
 * PHP 7.4+ | MySQL 5.7+
 */

if (!defined('HUBBS_ROOT')) {
    define('HUBBS_ROOT', dirname(__DIR__) . '/');
}

define('ROOT_DIR', dirname(__DIR__));

// 版本信息
define('HUBBS_VERSION', '1.9.0');
define('HUBBS_NAME', 'HuBBS');

// 数据库配置（安装后会被覆盖）
$db_config = [
    'host'     => 'localhost',
    'port'     => 3306,
    'user'     => 'root',
    'pass'     => '',
    'name'     => 'hubbs',
    'charset'  => 'utf8mb4',
    'prefix'   => 'hubbs_',
    'engine'   => 'InnoDB'
];

// 加载已安装的配置
$config_file = HUBBS_ROOT . 'data/config.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误报告（生产环境建议关闭）
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Session 配置
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');

// 安全相关 - 优先使用已安装配置中的盐值
define('HUBBS_SALT', isset($hubbs_salt) ? $hubbs_salt : 'hubbs_default_salt_change_this');

// 如果配置文件中存在加密的密码，确保盐值已加载
if (isset($db_config['pass_encrypted']) && $db_config['pass_encrypted'] && !isset($hubbs_salt)) {
    error_log('警告: 数据库密码已加密但无法获取盐值进行解密');
}

// 分页设置
define('POSTS_PER_PAGE', 20);
define('REPLIES_PER_PAGE', 20);

// 上传设置
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
