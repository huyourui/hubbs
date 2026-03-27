<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
$categoryTree = getCategoryTree();
$parentCategories = getParentCategories();

$currentParentId = null;
if ($categoryId) {
    foreach ($categoryTree as $cat) {
        if ($cat['id'] == $categoryId) {
            $currentParentId = $categoryId;
            break;
        }
        foreach ($cat['children'] ?? [] as $child) {
            if ($child['id'] == $categoryId) {
                $currentParentId = $cat['id'];
                break 2;
            }
        }
    }
}

$extraStyles = <<<CSS
.category-filter { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
.category-filter .badge { cursor: pointer; transition: all 0.15s ease-in-out; }
.category-filter .badge:hover { opacity: 0.85; }
.category-children { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #dee2e6; }
.category-children .badge { font-weight: normal; }
CSS;
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="category-filter mb-3">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="badge <?php echo !$categoryId ? 'bg-primary text-white' : 'bg-light text-dark text-decoration-none'; ?>">全部</a>
                    <?php foreach ($categoryTree as $cat): ?>
                        <?php if (!empty($cat['children'])): ?>
                            <a href="<?php echo SITE_URL; ?>/index.php?category=<?php echo $cat['id']; ?>" class="badge <?php echo ($currentParentId == $cat['id'] || $categoryId == $cat['id']) ? 'bg-primary text-white' : 'bg-light text-dark text-decoration-none'; ?>">
                                <?php echo escape($cat['name']); ?>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/index.php?category=<?php echo $cat['id']; ?>" class="badge <?php echo $categoryId == $cat['id'] ? 'bg-primary text-white' : 'bg-light text-dark text-decoration-none'; ?>">
                                <?php echo escape($cat['name']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <?php if ($currentParentId): ?>
                        <?php foreach ($categoryTree as $cat): ?>
                            <?php if ($cat['id'] == $currentParentId && !empty($cat['children'])): ?>
                                <div class="category-children w-100">
                                    <?php foreach ($cat['children'] as $child): ?>
                                        <a href="<?php echo SITE_URL; ?>/index.php?category=<?php echo $child['id']; ?>" class="badge <?php echo $categoryId == $child['id'] ? 'bg-primary text-white' : 'bg-light text-dark text-decoration-none'; ?>">
                                            <?php echo escape($child['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($announcements)): ?>
                    <div class="announcements mb-3">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item" style="background-color: <?php echo $announcement['bg_color']; ?>; padding: 1rem; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                                <div class="d-flex align-items-start">
                                    <span class="badge bg-warning text-dark me-2 flex-shrink-0">公告</span>
                                    <div class="announcement-content"><?php echo $announcement['content']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($posts)): ?>
                    <div class="text-center py-5 text-muted">
                        <p class="mb-0">暂无帖子</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <div class="post-item <?php echo $post['is_sticky'] ? 'sticky' : ''; ?> <?php echo !empty($post['is_digest']) ? 'digest' : ''; ?>">
                            <div class="post-title">
                                <?php if ($post['is_sticky']): ?>
                                    <span class="badge bg-danger me-1">置顶</span>
                                <?php endif; ?>
                                <?php if (!empty($post['is_digest'])): ?>
                                    <span class="badge bg-success me-1">精华</span>
                                <?php endif; ?>
                                <?php if ($post['is_locked']): ?>
                                    <span class="badge bg-secondary me-1">锁定</span>
                                <?php endif; ?>
                                <a href="<?php echo SITE_URL; ?>/post.php?id=<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></a>
                            </div>
                            <div class="post-meta">
                                <span>作者: <a href="<?php echo SITE_URL; ?>/profile.php?user=<?php echo $post['user_id']; ?>"><?php echo escape($post['username']); ?></a></span>
                                <span>分类: <?php echo escape($post['category_name'] ?? '未分类'); ?></span>
                                <span>回复: <?php echo $post['comment_count']; ?></span>
                                <span>浏览: <?php echo $post['views']; ?></span>
                                <span>发布: <?php echo formatTime($post['created_at']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">上一页</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php if ($i == $page): ?>
                                <span class="page-link"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $categoryId ? '&category=' . $categoryId : ''; ?>">下一页</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card sidebar-card">
            <div class="card-header">
                <h5 class="mb-0">站点统计</h5>
            </div>
            <div class="card-body">
                <?php $todayStats = getTodayStats(); ?>
                <div class="row text-center">
                    <div class="col-3">
                        <div class="stats-item">
                            <strong class="text-primary"><?php echo $todayStats['total']; ?></strong>
                            <small class="text-muted">今日</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stats-item">
                            <strong><?php echo getPostCount(); ?></strong>
                            <small class="text-muted">帖子</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stats-item">
                            <strong><?php echo getCommentCount(); ?></strong>
                            <small class="text-muted">评论</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="stats-item">
                            <strong><?php echo getUserCount(); ?></strong>
                            <small class="text-muted">用户</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
