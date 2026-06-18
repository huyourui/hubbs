<?php
/**
 * HuBBS - 公共函数库
 */

// 获取基础 URL
function base_url($path = '') {
    // 优先使用后台设置的网站URL
    // 使用 class_exists 和 method_exists 检查 Settings 类
    if (class_exists('Settings') && method_exists('Settings', 'get')) {
        try {
            $siteUrl = @Settings::get('site_url', '');
            if (!empty($siteUrl)) {
                $siteUrl = rtrim($siteUrl, '/');
                if ($path) {
                    return $siteUrl . '/' . ltrim($path, '/');
                }
                return $siteUrl;
            }
        } catch (Exception $e) {
            // 如果 Settings 类出错，继续执行自动检测
        } catch (Throwable $e) {
            // 捕获 PHP7+ 的错误
        }
    }
    
    // 使用 SCRIPT_FILENAME 和 DOCUMENT_ROOT 计算
    // 这是最可靠的方法，因为 SCRIPT_FILENAME 总是指向实际的 index.php 文件
    
    $scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '';
    
    $basePath = '';
    
    if (!empty($scriptFilename) && !empty($docRoot)) {
        // 规范化路径分隔符
        $scriptFilename = str_replace('\\', '/', $scriptFilename);
        $docRoot = str_replace('\\', '/', $docRoot);
        
        // 移除 DOCUMENT_ROOT 部分，得到从网站根目录开始的脚本路径
        if (strpos($scriptFilename, $docRoot) === 0) {
            $scriptPath = substr($scriptFilename, strlen($docRoot));
            
            // 获取目录部分（去掉 index.php）
            $lastSlash = strrpos($scriptPath, '/');
            if ($lastSlash !== false) {
                $basePath = substr($scriptPath, 0, $lastSlash);
            }
        }
    }
    
    // 如果上述方法失败，尝试使用 HUBBS_ROOT
    if (empty($basePath)) {
        $rootPath = str_replace('\\', '/', HUBBS_ROOT);
        $docRoot = str_replace('\\', '/', $docRoot);
        
        if (!empty($docRoot) && strpos($rootPath, $docRoot) === 0) {
            $basePath = substr($rootPath, strlen($docRoot));
        }
    }
    
    // 统一使用正斜杠并去除末尾斜杠
    $basePath = rtrim($basePath, '/');
    
    if ($path) {
        return $basePath . '/' . ltrim($path, '/');
    }
    return $basePath;
}

