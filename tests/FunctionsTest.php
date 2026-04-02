<?php
/**
 * HuBBS - 函数测试
 */

class FunctionsTest extends TestCase {
    
    public function testValidateUsername() {
        // 有效用户名
        $this->assertTrue(validate_username('testuser'));
        $this->assertTrue(validate_username('TestUser'));
        $this->assertTrue(validate_username('test_user'));
        $this->assertTrue(validate_username('test123'));
        $this->assertTrue(validate_username('测试用户'));
        
        // 无效用户名
        $this->assertFalse(validate_username('ab')); // 太短
        $this->assertFalse(validate_username('a')); // 太短
        $this->assertFalse(validate_username('test@user')); // 包含特殊字符
        $this->assertFalse(validate_username('test user')); // 包含空格
        $this->assertFalse(validate_username('')); // 空
    }
    
    public function testValidateEmail() {
        // 有效邮箱
        $this->assertTrue(validate_email('test@example.com'));
        $this->assertTrue(validate_email('user.name@domain.co.uk'));
        $this->assertTrue(validate_email('user+tag@example.com'));
        
        // 无效邮箱
        $this->assertFalse(validate_email('invalid'));
        $this->assertFalse(validate_email('@example.com'));
        $this->assertFalse(validate_email('test@'));
        $this->assertFalse(validate_email(''));
    }
    
    public function testCsrfToken() {
        // 生成token
        $token = csrf_token();
        $this->assertNotEmpty($token);
        $this->assertEquals(32, strlen($token)); // MD5长度
        
        // 验证token
        $this->assertTrue(verify_csrf($token));
        
        // 无效token
        $this->assertFalse(verify_csrf('invalid_token'));
    }
    
    public function testBaseUrl() {
        $url = base_url();
        $this->assertNotEmpty($url);
        $this->assertStringEndsWith('/', $url);
        
        $urlWithPath = base_url('test/path');
        $this->assertContains('test/path', $urlWithPath);
    }
    
    public function testFormatTime() {
        $now = time();
        
        // 刚刚
        $this->assertEquals('刚刚', format_time($now));
        
        // 分钟前
        $this->assertEquals('5分钟前', format_time($now - 300));
        
        // 小时前
        $this->assertEquals('2小时前', format_time($now - 7200));
        
        // 昨天
        $this->assertEquals('昨天', format_time($now - 86400));
    }
    
    public function testHtmlSpecialChars() {
        $input = '<script>alert("xss")</script>';
        $output = h($input);
        
        $this->assertNotContains('<script>', $output);
        $this->assertContains('&lt;script&gt;', $output);
    }
    
    public function testTruncateContent() {
        $longText = str_repeat('a', 200);
        $truncated = truncate_content($longText, 50);
        
        $this->assertLessThanOrEqual(50, mb_strlen($truncated));
    }
    
    public function testGenerateRandomString() {
        $str1 = generate_random_string(10);
        $str2 = generate_random_string(10);
        
        $this->assertEquals(10, strlen($str1));
        $this->assertEquals(10, strlen($str2));
        $this->assertNotEquals($str1, $str2); // 随机性
    }
}
