<?php
/**
 * HuBBS - 帖子列表模板（三栏布局）
 */
$pageTitle = $forumId > 0 ? ($forums[array_search($forumId, array_column($forums, 'id'))]['name'] ?? '帖子列表') : '';
include __DIR__ . '/header.php';
?>

<div class="three-column">
    <!-- 左侧：板块信息 -->
    <aside class="sidebar-left">
        <div class="card">
            <div class="card-header">
                <h3>板块</h3>
            </div>
            <div class="card-body">
                <ul class="forum-list">
                    <li class="forum-item<?php echo $forumId == 0 ? ' active' : ''; ?>">
                        <a href="index.php">
                            <span class="forum-icon">📋</span>
                            <span class="forum-name">全部</span>
                        </a>
                    </li>
                    <?php foreach ($forums as $forum): ?>
                    <!-- 一级分类 -->
                    <li class="forum-item forum-parent<?php echo $forumId == $forum['id'] ? ' active' : ''; ?>">
                        <a href="index.php?forum=<?php echo $forum['id']; ?>">
                            <span class="forum-icon"><?php echo getForumIcon($forum['icon']); ?></span>
                            <span class="forum-name"><?php e($forum['name']); ?></span>
                            <span class="forum-count"><?php echo $forum['post_count']; ?></span>
                        </a>
                    </li>
                    <!-- 二级分类 -->
                    <?php if (isset($children[$forum['id']])): ?>
                        <?php foreach ($children[$forum['id']] as $child): ?>
                        <li class="forum-item forum-child<?php echo $forumId == $child['id'] ? ' active' : ''; ?>">
                            <a href="index.php?forum=<?php echo $child['id']; ?>">
                                <span class="forum-icon"><?php echo getForumIcon($child['icon']); ?></span>
                                <span class="forum-name"><?php e($child['name']); ?></span>
                                <span class="forum-count"><?php echo $child['post_count']; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </aside>

    <!-- 中间：帖子列表 -->
    <div class="main-content">
        <div class="content-header">
            <h2><?php echo $forumId > 0 ? e($forums[array_search($forumId, array_column($forums, 'id'))]['name'] ?? '全部帖子') : '最新'; ?></h2>
            <?php if (Auth::check()): ?>
            <a href="index.php?module=post&action=create" class="btn-primary">+ 发布新帖</a>
            <?php endif; ?>
        </div>

        <div class="post-list-v2ex">
            <?php if (empty($posts)): ?>
            <div class="empty-state">
                <p>暂无帖子</p>
                <?php if (Auth::check()): ?>
                <a href="index.php?module=post&action=create" class="btn-primary">发布第一个帖子</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                <div class="post-item-v2ex<?php echo $post['is_top'] ? ' post-top' : ''; ?><?php echo $post['is_essence'] ? ' post-essence' : ''; ?>">
                    <div class="post-main-v2ex">
                        <div class="post-avatar-v2ex">
                            <?php if ($post['avatar']): ?>
                            <img src="<?php e($post['avatar']); ?>" alt="<?php e($post['username']); ?>">
                            <?php else: ?>
                            <?php echo render_default_avatar($post['user_id'], $post['username'], 'normal', 'avatar-v2ex-default'); ?>
                            <?php endif; ?>
                        </div>
                        <div class="post-content-v2ex">
                            <div class="post-title-v2ex">
                                <?php if ($post['is_top']): ?><span class="badge badge-top">置顶</span><?php endif; ?>
                                <?php if ($post['is_essence']): ?><span class="badge badge-essence">精华</span><?php endif; ?>
                                <?php if ($post['is_locked']): ?><span class="badge badge-locked">锁定</span><?php endif; ?>
                                <a href="index.php?module=post&action=view&id=<?php echo $post['id']; ?>" target="_blank"><?php e($post['title']); ?></a>
                            </div>
                            <?php
                            // 显示帖子摘要
                            $showExcerpt = ($settings['post_list_show_excerpt'] ?? '0') === '1';
                            if ($showExcerpt && !empty($post['content'])):
                                $excerptLength = intval($settings['post_list_excerpt_length'] ?? 100);
                                $excerpt = strip_tags($post['content']);
                                if (mb_strlen($excerpt) > $excerptLength):
                                    $excerpt = mb_substr($excerpt, 0, $excerptLength) . '...';
                                endif;
                            ?>
                            <div class="post-excerpt-v2ex"><?php e($excerpt); ?></div>
                            <?php endif; ?>
                            <div class="post-meta-v2ex">
                                <span class="meta-author"><?php e($post['username']); ?></span>
                                <span class="meta-dot">·</span>
                                <span class="meta-forum"><?php e($post['forum_name']); ?></span>
                                <span class="meta-dot">·</span>
                                <span class="meta-time"><?php echo time_ago($post['created_at']); ?></span>
                                <?php if ($post['replies'] > 0): ?>
                                <span class="meta-reply">
                                    <span class="meta-dot">·</span>
                                    最后回复 <?php echo time_ago($post['last_reply_at']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="post-stats-v2ex">
                        <span class="stat-count" title="浏览">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <?php echo $post['views']; ?>
                        </span>
                        <?php if ($post['replies'] > 0): ?>
                        <span class="stat-replies has-replies" title="回复">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            <?php echo $post['replies']; ?>
                        </span>
                        <?php else: ?>
                        <span class="stat-replies no-replies" title="回复">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                            </svg>
                            <?php echo $post['replies']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <?php 
            $url = 'index.php?' . ($forumId > 0 ? "forum=$forumId&" : '') . 'page=';
            echo pagination($total, $page, POSTS_PER_PAGE, $url); 
            ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 右侧：网站信息 -->
    <aside class="sidebar-right">
        <!-- 站点统计 -->
        <div class="card">
            <div class="card-header">
                <h3>站点数据</h3>
            </div>
            <div class="card-body">
                <?php
                $db = DB::getInstance();
                $totalPosts = $db->count('posts');
                $totalUsers = $db->count('users');
                $onlineUsers = 1; // 简化处理
                ?>
                <div class="stats-grid">
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $totalPosts; ?></span>
                        <span class="stat-label">主题</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $totalUsers; ?></span>
                        <span class="stat-label">用户</span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number"><?php echo $onlineUsers; ?></span>
                        <span class="stat-label">在线</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 最新用户 -->
        <div class="card">
            <div class="card-header">
                <h3>最新用户</h3>
            </div>
            <div class="card-body">
                <div class="user-avatars">
                    <?php foreach ($latestUsers as $u): ?>
                    <a href="index.php?module=user&action=profile&id=<?php echo $u['id']; ?>" class="user-avatar-item" title="<?php e($u['username']); ?>">
                        <?php if ($u['avatar']): ?>
                        <img src="<?php e($u['avatar']); ?>" alt="<?php e($u['username']); ?>">
                        <?php else: ?>
                        <?php echo render_default_avatar($u['id'], $u['username'], 'small', 'avatar-small'); ?>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 友情链接 -->
        <?php
        $friendLinks = $db->fetchAll("SELECT * FROM {$db->table('links')} WHERE is_visible = 1 ORDER BY sort_order ASC, id ASC LIMIT 20");
        if (!empty($friendLinks)):
        ?>
        <div class="card">
            <div class="card-header">
                <h3>友情链接</h3>
            </div>
            <div class="card-body">
                <ul class="link-list">
                    <?php foreach ($friendLinks as $link): ?>
                    <li>
                        <a href="<?php e($link['url']); ?>" target="_blank" rel="noopener" title="<?php e($link['description'] ?? ''); ?>">
                            <?php e($link['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </aside>
</div>

<?php
include __DIR__ . '/footer.php';

// 辅助函数：获取板块图标
function getForumIcon($icon) {
    // 如果为空，返回默认图标
    if (empty($icon)) {
        return '📁';
    }
    
    // 预设的文字标识映射
    $icons = [
        'chat' => '💬',
        'help' => '❓',
        'message' => '📝',
        'star' => '⭐',
        'book' => '📚',
        'flag' => '🚩',
    ];
    
    // 如果是预设的文字标识，返回对应的 emoji
    if (isset($icons[$icon])) {
        return $icons[$icon];
    }
    
    // 检查是否包含 emoji（通过检测字符的 Unicode 范围）
    // emoji 通常位于多字节字符范围
    if (preg_match('/[\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F100}-\x{1F1FF}]|[\x{1F200}-\x{1F2FF}]|[\x{1F600}-\x{1F64F}]|[\x{1F680}-\x{1F6FF}]|[\x{1F900}-\x{1F9FF}]/u', $icon)) {
        return $icon;
    }
    
    // 其他情况返回默认图标
    return '📁';
}
?>
