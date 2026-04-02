<?php
/**
 * HuBBS - 测试运行器
 * 运行所有测试
 */

define('HUBBS_ROOT', dirname(__DIR__) . '/');

// 加载核心文件
require_once HUBBS_ROOT . 'core/config.php';
require_once HUBBS_ROOT . 'core/functions.php';

// 加载测试基类
require_once __DIR__ . '/TestCase.php';

echo "HuBBS Test Runner\n";
echo str_repeat("=", 50) . "\n";

// 查找所有测试文件
$testFiles = glob(__DIR__ . '/*Test.php');

if (empty($testFiles)) {
    echo "No tests found.\n";
    exit(0);
}

$totalPassed = 0;
$totalFailed = 0;

foreach ($testFiles as $file) {
    require_once $file;
    $className = basename($file, '.php');
    
    if (class_exists($className)) {
        $test = new $className();
        $test->run();
        
        // 获取测试结果
        $reflection = new ReflectionClass($test);
        $passedProperty = $reflection->getProperty('passed');
        $passedProperty->setAccessible(true);
        $failedProperty = $reflection->getProperty('failed');
        $failedProperty->setAccessible(true);
        
        $totalPassed += $passedProperty->getValue($test);
        $totalFailed += $failedProperty->getValue($test);
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "FINAL RESULTS\n";
echo str_repeat("=", 50) . "\n";
$total = $totalPassed + $totalFailed;
echo "Total Tests: {$total}\n";
echo "Passed: {$totalPassed} ✓\n";
echo "Failed: {$totalFailed} " . ($totalFailed > 0 ? "✗" : "") . "\n";

exit($totalFailed > 0 ? 1 : 0);
