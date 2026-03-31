<?php
/**
 * HuBBS - 个人中心模板（新版）
 */
$isOwnProfile = $isOwnProfile ?? true;
$pageTitle = $isOwnProfile ? '个人中心' : $user['username'] . ' 的主页';
include __DIR__ . '/header.php';
?>

<div class="profile-page">
    <div class="container">
        <!-- 个人信息卡片 -->
        <div class="profile-header-card">
            <div class="profile-cover">
                <div class="cover-gradient"></div>
            </div>
            <div class="profile-main">
                <div class="profile-avatar-wrap">
                    <?php if ($user['avatar']): ?>
                    <img src="<?php e($user['avatar']); ?>" alt="<?php e($user['username']); ?>" class="profile-avatar">
                    <?php else: ?>
                    <?php echo render_default_avatar($user['id'], $user['username'], 'xlarge', 'profile-avatar'); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h1 class="profile-name"><?php e($user['username']); ?></h1>
                    <p class="profile-email"><?php e($user['email']); ?></p>
                    <p class="profile-meta">
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            注册于 <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?>
                        </span>
                    </p>
                </div>
                <div class="profile-stats-row">
                    <div class="stat-item">
                        <span class="stat-num"><?php echo $postCount; ?></span>
                        <span class="stat-label">主题</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <span class="stat-num"><?php echo $replyCount; ?></span>
                        <span class="stat-label">回复</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 导航 -->
        <div class="profile-tabs">
            <a href="index.php?module=user&action=profile&id=<?php echo $user['id']; ?>&tab=posts" class="tab-item <?php echo $tab === 'posts' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                <?php echo $isOwnProfile ? '我的主题' : 'TA的主题'; ?>
                <span class="tab-count"><?php echo $postCount; ?></span>
            </a>
            <a href="index.php?module=user&action=profile&id=<?php echo $user['id']; ?>&tab=replies" class="tab-item <?php echo $tab === 'replies' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                <?php echo $isOwnProfile ? '我的回复' : 'TA的回复'; ?>
                <span class="tab-count"><?php echo $replyCount; ?></span>
            </a>
            <a href="index.php?module=user&action=profile&id=<?php echo $user['id']; ?>&tab=favorites" class="tab-item <?php echo $tab === 'favorites' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                <?php echo $isOwnProfile ? '我的收藏' : 'TA的收藏'; ?>
                <span class="tab-count"><?php echo $favoriteCount; ?></span>
            </a>
            <a href="index.php?module=user&action=profile&id=<?php echo $user['id']; ?>&tab=likes" class="tab-item <?php echo $tab === 'likes' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                <?php echo $isOwnProfile ? '我的点赞' : 'TA的点赞'; ?>
                <span class="tab-count"><?php echo $likeCount; ?></span>
            </a>
        </div>

        <!-- 内容区域 -->
        <div class="profile-content">
            <?php if ($tab === 'posts'): ?>
            <!-- 我的主题 -->
            <div class="content-section">
                <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="currentColor" d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                    <p>还没有发布过主题</p>
                    <a href="index.php?module=post&action=create" class="btn-primary">发布第一个主题</a>
                </div>
                <?php else: ?>
                <div class="post-list">
                    <?php foreach ($posts as $post): ?>
                    <div class="post-item">
                        <div class="post-main">
                            <h3 class="post-title">
                                <?php if ($post['is_top']): ?><span class="badge badge-top">置顶</span><?php endif; ?>
                                <?php if ($post['is_essence']): ?><span class="badge badge-essence">精华</span><?php endif; ?>
                                <a href="index.php?module=post&action=view&id=<?php echo $post['id']; ?>"><?php e($post['title']); ?></a>
                            </h3>
                            <div class="post-meta">
                                <span class="meta-forum"><?php e($post['forum_name']); ?></span>
                                <span class="meta-dot">·</span>
                                <span class="meta-time"><?php echo time_ago($post['created_at']); ?></span>
                                <span class="meta-stats">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                    <?php echo $post['views']; ?>
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/></svg>
                                    <?php echo $post['replies']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($postTotalPages > 1): ?>
                <div class="pagination">
                    <?php echo pagination($postCount, $page, 10, 'index.php?module=user&action=profile&id=' . $user['id'] . '&tab=posts&page='); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php elseif ($tab === 'replies'): ?>
            <!-- 我的回复 -->
            <div class="content-section">
                <?php if (empty($replies)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="currentColor" d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                    <p>还没有发表过回复</p>
                </div>
                <?php else: ?>
                <div class="reply-list">
                    <?php foreach ($replies as $reply): ?>
                    <div class="reply-item">
                        <div class="reply-content">
                            <div class="reply-header">
                                <span class="reply-label">回复了主题</span>
                                <a href="index.php?module=post&action=view&id=<?php echo $reply['post_id']; ?>" class="reply-post-title"><?php e($reply['post_title']); ?></a>
                            </div>
                            <div class="reply-body">
                                <?php echo nl2br(h($reply['content'])); ?>
                            </div>
                            <div class="reply-meta">
                                <span class="meta-forum"><?php e($reply['forum_name']); ?></span>
                                <span class="meta-dot">·</span>
                                <span class="meta-time"><?php echo time_ago($reply['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($replyTotalPages > 1): ?>
                <div class="pagination">
                    <?php echo pagination($replyCount, $page, 10, 'index.php?module=user&action=profile&id=' . $user['id'] . '&tab=replies&page='); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php elseif ($tab === 'favorites'): ?>
            <!-- 我的收藏 -->
            <div class="content-section">
                <?php if (empty($favorites)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                    <p>还没有收藏过帖子</p>
                    <a href="index.php" class="btn-primary">去浏览帖子</a>
                </div>
                <?php else: ?>
                <div class="post-list favorite-list">
                    <?php foreach ($favorites as $post): ?>
                    <div class="post-item favorite-item">
                        <div class="post-main">
                            <h3 class="post-title">
                                <?php if ($post['is_top']): ?><span class="badge badge-top">置顶</span><?php endif; ?>
                                <?php if ($post['is_essence']): ?><span class="badge badge-essence">精华</span><?php endif; ?>
                                <a href="index.php?module=post&action=view&id=<?php echo $post['id']; ?>"><?php e($post['title']); ?></a>
                            </h3>
                            <div class="post-meta">
                                <span class="meta-author">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    <?php e($post['author_name']); ?>
                                </span>
                                <span class="meta-dot">·</span>
                                <span class="meta-forum"><?php e($post['forum_name']); ?></span>
                                <span class="meta-dot">·</span>
                                <span class="meta-time"><?php echo time_ago($post['created_at']); ?></span>
                            </div>
                        </div>
                        <div class="favorite-meta">
                            <span class="favorited-time">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                                收藏于 <?php echo time_ago($post['favorited_at']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($favoriteTotalPages > 1): ?>
                <div class="pagination">
                    <?php echo pagination($favoriteCount, $page, 10, 'index.php?module=user&action=profile&id=' . $user['id'] . '&tab=favorites&page='); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php elseif ($tab === 'likes'): ?>
            <!-- 我的点赞 -->
            <div class="content-section">
                <?php if (empty($likes)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    <p>还没有点赞过帖子</p>
                    <a href="index.php" class="btn-primary">去浏览帖子</a>
                </div>
                <?php else: ?>
                <div class="post-list like-list">
                    <?php foreach ($likes as $post): ?>
                    <div class="post-item like-item">
                        <div class="post-main">
                            <h3 class="post-title">
                                <?php if ($post['is_top']): ?><span class="badge badge-top">置顶</span><?php endif; ?>
                                <?php if ($post['is_essence']): ?><span class="badge badge-essence">精华</span><?php endif; ?>
                                <a href="index.php?module=post&action=view&id=<?php echo $post['id']; ?>"><?php e($post['title']); ?></a>
                            </h3>
                            <div class="post-meta">
                                <span class="meta-author">
                                    <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    <?php e($post['author_name']); ?>
                                </span>
                                <span class="meta-dot">·</span>
                                <span class="meta-forum"><?php e($post['forum_name']); ?></span>
                                <span class="meta-dot">·</span>
                                <span class="meta-time"><?php echo time_ago($post['created_at']); ?></span>
                            </div>
                        </div>
                        <div class="like-meta">
                            <span class="liked-time">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                点赞于 <?php echo time_ago($post['liked_at']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($likeTotalPages > 1): ?>
                <div class="pagination">
                    <?php echo pagination($likeCount, $page, 10, 'index.php?module=user&action=profile&id=' . $user['id'] . '&tab=likes&page='); ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* 个人中心页面 */
.profile-page {
    padding: 20px 0 40px;
    background: #f6f6f6;
    min-height: calc(100vh - 60px);
}

/* 个人信息卡片 */
.profile-header-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

.profile-cover {
    height: 120px;
    background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%);
    position: relative;
}

.cover-gradient {
    display: none;
}

.profile-main {
    padding: 0 30px 30px;
    position: relative;
    display: flex;
    align-items: flex-end;
    gap: 24px;
}

.profile-avatar-wrap {
    margin-top: -50px;
    flex-shrink: 0;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid #fff;
    box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    object-fit: cover;
}

.profile-info {
    flex: 1;
    padding-bottom: 8px;
}

.profile-name {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin: 0 0 8px;
}

.profile-email {
    font-size: 14px;
    color: #666;
    margin: 0 0 8px;
}

.profile-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 13px;
    color: #999;
}

.profile-meta .meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.profile-meta svg {
    opacity: 0.6;
}

/* 统计信息 */
.profile-stats-row {
    display: flex;
    align-items: center;
    gap: 24px;
    padding-bottom: 8px;
}

.profile-stats-row .stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.profile-stats-row .stat-num {
    font-size: 24px;
    font-weight: 700;
    color: #ff6b6b;
}

.profile-stats-row .stat-label {
    font-size: 13px;
    color: #999;
}

.stat-divider {
    width: 1px;
    height: 40px;
    background: #e8e8e8;
}

/* Tab 导航 */
.profile-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    background: #fff;
    border-radius: 12px;
    padding: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.tab-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 8px;
    color: #666;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: all 0.2s;
}

.tab-item:hover {
    background: #f5f5f5;
    color: #333;
}

.tab-item.active {
    background: #ff6b6b;
    color: #fff;
}

.tab-count {
    font-size: 12px;
    padding: 2px 8px;
    background: rgba(0,0,0,0.1);
    border-radius: 10px;
}

.tab-item.active .tab-count {
    background: rgba(255,255,255,0.2);
}

/* 内容区域 */
.profile-content {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
}

.content-section {
    padding: 20px;
}

/* 空状态 */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-state svg {
    color: #ddd;
    margin-bottom: 16px;
}

.empty-state p {
    margin: 0 0 20px;
    font-size: 15px;
}

/* 主题列表 */
.post-list {
    display: flex;
    flex-direction: column;
}

.post-item {
    padding: 20px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.2s;
}

.post-item:last-child {
    border-bottom: none;
}

.post-item:hover {
    background: #fafafa;
}

.post-title {
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.post-title a {
    color: #333;
    text-decoration: none;
}

.post-title a:hover {
    color: #ff6b6b;
}

.post-title .badge {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #999;
    flex-wrap: wrap;
}

.meta-forum {
    color: #778087;
}

.meta-dot {
    color: #ccc;
}

.meta-stats {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-left: auto;
}

.meta-stats svg {
    opacity: 0.5;
    margin-right: 2px;
}

/* 回复列表 */
.reply-list {
    display: flex;
    flex-direction: column;
}

.reply-item {
    padding: 20px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.2s;
}

.reply-item:last-child {
    border-bottom: none;
}

.reply-item:hover {
    background: #fafafa;
}

.reply-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.reply-label {
    font-size: 13px;
    color: #999;
}

.reply-post-title {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    text-decoration: none;
}

.reply-post-title:hover {
    color: #ff6b6b;
}

.reply-body {
    font-size: 14px;
    line-height: 1.8;
    color: #555;
    margin-bottom: 10px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    word-break: break-all;
}

.reply-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #999;
}

/* 收藏列表 */
.favorite-list .favorite-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 20px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.2s;
}

.favorite-list .favorite-item:last-child {
    border-bottom: none;
}

.favorite-list .favorite-item:hover {
    background: #fafafa;
}

.favorite-list .post-main {
    flex: 1;
    min-width: 0;
}

.favorite-list .post-title {
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.favorite-list .post-title a {
    color: #333;
    text-decoration: none;
}

.favorite-list .post-title a:hover {
    color: #ff6b6b;
}

.favorite-list .post-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #999;
    flex-wrap: wrap;
}

.favorite-list .meta-author {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #666;
}

.favorite-meta {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    margin-left: 16px;
}

.favorited-time {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #ffa726;
    background: #fff8e1;
    padding: 4px 10px;
    border-radius: 12px;
    white-space: nowrap;
}

.favorited-time svg {
    color: #ffa726;
}

/* 点赞列表 */
.like-list .like-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 20px;
    border-bottom: 1px solid #f5f5f5;
    transition: background 0.2s;
}

.like-list .like-item:last-child {
    border-bottom: none;
}

.like-list .like-item:hover {
    background: #fafafa;
}

.like-list .post-main {
    flex: 1;
    min-width: 0;
}

.like-list .post-title {
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.like-list .post-title a {
    color: #333;
    text-decoration: none;
}

.like-list .post-title a:hover {
    color: #ff6b6b;
}

.like-list .post-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #999;
    flex-wrap: wrap;
}

.like-list .meta-author {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #666;
}

.like-meta {
    display: flex;
    align-items: center;
    flex-shrink: 0;
    margin-left: 16px;
}

.liked-time {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: #ff6b6b;
    background: #fff2f0;
    padding: 4px 10px;
    border-radius: 12px;
    white-space: nowrap;
}

.liked-time svg {
    color: #ff6b6b;
}

/* 分页 */
.pagination {
    padding: 20px;
    border-top: 1px solid #f5f5f5;
}

/* 响应式适配 */
@media (max-width: 768px) {
    .profile-page {
        padding: 10px 0 30px;
    }
    
    .profile-cover {
        height: 80px;
    }
    
    .profile-main {
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 0 20px 24px;
        gap: 16px;
    }
    
    .profile-avatar-wrap {
        margin-top: -40px;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
    }
    
    .profile-name {
        font-size: 20px;
    }
    
    .profile-stats-row {
        width: 100%;
        justify-content: center;
        padding-bottom: 0;
        border-top: 1px solid #f0f0f0;
        padding-top: 16px;
    }
    
    .profile-tabs {
        margin: 0 10px 15px;
    }
    
    .tab-item {
        flex: 1;
        justify-content: center;
        padding: 10px 16px;
        font-size: 14px;
    }
    
    .profile-content {
        margin: 0 10px;
    }
    
    .content-section {
        padding: 15px;
    }
    
    .post-item,
    .reply-item {
        padding: 15px;
    }
    
    .post-title {
        font-size: 15px;
    }
    
    .meta-stats {
        width: 100%;
        margin-left: 0;
        margin-top: 8px;
    }
}

@media (max-width: 480px) {
    .profile-avatar {
        width: 70px;
        height: 70px;
    }
    
    .profile-name {
        font-size: 18px;
    }
    
    .tab-item svg {
        display: none;
    }
    
    .post-meta,
    .reply-meta {
        font-size: 12px;
    }
    
    /* 收藏列表响应式 */
    .favorite-list .favorite-item {
        flex-direction: column;
        gap: 12px;
    }

    .favorite-meta {
        margin-left: 0;
        width: 100%;
    }

    .favorited-time {
        width: fit-content;
    }

    /* 点赞列表响应式 */
    .like-list .like-item {
        flex-direction: column;
        gap: 12px;
    }

    .like-meta {
        margin-left: 0;
        width: 100%;
    }

    .liked-time {
        width: fit-content;
    }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
