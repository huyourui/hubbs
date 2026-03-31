<?php
/**
 * HuBBS - 文件上传接口
 */

// 关闭错误显示，避免输出 HTML 错误信息
ini_set('display_errors', 0);
error_reporting(E_ALL);

define('HUBBS_ROOT', __DIR__ . '/');
define('ROOT_DIR', __DIR__);

require_once HUBBS_ROOT . 'core/config.php';
require_once HUBBS_ROOT . 'core/db.php';
require_once HUBBS_ROOT . 'core/functions.php';
require_once HUBBS_ROOT . 'core/auth.php';
require_once HUBBS_ROOT . 'core/settings.php';
require_once HUBBS_ROOT . 'core/upload.php';

// 初始化认证
Auth::init();

// 检查用户是否登录
if (Auth::guest()) {
    json_response(['success' => false, 'message' => '请先登录']);
}

$action = $_GET['action'] ?? '';
$userId = Auth::id();

switch ($action) {
    case 'image':
        // 上传图片
        if (!isset($_FILES['file'])) {
            json_response(['success' => false, 'message' => '请选择要上传的图片']);
        }
        
        $result = Upload::image($_FILES['file'], $userId);
        json_response($result);
        break;
        
    case 'attachment':
        // 上传附件
        if (!isset($_FILES['file'])) {
            json_response(['success' => false, 'message' => '请选择要上传的附件']);
        }
        
        $result = Upload::attachment($_FILES['file'], $userId);
        json_response($result);
        break;
        
    case 'delete':
        // 删除文件
        $uploadId = intval($_POST['id'] ?? 0);
        if ($uploadId <= 0) {
            json_response(['success' => false, 'message' => '参数错误']);
        }
        
        $result = Upload::delete($uploadId, $userId);
        json_response($result);
        break;
        
    case 'list':
        // 获取用户上传列表
        $fileType = $_GET['type'] ?? null;
        $uploads = Upload::getUserUploads($userId, null, $fileType);
        
        // 格式化数据
        $data = array_map(function($upload) {
            return [
                'id' => $upload['id'],
                'name' => $upload['file_name'],
                'url' => $upload['file_path'],
                'type' => $upload['file_type'],
                'size' => $upload['file_size'],
                'size_formatted' => formatSize($upload['file_size']),
                'created_at' => $upload['created_at']
            ];
        }, $uploads);
        
        json_response(['success' => true, 'data' => $data]);
        break;
        
    default:
        json_response(['success' => false, 'message' => '未知操作']);
}

/**
 * 格式化文件大小
 */
function formatSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * JSON 响应
 */
function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
