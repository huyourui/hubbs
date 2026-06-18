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
                        <?php if (!empty($post['edit_count']) && $post['edit_count'] > 0): ?>
                        <span class="meta-item">
                            <span class="meta-label">编辑</span>
                            <span class="meta-value" title="已编辑 <?php echo $post['edit_count']; ?> 次">最后编辑于 <?php echo time_ago($post['last_edit_at']); ?></span>
                        </span>
                        <?php endif; ?>
                        <?php if (Auth::check() && $post['user_id'] == Auth::id()): ?>
                        <span class="meta-item">
                            <a href="index.php?module=post&action=edit&id=<?php echo $post['id']; ?>" class="edit-link">编辑帖子</a>
                        </span>
                        <span class="meta-item">
                            <a href="javascript:void(0);" onclick="if(confirm('确定要删除这个帖子吗？删除后无法恢复。')) { window.location.href='index.php?module=post&action=delete&id=<?php echo $post['id']; ?>&csrf_token=<?php echo csrf_token(); ?>'; }" class="delete-link">删除帖子</a>
                        </span>
                        <?php endif; ?>
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
                                <a href="index.php?module=user&action=profile&id=<?php echo $reply['user_id']; ?>">
                                    <?php if ($reply['avatar']): ?>
                                    <img src="<?php e($reply['avatar']); ?>" alt="<?php e($reply['username']); ?>">
                                    <?php else: ?>
                                    <?php echo render_default_avatar($reply['user_id'], $reply['username'], 'normal', 'avatar-default'); ?>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="author-info">
                                <div class="author-name"><a href="index.php?module=user&action=profile&id=<?php echo $reply['user_id']; ?>"><?php e($reply['username']); ?></a></div>
                                <div class="author-time">
                                    <?php echo time_ago($reply['created_at']); ?>
                                    <?php if (!empty($reply['edit_count']) && $reply['edit_count'] > 0): ?>
                                    <span class="edit-info" title="已编辑 <?php echo $reply['edit_count']; ?> 次">(最后编辑于 <?php echo time_ago($reply['last_edit_at']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="reply-floor">#<?php echo $index + 1; ?></div>
                        </div>
                        <div class="reply-content">
                            <div class="reply-body" id="reply-body-<?php echo $reply['id']; ?>">
                                <?php echo render_content($reply['content']); ?>
                            </div>

                            <?php if (Auth::check()): ?>
                            <div class="reply-actions">
                                <button type="button" class="action-btn reply-btn"
                                        onclick="showReplyForm(<?php echo $reply['id']; ?>, 0, '<?php e($reply['username']); ?>')">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/></svg>
                                    回复
                                </button>
                                <?php if ($reply['user_id'] == Auth::id()): ?>
                                <button type="button" class="action-btn edit-reply-btn"
                                        onclick="editReply(<?php echo $reply['id']; ?>, <?php echo $post['id']; ?>)">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    编辑
                                </button>
                                <button type="button" class="action-btn delete-reply-btn"
                                        onclick="if(confirm('确定要删除这条回复吗？')) { window.location.href='index.php?module=post&action=deleteReply&id=<?php echo $reply['id']; ?>&csrf_token=<?php echo csrf_token(); ?>'; }">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    删除
                                </button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- 楼中楼 -->
                            <?php if ($commentCount > 0): ?>
                            <div class="reply-comments">
                                <div class="comments-list" data-count="<?php echo $commentCount; ?>" data-limit="<?php echo $showLimit; ?>">
                                    <?php foreach ($comments as $cIndex => $comment): ?>
                                    <div class="comment-item <?php echo $cIndex >= $showLimit ? 'comment-collapsed' : ''; ?>" id="comment-<?php echo $comment['id']; ?>">
                                        <div class="comment-avatar">
                                            <a href="index.php?module=user&action=profile&id=<?php echo $comment['user_id']; ?>">
                                                <?php if ($comment['avatar']): ?>
                                                <img src="<?php e($comment['avatar']); ?>" alt="<?php e($comment['username']); ?>">
                                                <?php else: ?>
                                                <?php echo render_default_avatar($comment['user_id'], $comment['username'], 'tiny', 'avatar-tiny'); ?>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="comment-content">
                                            <div class="comment-header">
                                                <a href="index.php?module=user&action=profile&id=<?php echo $comment['user_id']; ?>" class="comment-author"><?php e($comment['username']); ?></a>
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

                <?php else: ?>
                <!-- 没有回复时的提示 -->
                <div class="no-replies">
                    <p>暂无回复，快来抢沙发吧！</p>
                </div>
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
                                <a href="index.php?module=user&action=profile&id=<?php echo $post['user_id']; ?>">
                                    <?php if ($post['avatar']): ?>
                                    <img src="<?php e($post['avatar']); ?>" alt="<?php e($post['username']); ?>">
                                    <?php else: ?>
                                    <?php echo render_default_avatar($post['user_id'], $post['username'], 'xlarge', 'avatar-default-large'); ?>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="profile-name">
                                <a href="index.php?module=user&action=profile&id=<?php echo $post['user_id']; ?>"><?php e($post['username']); ?></a>
                            </div>
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

// Ctrl+Enter 快捷提交 + AJAX表单提交
document.addEventListener('DOMContentLoaded', function() {
    // 主回复表单AJAX提交
    const mainReplyForm = document.querySelector('.main-reply-form .reply-form');
    if (mainReplyForm) {
        setupAjaxForm(mainReplyForm, function(data) {
            if (data.success && data.reply) {
                // 将新回复添加到回复列表末尾
                addReplyToList(data.reply);
                // 清空表单
                mainReplyForm.reset();
                // 显示成功提示
                showToast('回复成功', 'success');
                // 更新回复计数
                updateReplyCount(1);
            } else {
                showToast(data.message || '回复失败', 'error');
            }
        });
    }

    // 楼中楼回复表单AJAX提交
    document.querySelectorAll('.reply-comment-form').forEach(function(form) {
        setupAjaxForm(form, function(data) {
            if (data.success && data.comment) {
                // 将新评论添加到对应的楼中楼列表
                addCommentToList(data.comment);
                // 清空表单并隐藏
                form.reset();
                const replyId = form.querySelector('input[name="reply_id"]').value;
                hideReplyForm(replyId);
                // 显示成功提示
                showToast('回复成功', 'success');
                // 更新回复计数
                updateReplyCount(1);
            } else {
                showToast(data.message || '回复失败', 'error');
            }
        });
    });

    // 为所有回复表单绑定Ctrl+Enter快捷键
    document.querySelectorAll('.reply-form, .reply-comment-form').forEach(function(form) {
        const textarea = form.querySelector('textarea');
        if (textarea) {
            textarea.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    e.preventDefault();
                    // 触发自定义AJAX提交，而不是原生表单提交
                    if (form.dataset.ajaxSubmit) {
                        form.dispatchEvent(new Event('ajax-submit'));
                    } else {
                        form.submit();
                    }
                }
            });
        }
    });
});

