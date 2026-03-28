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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    redirect('');
}

$post = getPostById($id);

if (!$post) {
    flashMessage('帖子不存在', 'error');
    redirect('');
}

incrementPostViews($id);

/* 如果用户已登录，标记与该帖子相关的通知为已读 */
if (isLoggedIn()) {
    markPostNotificationsAsRead($id, $_SESSION['user_id']);
}

if (isset($_GET['toggle_favorite']) && isLoggedIn()) {
    if (toggleFavorite($id, $_SESSION['user_id'])) {
        $isNowFavorited = isFavorited($id, $_SESSION['user_id']);
        $favoriteCount = getFavoriteCount($id);
        if ($isNowFavorited && $post['user_id'] != $_SESSION['user_id']) {
            createNotification(
                $post['user_id'],
                'post_favorited',
                '您的帖子被收藏了',
                '用户 ' . $_SESSION['username'] . ' 收藏了您的帖子「' . mb_substr($post['title'], 0, 50) . '」',
                ['post_id' => $id, 'user_id' => $_SESSION['user_id']]
            );
        }
        $message = $isNowFavorited ? '收藏成功' : '已取消收藏';
        
        // 检查是否是AJAX请求
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'is_favorited' => $isNowFavorited,
                'favorite_count' => $favoriteCount
            ]);
            exit;
        } else {
            flashMessage($message, 'success');
            redirect('pages/post.php?id=' . $id);
        }
    }
}

if (isset($_GET['toggle_like']) && isLoggedIn()) {
    if (toggleLike($id, $_SESSION['user_id'])) {
        $isNowLiked = isLiked($id, $_SESSION['user_id']);
        $likeCount = getLikeCount($id);
        if ($isNowLiked) {
            addPoints($_SESSION['user_id'], 'like_post', 'post', $id, '点赞帖子');
            if ($post['user_id'] != $_SESSION['user_id']) {
                addPoints($post['user_id'], 'post_liked', 'post', $id, '帖子被点赞');
                createNotification(
                    $post['user_id'],
                    'post_liked',
                    '您的帖子被点赞了',
                    '用户 ' . $_SESSION['username'] . ' 点赞了您的帖子「' . mb_substr($post['title'], 0, 50) . '」',
                    ['post_id' => $id, 'user_id' => $_SESSION['user_id']]
                );
            }
        }
        $message = $isNowLiked ? '点赞成功' : '已取消点赞';
        
        // 检查是否是AJAX请求
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'is_liked' => $isNowLiked,
                'like_count' => $likeCount
            ]);
            exit;
        } else {
            flashMessage($message, 'success');
            redirect('pages/post.php?id=' . $id);
        }
    }
}

if (isset($_GET['toggle_digest']) && isAdmin()) {
    $isNowDigest = empty($post['is_digest']);
    $stmt = $pdo->prepare("UPDATE posts SET is_digest = ? WHERE id = ?");
    $stmt->execute([$isNowDigest ? 1 : 0, $id]);
    
    if ($post['user_id'] != $_SESSION['user_id']) {
        createNotification(
            $post['user_id'],
            $isNowDigest ? 'post_digest' : 'post_undigest',
            $isNowDigest ? '您的帖子已被设为精华' : '您的帖子已取消精华',
            '您的帖子「' . mb_substr($post['title'], 0, 50) . '」' . ($isNowDigest ? '已被管理员设为精华帖子' : '已取消精华'),
            ['post_id' => $id]
        );
    }
    flashMessage($isNowDigest ? '已设为精华' : '已取消精华', 'success');
    redirect('pages/post.php?id=' . $id);
}

if (isset($_GET['delete_comment']) && isLoggedIn()) {
    $commentId = (int)$_GET['delete_comment'];
    $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if ($comment && ($comment['user_id'] == $_SESSION['user_id'] || isAdmin())) {
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        addPoints($comment['user_id'], 'comment_deleted', 'comment', $commentId, '评论被删除');
        flashMessage('评论已删除', 'success');
    }
    redirect('pages/post.php?id=' . $id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']) && isLoggedIn()) {
    if ($post['is_locked'] && !isAdmin()) {
        flashMessage('此帖子已被锁定，无法评论', 'error');
    } else {
        $remainingTime = checkCommentInterval($_SESSION['user_id']);
        if ($remainingTime > 0) {
            flashMessage('评论过于频繁，请等待 ' . $remainingTime . ' 秒后再试', 'error');
        } else {
            $content = trim($_POST['content'] ?? '');
            $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $replyToUserId = isset($_POST['reply_to_user_id']) ? (int)$_POST['reply_to_user_id'] : null;
            $maxCommentLength = (int)getSetting('max_comment_length', '2000');
            
            if (empty($content)) {
                flashMessage('评论内容不能为空', 'error');
            } elseif (mb_strlen($content) > $maxCommentLength) {
                flashMessage('评论内容超过最大字数限制（' . $maxCommentLength . '字）', 'error');
            } else {
                $content = filterSensitiveWords($content);
                $ipAddress = getClientIP();
                $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_id, reply_to_user_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$id, $_SESSION['user_id'], $content, $parentId ?: null, $replyToUserId ?: null, $ipAddress])) {
                    $commentId = $pdo->lastInsertId();
                    addPoints($_SESSION['user_id'], 'create_comment', 'comment', $commentId, '发表评论');
                    
                    if ($parentId) {
                        $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
                        $stmt->execute([$parentId]);
                        $parentComment = $stmt->fetch();
                        if ($parentComment && $parentComment['user_id'] != $_SESSION['user_id']) {
                            createNotification(
                                $parentComment['user_id'],
                                'comment_reply',
                                '您的评论被回复了',
                                '用户 ' . $_SESSION['username'] . ' 回复了您在「' . mb_substr($post['title'], 0, 30) . '」中的评论',
                                ['post_id' => $id, 'comment_id' => $parentId]
                            );
                        }
                    } else {
                        if ($post['user_id'] != $_SESSION['user_id']) {
                            createNotification(
                                $post['user_id'],
                                'post_reply',
                                '您的帖子有新回复',
                                '用户 ' . $_SESSION['username'] . ' 回复了您的帖子「' . mb_substr($post['title'], 0, 50) . '」',
                                ['post_id' => $id]
                            );
                        }
                    }
                    flashMessage('评论发表成功', 'success');
                } else {
                    flashMessage('评论发表失败', 'error');
                }
            }
        }
    }
    redirect('pages/post.php?id=' . $id);
}

$comments = getCommentsByPostId($id);
$commentTree = buildCommentTree($comments);

$isFavorited = isLoggedIn() ? isFavorited($id, $_SESSION['user_id']) : false;
$favoriteCount = getFavoriteCount($id);
$isLiked = isLoggedIn() ? isLiked($id, $_SESSION['user_id']) : false;
$likeCount = getLikeCount($id);

$postImages = getPostImages($id);
$attachments = getPostAttachments($id);
$canDownloadAttachment = isLoggedIn() || isAttachmentGuestDownload();

render('post', [
    'post' => $post,
    'comments' => $comments,
    'commentTree' => $commentTree,
    'isFavorited' => $isFavorited,
    'favoriteCount' => $favoriteCount,
    'isLiked' => $isLiked,
    'likeCount' => $likeCount,
    'postImages' => $postImages,
    'attachments' => $attachments,
    'canDownloadAttachment' => $canDownloadAttachment,
    'id' => $id,
    'pageTitle' => $post['title'] . ' - ' . getSetting('site_title', SITE_NAME)
]);
