<?php
/**
 * HuBBS - 帖子详情模板（V2EX风格）
 */
$pageTitle = $post['title'];
include __DIR__ . '/header.php';

// 获取楼主信息和统计数据
$db = DB::getInstance();
$author = $db->fetch("SELECT * FROM {$db->table('users')} WHERE id = ? LIMIT 1", [$post['user_id']]);
// 获取楼主发帖数
$authorPostCount = $db->count('posts', 'user_id = ?', [$post['user_id']]);
// 获取楼主回复数
$authorReplyCount = $db->count('replies', 'user_id = ?', [$post['user_id']]);
?>

<div class="post-detail-container">
    <div class="container">
        <div class="post-detail-layout">
            <!-- 左侧主内容区 -->
            <div class="post-main">
                <!-- 帖子头部 -->
                <div class="post-header-box">
                    <div class="post-breadcrumb">
                        <a href="index.php">首页</a>
                        <span class="sep">›</span>
                        <a href="index.php?forum=<?php echo $post['forum_id']; ?>"><?php e($post['forum_name']); ?></a>
                    </div>
                    <h1 class="post-title">
                        <?php if ($post['is_top']): ?><span class="badge badge-top">置顶</span><?php endif; ?>
                        <?php if ($post['is_essence']): ?><span class="badge badge-essence">精华</span><?php endif; ?>
                        <?php if ($post['is_locked']): ?><span class="badge badge-locked">锁定</span><?php endif; ?>
                        <?php e($post['title']); ?>
                    </h1>
                    <div class="post-meta">
                        <span class="meta-item">
                            <span class="meta-label">楼主</span>
                            <a href="index.php?module=user&action=profile&id=<?php echo $post['user_id']; ?>" class="meta-value author-name"><?php e($post['username']); ?></a>
                        </span>
                        <span class="meta-item">
                            <span class="meta-label">发布于</span>
                            <span class="meta-value"><?php echo time_ago($post['created_at']); ?></span>
                        </span>
                        <span class="meta-item">
                            <span class="meta-label">浏览</span>
                            <span class="meta-value"><?php echo number_format($post['views']); ?></span>
                        </span>
                    </div>
                </div>

                <!-- 楼主内容 -->
                <div class="post-content-box" id="post-<?php echo $post['id']; ?>">
                    <div class="content-body">
                        <?php echo render_content($post['content']); ?>
                    </div>
                    
                    <!-- 点赞和收藏按钮 -->
                    <div class="post-actions-bar">
                        <div class="action-buttons">
                            <button type="button" 
                                    class="action-btn like-btn <?php echo $userLiked ? 'active' : ''; ?>" 
                                    onclick="toggleLike(<?php echo $post['id']; ?>, this)"
                                    data-liked="<?php echo $userLiked ? '1' : '0'; ?>">
                                <svg class="icon-heart" viewBox="0 0 24 24" width="18" height="18">
                                    <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                <span class="btn-text"><?php echo $userLiked ? '已赞' : '点赞'; ?></span>
                                <span class="count-badge" id="like-count-<?php echo $post['id']; ?>"><?php echo $post['likes'] ?? 0; ?></span>
                            </button>
                            
                            <?php if ($post['user_id'] != (Auth::id() ?? 0)): ?>
                            <button type="button" 
                                    class="action-btn favorite-btn <?php echo $userFavorited ? 'active' : ''; ?>" 
                                    onclick="toggleFavorite(<?php echo $post['id']; ?>, this)"
                                    data-favorited="<?php echo $userFavorited ? '1' : '0'; ?>">
                                <svg class="icon-star" viewBox="0 0 24 24" width="18" height="18">
                                    <path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                <span class="btn-text"><?php echo $userFavorited ? '已收藏' : '收藏'; ?></span>
                                <span class="count-badge" id="favorite-count-<?php echo $post['id']; ?>"><?php echo $post['favorites'] ?? 0; ?></span>
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-stats">
                            <span class="stat-item">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                <?php echo number_format($post['views']); ?> 浏览
                            </span>
                            <span class="stat-item">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/></svg>
                                <?php echo $total; ?> 回复
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 回复列表 -->
                <?php if (!empty($replies)): ?>
                <div class="replies-header">
                    <span class="replies-count"><?php echo $total; ?> 条回复</span>
                    <span class="replies-sort">最新回复</span>
                </div>

                <div class="replies-list">
                    <?php foreach ($replies as $index => $reply): ?>
                    <?php
                    $comments = $replyComments[$reply['id']] ?? [];
                    $commentCount = count($comments);
                    $showLimit = 3;
                    $hasMore = $commentCount > $showLimit;
                    ?>
                    <div class="reply-item" id="reply-<?php echo $reply['id']; ?>">
                        <div class="reply-author">
                            <div class="author-avatar">
                                <?php if ($reply['avatar']): ?>
                                <img src="<?php e($reply['avatar']); ?>" alt="<?php e($reply['username']); ?>">
                                <?php else: ?>
                                <?php echo render_default_avatar($reply['user_id'], $reply['username'], 'normal', 'avatar-default'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="author-info">
                                <div class="author-name"><?php e($reply['username']); ?></div>
                                <div class="author-time"><?php echo time_ago($reply['created_at']); ?></div>
                            </div>
                            <div class="reply-floor">#<?php echo $index + 1; ?></div>
                        </div>
                        <div class="reply-content">
                            <div class="reply-body">
                                <?php echo render_content($reply['content']); ?>
                            </div>
                            
                            <?php if (Auth::check()): ?>
                            <div class="reply-actions">
                                <button type="button" class="action-btn reply-btn" 
                                        onclick="showReplyForm(<?php echo $reply['id']; ?>, 0, '<?php e($reply['username']); ?>')">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/></svg>
                                    回复
                                </button>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 楼中楼 -->
                            <?php if ($commentCount > 0): ?>
                            <div class="reply-comments">
                                <div class="comments-list" data-count="<?php echo $commentCount; ?>" data-limit="<?php echo $showLimit; ?>">
                                    <?php foreach ($comments as $cIndex => $comment): ?>
                                    <div class="comment-item <?php echo $cIndex >= $showLimit ? 'comment-collapsed' : ''; ?>" id="comment-<?php echo $comment['id']; ?>">
                                        <div class="comment-avatar">
                                            <?php if ($comment['avatar']): ?>
                                            <img src="<?php e($comment['avatar']); ?>" alt="<?php e($comment['username']); ?>">
                                            <?php else: ?>
                                            <?php echo render_default_avatar($comment['user_id'], $comment['username'], 'tiny', 'avatar-tiny'); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <span class="comment-author"><?php e($comment['username']); ?></span>
                                                <?php if ($comment['to_user_id'] > 0 && $comment['to_username']): ?>
                                                <span class="comment-reply-to">
                                                    <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M8 7v4L2 6l6-5v4c5.52 0 10 4.48 10 10 0 2.29-.77 4.4-2.06 6.09l-1.49-1.49C17.22 17.24 18 15.4 18 13c0-3.31-2.69-6-6-6z"/></svg>
                                                    <?php e($comment['to_username']); ?>
                                                </span>
                                                <?php endif; ?>
                                                <span class="comment-time"><?php echo time_ago($comment['created_at']); ?></span>
                                            </div>
                                            <div class="comment-body">
                                                <?php echo render_content($comment['content']); ?>
                                            </div>
                                        </div>
                                        <?php if (Auth::check()): ?>
                                        <button type="button" class="comment-reply-btn" 
                                                onclick="showReplyForm(<?php echo $reply['id']; ?>, <?php echo $comment['user_id']; ?>, '<?php e($comment['username']); ?>')">
                                            回复
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <?php if ($hasMore): ?>
                                <div class="comments-expand">
                                    <button type="button" class="expand-btn" onclick="toggleComments(this, <?php echo $reply['id']; ?>)">
                                        <span class="expand-text">展开剩余 <?php echo $commentCount - $showLimit; ?> 条回复</span>
                                        <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 楼中楼回复表单 -->
                            <?php if (Auth::check()): ?>
                            <div class="reply-comment-form-wrapper" id="reply-form-<?php echo $reply['id']; ?>" style="display: none;">
                                <form method="post" action="index.php?module=post&action=replyComment" class="reply-comment-form">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="hidden" name="to_user_id" value="0" id="to-user-id-<?php echo $reply['id']; ?>">
                                    <div class="reply-input-box">
                                        <span class="reply-target" id="reply-target-<?php echo $reply['id']; ?>">回复 <?php e($reply['username']); ?>：</span>
                                        <textarea name="content" rows="2" placeholder="写下你的回复..." required></textarea>
                                    </div>
                                    <div class="reply-form-actions">
                                        <button type="button" class="btn-cancel" onclick="hideReplyForm(<?php echo $reply['id']; ?>)">取消</button>
                                        <button type="submit" class="btn-submit">发表</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination-wrapper">
                    <?php echo pagination($total, $page, REPLIES_PER_PAGE, 'index.php?module=post&action=view&id=' . $post['id'] . '&page='); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- 底部回复表单 -->
                <?php if ($post['is_locked'] && !Auth::isAdmin()): ?>
                <div class="locked-notice">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    该帖子已锁定，无法回复
                </div>
                <?php elseif (Auth::check()): ?>
                <div class="main-reply-form">
                    <div class="reply-form-header">
                        <div class="current-user">
                            <?php if (Auth::user()['avatar']): ?>
                            <img src="<?php e(Auth::user()['avatar']); ?>" alt="">
                            <?php else: ?>
                            <?php echo render_default_avatar(Auth::id(), Auth::user()['username'], 'small', 'avatar-tiny'); ?>
                            <?php endif; ?>
                            <span><?php e(Auth::user()['username']); ?></span>
                        </div>
                        <span class="reply-tip">添加一条新回复</span>
                    </div>
                    <form method="post" action="index.php?module=post&action=reply" class="reply-form">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <textarea name="content" rows="5" placeholder="请输入回复内容..." required></textarea>
                        <div class="form-actions">
                            <span class="shortcut-tip">Ctrl + Enter 快捷提交</span>
                            <button type="submit" class="btn-primary">发表回复</button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="login-notice">
                    <p>请 <a href="index.php?module=user&action=login">登录</a> 后参与讨论</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 右侧边栏 -->
            <div class="post-sidebar">
                <!-- 楼主信息卡片 -->
                <div class="sidebar-card author-card">
                    <div class="card-header">
                        <span class="card-title">楼主</span>
                    </div>
                    <div class="card-body">
                        <div class="author-profile">
                            <div class="profile-avatar">
                                <?php if ($post['avatar']): ?>
                                <img src="<?php e($post['avatar']); ?>" alt="<?php e($post['username']); ?>">
                                <?php else: ?>
                                <?php echo render_default_avatar($post['user_id'], $post['username'], 'xlarge', 'avatar-default-large'); ?>
                                <?php endif; ?>
                            </div>
                            <div class="profile-name"><?php e($post['username']); ?></div>
                            <div class="profile-stats">
                                <a href="index.php?module=user&action=profile&id=<?php echo $post['user_id']; ?>" class="stat-box">
                                    <span class="stat-num"><?php echo $authorPostCount; ?></span>
                                    <span class="stat-label">主题</span>
                                </a>
                                <a href="index.php?module=user&action=profile&id=<?php echo $post['user_id']; ?>" class="stat-box">
                                    <span class="stat-num"><?php echo $authorReplyCount; ?></span>
                                    <span class="stat-label">回复</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 帖子信息 -->
                <div class="sidebar-card post-info-card">
                    <div class="card-header">
                        <span class="card-title">帖子信息</span>
                    </div>
                    <div class="card-body">
                        <div class="info-item">
                            <span class="info-label">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-.9 2.24-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                浏览
                            </span>
                            <span class="info-value"><?php echo number_format($post['views']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/></svg>
                                回复
                            </span>
                            <span class="info-value"><?php echo $total; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label like-label">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                点赞
                            </span>
                            <span class="info-value" id="sidebar-like-count"><?php echo number_format($post['likes'] ?? 0); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label favorite-label">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                收藏
                            </span>
                            <span class="info-value" id="sidebar-favorite-count"><?php echo number_format($post['favorites'] ?? 0); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">发布</span>
                            <span class="info-value"><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></span>
                        </div>
                        <?php if ($post['last_reply_at']): ?>
                        <div class="info-item">
                            <span class="info-label">最后回复</span>
                            <span class="info-value"><?php echo time_ago($post['last_reply_at']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 回到顶部 -->
                <div class="back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/></svg>
                    回到顶部
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 显示楼中楼回复表单
function showReplyForm(replyId, toUserId, toUsername) {
    const formWrapper = document.getElementById('reply-form-' + replyId);
    const toUserIdInput = document.getElementById('to-user-id-' + replyId);
    const targetSpan = document.getElementById('reply-target-' + replyId);
    const textarea = formWrapper.querySelector('textarea');
    
    formWrapper.style.display = 'block';
    toUserIdInput.value = toUserId;
    targetSpan.textContent = '回复 ' + toUsername + '：';
    textarea.focus();
    
    // 滚动到表单
    formWrapper.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// 隐藏楼中楼回复表单
function hideReplyForm(replyId) {
    const formWrapper = document.getElementById('reply-form-' + replyId);
    formWrapper.style.display = 'none';
}

// 展开/收起楼中楼评论
function toggleComments(btn, replyId) {
    const commentsList = btn.closest('.reply-comments').querySelector('.comments-list');
    const collapsedItems = commentsList.querySelectorAll('.comment-collapsed');
    const isExpanding = btn.classList.contains('expand-btn');
    
    if (isExpanding) {
        // 展开
        collapsedItems.forEach(function(item) {
            item.classList.remove('comment-collapsed');
            item.classList.add('comment-expanded');
        });
        btn.classList.remove('expand-btn');
        btn.classList.add('collapse-btn');
        btn.querySelector('.expand-text').textContent = '收起回复';
        btn.querySelector('svg').style.transform = 'rotate(180deg)';
    } else {
        // 收起
        const limit = parseInt(commentsList.dataset.limit);
        const allItems = commentsList.querySelectorAll('.comment-item');
        allItems.forEach(function(item, index) {
            if (index >= limit) {
                item.classList.add('comment-collapsed');
                item.classList.remove('comment-expanded');
            }
        });
        btn.classList.remove('collapse-btn');
        btn.classList.add('expand-btn');
        const count = parseInt(commentsList.dataset.count);
        btn.querySelector('.expand-text').textContent = '展开剩余 ' + (count - limit) + ' 条回复';
        btn.querySelector('svg').style.transform = 'rotate(0deg)';
        
        // 滚动到列表顶部
        commentsList.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Ctrl+Enter 快捷提交
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.reply-form, .reply-comment-form').forEach(function(form) {
        const textarea = form.querySelector('textarea');
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });
        }
    });
});