/**
 * 设置表单AJAX提交
 */
function setupAjaxForm(form, callback) {
    form.dataset.ajaxSubmit = 'true';

    form.addEventListener('ajax-submit', function(e) {
        e.preventDefault();
        submitFormAjax(form, callback);
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitFormAjax(form, callback);
    });
}

/**
 * AJAX提交表单
 */
function submitFormAjax(form, callback) {
    const formData = new FormData(form);
    const action = form.getAttribute('action');

    // 添加AJAX标识
    fetch(action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        callback(data);
    })
    .catch(error => {
        showToast('网络错误，请稍后重试', 'error');
    });
}

/**
 * 添加新回复到列表
 */
function addReplyToList(reply) {
    const repliesList = document.querySelector('.replies-list');
    const noReplies = document.querySelector('.no-replies');

    // 移除"暂无回复"提示
    if (noReplies) {
        noReplies.remove();
    }

    // 如果没有回复列表容器，创建一个新的
    let listContainer = repliesList;
    if (!listContainer) {
        // 在回复表单之前插入回复列表
        const mainReplyForm = document.querySelector('.main-reply-form');
        if (mainReplyForm) {
            listContainer = document.createElement('div');
            listContainer.className = 'replies-list';
            mainReplyForm.parentNode.insertBefore(listContainer, mainReplyForm);
        }
    }

    if (!listContainer) return;

    // 计算楼层号
    const existingReplies = listContainer.querySelectorAll('.reply-item');
    const floorNumber = existingReplies.length + 1;

    // 获取当前用户信息
    const currentUserAvatar = '<?php echo Auth::check() ? (Auth::user()['avatar'] ?? '') : ''; ?>';
    const currentUserId = '<?php echo Auth::check() ? Auth::id() : 0; ?>';

    // 创建回复HTML
    const replyHtml = createReplyHtml(reply, floorNumber, currentUserAvatar, currentUserId);

    // 添加到列表末尾
    listContainer.insertAdjacentHTML('beforeend', replyHtml);

    // 滚动到新回复
    const newReply = listContainer.lastElementChild;
    if (newReply) {
        newReply.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // 添加高亮动画
        newReply.classList.add('reply-highlight');
        setTimeout(() => newReply.classList.remove('reply-highlight'), 2000);
    }
}

