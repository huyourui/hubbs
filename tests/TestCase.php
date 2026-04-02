<?php
/**
 * HuBBS - 单元测试基类
 * 简单的测试框架
 */

class TestCase {
    
    protected $passed = 0;
    protected $failed = 0;
    protected $tests = [];
    
    /**
     * 运行所有测试
     */
    public function run() {
        $methods = get_class_methods($this);
        
        echo "\n" . get_class($this) . "\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                $this->runTest($method);
            }
        }
        
        $this->printSummary();
    }
    
    /**
     * 运行单个测试
     * @param string $method
     */
    protected function runTest($method) {
        try {
            $this->setUp();
            $this->$method();
            $this->tearDown();
            $this->passed++;
            $this->tests[] = ['name' => $method, 'status' => 'PASS'];
            echo "✓ {$method}\n";
        } catch (Exception $e) {
            $this->failed++;
            $this->tests[] = ['name' => $method, 'status' => 'FAIL', 'message' => $e->getMessage()];
            echo "✗ {$method}\n";
            echo "  Error: {$e->getMessage()}\n";
        }
    }
    
    /**
     * 测试前置操作
     */
    protected function setUp() {
        // 子类可重写
    }
    
    /**
     * 测试后置操作
     */
    protected function tearDown() {
        // 子类可重写
    }
    
    /**
     * 断言相等
     * @param mixed $expected
     * @param mixed $actual
     * @param string $message
     */
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected: " . var_export($expected, true) . ", Actual: " . var_export($actual, true);
            throw new Exception($msg);
        }
    }
    
    /**
     * 断言为真
     * @param mixed $condition
     * @param string $message
     */
    protected function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception($message ?: "Expected true, got false");
        }
    }
    
    /**
     * 断言为假
     * @param mixed $condition
     * @param string $message
     */
    protected function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception($message ?: "Expected false, got true");
        }
    }
    
    /**
     * 断言不为空
     * @param mixed $value
     * @param string $message
     */
    protected function assertNotEmpty($value, $message = '') {
        if (empty($value)) {
            throw new Exception($message ?: "Expected non-empty value");
        }
    }
    
    /**
     * 断言为空
     * @param mixed $value
     * @param string $message
     */
    protected function assertEmpty($value, $message = '') {
        if (!empty($value)) {
            throw new Exception($message ?: "Expected empty value");
        }
    }
    
    /**
     * 断言包含
     * @param string $needle
     * @param string $haystack
     * @param string $message
     */
    protected function assertContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "Expected '{$haystack}' to contain '{$needle}'");
        }
    }
    
    /**
     * 断言抛出异常
     * @param callable $callback
     * @param string $expectedException
     * @param string $message
     */
    protected function assertException($callback, $expectedException = 'Exception', $message = '') {
        try {
            $callback();
            throw new Exception($message ?: "Expected exception {$expectedException} was not thrown");
        } catch (Exception $e) {
            if (!($e instanceof $expectedException)) {
                throw new Exception($message ?: "Expected {$expectedException}, got " . get_class($e));
            }
        }
    }
    
    /**
     * 打印测试摘要
     */
    protected function printSummary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        $total = $this->passed + $this->failed;
        echo "Total: {$total}, Passed: {$this->passed}, Failed: {$this->failed}\n";
        
        if ($this->failed === 0) {
            echo "All tests passed! ✓\n";
        } else {
            echo "Some tests failed! ✗\n";
        }
    }
}
