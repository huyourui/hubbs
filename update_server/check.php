<?php
/**
 * HuBBS 更新服务器 - 版本检查接口
 * 
 * 返回格式：
 * {
 *   "success": true,
 *   "version": "1.6.0",
 *   "download_url": "https://your-server.com/updates/hubbs-1.6.0.zip",
 *   "release_notes": "更新内容...",
 *   "release_date": "2025-04-02",
 *   "force_update": false,
 *   "file_size": 1234567,
 *   "file_hash": "md5_hash_here"
 * }
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 当前最新版本信息
$latestVersion = [
    'success' => true,
    'version' => '1.6.0',
    'download_url' => 'https://update.bbs.huyourui.com/downloads/hubbs-1.6.0.zip',
    'release_notes' => "新增功能：\n- ORM系统支持\n- RESTful API\n- 路由系统优化\n- 单元测试框架",
    'release_date' => '2025-04-02',
    'force_update' => false,
    'file_size' => 0, // 单位：字节，实际部署时填写
    'file_hash' => '', // MD5哈希，用于校验
];

// 获取客户端当前版本
$clientVersion = $_GET['version'] ?? '0.0.0';
$clientVersion = preg_replace('/[^0-9.]/', '', $clientVersion);

// 比较版本
$hasUpdate = version_compare($latestVersion['version'], $clientVersion, '>');

$response = [
    'success' => true,
    'has_update' => $hasUpdate,
    'current_version' => $clientVersion,
    'latest_version' => $latestVersion['version'],
];

if ($hasUpdate) {
    $response['download_url'] = $latestVersion['download_url'];
    $response['release_notes'] = $latestVersion['release_notes'];
    $response['release_date'] = $latestVersion['release_date'];
    $response['force_update'] = $latestVersion['force_update'];
    $response['file_size'] = $latestVersion['file_size'];
    $response['file_hash'] = $latestVersion['file_hash'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