/**
 * 添加新评论到楼中楼列表
 */
function addCommentToList(comment) {
    const replyId = comment.reply_id;
    const replyItem = document.getElementById('reply-' + replyId);
    if (!replyItem) return;

    let commentsContainer = replyItem.querySelector('.reply-comments');

    // 如果没有楼中楼容器，创建一个
    if (!commentsContainer) {
        const replyContent = replyItem.querySelector('.reply-content');
        if (!replyContent) return;

        commentsContainer = document.createElement('div');
        commentsContainer.className = 'reply-comments';
        commentsContainer.innerHTML = '<div class="comments-list" data-count="0" data-limit="3"></div>';
        replyContent.appendChild(commentsContainer);
    }

    const commentsList = commentsContainer.querySelector('.comments-list');
    if (!commentsList) return;

    // 更新计数
    let currentCount = parseInt(commentsList.dataset.count || 0);
    currentCount++;
    commentsList.dataset.count = currentCount;

    // 判断是否需要折叠（超过3条时）
    const isCollapsed = currentCount > 3;

    // 创建评论HTML
    const commentHtml = createCommentHtml(comment, isCollapsed);

    // 添加到列表末尾
    commentsList.insertAdjacentHTML('beforeend', commentHtml);

    // 更新或创建展开按钮
    updateExpandButton(commentsContainer, currentCount);

    // 滚动到新评论
    const newComment = commentsList.lastElementChild;
    if (newComment) {
        newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        // 添加高亮动画
        newComment.classList.add('comment-highlight');
        setTimeout(() => newComment.classList.remove('comment-highlight'), 2000);
    }
}

/**
 * 创建回复HTML
 */
function createReplyHtml(reply, floorNumber, currentUserAvatar, currentUserId) {
    const avatarHtml = reply.avatar
        ? `<img src="${escapeHtml(reply.avatar)}" alt="${escapeHtml(reply.username)}">`
        : renderAvatarSvg(reply.user_id, reply.username, 'normal', 'avatar-default');

    const isOwnReply = parseInt(currentUserId) === parseInt(reply.user_id);
    const editButtons = isOwnReply ? `
        <button type="button" class="action-btn edit-reply-btn" onclick="editReply(${reply.id}, ${reply.post_id})">
            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            编辑
        </button>
        <button type="button" class="action-btn delete-reply-btn" onclick="if(confirm('确定要删除这条回复吗？')) { window.location.href='index.php?module=post&action=deleteReply&id=${reply.id}&csrf_token=${getCsrfToken()}'; }">
            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
            删除
        </button>
    ` : '';

    return `
        <div class="reply-item reply-highlight" id="reply-${reply.id}">
            <div class="reply-author">
                <div class="author-avatar">
                    <a href="index.php?module=user&action=profile&id=${reply.user_id}">
                        ${avatarHtml}
                    </a>
                </div>
                <div class="author-info">
                    <div class="author-name"><a href="index.php?module=user&action=profile&id=${reply.user_id}">${escapeHtml(reply.username)}</a></div>
                    <div class="author-time">刚刚</div>
                </div>
                <div class="reply-floor">#${floorNumber}</div>
            </div>
            <div class="reply-content">
                <div class="reply-body" id="reply-body-${reply.id}">
                    ${reply.content}
                </div>
                <div class="reply-actions">
                    <button type="button" class="action-btn reply-btn" onclick="showReplyForm(${reply.id}, 0, '${escapeHtml(reply.username)}')">
                        <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M10 9V5l-7 7 7 7v-4.1c5 0 8.5 1.6 11 5.1-1-5-4-10-11-11z"/></svg>
                        回复
                    </button>
                    ${editButtons}
                </div>
            </div>
        </div>
    `;
}

/**
 * 创建楼中楼评论HTML
 */
