<?php
/**
 * HuBBS - Utils 类测试
 */

require_once HUBBS_ROOT . 'core/Utils.php';

class UtilsTest extends TestCase {
    
    public function testHtmlEscape() {
        $input = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        $this->assertEquals($expected, Utils::h($input));
    }
    
    public function testHtmlEscapeNull() {
        $this->assertEquals('', Utils::h(null));
    }
    
    public function testValidateEmail() {
        $this->assertTrue(Utils::validateEmail('test@example.com'));
        $this->assertTrue(Utils::validateEmail('user.name@domain.co.uk'));
    }
    
    public function testValidateEmailInvalid() {
        $this->assertFalse(Utils::validateEmail('invalid-email'));
        $this->assertFalse(Utils::validateEmail('@example.com'));
        $this->assertFalse(Utils::validateEmail('test@'));
    }
    
    public function testValidateUsername() {
        $this->assertTrue(Utils::validateUsername('admin'));
        $this->assertTrue(Utils::validateUsername('user_123'));
        $this->assertTrue(Utils::validateUsername('用户名'));
    }
    
    public function testValidateUsernameInvalid() {
        $this->assertFalse(Utils::validateUsername('a')); // 太短
        $this->assertFalse(Utils::validateUsername('')); // 空
        $this->assertFalse(Utils::validateUsername('user@name')); // 非法字符
    }
    
    public function testFormatSize() {
        $this->assertEquals('100 B', Utils::formatSize(100));
        $this->assertEquals('1.95 KB', Utils::formatSize(2000));
        $this->assertEquals('1.95 MB', Utils::formatSize(2000000));
    }
    
    public function testTimeAgo() {
        // 刚刚
        $this->assertEquals('刚刚', Utils::timeAgo(date('Y-m-d H:i:s')));
        
        // 分钟前
        $fiveMinutesAgo = date('Y-m-d H:i:s', time() - 300);
        $this->assertContains('分钟前', Utils::timeAgo($fiveMinutesAgo));
        
        // 小时前
        $twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
        $this->assertContains('小时前', Utils::timeAgo($twoHoursAgo));
    }
}
