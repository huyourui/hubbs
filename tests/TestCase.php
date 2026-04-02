<?php
/**
 * HuBBS - 测试基类
 * 提供测试基础功能
 */

class TestCase {
    
    // 测试结果
    protected $passed = 0;
    protected $failed = 0;
    protected $errors = [];
    
    /**
     * 设置测试环境
     */
    public function setUp() {
        // 子类可覆盖
    }
    
    /**
     * 清理测试环境
     */
    public function tearDown() {
        // 子类可覆盖
    }
    
    /**
     * 断言相等
     */
    protected function assertEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertEquals',
            'message' => $message ?: "Expected: " . var_export($expected, true) . ", Got: " . var_export($actual, true),
            'expected' => $expected,
            'actual' => $actual
        ];
        return false;
    }
    
    /**
     * 断言为真
     */
    protected function assertTrue($condition, $message = '') {
        if ($condition === true) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertTrue',
            'message' => $message ?: "Expected true, got false"
        ];
        return false;
    }
    
    /**
     * 断言为假
     */
    protected function assertFalse($condition, $message = '') {
        if ($condition === false) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertFalse',
            'message' => $message ?: "Expected false, got true"
        ];
        return false;
    }
    
    /**
     * 断言不为空
     */
    protected function assertNotEmpty($value, $message = '') {
        if (!empty($value)) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertNotEmpty',
            'message' => $message ?: "Expected non-empty value"
        ];
        return false;
    }
    
    /**
     * 断言为空
     */
    protected function assertEmpty($value, $message = '') {
        if (empty($value)) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertEmpty',
            'message' => $message ?: "Expected empty value"
        ];
        return false;
    }
    
    /**
     * 断言包含
     */
    protected function assertContains($needle, $haystack, $message = '') {
        if (strpos($haystack, $needle) !== false || (is_array($haystack) && in_array($needle, $haystack))) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertContains',
            'message' => $message ?: "Expected to contain: {$needle}"
        ];
        return false;
    }
    
    /**
     * 断言大于
     */
    protected function assertGreaterThan($expected, $actual, $message = '') {
        if ($actual > $expected) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertGreaterThan',
            'message' => $message ?: "Expected greater than {$expected}, got {$actual}"
        ];
        return false;
    }
    
    /**
     * 断言小于
     */
    protected function assertLessThan($expected, $actual, $message = '') {
        if ($actual < $expected) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertLessThan',
            'message' => $message ?: "Expected less than {$expected}, got {$actual}"
        ];
        return false;
    }
    
    /**
     * 断言数组有键
     */
    protected function assertArrayHasKey($key, $array, $message = '') {
        if (is_array($array) && array_key_exists($key, $array)) {
            $this->passed++;
            return true;
        }
        
        $this->failed++;
        $this->errors[] = [
            'type' => 'assertArrayHasKey',
            'message' => $message ?: "Expected array to have key: {$key}"
        ];
        return false;
    }
    
    /**
     * 断言抛出异常
     */
    protected function assertException($callback, $expectedException = 'Exception', $message = '') {
        try {
            call_user_func($callback);
            $this->failed++;
            $this->errors[] = [
                'type' => 'assertException',
                'message' => $message ?: "Expected exception {$expectedException} was not thrown"
            ];
            return false;
        } catch (Exception $e) {
            if ($e instanceof $expectedException) {
                $this->passed++;
                return true;
            }
            
            $this->failed++;
            $this->errors[] = [
                'type' => 'assertException',
                'message' => $message ?: "Expected {$expectedException}, got " . get_class($e)
            ];
            return false;
        }
    }
    
    /**
     * 获取测试结果
     */
    public function getResults() {
        return [
            'passed' => $this->passed,
            'failed' => $this->failed,
            'total' => $this->passed + $this->failed,
            'errors' => $this->errors
        ];
    }
    
    /**
     * 运行所有测试方法
     */
    public function run() {
        $this->setUp();
        
        $methods = get_class_methods($this);
        foreach ($methods as $method) {
            if (strpos($method, 'test') === 0) {
                try {
                    $this->$method();
                } catch (Exception $e) {
                    $this->failed++;
                    $this->errors[] = [
                        'type' => 'exception',
                        'method' => $method,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ];
                }
            }
        }
        
        $this->tearDown();
        
        return $this->getResults();
    }
}
