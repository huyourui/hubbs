<?php
/**
 * HuBBS - Model测试
 */

class ModelTest extends TestCase {
    
    public function testModelExists() {
        $this->assertTrue(class_exists('Model'));
    }
    
    public function testModelHasRequiredMethods() {
        $methods = [
            'query', 'find', 'findOrFail', 'all', 'create',
            'where', 'orderBy', 'limit', 'get', 'first',
            'count', 'exists', 'save', 'delete'
        ];
        
        foreach ($methods as $method) {
            $this->assertTrue(method_exists('Model', $method), "Method {$method} should exist");
        }
    }
    
    public function testModelTableNameInference() {
        // 测试自动推断表名
        $reflection = new ReflectionClass('Model');
        $method = $reflection->getMethod('getTable');
        $method->setAccessible(true);
        
        // 创建测试模型
        $testModel = new class extends Model {
            protected static $table = '';
        };
        
        $tableName = $method->invoke(null);
        $this->assertNotEmpty($tableName);
    }
    
    public function testModelAttributes() {
        // 创建测试模型
        $model = new class extends Model {
            protected static $fillable = ['name', 'email'];
        };
        
        $model->fill(['name' => 'Test', 'email' => 'test@example.com']);
        
        $this->assertEquals('Test', $model->name);
        $this->assertEquals('test@example.com', $model->email);
    }
}