function createCommentHtml(comment, isCollapsed) {
    const avatarHtml = comment.avatar
        ? `<img src="${escapeHtml(comment.avatar)}" alt="${escapeHtml(comment.username)}">`
        : renderAvatarSvg(comment.user_id, comment.username, 'tiny', 'avatar-tiny');

    const replyToHtml = comment.to_user_id > 0 && comment.to_username
        ? `<span class="comment-reply-to">
            <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M8 7v4L2 6l6-5v4c5.52 0 10 4.48 10 10 0 2.29-.77 4.4-2.06 6.09l-1.49-1.49C17.22 17.24 18 15.4 18 13c0-3.31-2.69-6-6-6z"/></svg>
            ${escapeHtml(comment.to_username)}
          </span>`
        : '';

    const collapsedClass = isCollapsed ? 'comment-collapsed' : '';

    return `
        <div class="comment-item ${collapsedClass} comment-highlight" id="comment-${comment.id}">
            <div class="comment-avatar">
                <a href="index.php?module=user&action=profile&id=${comment.user_id}">
                    ${avatarHtml}
                </a>
            </div>
            <div class="comment-content">
                <div class="comment-header">
                    <a href="index.php?module=user&action=profile&id=${comment.user_id}" class="comment-author">${escapeHtml(comment.username)}</a>
                    ${replyToHtml}
                    <span class="comment-time">刚刚</span>
                </div>
                <div class="comment-body">
                    ${comment.content}
                </div>
            </div>
            <button type="button" class="comment-reply-btn" onclick="showReplyForm(${comment.reply_id}, ${comment.user_id}, '${escapeHtml(comment.username)}')">
                回复
            </button>
        </div>
    `;
}

/**
 * 更新展开按钮
 */
function updateExpandButton(commentsContainer, count) {
    let expandWrapper = commentsContainer.querySelector('.comments-expand');
    const limit = 3;

    if (count > limit) {
        if (!expandWrapper) {
            expandWrapper = document.createElement('div');
            expandWrapper.className = 'comments-expand';
            commentsContainer.appendChild(expandWrapper);
        }
        expandWrapper.innerHTML = `
            <button type="button" class="expand-btn" onclick="toggleComments(this, ${commentsContainer.closest('.reply-item').id.replace('reply-', '')})">
                <span class="expand-text">展开剩余 ${count - limit} 条回复</span>
                <svg viewBox="0 0 24 24" width="12" height="12"><path fill="currentColor" d="M7 10l5 5 5-5z"/></svg>
            </button>
        `;
    } else if (expandWrapper) {
        expandWrapper.remove();
    }
}

/**
 * 更新回复计数显示
 */
function updateReplyCount(delta) {
    // 更新帖子头部统计
    const statItems = document.querySelectorAll('.stat-item');
    statItems.forEach(function(item) {
        const text = item.textContent;
        if (text.includes('回复')) {
            const numEl = item.querySelector('.stat-num');
            if (numEl) {
                const current = parseInt(numEl.textContent.replace(/,/g, '')) || 0;
                numEl.textContent = numberFormat(current + delta);
            }
        }
    });

    // 更新回复列表头部计数
    const repliesCount = document.querySelector('.replies-count');
    if (repliesCount) {
        const current = parseInt(repliesCount.textContent) || 0;
        repliesCount.textContent = (current + delta) + ' 条回复';
    }
}

/**
 * 数字格式化（添加千分位）
 */
