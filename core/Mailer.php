<?php
/**
 * HuBBS - 邮件发送类
 * 支持多种邮件服务：SMTP、Sendmail、Mail()
 */

class Mailer {
    private static $instance = null;
    private $config = [];
    private $lastError = '';
    
    // 邮件发送方式
    const METHOD_SMTP = 'smtp';
    const METHOD_SENDMAIL = 'sendmail';
    const METHOD_MAIL = 'mail';
    
    // 主流邮件服务商配置
    private $providers = [
        'qq' => [
            'host' => 'smtp.qq.com',
            'port' => 465,
            'encryption' => 'ssl',
        ],
        '163' => [
            'host' => 'smtp.163.com',
            'port' => 465,
            'encryption' => 'ssl',
        ],
        '126' => [
            'host' => 'smtp.126.com',
            'port' => 465,
            'encryption' => 'ssl',
        ],
        'gmail' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'encryption' => 'tls',
        ],
        'outlook' => [
            'host' => 'smtp.office365.com',
            'port' => 587,
            'encryption' => 'tls',
        ],
        'yahoo' => [
            'host' => 'smtp.mail.yahoo.com',
            'port' => 587,
            'encryption' => 'tls',
        ],
    ];
    
    private function __construct() {
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 加载邮件配置
     */
    private function loadConfig() {
        $this->config = [
            'enabled' => Settings::get('mail_enabled', '0'),
            'method' => Settings::get('mail_method', 'smtp'),
            'provider' => Settings::get('mail_provider', ''),
            'host' => Settings::get('mail_host', ''),
            'port' => Settings::get('mail_port', '587'),
            'encryption' => Settings::get('mail_encryption', 'tls'),
            'username' => Settings::get('mail_username', ''),
            'password' => Settings::get('mail_password', ''),
            'from_address' => Settings::get('mail_from_address', ''),
            'from_name' => Settings::get('mail_from_name', Settings::get('site_title', 'HuBBS')),
        ];
    }
    
    /**
     * 重新加载配置（用于配置更新后）
     */
    public function reloadConfig() {
        $this->loadConfig();
    }
    
    /**
     * 检查邮件功能是否启用
     */
    public function isEnabled() {
        return $this->config['enabled'] === '1';
    }
    
    /**
     * 发送邮件
     * @param string $to 收件人邮箱
     * @param string $subject 主题
     * @param string $body 内容（支持HTML）
     * @param array $attachments 附件列表
     * @return bool
     */
    public function send($to, $subject, $body, $attachments = []) {
        if (!$this->isEnabled()) {
            $this->lastError = '邮件功能未启用';
            return false;
        }
        
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = '收件人邮箱格式不正确';
            return false;
        }
        
        $method = $this->config['method'];
        
        switch ($method) {
            case self::METHOD_SMTP:
                return $this->sendViaSmtp($to, $subject, $body, $attachments);
            case self::METHOD_SENDMAIL:
                return $this->sendViaSendmail($to, $subject, $body, $attachments);
            case self::METHOD_MAIL:
                return $this->sendViaMail($to, $subject, $body, $attachments);
            default:
                $this->lastError = '未知的邮件发送方式';
                return false;
        }
    }
    
    /**
     * 通过 SMTP 发送邮件
     */
    private function sendViaSmtp($to, $subject, $body, $attachments) {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $encryption = $this->config['encryption'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        
        if (empty($host) || empty($username) || empty($password)) {
            $this->lastError = 'SMTP 配置不完整';
            return false;
        }
        
        try {
            // 建立连接
            $timeout = 30;
            if ($encryption === 'ssl') {
                $socket = fsockopen('ssl://' . $host, $port, $errno, $errstr, $timeout);
            } else {
                $socket = fsockopen($host, $port, $errno, $errstr, $timeout);
            }
            
            if (!$socket) {
                $this->lastError = "连接 SMTP 服务器失败: $errstr ($errno)";
                return false;
            }
            
            stream_set_timeout($socket, $timeout);
            
            // 读取服务器响应
            $this->smtpResponse($socket);
            
            // EHLO
            fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
            $this->smtpResponse($socket);
            
            // STARTTLS (如果需要)
            if ($encryption === 'tls') {
                fputs($socket, "STARTTLS\r\n");
                $this->smtpResponse($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fputs($socket, "EHLO " . $_SERVER['HTTP_HOST'] . "\r\n");
                $this->smtpResponse($socket);
            }
            
            // 认证
            fputs($socket, "AUTH LOGIN\r\n");
            $this->smtpResponse($socket);
            fputs($socket, base64_encode($username) . "\r\n");
            $this->smtpResponse($socket);
            fputs($socket, base64_encode($password) . "\r\n");
            $this->smtpResponse($socket);
            
            // 发送邮件
            $from = $this->config['from_address'] ?: $username;
            fputs($socket, "MAIL FROM:<$from>\r\n");
            $this->smtpResponse($socket);
            fputs($socket, "RCPT TO:<$to>\r\n");
            $this->smtpResponse($socket);
            fputs($socket, "DATA\r\n");
            $this->smtpResponse($socket);
            
            // 构建邮件内容
            $boundary = md5(time());
            $headers = $this->buildHeaders($to, $boundary, !empty($attachments));
            $message = $headers . "\r\n" . $this->buildBody($body, $boundary, $attachments);
            
            fputs($socket, $message . "\r\n.\r\n");
            $this->smtpResponse($socket);
            
            // 关闭连接
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return true;
            
        } catch (Exception $e) {
            $this->lastError = 'SMTP 发送失败: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * 读取 SMTP 响应
     */
    private function smtpResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }
    
    /**
     * 通过 Sendmail 发送
     */
    private function sendViaSendmail($to, $subject, $body, $attachments) {
        $sendmail_path = ini_get('sendmail_path');
        if (empty($sendmail_path)) {
            $this->lastError = 'Sendmail 未配置';
            return false;
        }
        
        $from = $this->config['from_address'];
        $fromName = $this->config['from_name'];
        
        $headers = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        return mail($to, $subject, $body, $headers);
    }
    
    /**
     * 通过 PHP mail() 发送
     */
    private function sendViaMail($to, $subject, $body, $attachments) {
        return $this->sendViaSendmail($to, $subject, $body, $attachments);
    }
    
    /**
     * 构建邮件头
     */
    private function buildHeaders($to, $boundary, $hasAttachments) {
        $from = $this->config['from_address'];
        $fromName = $this->config['from_name'];
        $subject = '=?UTF-8?B?' . base64_encode($this->getLastSubject()) . '?=';
        
        $headers = "To: $to\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        
        if ($hasAttachments) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        return $headers;
    }
    
    private $lastSubject = '';
    
    private function getLastSubject() {
        return $this->lastSubject;
    }
    
    /**
     * 构建邮件正文
     */
    private function buildBody($body, $boundary, $attachments) {
        if (empty($attachments)) {
            return $body;
        }
        
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= base64_encode($body) . "\r\n";
        
        foreach ($attachments as $attachment) {
            $filename = basename($attachment);
            $fileContent = file_get_contents($attachment);
            
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"$filename\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
            $message .= base64_encode($fileContent) . "\r\n";
        }
        
        $message .= "--$boundary--\r\n";
        
        return $message;
    }
    
    /**
     * 发送注册验证码邮件
     */
    public function sendRegisterCode($to, $code) {
        $siteTitle = Settings::get('site_title', 'HuBBS');
        $subject = "【{$siteTitle}】注册验证码";
        $this->lastSubject = $subject;
        
        $body = $this->getEmailTemplate('register_code', [
            'site_title' => $siteTitle,
            'code' => $code,
            'expire_minutes' => 30,
        ]);
        
        return $this->send($to, $subject, $body);
    }
    
    /**
     * 发送互动通知邮件
     */
    public function sendNotification($to, $type, $data) {
        $siteTitle = Settings::get('site_title', 'HuBBS');
        
        $templates = [
            'reply' => [
                'subject' => "【{$siteTitle}】有人回复了你的帖子",
                'template' => 'reply_notification',
            ],
            'mention' => [
                'subject' => "【{$siteTitle}】有人在帖子中提到了你",
                'template' => 'mention_notification',
            ],
            'follow' => [
                'subject' => "【{$siteTitle}】有人关注了你",
                'template' => 'follow_notification',
            ],
        ];
        
        if (!isset($templates[$type])) {
            $this->lastError = '未知的通知类型';
            return false;
        }
        
        $template = $templates[$type];
        $subject = $template['subject'];
        $this->lastSubject = $subject;
        
        $data['site_title'] = $siteTitle;
        $body = $this->getEmailTemplate($template['template'], $data);
        
        return $this->send($to, $subject, $body);
    }
    
    /**
     * 获取邮件模板
     */
    private function getEmailTemplate($template, $data) {
        $templates = [
            'register_code' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #ff6b6b, #ff8e8e); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e8e8e8; border-top: none; border-radius: 0 0 8px 8px; }
        .code { font-size: 32px; font-weight: bold; color: #ff6b6b; text-align: center; padding: 20px; background: #fff2f0; border-radius: 8px; margin: 20px 0; letter-spacing: 8px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$data['site_title']}</h1>
        </div>
        <div class="content">
            <h2>注册验证码</h2>
            <p>您好！</p>
            <p>您正在注册 {$data['site_title']} 账号，请使用以下验证码完成注册：</p>
            <div class="code">{$data['code']}</div>
            <p>此验证码将在 {$data['expire_minutes']} 分钟后失效，请勿泄露给他人。</p>
            <p>如非本人操作，请忽略此邮件。</p>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿回复</p>
            <p>&copy; {$data['site_title']}</p>
        </div>
    </div>
</body>
</html>
HTML,
            'reply_notification' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e8e8e8; border-top: none; border-radius: 0 0 8px 8px; }
        .post-title { background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 15px 0; font-weight: 500; }
        .btn { display: inline-block; background: #ff6b6b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$data['site_title']}</h1>
        </div>
        <div class="content">
            <h2>新回复通知</h2>
            <p>您好，{$data['to_username']}！</p>
            <p><strong>{$data['from_username']}</strong> 回复了您的帖子：</p>
            <div class="post-title">{$data['post_title']}</div>
            <p>回复内容：</p>
            <blockquote style="border-left: 3px solid #ff6b6b; padding-left: 15px; color: #666;">{$data['reply_content']}</blockquote>
            <a href="{$data['post_url']}" class="btn">查看回复</a>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿回复</p>
            <p>&copy; {$data['site_title']}</p>
        </div>
    </div>
</body>
</html>
HTML,
            'mention_notification' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #4ecdc4, #6ee7de); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e8e8e8; border-top: none; border-radius: 0 0 8px 8px; }
        .btn { display: inline-block; background: #ff6b6b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$data['site_title']}</h1>
        </div>
        <div class="content">
            <h2>@提及通知</h2>
            <p>您好，{$data['to_username']}！</p>
            <p><strong>{$data['from_username']}</strong> 在帖子中提到了您：</p>
            <div style="background: #f5f5f5; padding: 15px; border-radius: 6px; margin: 15px 0;">{$data['post_title']}</div>
            <a href="{$data['post_url']}" class="btn">查看帖子</a>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿回复</p>
            <p>&copy; {$data['site_title']}</p>
        </div>
    </div>
</body>
</html>
HTML,
            'follow_notification' => <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #fff; padding: 30px; border: 1px solid #e8e8e8; border-top: none; border-radius: 0 0 8px 8px; }
        .btn { display: inline-block; background: #ff6b6b; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin-top: 20px; }
        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$data['site_title']}</h1>
        </div>
        <div class="content">
            <h2>新关注通知</h2>
            <p>您好，{$data['to_username']}！</p>
            <p><strong>{$data['from_username']}</strong> 关注了你 🎉</p>
            <a href="{$data['profile_url']}" class="btn">查看粉丝</a>
        </div>
        <div class="footer">
            <p>此邮件由系统自动发送，请勿回复</p>
            <p>&copy; {$data['site_title']}</p>
        </div>
    </div>
</body>
</html>
HTML,
        ];
        
        return $templates[$template] ?? '';
    }
    
    /**
     * 获取最后错误信息
     */
    public function getLastError() {
        return $this->lastError;
    }
    
    /**
     * 获取支持的邮件服务商列表
     */
    public function getProviders() {
        return $this->providers;
    }
}