// 点赞功能
function toggleLike(postId, btn) {
    <?php if (Auth::guest()): ?>
        window.location.href = 'index.php?module=user&action=login';
        return;
    <?php endif; ?>
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('csrf_token', '<?php echo csrf_token(); ?>');
    
    // 添加加载状态
    btn.classList.add('loading');
    
    fetch('index.php?module=post&action=like', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.classList.remove('loading');
        
        if (data.success) {
            const countEl = document.getElementById('like-count-' + postId);
            const sidebarCountEl = document.getElementById('sidebar-like-count');
            const textEl = btn.querySelector('.btn-text');
            
            if (data.liked) {
                btn.classList.add('active');
                btn.setAttribute('data-liked', '1');
                textEl.textContent = '已赞';
                // 添加动画效果
                btn.classList.add('animate-pop');
                setTimeout(() => btn.classList.remove('animate-pop'), 300);
            } else {
                btn.classList.remove('active');
                btn.setAttribute('data-liked', '0');
                textEl.textContent = '点赞';
            }
            
            if (countEl) {
                countEl.textContent = data.count;
                countEl.classList.add('count-update');
                setTimeout(() => countEl.classList.remove('count-update'), 300);
            }
            
            // 同步更新侧边栏数字
            if (sidebarCountEl) {
                sidebarCountEl.textContent = data.count;
            }
        } else {
            showToast(data.message || '操作失败', 'error');
        }
    })
    .catch(error => {
        btn.classList.remove('loading');
        showToast('网络错误，请稍后重试', 'error');
    });
}

