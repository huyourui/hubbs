<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
require_once __DIR__ . '/../functions.php';

if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'admin.php';
    flashMessage('请先登录', 'info');
    redirect('pages/login.php');
}

if (!isAdmin()) {
    flashMessage('您没有权限访问此页面', 'error');
    redirect('');
}

$tab = $_GET['tab'] ?? 'dashboard';

$dashboardData = [];

if ($tab === 'dashboard') {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $dashboardData['totalUsers'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $dashboardData['totalPosts'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM comments");
    $dashboardData['totalComments'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $dashboardData['totalCategories'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT SUM(views) FROM posts");
    $dashboardData['totalViews'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $dashboardData['weekPosts'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $dashboardData['weekComments'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $dashboardData['weekUsers'] = (int)$stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT id, title, views, created_at FROM posts ORDER BY views DESC LIMIT 5");
    $dashboardData['hotPosts'] = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT id, username, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $dashboardData['newUsers'] = $stmt->fetchAll();
    
    $dashboardData['phpVersion'] = PHP_VERSION;
    $dashboardData['mysqlVersion'] = $pdo->query("SELECT VERSION()")->fetchColumn();
    $dashboardData['serverSoftware'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $dashboardData['serverOS'] = PHP_OS_FAMILY . ' ' . php_uname('r');
    $dashboardData['serverTime'] = date('Y-m-d H:i:s');
    $dashboardData['serverName'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $dashboardData['maxUploadSize'] = ini_get('upload_max_filesize');
    $dashboardData['maxPostSize'] = ini_get('post_max_size');
    $dashboardData['memoryLimit'] = ini_get('memory_limit');
    $dashboardData['hubbsVersion'] = defined('HUBBS_VERSION') ? HUBBS_VERSION : '1.0.0';
}

if (isset($_POST['batch_delete_posts']) && isAdmin()) {
    $postIds = $_POST['post_ids'] ?? [];
    if (!empty($postIds)) {
        foreach ($postIds as $postId) {
            $postId = (int)$postId;
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch();
            
            if ($post) {
                addPoints($post['user_id'], 'post_deleted', 'post', $postId, '帖子被批量删除');
                
                $attachments = getPostAttachments($postId);
                foreach ($attachments as $attachment) {
                    if (file_exists($attachment['file_path'])) {
                        @unlink($attachment['file_path']);
                    }
                }
                $stmt = $pdo->prepare("DELETE FROM attachments WHERE post_id = ?");
                $stmt->execute([$postId]);
                
                $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
                $stmt->execute([$postId]);
                
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE post_id = ?");
                $stmt->execute([$postId]);
                
                $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
                $stmt->execute([$postId]);
                
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$postId]);
            }
        }
        flashMessage('已批量删除 ' . count($postIds) . ' 个帖子', 'success');
        redirect('pages/admin.php?tab=posts');
    }
}

if (isset($_GET['delete_post'])) {
    $postId = (int)$_GET['delete_post'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    if ($post) {
        addPoints($post['user_id'], 'post_deleted', 'post', $postId, '帖子被删除：' . mb_substr($post['title'], 0, 50));
        
        if ($post['user_id'] != $_SESSION['user_id']) {
            createNotification(
                $post['user_id'],
                'post_deleted',
                '您的帖子已被删除',
                '您的帖子「' . mb_substr($post['title'], 0, 50) . '」已被管理员删除',
                ['post_title' => $post['title']]
            );
        }
        
        $attachments = getPostAttachments($postId);
        foreach ($attachments as $attachment) {
            if (file_exists($attachment['file_path'])) {
                @unlink($attachment['file_path']);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM attachments WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM post_images WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$postId]);
        
        flashMessage('帖子已删除', 'success');
        redirect('pages/admin.php?tab=posts');
    }
}

if (isset($_GET['toggle_sticky'])) {
    $postId = (int)$_GET['toggle_sticky'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE posts SET is_sticky = NOT is_sticky WHERE id = ?");
    $stmt->execute([$postId]);
    
    if ($post && $post['user_id'] != $_SESSION['user_id']) {
        $isNowSticky = !$post['is_sticky'];
        createNotification(
            $post['user_id'],
            $isNowSticky ? 'post_sticky' : 'post_unsticky',
            $isNowSticky ? '您的帖子已被置顶' : '您的帖子已取消置顶',
            '您的帖子「' . mb_substr($post['title'], 0, 50) . '」' . ($isNowSticky ? '已被管理员置顶' : '已取消置顶'),
            ['post_id' => $postId]
        );
    }
    
    flashMessage('置顶状态已更新', 'success');
    redirect('pages/admin.php?tab=posts');
}

if (isset($_GET['toggle_lock'])) {
    $postId = (int)$_GET['toggle_lock'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE posts SET is_locked = NOT is_locked WHERE id = ?");
    $stmt->execute([$postId]);
    
    if ($post && $post['user_id'] != $_SESSION['user_id']) {
        $isNowLocked = !$post['is_locked'];
        createNotification(
            $post['user_id'],
            $isNowLocked ? 'post_locked' : 'post_unlocked',
            $isNowLocked ? '您的帖子已被锁定' : '您的帖子已解锁',
            '您的帖子「' . mb_substr($post['title'], 0, 50) . '」' . ($isNowLocked ? '已被管理员锁定，无法继续回复' : '已解锁，可以继续回复'),
            ['post_id' => $postId]
        );
    }
    
    flashMessage('锁定状态已更新', 'success');
    redirect('pages/admin.php?tab=posts');
}

if (isset($_GET['toggle_digest'])) {
    $postId = (int)$_GET['toggle_digest'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE posts SET is_digest = NOT is_digest WHERE id = ?");
    $stmt->execute([$postId]);
    
    if ($post && $post['user_id'] != $_SESSION['user_id']) {
        $isNowDigest = !$post['is_digest'];
        createNotification(
            $post['user_id'],
            $isNowDigest ? 'post_digest' : 'post_undigest',
            $isNowDigest ? '您的帖子已被设为精华' : '您的帖子已取消精华',
            '您的帖子「' . mb_substr($post['title'], 0, 50) . '」' . ($isNowDigest ? '已被管理员设为精华帖子' : '已取消精华'),
            ['post_id' => $postId]
        );
    }
    
    flashMessage('精华状态已更新', 'success');
    redirect('pages/admin.php?tab=posts');
}

if (isset($_GET['delete_user'])) {
    $userId = (int)$_GET['delete_user'];
    if ($userId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        flashMessage('用户已删除', 'success');
    } else {
        flashMessage('不能删除自己的账号', 'error');
    }
    redirect('pages/admin.php?tab=users');
}

if (isset($_GET['toggle_admin'])) {
    $userId = (int)$_GET['toggle_admin'];
    if ($userId != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = ?");
        $stmt->execute([$userId]);
        flashMessage('用户角色已更新', 'success');
    } else {
        flashMessage('不能修改自己的角色', 'error');
    }
    redirect('pages/admin.php?tab=users');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['category_description'] ?? '');
    $allowedUsers = trim($_POST['allowed_users'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'cat-' . time();
    }
    $originalSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) break;
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] ? (int)$_POST['parent_id'] : null;
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, slug, parent_id, allowed_users) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $slug, $parentId, $allowedUsers]);
        /* 清除分类缓存 */
        cacheDelete('categories_all');
        flashMessage('分类已添加', 'success');
        redirect('pages/admin.php?tab=categories');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $catId = (int)$_POST['category_id'];
    $name = trim($_POST['category_name'] ?? '');
    $description = trim($_POST['category_description'] ?? '');
    $allowedUsers = trim($_POST['allowed_users'] ?? '');
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $parentId = isset($_POST['parent_id']) && $_POST['parent_id'] ? (int)$_POST['parent_id'] : null;
    
    if ($parentId == $catId) {
        $parentId = null;
    }
    
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
    $slug = trim($slug, '-');
    if (empty($slug)) {
        $slug = 'cat-' . $catId;
    }
    $originalSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $catId]);
        if (!$stmt->fetch()) break;
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, slug = ?, sort_order = ?, parent_id = ?, allowed_users = ? WHERE id = ?");
        $stmt->execute([$name, $description, $slug, $sortOrder, $parentId, $allowedUsers, $catId]);
        /* 清除分类缓存 */
        cacheDelete('categories_all');
        flashMessage('分类已更新', 'success');
        redirect('pages/admin.php?tab=categories');
    }
}

if (isset($_GET['move_up'])) {
    $catId = (int)$_GET['move_up'];
    $categories = getCategories();
    $ids = array_column($categories, 'id');
    $index = array_search($catId, $ids);
    
    if ($index > 0) {
        $temp = $ids[$index - 1];
        $ids[$index - 1] = $ids[$index];
        $ids[$index] = $temp;
        
        foreach ($ids as $i => $id) {
            $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?")->execute([$i + 1, $id]);
        }
    }
    /* 清除分类缓存 */
    cacheDelete('categories_all');
    flashMessage('排序已更新', 'success');
    redirect('pages/admin.php?tab=categories');
}

if (isset($_GET['move_down'])) {
    $catId = (int)$_GET['move_down'];
    $categories = getCategories();
    $ids = array_column($categories, 'id');
    $index = array_search($catId, $ids);
    
    if ($index !== false && $index < count($ids) - 1) {
        $temp = $ids[$index + 1];
        $ids[$index + 1] = $ids[$index];
        $ids[$index] = $temp;
        
        foreach ($ids as $i => $id) {
            $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?")->execute([$i + 1, $id]);
        }
    }
    /* 清除分类缓存 */
    cacheDelete('categories_all');
    flashMessage('排序已更新', 'success');
    redirect('pages/admin.php?tab=categories');
}

if (isset($_GET['delete_category'])) {
    $categoryId = (int)$_GET['delete_category'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    /* 清除分类缓存 */
    cacheDelete('categories_all');
    flashMessage('分类已删除', 'success');
    redirect('pages/admin.php?tab=categories');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $siteTitle = trim($_POST['site_title'] ?? '');
    $siteSubtitle = trim($_POST['site_subtitle'] ?? '');
    $requireCategory = isset($_POST['require_category']) ? '1' : '0';
    $allowRegister = isset($_POST['allow_register']) ? '1' : '0';
    $postsPerPage = max(5, min(100, (int)($_POST['posts_per_page'] ?? 10)));
    $maxPostLength = max(100, (int)($_POST['max_post_length'] ?? 10000));
    $maxCommentLength = max(50, (int)($_POST['max_comment_length'] ?? 2000));
    $siteTheme = trim($_POST['site_theme'] ?? 'default');
    $restrictEmailDomain = isset($_POST['restrict_email_domain']) ? '1' : '0';
    $allowedEmailDomains = trim($_POST['allowed_email_domains'] ?? '');
    $emailVerifyRegister = isset($_POST['email_verify_register']) ? '1' : '0';
    $maxImageSize = max(1, (int)($_POST['max_image_size'] ?? 5));
    $maxImageWidth = max(100, (int)($_POST['max_image_width'] ?? 1920));
    $imageQuality = max(50, min(100, (int)($_POST['image_quality'] ?? 85)));
    $thumbWidth = max(50, min(500, (int)($_POST['thumb_width'] ?? 300)));
    $forbiddenUsernames = trim($_POST['forbidden_usernames'] ?? '');
    $sensitiveWords = trim($_POST['sensitive_words'] ?? '');
    $sensitiveReplacement = trim($_POST['sensitive_replacement'] ?? '***');
    $attachmentMaxSize = max(1, min(100, (int)($_POST['attachment_max_size'] ?? 10)));
    $attachmentMaxCount = max(1, min(20, (int)($_POST['attachment_max_count'] ?? 5)));
    $attachmentAllowedExts = trim($_POST['attachment_allowed_exts'] ?? '');
    $attachmentGuestDownload = isset($_POST['attachment_guest_download']) ? '1' : '0';
    $postInterval = max(0, (int)($_POST['post_interval'] ?? 0));
    $commentInterval = max(0, (int)($_POST['comment_interval'] ?? 0));
    $pointsName = trim($_POST['points_name'] ?? '积分');
    $inviteOnly = isset($_POST['invite_only']) ? '1' : '0';
    
    updateSetting('site_title', $siteTitle);
    updateSetting('site_subtitle', $siteSubtitle);
    updateSetting('require_category', $requireCategory);
    updateSetting('allow_register', $allowRegister);
    updateSetting('posts_per_page', (string)$postsPerPage);
    updateSetting('max_post_length', (string)$maxPostLength);
    updateSetting('max_comment_length', (string)$maxCommentLength);
    updateSetting('site_theme', $siteTheme);
    updateSetting('restrict_email_domain', $restrictEmailDomain);
    updateSetting('allowed_email_domains', $allowedEmailDomains);
    updateSetting('email_verify_register', $emailVerifyRegister);
    updateSetting('max_image_size', (string)$maxImageSize);
    updateSetting('max_image_width', (string)$maxImageWidth);
    updateSetting('image_quality', (string)$imageQuality);
    updateSetting('thumb_width', (string)$thumbWidth);
    updateSetting('forbidden_usernames', $forbiddenUsernames);
    updateSetting('sensitive_words', $sensitiveWords);
    updateSetting('sensitive_replacement', $sensitiveReplacement);
    updateSetting('attachment_max_size', (string)$attachmentMaxSize);
    updateSetting('attachment_max_count', (string)$attachmentMaxCount);
    updateSetting('attachment_allowed_exts', $attachmentAllowedExts);
    updateSetting('attachment_guest_download', $attachmentGuestDownload);
    updateSetting('post_interval', (string)$postInterval);
    updateSetting('comment_interval', (string)$commentInterval);
    updateSetting('points_name', $pointsName);
    updateSetting('invite_only', $inviteOnly);
    
    flashMessage('设置已保存', 'success');
    redirect('pages/admin.php?tab=settings');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_email_settings'])) {
    $smtpEnabled = isset($_POST['smtp_enabled']) ? '1' : '0';
    $smtpHost = trim($_POST['smtp_host'] ?? '');
    $smtpPort = (int)($_POST['smtp_port'] ?? 465);
    $smtpSecure = trim($_POST['smtp_secure'] ?? 'ssl');
    $smtpUsername = trim($_POST['smtp_username'] ?? '');
    $smtpPassword = $_POST['smtp_password'] ?? '';
    $smtpFromEmail = trim($_POST['smtp_from_email'] ?? '');
    $smtpFromName = trim($_POST['smtp_from_name'] ?? 'HuBBS Forum');
    $emailNotifyReply = isset($_POST['email_notify_reply']) ? '1' : '0';
    
    updateSetting('smtp_enabled', $smtpEnabled);
    updateSetting('smtp_host', $smtpHost);
    updateSetting('smtp_port', (string)$smtpPort);
    updateSetting('smtp_secure', $smtpSecure);
    updateSetting('smtp_username', $smtpUsername);
    
    if (!empty($smtpPassword)) {
        updateSetting('smtp_password', $smtpPassword);
    }
    
    updateSetting('smtp_from_email', $smtpFromEmail);
    updateSetting('smtp_from_name', $smtpFromName);
    updateSetting('email_notify_reply', $emailNotifyReply);
    
    flashMessage('邮件设置已保存', 'success');
    redirect('pages/admin.php?tab=email');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testTo = trim($_POST['test_to'] ?? '');
    
    if (empty($testTo) || !filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        flashMessage('请输入有效的测试邮箱地址', 'error');
    } else {
        $result = sendTestEmail($testTo);
        if ($result['success']) {
            flashMessage('测试邮件发送成功，请检查收件箱', 'success');
        } else {
            flashMessage('测试邮件发送失败：' . $result['error'], 'error');
        }
    }
    redirect('pages/admin.php?tab=email');
}

$editCategory = null;
if (isset($_GET['edit_category'])) {
    $catId = (int)$_GET['edit_category'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$catId]);
    $editCategory = $stmt->fetch();
}

if (isset($_GET['delete_link'])) {
    $linkId = (int)$_GET['delete_link'];
    deleteLink($linkId);
    flashMessage('友情链接已删除', 'success');
    redirect('pages/admin.php?tab=links');
}

if (isset($_GET['toggle_link'])) {
    $linkId = (int)$_GET['toggle_link'];
    $link = getLinkById($linkId);
    if ($link) {
        updateLink($link['id'], $link['name'], $link['url'], $link['description'], $link['sort_order'], $link['is_visible'] ? 0 : 1);
        flashMessage('友情链接状态已更新', 'success');
    }
    redirect('pages/admin.php?tab=links');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_link'])) {
    $name = trim($_POST['link_name'] ?? '');
    $url = trim($_POST['link_url'] ?? '');
    $description = trim($_POST['link_description'] ?? '') ?: null;
    $sortOrder = (int)($_POST['link_sort_order'] ?? 0);
    
    if (!empty($name) && !empty($url)) {
        addLink($name, $url, $description, $sortOrder);
        flashMessage('友情链接已添加', 'success');
        redirect('pages/admin.php?tab=links');
    } else {
        flashMessage('请填写网站名称和网址', 'error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_link'])) {
    $linkId = (int)$_POST['link_id'];
    $name = trim($_POST['link_name'] ?? '');
    $url = trim($_POST['link_url'] ?? '');
    $description = trim($_POST['link_description'] ?? '') ?: null;
    $sortOrder = (int)($_POST['link_sort_order'] ?? 0);
    $isVisible = isset($_POST['link_is_visible']) ? 1 : 0;
    
    if (!empty($name) && !empty($url)) {
        updateLink($linkId, $name, $url, $description, $sortOrder, $isVisible);
        flashMessage('友情链接已更新', 'success');
        redirect('pages/admin.php?tab=links');
    } else {
        flashMessage('请填写网站名称和网址', 'error');
    }
}

$editLink = null;
if (isset($_GET['edit_link'])) {
    $linkId = (int)$_GET['edit_link'];
    $editLink = getLinkById($linkId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_point_rules'])) {
    $rules = $_POST['rules'] ?? [];
    foreach ($rules as $ruleId => $ruleData) {
        $points = (int)($ruleData['points'] ?? 0);
        $isEnabled = isset($ruleData['is_enabled']) ? 1 : 0;
        $dailyLimit = (int)($ruleData['daily_limit'] ?? 0);
        updatePointRule((int)$ruleId, $points, $isEnabled, $dailyLimit);
    }
    flashMessage('积分规则已保存', 'success');
    redirect('pages/admin.php?tab=points');
}

if (isset($_GET['toggle_point_rule'])) {
    $ruleId = (int)$_GET['toggle_point_rule'];
    $stmt = $pdo->prepare("SELECT * FROM point_rules WHERE id = ?");
    $stmt->execute([$ruleId]);
    $rule = $stmt->fetch();
    if ($rule) {
        updatePointRule($ruleId, $rule['points'], $rule['is_enabled'] ? 0 : 1, $rule['daily_limit']);
        flashMessage('积分规则状态已更新', 'success');
    }
    redirect('pages/admin.php?tab=points');
}

$editAnnouncement = null;

if ($tab === 'announcements') {
    if (isset($_GET['delete_announcement'])) {
        $announcementId = (int)$_GET['delete_announcement'];
        deleteAnnouncement($announcementId);
        flashMessage('公告已删除', 'success');
        redirect('pages/admin.php?tab=announcements');
    }
    
    if (isset($_GET['edit_announcement'])) {
        $editAnnouncement = getAnnouncementById((int)$_GET['edit_announcement']);
    }
    
    if (isset($_GET['toggle_announcement'])) {
        $announcementId = (int)$_GET['toggle_announcement'];
        $stmt = $pdo->prepare("SELECT is_enabled FROM announcements WHERE id = ?");
        $stmt->execute([$announcementId]);
        $announcement = $stmt->fetch();
        if ($announcement) {
            $stmt = $pdo->prepare("UPDATE announcements SET is_enabled = ? WHERE id = ?");
            $stmt->execute([$announcement['is_enabled'] ? 0 : 1, $announcementId]);
            flashMessage('公告状态已更新', 'success');
        }
        redirect('pages/admin.php?tab=announcements');
    }
    
    if (isset($_POST['save_announcement'])) {
        $announcementId = (int)($_POST['announcement_id'] ?? 0);
        $content = trim($_POST['announcement_content'] ?? '');
        $bgColor = trim($_POST['announcement_bg_color'] ?? '#fff3cd');
        $isEnabled = isset($_POST['announcement_enabled']);
        
        if (empty($content)) {
            flashMessage('请填写公告内容', 'error');
        } else {
            if ($announcementId > 0) {
                updateAnnouncement($announcementId, $content, $bgColor, $isEnabled);
                flashMessage('公告已更新', 'success');
            } else {
                createAnnouncement($content, $bgColor, $isEnabled);
                flashMessage('公告已添加', 'success');
            }
            redirect('pages/admin.php?tab=announcements');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_level'])) {
    $name = trim($_POST['level_name'] ?? '');
    $minPoints = (int)($_POST['min_points'] ?? 0);
    $maxPoints = (int)($_POST['max_points'] ?? 0);
    
    if (empty($name)) {
        flashMessage('请填写等级名称', 'error');
    } elseif ($minPoints < 0 || $maxPoints < 0) {
        flashMessage('积分不能为负数', 'error');
    } elseif ($minPoints > $maxPoints) {
        flashMessage('最小积分不能大于最大积分', 'error');
    } else {
        $stmt = $pdo->query("SELECT COALESCE(MAX(sort_order), -1) FROM user_levels");
        $maxOrder = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("INSERT INTO user_levels (name, min_points, max_points, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $minPoints, $maxPoints, $maxOrder + 1]);
        flashMessage('等级已添加', 'success');
        redirect('pages/admin.php?tab=levels');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_level'])) {
    $levelId = (int)$_POST['level_id'];
    $name = trim($_POST['level_name'] ?? '');
    $minPoints = (int)($_POST['min_points'] ?? 0);
    $maxPoints = (int)($_POST['max_points'] ?? 0);
    
    if (empty($name)) {
        flashMessage('请填写等级名称', 'error');
    } elseif ($minPoints < 0 || $maxPoints < 0) {
        flashMessage('积分不能为负数', 'error');
    } elseif ($minPoints > $maxPoints) {
        flashMessage('最小积分不能大于最大积分', 'error');
    } else {
        $stmt = $pdo->prepare("UPDATE user_levels SET name = ?, min_points = ?, max_points = ? WHERE id = ?");
        $stmt->execute([$name, $minPoints, $maxPoints, $levelId]);
        flashMessage('等级已更新', 'success');
        redirect('pages/admin.php?tab=levels');
    }
}

if (isset($_GET['delete_level'])) {
    $levelId = (int)$_GET['delete_level'];
    $stmt = $pdo->prepare("DELETE FROM user_levels WHERE id = ?");
    $stmt->execute([$levelId]);
    flashMessage('等级已删除', 'success');
    redirect('pages/admin.php?tab=levels');
}

if (isset($_GET['move_level_up'])) {
    $levelId = (int)$_GET['move_level_up'];
    $stmt = $pdo->query("SELECT id, sort_order FROM user_levels ORDER BY sort_order ASC");
    $levels = $stmt->fetchAll();
    $ids = array_column($levels, 'id');
    $index = array_search($levelId, $ids);
    
    if ($index > 0) {
        $temp = $ids[$index - 1];
        $ids[$index - 1] = $ids[$index];
        $ids[$index] = $temp;
        
        foreach ($ids as $i => $id) {
            $stmt = $pdo->prepare("UPDATE user_levels SET sort_order = ? WHERE id = ?");
            $stmt->execute([$i, $id]);
        }
        flashMessage('排序已更新', 'success');
    }
    redirect('pages/admin.php?tab=levels');
}

if (isset($_GET['move_level_down'])) {
    $levelId = (int)$_GET['move_level_down'];
    $stmt = $pdo->query("SELECT id, sort_order FROM user_levels ORDER BY sort_order ASC");
    $levels = $stmt->fetchAll();
    $ids = array_column($levels, 'id');
    $index = array_search($levelId, $ids);
    
    if ($index !== false && $index < count($ids) - 1) {
        $temp = $ids[$index + 1];
        $ids[$index + 1] = $ids[$index];
        $ids[$index] = $temp;
        
        foreach ($ids as $i => $id) {
            $stmt = $pdo->prepare("UPDATE user_levels SET sort_order = ? WHERE id = ?");
            $stmt->execute([$i, $id]);
        }
        flashMessage('排序已更新', 'success');
    }
    redirect('pages/admin.php?tab=levels');
}

$editLevel = null;
if (isset($_GET['edit_level'])) {
    $levelId = (int)$_GET['edit_level'];
    $stmt = $pdo->prepare("SELECT * FROM user_levels WHERE id = ?");
    $stmt->execute([$levelId]);
    $editLevel = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invite_codes'])) {
    $count = max(1, min(100, (int)($_POST['invite_code_count'] ?? 1)));
    $generated = 0;
    
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(8)));
        try {
            $stmt = $pdo->prepare("INSERT INTO invite_codes (code, created_by) VALUES (?, ?)");
            $stmt->execute([$code, $_SESSION['user_id']]);
            $generated++;
        } catch (PDOException $e) {
            continue;
        }
    }
    
    flashMessage("成功生成 {$generated} 个邀请码", 'success');
    redirect('pages/admin.php?tab=invite');
}

if (isset($_GET['delete_invite_code'])) {
    $codeId = (int)$_GET['delete_invite_code'];
    $stmt = $pdo->prepare("DELETE FROM invite_codes WHERE id = ? AND is_used = 0");
    $stmt->execute([$codeId]);
    flashMessage('邀请码已删除', 'success');
    redirect('pages/admin.php?tab=invite');
}

if (isset($_GET['delete_all_unused_codes'])) {
    $stmt = $pdo->exec("DELETE FROM invite_codes WHERE is_used = 0");
    flashMessage('所有未使用的邀请码已删除', 'success');
    redirect('pages/admin.php?tab=invite');
}

$stmt = $pdo->query("SELECT p.*, u.username, c.name as category_name FROM posts p JOIN users u ON p.user_id = u.id LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$categories = getCategories();
$links = getAllLinks();
$pointRules = getAllPointRules();
$announcements = getAnnouncements(false);
$announcementColors = getAnnouncementColors();
$userLevels = getUserLevels();
$inviteCodes = $pdo->query("SELECT ic.*, u.username as created_by_name FROM invite_codes ic LEFT JOIN users u ON ic.created_by = u.id WHERE ic.is_used = 0 ORDER BY ic.created_at DESC")->fetchAll();

render('admin', [
    'tab' => $tab,
    'posts' => $posts,
    'users' => $users,
    'categories' => $categories,
    'editCategory' => $editCategory,
    'links' => $links,
    'editLink' => $editLink,
    'pointRules' => $pointRules,
    'dashboardData' => $dashboardData,
    'announcements' => $announcements,
    'editAnnouncement' => $editAnnouncement,
    'announcementColors' => $announcementColors,
    'userLevels' => $userLevels,
    'editLevel' => $editLevel,
    'inviteCodes' => $inviteCodes,
    'pageTitle' => '管理后台 - ' . getSetting('site_title', SITE_NAME)
]);
