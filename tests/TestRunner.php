<?php
/**
 * HuBBS - 测试运行器
 * 自动发现并运行所有测试
 */

class TestRunner {
    
    private $testDir;
    private $results = [];
    private $totalPassed = 0;
    private $totalFailed = 0;
    
    public function __construct($testDir = __DIR__) {
        $this->testDir = $testDir;
    }
    
    /**
     * 发现所有测试文件
     */
    public function discoverTests() {
        $tests = [];
        $files = glob($this->testDir . '/*Test.php');
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $tests[] = [
                'file' => $file,
                'class' => $className
            ];
        }
        
        return $tests;
    }
    
    /**
     * 运行单个测试类
     */
    public function runTest($testClass) {
        if (!class_exists($testClass)) {
            return [
                'class' => $testClass,
                'error' => 'Class not found'
            ];
        }
        
        $test = new $testClass();
        if (!($test instanceof TestCase)) {
            return [
                'class' => $testClass,
                'error' => 'Must extend TestCase'
            ];
        }
        
        $result = $test->run();
        $result['class'] = $testClass;
        
        $this->totalPassed += $result['passed'];
        $this->totalFailed += $result['failed'];
        
        return $result;
    }
    
    /**
     * 运行所有测试
     */
    public function runAll() {
        // 加载测试基类
        require_once $this->testDir . '/TestCase.php';
        
        $tests = $this->discoverTests();
        
        foreach ($tests as $test) {
            require_once $test['file'];
            $this->results[] = $this->runTest($test['class']);
        }
        
        return $this->results;
    }
    
    /**
     * 获取汇总结果
     */
    public function getSummary() {
        return [
            'total_tests' => count($this->results),
            'total_assertions' => $this->totalPassed + $this->totalFailed,
            'passed' => $this->totalPassed,
            'failed' => $this->totalFailed,
            'success_rate' => $this->totalPassed + $this->totalFailed > 0 
                ? round($this->totalPassed / ($this->totalPassed + $this->totalFailed) * 100, 2) 
                : 0
        ];
    }
    
    /**
     * 输出测试结果（CLI格式）
     */
    public function outputCli() {
        echo "\n";
        echo "========================================\n";
        echo "         HuBBS 测试报告\n";
        echo "========================================\n\n";
        
        foreach ($this->results as $result) {
            echo "测试类: {$result['class']}\n";
            echo "----------------------------------------\n";
            
            if (isset($result['error'])) {
                echo "错误: {$result['error']}\n";
            } else {
                echo "通过: {$result['passed']}\n";
                echo "失败: {$result['failed']}\n";
                
                if (!empty($result['errors'])) {
                    echo "\n错误详情:\n";
                    foreach ($result['errors'] as $i => $error) {
                        echo "  " . ($i + 1) . ". [{$error['type']}] {$error['message']}\n";
                    }
                }
            }
            
            echo "\n";
        }
        
        $summary = $this->getSummary();
        echo "========================================\n";
        echo "              汇总\n";
        echo "========================================\n";
        echo "测试类数: {$summary['total_tests']}\n";
        echo "断言总数: {$summary['total_assertions']}\n";
        echo "通过: {$summary['passed']}\n";
        echo "失败: {$summary['failed']}\n";
        echo "成功率: {$summary['success_rate']}%\n";
        echo "========================================\n";
    }
    
    /**
     * 输出测试结果（HTML格式）
     */
    public function outputHtml() {
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>HuBBS 测试报告</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; }
                .header { background: #333; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
                .summary { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .test-class { background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #28a745; }
                .error { color: #dc3545; }
                .warning { color: #ffc107; }
                .stats { display: flex; gap: 20px; margin-top: 10px; }
                .stat { padding: 10px 20px; border-radius: 5px; }
                .stat.passed { background: #d4edda; }
                .stat.failed { background: #f8d7da; }
                .error-list { margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                .error-item { padding: 10px; margin-bottom: 10px; background: #fff3cd; border-left: 4px solid #ffc107; }
                pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>HuBBS 测试报告</h1>
                </div>
                
                <div class="summary">
                    <h2>汇总</h2>
                    <?php $summary = $this->getSummary(); ?>
                    <div class="stats">
                        <div class="stat">测试类: <?php echo $summary['total_tests']; ?></div>
                        <div class="stat">断言总数: <?php echo $summary['total_assertions']; ?></div>
                        <div class="stat passed">通过: <?php echo $summary['passed']; ?></div>
                        <div class="stat failed">失败: <?php echo $summary['failed']; ?></div>
                        <div class="stat">成功率: <?php echo $summary['success_rate']; ?>%</div>
                    </div>
                </div>
                
                <?php foreach ($this->results as $result): ?>
                <div class="test-class">
                    <h3><?php echo htmlspecialchars($result['class']); ?></h3>
                    
                    <?php if (isset($result['error'])): ?>
                        <p class="error">错误: <?php echo htmlspecialchars($result['error']); ?></p>
                    <?php else: ?>
                        <div class="stats">
                            <div class="stat passed">通过: <?php echo $result['passed']; ?></div>
                            <div class="stat failed">失败: <?php echo $result['failed']; ?></div>
                        </div>
                        
                        <?php if (!empty($result['errors'])): ?>
                        <div class="error-list">
                            <h4>错误详情</h4>
                            <?php foreach ($result['errors'] as $i => $error): ?>
                            <div class="error-item">
                                <strong><?php echo ($i + 1); ?>. [<?php echo htmlspecialchars($error['type']); ?>]</strong>
                                <p><?php echo htmlspecialchars($error['message']); ?></p>
                                <?php if (isset($error['file'])): ?>
                                <small><?php echo htmlspecialchars($error['file']); ?>:<?php echo $error['line']; ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </body>
        </html>
        <?php
    }
}

// 如果直接访问此文件，运行测试
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    define('HUBBS_ROOT', dirname(__DIR__) . '/');
    
    require_once HUBBS_ROOT . 'core/config.php';
    require_once HUBBS_ROOT . 'core/db.php';
    require_once HUBBS_ROOT . 'core/functions.php';
    require_once HUBBS_ROOT . 'core/Model.php';
    
    $runner = new TestRunner();
    $runner->runAll();
    $runner->outputCli();
    
    exit($runner->getSummary()['failed'] > 0 ? 1 : 0);
}