// 收藏功能
function toggleFavorite(postId, btn) {
    <?php if (Auth::guest()): ?>
        window.location.href = 'index.php?module=user&action=login';
        return;
    <?php endif; ?>
    
    const formData = new FormData();
    formData.append('post_id', postId);
    formData.append('csrf_token', '<?php echo csrf_token(); ?>');
    
    // 添加加载状态
    btn.classList.add('loading');
    
    fetch('index.php?module=post&action=favorite', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.classList.remove('loading');
        
        if (data.success) {
            const countEl = document.getElementById('favorite-count-' + postId);
            const sidebarCountEl = document.getElementById('sidebar-favorite-count');
            const textEl = btn.querySelector('.btn-text');
            
            if (data.favorited) {
                btn.classList.add('active');
                btn.setAttribute('data-favorited', '1');
                textEl.textContent = '已收藏';
                // 添加动画效果
                btn.classList.add('animate-pop');
                setTimeout(() => btn.classList.remove('animate-pop'), 300);
                showToast('收藏成功', 'success');
            } else {
                btn.classList.remove('active');
                btn.setAttribute('data-favorited', '0');
                textEl.textContent = '收藏';
                showToast('已取消收藏', 'info');
            }
            
            if (countEl) {
                countEl.textContent = data.count;
                countEl.classList.add('count-update');
                setTimeout(() => countEl.classList.remove('count-update'), 300);
            }
            
            // 同步更新侧边栏数字
            if (sidebarCountEl) {
                sidebarCountEl.textContent = data.count;
            }
        } else {
            showToast(data.message || '操作失败', 'error');
        }
    })
    .catch(error => {
        btn.classList.remove('loading');
        showToast('网络错误，请稍后重试', 'error');
    });
}

// Toast 提示
function showToast(message, type = 'info') {
    // 移除已有的 toast
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification toast-' + type;
    toast.innerHTML = `
        <svg viewBox="0 0 24 24" width="18" height="18">
            ${type === 'success' 
                ? '<path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>'
                : type === 'error'
                ? '<path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>'
                : '<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>'
            }
        </svg>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    // 触发动画
    requestAnimationFrame(() => {
        toast.classList.add('show');
    });
    
    // 自动移除
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2500);
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
