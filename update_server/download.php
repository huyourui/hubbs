<?php
/**
 * HuBBS 更新服务器 - 文件下载接口
 * 
 * 支持断点续传和限速
 */

// 更新包存放目录
$updateDir = __DIR__ . '/downloads/';

// 获取请求的文件
$version = $_GET['version'] ?? '';
$version = preg_replace('/[^0-9.]/', '', $version);

if (empty($version)) {
    http_response_code(400);
    echo 'Version parameter is required';
    exit;
}

$fileName = "hubbs-{$version}.zip";
$filePath = $updateDir . $fileName;

// 检查文件是否存在
if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'Update package not found';
    exit;
}

$fileSize = filesize($filePath);
$fileHash = md5_file($filePath);

// 设置响应头
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('ETag: "' . $fileHash . '"');
header('Cache-Control: public, max-age=86400');

// 支持断点续传
$range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

if ($range) {
    // 解析Range头
    if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        exit;
    }
    
    $start = intval($matches[1]);
    $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;
    
    if ($start >= $fileSize || $end >= $fileSize || $start > $end) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        exit;
    }
    
    $length = $end - $start + 1;
    
    header('HTTP/1.1 206 Partial Content');
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $fileSize);
    header('Content-Length: ' . $length);
    
    $fp = fopen($filePath, 'rb');
    fseek($fp, $start);
    
    $remaining = $length;
    while ($remaining > 0 && !feof($fp)) {
        $chunkSize = min(8192, $remaining);
        echo fread($fp, $chunkSize);
        $remaining -= $chunkSize;
        flush();
    }
    
    fclose($fp);
} else {
    // 普通下载
    readfile($filePath);
}