// 安全过滤
function h($str) {
    if ($str === null) {
        return '';
    }
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function e($str) {
    echo h($str);
}

// 密码哈希
function password_hash_hubbs($password) {
    return password_hash($password . HUBBS_SALT, PASSWORD_BCRYPT);
}

function password_verify_hubbs($password, $hash) {
    return password_verify($password . HUBBS_SALT, $hash);
}

// CSRF Token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 跳转
function redirect($url) {
    // 防止浏览器缓存重定向后的页面，确保新数据立即显示
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header("Location: $url");
    exit;
}

// 消息提示
function set_message($msg, $type = 'success') {
    $_SESSION['message'] = ['text' => $msg, 'type' => $type];
}

function get_message() {
    if (isset($_SESSION['message'])) {
        $msg = $_SESSION['message'];
        unset($_SESSION['message']);
        return $msg;
    }
    return null;
}

// 分页
function pagination($total, $page, $perPage, $url) {
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    
    $html = '<div class="pagination">';
    
    // 上一页
    if ($page > 1) {
        $html .= '<a href="' . $url . ($page - 1) . '" class="page-btn">&lt;</a>';
    }
    
    // 页码
    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $url . '1" class="page-btn">1</a>';
        if ($start > 2) $html .= '<span class="page-ellipsis">...</span>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $page ? ' active' : '';
        $html .= '<a href="' . $url . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="page-ellipsis">...</span>';
        $html .= '<a href="' . $url . $totalPages . '" class="page-btn">' . $totalPages . '</a>';
    }
    
    // 下一页
    if ($page < $totalPages) {
        $html .= '<a href="' . $url . ($page + 1) . '" class="page-btn">&gt;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

// 时间格式化
function time_ago($time) {
    $diff = time() - strtotime($time);
    
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    
    return date('Y-m-d', strtotime($time));
}

// 验证
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{2,20}$/u', $username);
}

// 获取客户端IP
function get_client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

// 自动加载
function hubbs_autoload($class) {
    // 类名到文件名的映射（保持与文件名大小写一致）
    $classMap = [
        'DB' => 'db.php',
        'Auth' => 'Auth.php',
        'Settings' => 'Settings.php',
        'Mailer' => 'Mailer.php',
        'Migrate' => 'migrate.php',
        'Upload' => 'Upload.php',
        'Notification' => 'notification.php',
    ];
    
    if (isset($classMap[$class])) {
        $file = HUBBS_ROOT . 'core/' . $classMap[$class];
        if (file_exists($file)) {
            require_once $file;
        }
    }
}
spl_autoload_register('hubbs_autoload');

/**
 * 渲染帖子内容（支持 Markdown 和 HTML）
 * 自动检测内容类型并正确渲染
 */
function render_content($content) {
    if (empty($content)) {
        return '';
    }

    // 内容已经是 HTML，进行安全过滤后返回
    // 允许的基本标签
    $allowedTags = '<p><br><strong><b><em><i><del><s><code><pre><blockquote><ul><ol><li><a><img><h1><h2><h3><h4><h5><h6><hr><table><thead><tbody><tr><th><td><div><span><font>';
    $content = strip_tags($content, $allowedTags);
    
    // 为所有链接添加 target="_blank" 和 rel="noopener"
    $content = preg_replace_callback('/<a\s+([^>]*)>/i', function($matches) {
        $attrs = $matches[1];
        
        // 如果已经有 target 属性，替换它
        if (preg_match('/target=["\'][^"\']*["\']/i', $attrs)) {
            $attrs = preg_replace('/target=["\'][^"\']*["\']/i', 'target="_blank"', $attrs);
        } else {
            // 否则添加 target 属性
            $attrs .= ' target="_blank"';
        }
        
        // 如果已经有 rel 属性，追加 noopener
        if (preg_match('/rel=["\']([^"\']*)["\']/i', $attrs, $relMatches)) {
            $relValue = $relMatches[1];
            if (strpos($relValue, 'noopener') === false) {
                $attrs = preg_replace('/rel=["\']([^"\']*)["\']/i', 'rel="$1 noopener"', $attrs);
            }
        } else {
            // 否则添加 rel 属性
            $attrs .= ' rel="noopener"';
        }
        
        return '<a ' . $attrs . '>';
    }, $content);
    
    return $content;
}

/**
 * Markdown 解析器
 */
function parse_markdown($text) {
    if (empty($text)) return '';

    // 转义 HTML
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    // 代码块（必须在行内代码之前处理）
    $text = preg_replace_callback('/```(\w+)?\n([\s\S]*?)```/', function($matches) {
        $lang = $matches[1] ?? '';
        $code = $matches[2];
        return '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
    }, $text);

    // 行内代码
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

    // 段落分割（先分割成段落，再处理每个段落）
    $paragraphs = explode("\n\n", $text);
    $result = [];

    foreach ($paragraphs as $p) {
        $p = trim($p);
        if (empty($p)) continue;

        // 检查是否是特殊块级元素
        $firstLine = strtok($p, "\n");

        // 标题 # ## ###
        if (preg_match('/^#{1,6}\s+(.+)$/', $firstLine, $matches)) {
            $level = strlen(strtok($firstLine, ' '));
            $content = substr($firstLine, $level + 1);
            $result[] = "<h$level>$content</h$level>";
            continue;
        }

        // 引用 >
        if (preg_match('/^>\s+(.+)$/', $firstLine)) {
            $lines = explode("\n", $p);
            $quoteLines = [];
            foreach ($lines as $line) {
                if (preg_match('/^>\s*(.*)$/', $line, $m)) {
                    $quoteLines[] = $m[1];
                }
            }
            $result[] = '<blockquote>' . implode("<br>", $quoteLines) . '</blockquote>';
            continue;
        }

        // 无序列表 - *
        if (preg_match('/^[\-\*]\s+(.+)$/', $firstLine)) {
            $lines = explode("\n", $p);
            $items = [];
            foreach ($lines as $line) {
                if (preg_match('/^[\-\*]\s*(.*)$/', $line, $m)) {
                    $items[] = '<li>' . $m[1] . '</li>';
                }
            }
            $result[] = '<ul>' . implode('', $items) . '</ul>';
            continue;
        }

        // 有序列表 1. 2.
        if (preg_match('/^\d+\.\s+(.+)$/', $firstLine)) {
            $lines = explode("\n", $p);
            $items = [];
            foreach ($lines as $line) {
                if (preg_match('/^\d+\.\s*(.*)$/', $line, $m)) {
                    $items[] = '<li>' . $m[1] . '</li>';
                }
            }
            $result[] = '<ol>' . implode('', $items) . '</ol>';
            continue;
        }

        // 水平线 --- ***
        if (preg_match('/^---+$/', $p) || preg_match('/^\*\*\*+$/', $p)) {
            $result[] = '<hr />';
            continue;
        }

        // 表格
        if (preg_match('/\|.+\|/', $firstLine) && substr_count($p, "\n") >= 2) {
            $lines = explode("\n", $p);
            if (count($lines) >= 2 && preg_match('/^\|[-:\|\s]+\|$/', $lines[1])) {
                $headers = array_map('trim', explode('|', trim($lines[0], '|')));
                $html = '<table><thead><tr>';
                foreach ($headers as $h) {
                    $html .= '<th>' . $h . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                for ($i = 2; $i < count($lines); $i++) {
                    $cells = array_map('trim', explode('|', trim($lines[$i], '|')));
                    $html .= '<tr>';
                    foreach ($cells as $c) {
                        $html .= '<td>' . $c . '</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
                $result[] = $html;
                continue;
            }
        }

        // 普通段落 - 处理行内元素
        // 粗体
        $p = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $p);
        $p = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $p);

        // 斜体
        $p = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $p);
        $p = preg_replace('/_([^_]+)_/', '<em>$1</em>', $p);

        // 删除线
        $p = preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $p);

        // 图片（必须在链接之前处理，因为图片语法也包含 []()）
        $p = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" style="max-width:100%;" />', $p);

        // 链接
        $p = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
            $text = $matches[1];
            $url = trim($matches[2]);
            $url = str_replace(['"', "'", '<', '>'], '', $url);
            return '<a href="' . $url . '" target="_blank" rel="noopener">' . $text . '</a>';
        }, $p);

        // 处理单行换行
        $p = str_replace("\n", '<br>', $p);
        $result[] = '<p>' . $p . '</p>';
    }

    $text = implode("\n", $result);

    // 合并相邻的列表
    $text = preg_replace('/<\/ul>\s*<ul>/', '', $text);
    $text = preg_replace('/<\/ol>\s*<ol>/', '', $text);

    return $text;
}

