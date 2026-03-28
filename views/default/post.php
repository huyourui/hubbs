<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
$maxCommentLength = (int)getSetting('max_comment_length', '2000');

$GLOBALS['extraStyles'] = <<<CSS
.container { max-width: 900px; }
.post-content { line-height: 1.8; font-size: 1rem; }
.post-content a { color: #0d6efd; text-decoration: underline; }
.post-content img { max-width: 100%; height: auto; cursor: pointer; border-radius: 0.375rem; margin: 0.5rem 0; }
.post-content h1, .post-content h2, .post-content h3, .post-content h4, .post-content h5, .post-content h6 { margin-top: 1rem; margin-bottom: 0.5rem; font-weight: 600; }
.post-content h1 { font-size: 1.75rem; border-bottom: 1px solid #dee2e6; padding-bottom: 0.3rem; }
.post-content h2 { font-size: 1.5rem; border-bottom: 1px solid #dee2e6; padding-bottom: 0.3rem; }
.post-content h3 { font-size: 1.25rem; }
.post-content h4 { font-size: 1.1rem; }
.post-content p { margin-bottom: 1rem; }
.post-content ul, .post-content ol { margin-bottom: 1rem; padding-left: 2rem; }
.post-content li { margin-bottom: 0.25rem; }
.post-content blockquote { border-left: 4px solid #0d6efd; padding-left: 1rem; margin: 1rem 0; color: #6c757d; background: #f8f9fa; padding: 0.5rem 1rem; border-radius: 0 0.375rem 0.375rem 0; }
.post-content code { background: #f8f9fa; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 0.9em; }
.post-content pre { background: #f8f9fa; padding: 1rem; border-radius: 0.375rem; overflow-x: auto; margin: 1rem 0; }
.post-content pre code { background: none; padding: 0; }
.post-content hr { border: none; border-top: 2px solid #dee2e6; margin: 1.5rem 0; }
.post-content table { width: 100%; margin-bottom: 1rem; border-collapse: collapse; }
.post-content th, .post-content td { border: 1px solid #dee2e6; padding: 0.5rem; }
.post-content th { background: #f8f9fa; }
.post-images { margin-top: 1.5rem; }
.post-images-title { font-size: 0.875rem; color: #6c757d; margin-bottom: 0.5rem; }
.post-images-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.post-images-grid img { max-width: 100%; height: auto; border-radius: 0.375rem; cursor: pointer; }
.comment-main { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
.comment-main:last-child { border-bottom: none; }
.comment-author a { color: #333; font-weight: 600; text-decoration: none; }
.comment-author a:hover { color: #0d6efd; }
.comment-time { color: #999; font-size: 12px; margin-left: 8px; }
.comment-text { margin: 8px 0; line-height: 1.6; color: #333; }
.comment-actions a { color: #999; font-size: 12px; text-decoration: none; margin-right: 15px; }
.comment-actions a:hover { color: #0d6efd; }
.reply-form { display: none; margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; }
.reply-form textarea { width: 100%; border: 1px solid #ddd; border-radius: 4px; padding: 8px; font-size: 14px; resize: none; height: 60px; }
.reply-form button { margin-top: 8px; padding: 5px 15px; background: #0d6efd; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
.reply-form button:hover { background: #0b5ed7; }
.comment-replies { margin-left: 20px; padding-left: 15px; border-left: 2px solid #e8e8e8; margin-top: 10px; }
.reply-item { padding: 8px 0; font-size: 14px; line-height: 1.5; }
.reply-author a { color: #333; font-weight: 500; text-decoration: none; }
.reply-author a:hover { color: #0d6efd; }
.reply-to { color: #666; }
.reply-to a { color: #0d6efd; text-decoration: none; }
.reply-text { color: #333; }
.reply-time { color: #999; font-size: 12px; margin-left: 8px; }
.reply-actions a { color: #999; font-size: 12px; text-decoration: none; margin-left: 10px; }
.reply-actions a:hover { color: #0d6efd; }
.char-counter { font-size: 0.875rem; color: #6c757d; }
.char-counter.warning { color: #ffc107; }
.char-counter.danger { color: #dc3545; }
.hidden-content-locked { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px dashed #6c757d; border-radius: 8px; padding: 1.5rem; text-align: center; margin: 1rem 0; color: #495057; }
.hidden-content-locked .lock-icon { font-size: 1.5rem; }
.hidden-content-locked strong { color: #212529; }
.hidden-content-locked small { color: #6c757d; }
.hidden-content-revealed { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); border: 2px solid #28a745; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
.hidden-content-revealed strong { color: #155724; }
.hidden-content-inner { margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px dashed #28a745; }
.image-lightbox { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: zoom-out; }
.image-lightbox img { max-width: 95%; max-height: 95%; object-fit: contain; }
CSS;

$GLOBALS['extraScripts'] = <<<JS
document.querySelectorAll('.reply-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var commentId = this.getAttribute('data-comment-id');
        var username = this.getAttribute('data-username');
        var replyToId = this.getAttribute('data-reply-to-id');
        var form = document.getElementById('reply-form-' + commentId);
        
        document.querySelectorAll('.reply-form').forEach(function(f) {
            f.style.display = 'none';
        });
        
        form.style.display = 'block';
        var textarea = form.querySelector('textarea');
        var replyInput = form.querySelector('input[name="reply_to_user_id"]');
        
        if (replyToId) {
            textarea.placeholder = '回复 @' + username + '...';
            replyInput.value = replyToId;
        } else {
            textarea.placeholder = '回复 @' + username + '...';
            replyInput.value = form.querySelector('input[name="parent_id"]').value;
        }
        textarea.focus();
    });
});

(function() {
    var maxLength = {$maxCommentLength};
    var commentForm = document.querySelector('form[name="comment-form"]');
    if (commentForm) {
        var textarea = commentForm.querySelector('textarea[name="content"]');
        var counter = document.getElementById('comment-char-counter');
        
        function updateCounter() {
            var len = textarea.value.length;
            counter.textContent = len + ' / ' + maxLength + ' 字';
            counter.className = 'char-counter';
            if (len > maxLength) {
                counter.classList.add('danger');
            } else if (len > maxLength * 0.9) {
                counter.classList.add('warning');
            }
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter();
        
        /* Ctrl+Enter 快捷键发布评论 */
        textarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                if (textarea.value.length <= maxLength && textarea.value.trim()) {
                    /* 创建隐藏字段传递 submit_comment 值 */
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'submit_comment';
                    hiddenInput.value = '1';
                    commentForm.appendChild(hiddenInput);
                    commentForm.submit();
                }
            }
        });
        
        commentForm.addEventListener('submit', function(e) {
            if (textarea.value.length > maxLength) {
                e.preventDefault();
                alert('评论内容超过最大字数限制（' + maxLength + '字）');
                return false;
            }
        });
    }
})();

/* 回复评论表单支持 Ctrl+Enter 快速发布 */
document.querySelectorAll('.reply-form form').forEach(function(form) {
    var textarea = form.querySelector('textarea');
    if (textarea) {
        textarea.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                if (textarea.value.trim()) {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'submit_comment';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            }
        });
    }
});

function openLightbox(src) {
    var lightbox = document.getElementById('image-lightbox');
    var img = lightbox.querySelector('img');
    img.src = src;
    lightbox.style.display = 'flex';
}

document.querySelectorAll('.post-content img, .post-images-grid img').forEach(function(img) {
    img.addEventListener('click', function() {
        openLightbox(this.src);
    });
});

document.getElementById('image-lightbox').addEventListener('click', function() {
    this.style.display = 'none';
});

var lazyImages = document.querySelectorAll('.lazy-image');
var imageObserver = new IntersectionObserver(function(entries, observer) {
    entries.forEach(function(entry) {
    if (entry.isIntersecting) {
    var img = entry.target;
    img.src = img.dataset.src;
    img.classList.remove('lazy');
    observer.unobserve(img);
    }
});
}, { rootMargin: '100px' });

lazyImages.forEach(function(img) {
    imageObserver.observe(img);
});

// 点赞和收藏功能的AJAX处理
(function() {
    // 显示消息提示
    function showMessage(message, type) {
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (type || 'success') + ' alert-dismissible fade show fixed-top m-3';
        alertDiv.role = 'alert';
        alertDiv.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        document.body.appendChild(alertDiv);
        setTimeout(function() {
            alertDiv.remove();
        }, 3000);
    }

    // 处理点赞按钮点击
    var likeButton = document.getElementById('like-button');
    if (likeButton) {
        likeButton.addEventListener('click', function(e) {
            e.preventDefault();
            var url = this.getAttribute('href');
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var likeCount = document.getElementById('like-count');
                    if (data.is_liked) {
                        likeButton.classList.remove('btn-outline-primary');
                        likeButton.classList.add('btn-primary');
                        likeButton.innerHTML = '已点赞' + ' (' + data.like_count + ')';
                    } else {
                        likeButton.classList.remove('btn-primary');
                        likeButton.classList.add('btn-outline-primary');
                        likeButton.innerHTML = '点赞' + ' (' + data.like_count + ')';
                    }
                    showMessage(data.message);
                } else {
                    showMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showMessage('操作失败，请重试', 'danger');
            });
        });
    }

    // 处理收藏按钮点击
    var favoriteButton = document.getElementById('favorite-button');
    if (favoriteButton) {
        favoriteButton.addEventListener('click', function(e) {
            e.preventDefault();
            var url = this.getAttribute('href');
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var favoriteCount = document.getElementById('favorite-count');
                    if (data.is_favorited) {
                        favoriteButton.classList.remove('btn-outline-warning');
                        favoriteButton.classList.add('btn-warning');
                        favoriteButton.innerHTML = '取消收藏' + ' (' + data.favorite_count + ')';
                    } else {
                        favoriteButton.classList.remove('btn-warning');
                        favoriteButton.classList.add('btn-outline-warning');
                        favoriteButton.innerHTML = '收藏' + ' (' + data.favorite_count + ')';
                    }
                    showMessage(data.message);
                } else {
                    showMessage(data.message, 'danger');
                }
            })
            .catch(error => {
                showMessage('操作失败，请重试', 'danger');
            });
        });
    }
})();
JS;

$processedContent = renderPostContent(
    $post['content'],
    $post['id'],
    isLoggedIn() ? $_SESSION['user_id'] : null,
    $post['user_id']
);
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/">首页</a></li>
        <?php if ($post['category_id']): ?>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/?category=<?php echo $post['category_id']; ?>"><?php echo escape($post['category_name']); ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?php echo escape(mb_substr($post['title'], 0, 50)); ?></li>
    </ol>
</nav>

<div class="card mb-4">
    <div class="card-body">
        <h1 class="h3 mb-3">
            <?php if ($post['is_sticky']): ?>
                <span class="badge bg-danger">置顶</span>
            <?php endif; ?>
            <?php if (!empty($post['is_digest'])): ?>
                <span class="badge bg-success">精华</span>
            <?php endif; ?>
            <?php if ($post['is_locked']): ?>
                <span class="badge bg-secondary">锁定</span>
            <?php endif; ?>
            <?php echo escape($post['title']); ?>
        </h1>
        <div class="text-muted mb-3">
            <span class="me-3">作者: <a href="<?php echo SITE_URL; ?>/pages/profile.php?user=<?php echo $post['user_id']; ?>"><?php echo escape($post['username']); ?></a>
                <span class="badge bg-info ms-1"><?php echo getUserLevelName($post['points'] ?? 0); ?></span>
            </span>
            <span class="me-3">分类: <?php echo escape($post['category_name'] ?? '未分类'); ?></span>
            <span class="me-3">浏览: <?php echo $post['views']; ?></span>
            <span class="me-3">回复: <?php echo $post['comment_count']; ?></span>
            <span class="me-3">发布: <?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>
            <?php if (!empty($post['ip_address'])): ?>
            <span class="text-muted small">地区: <?php echo escape(parseIpAddress($post['ip_address'], true)); ?></span>
            <?php endif; ?>
        </div>
        <div class="post-content">
            <?php echo $processedContent; ?>
        </div>
        
        <?php 
        $uninsertedImages = array_filter($postImages, function($img) {
            return !$img['is_inserted'];
        });
        if (!empty($uninsertedImages)):
        ?>
        <div class="post-images">
            <div class="post-images-title">附件图片</div>
            <div class="post-images-grid">
                <?php foreach ($uninsertedImages as $image): ?>
                    <img src="<?php echo SITE_URL . '/' . $image['thumbpath']; ?>" 
                         data-src="<?php echo SITE_URL . '/' . $image['filepath']; ?>"
                         alt="" 
                         class="lazy-image"
                         loading="lazy"
                         onclick="openLightbox(this.dataset.src)">
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($attachments)): ?>
        <div class="post-attachments mt-3">
            <div class="post-images-title">附件下载</div>
            <div class="attachment-list">
                <?php foreach ($attachments as $attachment): ?>
                    <div class="attachment-item d-flex align-items-center justify-content-between p-2 bg-light rounded mb-2">
                        <div>
                            <span class="badge bg-secondary me-2"><?php echo strtoupper($attachment['file_ext']); ?></span>
                            <?php echo escape($attachment['original_name']); ?>
                            <small class="text-muted ms-2">(<?php echo formatFileSize($attachment['file_size']); ?>)</small>
                            <small class="text-muted ms-2">下载: <?php echo $attachment['download_count']; ?> 次</small>
                        </div>
                        <?php if ($canDownloadAttachment): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/download.php?id=<?php echo $attachment['id']; ?>" class="btn btn-sm btn-primary">下载</a>
                        <?php else: ?>
                            <span class="text-muted small">登录后可下载</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="post-actions">
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['id']; ?>&toggle_like=1" id="like-button" class="btn btn-sm <?php echo $isLiked ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo $isLiked ? '已点赞' : '点赞'; ?> (<span id="like-count"><?php echo $likeCount; ?></span>)
                </a>
                <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['id']; ?>&toggle_favorite=1" id="favorite-button" class="btn btn-sm <?php echo $isFavorited ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <?php echo $isFavorited ? '取消收藏' : '收藏'; ?> (<span id="favorite-count"><?php echo $favoriteCount; ?></span>)
                </a>
            <?php endif; ?>
            <?php if (isLoggedIn() && (($_SESSION['user_id'] == $post['user_id'] && !$post['is_locked']) || isAdmin())): ?>
                <a href="<?php echo SITE_URL; ?>/pages/edit.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary btn-sm">编辑</a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        操作
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_sticky=<?php echo $post['id']; ?>"><?php echo $post['is_sticky'] ? '取消置顶' : '设为置顶'; ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['id']; ?>&toggle_digest=1"><?php echo !empty($post['is_digest']) ? '取消精华' : '设为精华'; ?></a></li>
                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_lock=<?php echo $post['id']; ?>"><?php echo $post['is_locked'] ? '解锁帖子' : '锁定帖子'; ?></a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/pages/admin.php?delete_post=<?php echo $post['id']; ?>" onclick="return confirm('确定要删除这篇帖子吗？')">删除帖子</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($post['is_locked'] && !isAdmin()): ?>
    <div class="alert alert-warning">
        此帖子已被锁定，无法发表评论
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">评论 (<?php echo count($comments); ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (!empty($commentTree)): ?>
            <?php echo renderComments($commentTree, $id, $post['is_locked']); ?>
        <?php else: ?>
            <p class="text-center text-muted py-4">暂无评论</p>
        <?php endif; ?>

        <?php if (isLoggedIn() && (!$post['is_locked'] || isAdmin())): ?>
            <hr class="my-4">
            <h5 class="mb-3">发表评论</h5>
            <form method="POST" action="" name="comment-form">
                <div class="mb-3">
                    <div class="d-flex justify-content-end mb-1">
                        <span id="comment-char-counter" class="char-counter">0 / <?php echo $maxCommentLength; ?> 字</span>
                    </div>
                    <textarea name="content" class="form-control" rows="4" placeholder="写下你的评论..." required></textarea>
                </div>
                <button type="submit" name="submit_comment" class="btn btn-primary">发表评论</button>
            </form>
        <?php elseif (!isLoggedIn()): ?>
            <hr class="my-4">
            <p class="text-center text-muted">
                <a href="<?php echo SITE_URL; ?>/pages/login.php">登录</a> 后参与讨论
            </p>
        <?php endif; ?>
    </div>
</div>

<div id="image-lightbox" class="image-lightbox" style="display: none;">
    <img src="" alt="">
</div>