function numberFormat(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * HTML转义
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * 获取CSRF Token
 */
function getCsrfToken() {
    const tokenEl = document.querySelector('input[name="csrf_token"]');
    return tokenEl ? tokenEl.value : '';
}

/**
 * 渲染默认头像SVG
 */
function renderAvatarSvg(userId, username, size, className) {
    // 使用与后端相同的颜色生成逻辑
    const colors = [
        '#FF6B6B', '#4ECDC4', '#667EEA', '#F093FB', '#4FACFE',
        '#43E97B', '#FA709A', '#30CFD0', '#FF9A9E', '#FCB69F',
        '#FF8A80', '#B388FF', '#82B1FF', '#69F0AE', '#FFAB40',
        '#FF5252', '#E040FB', '#536DFE', '#40C4FF', '#AB47BC',
        '#26C6DA', '#66BB6A', '#FFCA28', '#EF5350', '#EC407A',
        '#7E57C2', '#5C6BC0', '#29B6F6', '#26A69A', '#9CCC65'
    ];
    const color = colors[parseInt(userId) % colors.length];
    const initial = username ? username.charAt(0) : '?';

    const sizeMap = { tiny: 24, small: 32, normal: 40, large: 48, xlarge: 80, xxlarge: 100 };
    const sizePx = sizeMap[size] || 40;

    return `<svg width="${sizePx}" height="${sizePx}" viewBox="0 0 40 40" class="default-avatar ${className}">
        <circle cx="20" cy="20" r="20" fill="${color}" />
        <text x="20" y="26" text-anchor="middle" fill="#fff" font-size="16" font-weight="500">${escapeHtml(initial)}</text>
    </svg>`;
}

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

// 编辑回复
function editReply(replyId, postId) {
    <?php if (Auth::guest()): ?>
        window.location.href = 'index.php?module=user&action=login';
        return;
    <?php endif; ?>

    // 获取回复内容
    fetch('index.php?module=post&action=editReply&id=' + replyId + '&post_id=' + postId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const replyBody = document.getElementById('reply-body-' + replyId);
            const originalContent = data.reply.content;

            // 创建编辑表单
            const editForm = document.createElement('form');
            editForm.className = 'edit-reply-form';
            editForm.innerHTML = `
                <textarea name="content" rows="4" required>${originalContent}</textarea>
                <div class="edit-form-actions">
                    <button type="button" class="btn-cancel" onclick="cancelEditReply(${replyId})">取消</button>
                    <button type="submit" class="btn-submit">保存</button>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            `;

            // 保存原始内容
            replyBody.dataset.originalContent = replyBody.innerHTML;
            replyBody.innerHTML = '';
            replyBody.appendChild(editForm);

            // 绑定提交事件
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(editForm);
                formData.append('post_id', postId);

                fetch('index.php?module=post&action=editReply&id=' + replyId, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => {
                    if (response.redirected) {
                        window.location.reload();
                        return;
                    }
                    return response.json();
                })
                .then(data => {
                    if (data && data.success) {
                        window.location.reload();
                    } else {
                        showToast(data ? data.message : '编辑失败', 'error');
                    }
                })
                .catch(error => {
                    window.location.reload();
                });
            });

            // 聚焦到文本框
            editForm.querySelector('textarea').focus();
        } else {
            showToast(data.message || '获取回复内容失败', 'error');
        }
    })
    .catch(error => {
        showToast('网络错误，请稍后重试', 'error');
    });
}

// 取消编辑回复
function cancelEditReply(replyId) {
    const replyBody = document.getElementById('reply-body-' + replyId);
    if (replyBody.dataset.originalContent) {
        replyBody.innerHTML = replyBody.dataset.originalContent;
        delete replyBody.dataset.originalContent;
    }
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

<style>
/* 编辑信息样式 */
.edit-info {
    color: #999;
    font-size: 12px;
    margin-left: 8px;
    cursor: help;
}

.edit-link {
    color: #ff6b6b;
    text-decoration: none;
    font-size: 13px;
}

.edit-link:hover {
    text-decoration: underline;
}

/* 编辑回复表单样式 */
.edit-reply-form {
    margin: 10px 0;
}

.edit-reply-form textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.6;
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.edit-reply-form textarea:focus {
    outline: none;
    border-color: #ff6b6b;
}

.edit-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    justify-content: flex-end;
}

.edit-form-actions .btn-cancel,
.edit-form-actions .btn-submit {
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    border: none;
}

.edit-form-actions .btn-cancel {
    background: #f5f5f5;
    color: #666;
}

.edit-form-actions .btn-cancel:hover {
    background: #e8e8e8;
}

.edit-form-actions .btn-submit {
    background: #ff6b6b;
    color: #fff;
}

.edit-form-actions .btn-submit:hover {
    background: #ff5252;
}

/* 编辑按钮样式 */
.edit-reply-btn {
    color: #666;
}

.edit-reply-btn:hover {
    color: #ff6b6b;
}

/* 新回复高亮动画 */
@keyframes replyHighlight {
    0% { background-color: rgba(255, 107, 107, 0.15); }
    100% { background-color: transparent; }
}

.reply-highlight {
    animation: replyHighlight 2s ease-out;
}

/* 新评论高亮动画 */
@keyframes commentHighlight {
    0% { background-color: rgba(255, 107, 107, 0.12); }
    100% { background-color: transparent; }
}

.comment-highlight {
    animation: commentHighlight 2s ease-out;
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