// 预定义的颜色数组 - 精心挑选的单色配色方案
function get_avatar_colors() {
    return [
        '#FF6B6B', // 珊瑚红
        '#4ECDC4', // 青绿色
        '#667EEA', // 紫罗兰
        '#F093FB', // 粉紫色
        '#4FACFE', // 天蓝色
        '#43E97B', // 翠绿色
        '#FA709A', // 粉色
        '#30CFD0', // 青色
        '#FF9A9E', // 浅粉色
        '#FCB69F', // 橙黄色
        '#FF8A80', // 浅红色
        '#B388FF', // 浅紫色
        '#82B1FF', // 浅蓝色
        '#69F0AE', // 浅绿色
        '#FFAB40', // 深橙色
        '#FF5252', // 深红色
        '#E040FB', // 深紫色
        '#536DFE', // 深蓝色
        '#40C4FF', // 亮蓝色
        '#AB47BC', // 紫色
        '#26C6DA', // 青色
        '#66BB6A', // 绿色
        '#FFCA28', // 黄色
        '#EF5350', // 红色
        '#EC407A', // 粉色
        '#7E57C2', // 深紫色
        '#5C6BC0', // 靛蓝色
        '#29B6F6', // 天蓝色
        '#26A69A', // 蓝绿色
        '#9CCC65', // 浅绿色
    ];
}

