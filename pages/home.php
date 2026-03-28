<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * 首页
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */

$postsPerPage = (int)getSetting('posts_per_page', '10');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;

$offset = ($page - 1) * $postsPerPage;

$whereClause = '';
$categoryParams = [];

if ($categoryId) {
    $childIds = getChildCategories($categoryId);
    if (!empty($childIds)) {
        $allIds = array_merge([$categoryId], array_column($childIds, 'id'));
        $placeholders = implode(',', array_fill(0, count($allIds), '?'));
        $whereClause = "WHERE p.category_id IN ($placeholders)";
        $categoryParams = $allIds;
    } else {
        $whereClause = 'WHERE p.category_id = ?';
        $categoryParams[] = $categoryId;
    }
}

$stmt = $pdo->prepare("
    SELECT p.id, p.title, p.user_id, p.category_id, p.views, p.is_sticky, p.is_locked, p.is_digest, p.created_at,
           u.username, u.avatar, u.points,
           c.name as category_name, c.slug as category_slug,
           COALESCE(cm.cnt, 0) as comment_count,
           COALESCE(cm.last_comment, p.created_at) as last_activity
    FROM posts p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN (
        SELECT post_id, COUNT(*) as cnt, MAX(created_at) as last_comment
        FROM comments GROUP BY post_id
    ) cm ON cm.post_id = p.id
    $whereClause
    ORDER BY p.is_sticky DESC, last_activity DESC
    LIMIT ? OFFSET ?
");
$params = array_merge($categoryParams, [$postsPerPage, $offset]);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM posts p $whereClause");
$countStmt->execute($categoryParams);
$totalPosts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalPosts / $postsPerPage);

$categories = getCategories();

$announcements = getAnnouncements(true);

render('index', [
    'posts' => $posts,
    'categories' => $categories,
    'page' => $page,
    'totalPages' => $totalPages,
    'categoryId' => $categoryId,
    'announcements' => $announcements,
    'pageTitle' => getSetting('site_title', SITE_NAME) . ' - ' . getSetting('site_subtitle', 'An Open Source Forum System')
]);
