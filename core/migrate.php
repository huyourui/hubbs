<?php
/**
 * HuBBS - 数据库迁移类
 * 无感迁移，自动在访问时执行
 */

class Migrate {
    private static $version = 21;
    
    public static function run() {
        $db = DB::getInstance();
        
        // 检查迁移记录表是否存在
        if (!self::tableExists($db, 'migrations')) {
            self::createMigrationsTable($db);
        }
        
        // 获取当前版本
        $currentVersion = self::getCurrentVersion($db);
        
        // 执行待执行的迁移
        for ($i = $currentVersion + 1; $i <= self::$version; $i++) {
            $method = 'migrate' . $i;
            if (method_exists(self::class, $method)) {
                try {
                    self::$method($db);
                    self::recordVersion($db, $i);
                } catch (Exception $e) {
                    error_log("Migration {$i} failed: " . $e->getMessage());
                }
            }
        }
    }
    
    private static function tableExists($db, $table) {
        try {
            $db->query("SELECT 1 FROM {$db->table($table)} LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private static function columnExists($db, $table, $column) {
        try {
            $db->query("SELECT {$column} FROM {$db->table($table)} LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private static function createMigrationsTable($db) {
        $sql = "CREATE TABLE IF NOT EXISTS {$db->table('migrations')} (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            version INT UNSIGNED NOT NULL,
            executed_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uk_version (version)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $db->query($sql);
    }
    
    private static function getCurrentVersion($db) {
        try {
            $result = $db->fetch("SELECT MAX(version) as v FROM {$db->table('migrations')}");
            return (int)($result['v'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private static function recordVersion($db, $version) {
        $db->insert('migrations', [
            'version' => $version,
            'executed_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    // ========== 迁移脚本 ==========
    
    /**
     * 迁移1：添加用户管理员字段
     */
    private static function migrate1($db) {
        // 检查字段是否已存在
        if (!self::columnExists($db, 'users', 'is_admin')) {
            $sql = "ALTER TABLE {$db->table('users')} 
                    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否管理员' AFTER avatar";
            $db->query($sql);
            
            // 将第一个用户设为管理员
            $db->query("UPDATE {$db->table('users')} SET is_admin = 1 ORDER BY id ASC LIMIT 1");
        }
    }
    
    /**
     * 迁移2：创建设置表
     */
    private static function migrate2($db) {
        if (!self::tableExists($db, 'settings')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('settings')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                setting_key VARCHAR(50) NOT NULL COMMENT '设置键',
                setting_value TEXT COMMENT '设置值',
                description VARCHAR(255) DEFAULT NULL COMMENT '描述',
                PRIMARY KEY (id),
                UNIQUE KEY uk_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表'";
            $db->query($sql);

            // 插入默认设置
            $defaultSettings = [
                ['site_title', 'HuBBS', '网站标题'],
                ['site_subtitle', '开源论坛程序', '网站副标题'],
                ['site_keywords', 'HuBBS,论坛,开源,PHP', '网站关键词'],
                ['site_description', 'HuBBS是一款轻量级开源论坛程序', '网站描述'],
                ['enable_register', '1', '是否开放注册'],
            ];

            $stmt = $db->getPdo()->prepare("INSERT INTO {$db->table('settings')} (setting_key, setting_value, description) VALUES (?, ?, ?)");
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
        }
    }
    
    /**
     * 迁移3：修改板块表支持二级分类
     */
    private static function migrate3($db) {
        // 添加parent_id字段
        if (!self::columnExists($db, 'forums', 'parent_id')) {
            $sql = "ALTER TABLE {$db->table('forums')} 
                    ADD COLUMN parent_id INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '父分类ID' AFTER id";
            $db->query($sql);
        }
        
        // 添加is_force_forum设置
        $exists = $db->fetch("SELECT id FROM {$db->table('settings')} WHERE setting_key = 'is_force_forum' LIMIT 1");
        if (!$exists) {
            $db->insert('settings', [
                'setting_key' => 'is_force_forum',
                'setting_value' => '1',
                'description' => '是否强制选择分类'
            ]);
        }
    }
    
    /**
     * 迁移4：添加用户状态字段和帖子状态字段，以及新设置项
     */
    private static function migrate4($db) {
        // 添加用户状态字段
        if (!self::columnExists($db, 'users', 'status')) {
            $sql = "ALTER TABLE {$db->table('users')} 
                    ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1 COMMENT '用户状态：0-封禁，1-正常' AFTER is_admin";
            $db->query($sql);
        }
        
        // 添加帖子状态字段
        if (!self::columnExists($db, 'posts', 'is_locked')) {
            $sql = "ALTER TABLE {$db->table('posts')} 
                    ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否锁定' AFTER is_essence";
            $db->query($sql);
        }
        
        // 创建友情链接表
        if (!self::tableExists($db, 'links')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('links')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL COMMENT '链接名称',
                url VARCHAR(255) NOT NULL COMMENT '链接地址',
                sort_order SMALLINT UNSIGNED DEFAULT 0 COMMENT '排序',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_sort (sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='友情链接表'";
            $db->query($sql);
        }
        
        // 创建楼中楼回复表
        if (!self::tableExists($db, 'reply_comments')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('reply_comments')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                reply_id INT UNSIGNED NOT NULL COMMENT '一级回复ID',
                user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
                to_user_id INT UNSIGNED DEFAULT 0 COMMENT '回复给哪个用户',
                content TEXT NOT NULL COMMENT '内容',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_reply (reply_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='楼中楼回复'";
            $db->query($sql);
        }
        
        // 添加新设置项
        $newSettings = [
            'register_email_suffix' => '',
            'register_banned_words' => 'admin,root,system,管理员',
            'username_min_length' => '2',
            'username_max_length' => '20',
            'post_min_length' => '5',
            'post_max_length' => '10000',
            'reply_min_length' => '2',
            'reply_max_length' => '5000',
            'posts_per_page' => '20',
            'replies_per_page' => '20',
        ];
        
        foreach ($newSettings as $key => $value) {
            $exists = $db->fetch("SELECT id FROM {$db->table('settings')} WHERE setting_key = ? LIMIT 1", [$key]);
            if (!$exists) {
                $db->insert('settings', [
                    'setting_key' => $key,
                    'setting_value' => $value
                ]);
            }
        }
    }
    
    /**
     * 迁移5：确保楼中楼回复表存在
     */
    private static function migrate5($db) {
        // 创建楼中楼回复表
        if (!self::tableExists($db, 'reply_comments')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('reply_comments')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                reply_id INT UNSIGNED NOT NULL COMMENT '一级回复ID',
                user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
                to_user_id INT UNSIGNED DEFAULT 0 COMMENT '回复给哪个用户',
                content TEXT NOT NULL COMMENT '内容',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_reply (reply_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='楼中楼回复'";
            $db->query($sql);
        }
    }
    
    /**
     * 迁移6：确保板块图标字段支持emoji
     */
    private static function migrate6($db) {
        // 检查并修改 forums 表的 icon 字段为 utf8mb4
        if (self::tableExists($db, 'forums')) {
            // 修改表字符集
            $db->query("ALTER TABLE {$db->table('forums')} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // 确保 icon 字段存在且为 varchar 类型
            if (self::columnExists($db, 'forums', 'icon')) {
                $db->query("ALTER TABLE {$db->table('forums')} MODIFY COLUMN icon VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '图标'");
            }
        }
    }
    
    /**
     * 迁移7：创建邮件验证码表
     */
    private static function migrate7($db) {
        // 创建验证码表
        if (!self::tableExists($db, 'email_codes')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('email_codes')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(100) NOT NULL COMMENT '邮箱地址',
                code VARCHAR(10) NOT NULL COMMENT '验证码',
                type VARCHAR(20) NOT NULL DEFAULT 'register' COMMENT '验证码类型：register-注册, reset-重置密码',
                expires_at DATETIME NOT NULL COMMENT '过期时间',
                used TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已使用',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_email (email),
                KEY idx_code (code),
                KEY idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='邮件验证码表'";
            $db->query($sql);
        }
        
        // 添加邮件相关设置
        $mailSettings = [
            'mail_enabled' => '0',
            'mail_method' => 'smtp',
            'mail_provider' => '',
            'mail_host' => '',
            'mail_port' => '587',
            'mail_encryption' => 'tls',
            'mail_username' => '',
            'mail_password' => '',
            'mail_from_address' => '',
            'mail_from_name' => '',
            'mail_verify_register' => '0',
        ];
        
        foreach ($mailSettings as $key => $value) {
            $exists = $db->fetch("SELECT id FROM {$db->table('settings')} WHERE setting_key = ? LIMIT 1", [$key]);
            if (!$exists) {
                $db->insert('settings', [
                    'setting_key' => $key,
                    'setting_value' => $value
                ]);
            }
        }
    }
    
    /**
     * 迁移8：创建点赞和收藏表
     */
    private static function migrate8($db) {
        // 创建点赞表
        if (!self::tableExists($db, 'post_likes')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('post_likes')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id INT UNSIGNED NOT NULL COMMENT '帖子ID',
                user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uk_post_user (post_id, user_id),
                KEY idx_post (post_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帖子点赞表'";
            $db->query($sql);
        }
        
        // 创建收藏表
        if (!self::tableExists($db, 'post_favorites')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('post_favorites')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id INT UNSIGNED NOT NULL COMMENT '帖子ID',
                user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uk_post_user (post_id, user_id),
                KEY idx_post (post_id),
                KEY idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='帖子收藏表'";
            $db->query($sql);
        }
        
        // 添加点赞数和收藏数字段到posts表
        if (self::tableExists($db, 'posts')) {
            if (!self::columnExists($db, 'posts', 'likes')) {
                $db->query("ALTER TABLE {$db->table('posts')} ADD COLUMN likes INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '点赞数' AFTER replies");
            }
            if (!self::columnExists($db, 'posts', 'favorites')) {
                $db->query("ALTER TABLE {$db->table('posts')} ADD COLUMN favorites INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '收藏数' AFTER likes");
            }
        }
    }
    
    /**
     * 迁移9：创建消息通知表
     */
    private static function migrate9($db) {
        // 创建消息表
        if (!self::tableExists($db, 'notifications')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('notifications')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL COMMENT '接收消息的用户ID',
                sender_id INT UNSIGNED DEFAULT 0 COMMENT '发送者用户ID，0表示系统',
                type VARCHAR(30) NOT NULL COMMENT '消息类型：reply_post-回复帖子, reply_comment-回复评论, like_post-点赞帖子, favorite_post-收藏帖子, system-系统消息',
                title VARCHAR(200) DEFAULT NULL COMMENT '消息标题',
                content TEXT COMMENT '消息内容',
                target_id INT UNSIGNED DEFAULT 0 COMMENT '关联目标ID（如帖子ID）',
                target_type VARCHAR(20) DEFAULT NULL COMMENT '关联目标类型：post, reply, comment',
                is_read TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否已读：0-未读，1-已读',
                created_at DATETIME NOT NULL,
                read_at DATETIME DEFAULT NULL COMMENT '阅读时间',
                PRIMARY KEY (id),
                KEY idx_user (user_id),
                KEY idx_user_read (user_id, is_read),
                KEY idx_type (type),
                KEY idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='消息通知表'";
            $db->query($sql);
        }
        
        // 添加用户未读消息数字段
        if (self::tableExists($db, 'users')) {
            if (!self::columnExists($db, 'users', 'unread_count')) {
                $db->query("ALTER TABLE {$db->table('users')} ADD COLUMN unread_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '未读消息数' AFTER status");
            }
        }
    }
    
    /**
     * 迁移10：创建友情链接表
     */
    private static function migrate10($db) {
        // 创建友情链接表
        if (!self::tableExists($db, 'links')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('links')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL COMMENT '链接名称',
                url VARCHAR(500) NOT NULL COMMENT '链接地址',
                description VARCHAR(255) DEFAULT NULL COMMENT '链接描述',
                sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序顺序，数字越小越靠前',
                is_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否显示：0-隐藏，1-显示',
                created_at DATETIME NOT NULL,
                updated_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_sort (sort_order),
                KEY idx_visible (is_visible)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='友情链接表'";
            $db->query($sql);
        } else {
            // 表已存在，检查并添加缺失的列
            // 注意：按顺序添加，后面的列依赖前面的列
            if (!self::columnExists($db, 'links', 'description')) {
                $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT '链接描述' AFTER url");
            }
            if (!self::columnExists($db, 'links', 'sort_order')) {
                $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序顺序，数字越小越靠前' AFTER description");
            }
            if (!self::columnExists($db, 'links', 'is_visible')) {
                $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否显示：0-隐藏，1-显示' AFTER sort_order");
            }
            if (!self::columnExists($db, 'links', 'updated_at')) {
                $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at");
            }
        }
    }
    
    /**
     * 迁移11：修复友情链接表缺失的列
     */
    private static function migrate11($db) {
        // 确保友情链接表有所有需要的列
        if (self::tableExists($db, 'links')) {
            // 先添加 description，因为 sort_order 依赖于它
            if (!self::columnExists($db, 'links', 'description')) {
                try {
                    $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT '链接描述' AFTER url");
                } catch (Exception $e) {
                    error_log("Failed to add description column: " . $e->getMessage());
                }
            }
            if (!self::columnExists($db, 'links', 'sort_order')) {
                try {
                    $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序顺序' AFTER description");
                } catch (Exception $e) {
                    error_log("Failed to add sort_order column: " . $e->getMessage());
                }
            }
            if (!self::columnExists($db, 'links', 'is_visible')) {
                try {
                    $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN is_visible TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否显示：0-隐藏，1-显示' AFTER sort_order");
                } catch (Exception $e) {
                    error_log("Failed to add is_visible column: " . $e->getMessage());
                }
            }
            if (!self::columnExists($db, 'links', 'updated_at')) {
                try {
                    $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN updated_at DATETIME DEFAULT NULL AFTER created_at");
                } catch (Exception $e) {
                    error_log("Failed to add updated_at column: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * 迁移12：强制修复友情链接表缺失的 description 列
     */
    private static function migrate12($db) {
        // 强制添加 description 列（如果不存在）
        if (self::tableExists($db, 'links')) {
            // 使用 DESCRIBE 检查列是否存在
            try {
                $columns = $db->fetchAll("DESCRIBE {$db->table('links')}");
                $hasDescription = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === 'description') {
                        $hasDescription = true;
                        break;
                    }
                }
                if (!$hasDescription) {
                    $db->query("ALTER TABLE {$db->table('links')} ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT '链接描述' AFTER url");
                }
            } catch (Exception $e) {
                error_log("Migration 12 failed: " . $e->getMessage());
            }
        }
    }

    /**
     * 迁移13：添加发帖和评论间隔时间设置
     */
    private static function migrate13($db) {
        // 添加默认的发帖和评论间隔时间设置
        if (self::tableExists($db, 'settings')) {
            // 检查并添加发帖间隔时间设置
            $postInterval = $db->fetch("SELECT * FROM {$db->table('settings')} WHERE setting_key = 'post_interval'");
            if (!$postInterval) {
                $db->insert('settings', [
                    'setting_key' => 'post_interval',
                    'setting_value' => '0',
                    'description' => '发帖间隔时间（秒），0表示不限制'
                ]);
            }

            // 检查并添加评论间隔时间设置
            $replyInterval = $db->fetch("SELECT * FROM {$db->table('settings')} WHERE setting_key = 'reply_interval'");
            if (!$replyInterval) {
                $db->insert('settings', [
                    'setting_key' => 'reply_interval',
                    'setting_value' => '0',
                    'description' => '评论间隔时间（秒），0表示不限制'
                ]);
            }
        }
    }

    /**
     * 迁移14：添加文件上传功能和设置
     */
    private static function migrate14($db) {
        // 创建上传文件表
        if (!self::tableExists($db, 'uploads')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('uploads')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL COMMENT '上传用户ID',
                file_name VARCHAR(255) NOT NULL COMMENT '原始文件名',
                file_path VARCHAR(500) NOT NULL COMMENT '文件存储路径',
                file_type VARCHAR(50) NOT NULL COMMENT '文件类型：image-图片，attachment-附件',
                file_size INT UNSIGNED NOT NULL COMMENT '文件大小（字节）',
                mime_type VARCHAR(100) DEFAULT NULL COMMENT 'MIME类型',
                post_id INT UNSIGNED DEFAULT NULL COMMENT '关联的帖子ID',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_user (user_id),
                KEY idx_post (post_id),
                KEY idx_type (file_type),
                KEY idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='上传文件表'";
            $db->query($sql);
        }

        // 添加上传相关设置
        if (self::tableExists($db, 'settings')) {
            $uploadSettings = [
                ['upload_image_exts', 'jpg,jpeg,png,gif,webp', '允许上传的图片后缀，多个用逗号分隔'],
                ['upload_image_max_size', '5242880', '单张图片最大大小（字节），默认5MB'],
                ['upload_image_max_count', '10', '单篇帖子最多上传图片数量'],
                ['upload_attachment_exts', 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,md', '允许上传的附件后缀，多个用逗号分隔'],
                ['upload_attachment_max_size', '10485760', '单个附件最大大小（字节），默认10MB'],
                ['upload_attachment_max_count', '5', '单篇帖子最多上传附件数量'],
            ];

            foreach ($uploadSettings as $setting) {
                $exists = $db->fetch("SELECT * FROM {$db->table('settings')} WHERE setting_key = ?", [$setting[0]]);
                if (!$exists) {
                    $db->insert('settings', [
                        'setting_key' => $setting[0],
                        'setting_value' => $setting[1],
                        'description' => $setting[2]
                    ]);
                }
            }
        }
    }

    /**
     * 迁移15：添加用户软删除字段
     */
    private static function migrate15($db) {
        // 添加软删除字段
        if (self::tableExists($db, 'users')) {
            if (!self::columnExists($db, 'users', 'deleted_at')) {
                $db->query("ALTER TABLE {$db->table('users')} ADD COLUMN deleted_at DATETIME DEFAULT NULL COMMENT '删除时间，NULL表示未删除' AFTER unread_count");
            }
        }
    }

    /**
     * 迁移16：添加用户原始邮箱字段（用于软删除后恢复）
     */
    private static function migrate16($db) {
        if (self::tableExists($db, 'users')) {
            // 添加原始邮箱字段
            if (!self::columnExists($db, 'users', 'original_email')) {
                $db->query("ALTER TABLE {$db->table('users')} ADD COLUMN original_email VARCHAR(100) DEFAULT NULL COMMENT '注销前的原始邮箱' AFTER email");
            }
        }
    }

    /**
     * 迁移17：修复 settings 表字段名（将 key/value 改为 setting_key/setting_value）
     */
    private static function migrate17($db) {
        if (self::tableExists($db, 'settings')) {
            // 检查是否存在旧字段名 `key`
            if (self::columnExists($db, 'settings', 'key')) {
                // 重命名 key 为 setting_key
                $db->query("ALTER TABLE {$db->table('settings')} CHANGE `key` setting_key VARCHAR(50) NOT NULL COMMENT '设置键'");
            }
            // 检查是否存在旧字段名 `value`
            if (self::columnExists($db, 'settings', 'value')) {
                // 重命名 value 为 setting_value
                $db->query("ALTER TABLE {$db->table('settings')} CHANGE `value` setting_value TEXT COMMENT '设置值'");
            }
            // 检查是否存在 description 字段
            if (!self::columnExists($db, 'settings', 'description')) {
                $db->query("ALTER TABLE {$db->table('settings')} ADD COLUMN description VARCHAR(255) DEFAULT NULL COMMENT '描述' AFTER setting_value");
            }
        }
    }

    /**
     * 迁移18：添加帖子和回复的编辑次数字段
     */
    private static function migrate18($db) {
        // 为 posts 表添加编辑次数字段
        if (self::tableExists($db, 'posts')) {
            if (!self::columnExists($db, 'posts', 'edit_count')) {
                $db->query("ALTER TABLE {$db->table('posts')} ADD COLUMN edit_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '编辑次数' AFTER updated_at");
            }
            if (!self::columnExists($db, 'posts', 'last_edit_at')) {
                $db->query("ALTER TABLE {$db->table('posts')} ADD COLUMN last_edit_at DATETIME DEFAULT NULL COMMENT '最后编辑时间' AFTER edit_count");
            }
        }

        // 为 replies 表添加编辑次数字段
        if (self::tableExists($db, 'replies')) {
            if (!self::columnExists($db, 'replies', 'edit_count')) {
                $db->query("ALTER TABLE {$db->table('replies')} ADD COLUMN edit_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '编辑次数' AFTER created_at");
            }
            if (!self::columnExists($db, 'replies', 'last_edit_at')) {
                $db->query("ALTER TABLE {$db->table('replies')} ADD COLUMN last_edit_at DATETIME DEFAULT NULL COMMENT '最后编辑时间' AFTER edit_count");
            }
        }
    }

    /**
     * 迁移19：添加板块指定发帖用户字段
     */
    private static function migrate19($db) {
        // 为 forums 表添加允许发帖用户字段
        if (self::tableExists($db, 'forums')) {
            if (!self::columnExists($db, 'forums', 'allowed_users')) {
                $db->query("ALTER TABLE {$db->table('forums')} ADD COLUMN allowed_users TEXT DEFAULT NULL COMMENT '允许发帖的用户ID，逗号分隔' AFTER sort_order");
            }
        }
    }

    /**
     * 迁移20：添加用户个人介绍字段
     */
    private static function migrate20($db) {
        // 为 users 表添加个人介绍字段
        if (self::tableExists($db, 'users')) {
            if (!self::columnExists($db, 'users', 'bio')) {
                $db->query("ALTER TABLE {$db->table('users')} ADD COLUMN bio TEXT DEFAULT NULL COMMENT '个人介绍' AFTER avatar");
            }
        }
    }
    
    /**
     * 迁移21：为搜索功能添加全文索引
     */
    private static function migrate21($db) {
        // 为 posts 表添加全文索引
        if (self::tableExists($db, 'posts')) {
            // 检查是否已存在全文索引
            try {
                $db->query("ALTER TABLE {$db->table('posts')} ADD FULLTEXT INDEX ft_title_content (title, content)");
            } catch (Exception $e) {
                // 索引可能已存在，忽略错误
            }
        }
        
        // 创建搜索日志表
        if (!self::tableExists($db, 'search_logs')) {
            $sql = "CREATE TABLE IF NOT EXISTS {$db->table('search_logs')} (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                keyword VARCHAR(255) NOT NULL COMMENT '搜索关键词',
                user_id INT UNSIGNED DEFAULT 0 COMMENT '用户ID',
                ip VARCHAR(45) DEFAULT NULL COMMENT 'IP地址',
                results_count INT UNSIGNED DEFAULT 0 COMMENT '结果数量',
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY idx_keyword (keyword),
                KEY idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $db->query($sql);
        }
    }
}