// 获取用户头像颜色
function get_user_avatar_color($userId) {
    $colors = get_avatar_colors();
    $index = intval($userId) % count($colors);
    return $colors[$index];
}

// 渲染默认头像 SVG
function render_default_avatar($userId, $username, $size = 'normal', $class = '') {
    $color = get_user_avatar_color($userId);
    $initial = mb_substr($username, 0, 1, 'UTF-8');

    // 尺寸映射
    $sizeMap = [
        'tiny' => 24,
        'small' => 32,
        'normal' => 40,
        'large' => 48,
        'xlarge' => 80,
        'xxlarge' => 100
    ];
    $sizePx = isset($sizeMap[$size]) ? $sizeMap[$size] : 40;

    echo '<svg width="' . $sizePx . '" height="' . $sizePx . '" viewBox="0 0 40 40" class="default-avatar ' . $class . '">';
    echo '<circle cx="20" cy="20" r="20" fill="' . $color . '" />';
    echo '<text x="20" y="26" text-anchor="middle" fill="#fff" font-size="16" font-weight="500">' . h($initial) . '</text>';
    echo '</svg>';
}

/**
 * 获取用户头像URL
 * @param string|null $avatar 头像路径
 * @param int $userId 用户ID（用于生成默认头像颜色）
 * @param string $username 用户名（用于生成首字符头像）
 * @return string 头像URL
 */
function get_avatar_url($avatar, $userId = 0, $username = '') {
    if (!empty($avatar) && file_exists(ROOT_DIR . '/' . $avatar)) {
        return $avatar;
    }
    // 生成首字符头像
    $color = get_user_avatar_color($userId);
    $initial = $username ? mb_substr($username, 0, 1, 'UTF-8') : '?';
    return 'data:image/svg+xml,' . urlencode('<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50" fill="' . $color . '"/><text x="50" y="68" text-anchor="middle" fill="#fff" font-size="45" font-weight="500">' . $initial . '</text></svg>');
}

// 获取用户信息（支持已删除用户）
function get_user_info($userId) {
    static $cache = [];

    if (isset($cache[$userId])) {
        return $cache[$userId];
    }

    $db = DB::getInstance();
    $user = $db->fetch(
        "SELECT id, username, email, avatar, is_admin, deleted_at FROM {$db->table('users')} WHERE id = ? LIMIT 1",
        [$userId]
    );

    if (!$user) {
        // 用户不存在
        $user = [
            'id' => $userId,
            'username' => '未知用户',
            'email' => '',
            'avatar' => '',
            'is_admin' => 0,
            'deleted_at' => null,
            'is_deleted' => true
        ];
    } elseif (!empty($user['deleted_at'])) {
        // 已删除用户
        $user['is_deleted'] = true;
        $user['original_username'] = $user['username'];
        $user['username'] = '已注销用户';
    } else {
        $user['is_deleted'] = false;
    }

    $cache[$userId] = $user;
    return $user;
}

// 获取用户名（自动处理已删除用户）
function get_username($userId) {
    $user = get_user_info($userId);
    return $user['username'];
}
