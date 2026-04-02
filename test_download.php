<?php
/**
 * 测试Gitee下载链接
 */

// 测试不同的下载URL格式
$urls = [
    'API zipball' => 'https://gitee.com/api/v5/repos/youruihu/hubbs/zipball/v1.6.0',
    'Archive zip' => 'https://gitee.com/youruihu/hubbs/repository/archive/v1.6.0.zip',
    'Archive' => 'https://gitee.com/youruihu/hubbs/repository/archive/v1.6.0',
    'Raw zipball' => 'https://gitee.com/youruihu/hubbs/zipball/v1.6.0',
];

echo "<h2>测试Gitee下载链接</h2>";
echo "<pre>";

foreach ($urls as $name => $url) {
    echo "\n=== 测试: {$name} ===\n";
    echo "URL: {$url}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: zh-CN,zh;q=0.9',
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    echo "Content-Type: {$contentType}\n";
    echo "Error: {$error}\n";
    
    if ($response) {
        $isZip = (strlen($response) >= 4 && substr($response, 0, 4) === "PK\x03\x04");
        echo "Is ZIP: " . ($isZip ? 'Yes' : 'No') . "\n";
        echo "Size: " . strlen($response) . " bytes\n";
        echo "Preview: " . substr($response, 0, 200) . "\n";
    }
}

echo "</pre>";
