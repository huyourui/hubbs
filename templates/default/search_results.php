<?php
/**
 * HuBBS - 搜索结果页面
 * 
 * @package HuBBS
 * @version 1.8.2
 */
$pageTitle = !empty($keyword) ? '搜索：' . $keyword : '搜索';
include __DIR__ . '/header.php';
?>

<div class="search-results-page">
    <div class="search-header">
        <h1 class="search-title">
            <?php if (!empty($keyword)): ?>
                "<?php e($keyword); ?>" 的搜索结果
                <span class="search-count">共 <?php echo $total; ?> 条</span>
            <?php else: ?>
                搜索帖子
            <?php endif; ?>
        </h1>
        
        <!-- 搜索类型筛选 -->
        <div class="search-filters">
            <a href="<?php echo base_url('index.php?module=search&action=index&keyword=' . urlencode($keyword) . '&type=all'); ?>" 
               class="filter-btn <?php echo $type === 'all' ? 'active' : ''; ?>">全部</a>
            <a href="<?php echo base_url('index.php?module=search&action=index&keyword=' . urlencode($keyword) . '&type=title'); ?>" 
               class="filter-btn <?php echo $type === 'title' ? 'active' : ''; ?>">标题</a>
            <a href="<?php echo base_url('index.php?module=search&action=index&keyword=' . urlencode($keyword) . '&type=content'); ?>" 
               class="filter-btn <?php echo $type === 'content' ? 'active' : ''; ?>">内容</a>
        </div>
    </div>
    
    <?php if (!empty($keyword)): ?>
        <?php if (empty($results)): ?>
            <!-- 无搜索结果 -->
            <div class="search-empty">
                <div class="empty-icon">
                    <svg viewBox="0 0 24 24" width="64" height="64"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
                </div>
                <h3>未找到相关结果</h3>
                <p>建议：检查关键词拼写，或尝试其他关键词</p>
            </div>
        <?php else: ?>
            <!-- 搜索结果列表 -->
            <div class="search-results-list">
                <?php foreach ($results as $post): ?>
                <div class="search-result-item">
                    <div class="result-header">
                        <a href="<?php echo base_url('index.php?module=post&action=view&id=' . $post['id']); ?>" class="result-title">
                            <?php echo $post['title_highlighted']; ?>
                        </a>
                        <?php if ($post['is_top']): ?>
                            <span class="badge badge-top">置顶</span>
                        <?php endif; ?>
                        <?php if ($post['is_essence']): ?>
                            <span class="badge badge-essence">精华</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="result-content">
                        <?php echo $post['content_highlighted']; ?>
                    </div>
                    
                    <div class="result-meta">
                        <span class="meta-item">
                            <img src="<?php echo get_avatar_url($post['user_id'], $post['avatar']); ?>" alt="" class="meta-avatar">
                            <?php e($post['username']); ?>
                        </span>
                        <?php if (!empty($post['forum_name'])): ?>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
                            <?php e($post['forum_name']); ?>
                        </span>
                        <?php endif; ?>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                            <?php echo time_ago($post['created_at']); ?>
                        </span>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                            <?php echo $post['views']; ?> 浏览
                        </span>
                        <span class="meta-item">
                            <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M21 6h-2v9H6v2c0 .55.45 1 1 1h11l4 4V7c0-.55-.45-1-1-1zm-4 6V3c0-.55-.45-1-1-1H3c-.55 0-1 .45-1 1v14l4-4h10c.55 0 1-.45 1-1z"/></svg>
                            <?php echo $post['replies']; ?> 回复
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                <a href="<?php echo base_url('index.php?module=search&action=index&keyword=' . urlencode($keyword) . '&type=' . $type . '&page=' . ($page - 1)); ?>" class="page-btn">上一页</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                <span class="page-btn active"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="<?php echo base_url('index.php?module=search&action=index&keyword=' . urlencode($keyword) . '&type=' . $type . '&page=' . $i); ?>" class="page-btn"><?php echo $i; ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <a href="<?php echo base_url('index.php?module=search&action=index&keyword=' . urlencode($keyword) . '&type=' . $type . '&page=' . ($page + 1)); ?>" class="page-btn">下一页</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <!-- 搜索提示 -->
        <div class="search-tips">
            <h3>搜索提示</h3>
            <ul>
                <li>输入关键词搜索帖子标题和内容</li>
                <li>可以使用筛选器精确搜索标题或内容</li>
                <li>支持多关键词搜索，用空格分隔</li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
.search-results-page {
    max-width: 900px;
    margin: 0 auto;
    padding: 20px;
}

.search-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.search-title {
    font-size: 22px;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
}

.search-count {
    font-size: 14px;
    font-weight: normal;
    color: #999;
    margin-left: 10px;
}

.search-filters {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 20px;
    background: #f5f5f5;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: #e8e8e8;
}

.filter-btn.active {
    background: #ff6b6b;
    color: #fff;
}

/* 搜索结果列表 */
.search-results-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.search-result-item {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: transform 0.2s, box-shadow 0.2s;
}

.search-result-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.result-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
}

.result-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    text-decoration: none;
    line-height: 1.4;
}

.result-title:hover {
    color: #ff6b6b;
}

.result-title mark {
    background: #fff3cd;
    color: #856404;
    padding: 0 2px;
    border-radius: 2px;
}

.badge {
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.badge-top {
    background: #ff6b6b;
    color: #fff;
}

.badge-essence {
    background: #ffc107;
    color: #333;
}

.result-content {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.result-content mark {
    background: #fff3cd;
    color: #856404;
    padding: 0 2px;
    border-radius: 2px;
}

.result-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
    font-size: 13px;
    color: #999;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.meta-avatar {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    object-fit: cover;
}

/* 空结果 */
.search-empty {
    text-align: center;
    padding: 60px 20px;
    color: #999;
}

.empty-icon {
    margin-bottom: 20px;
    opacity: 0.5;
}

.search-empty h3 {
    font-size: 18px;
    color: #666;
    margin-bottom: 10px;
}

/* 搜索提示 */
.search-tips {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    text-align: center;
}

.search-tips h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 15px;
}

.search-tips ul {
    list-style: none;
    padding: 0;
    color: #666;
}

.search-tips li {
    padding: 8px 0;
}

.search-tips li::before {
    content: "•";
    color: #ff6b6b;
    margin-right: 8px;
}

/* 分页 */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
}

.page-btn {
    padding: 8px 14px;
    border-radius: 8px;
    background: #fff;
    border: 1px solid #e0e0e0;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.page-btn:hover {
    border-color: #ff6b6b;
    color: #ff6b6b;
}

.page-btn.active {
    background: #ff6b6b;
    border-color: #ff6b6b;
    color: #fff;
}

/* 响应式 */
@media (max-width: 768px) {
    .search-results-page {
        padding: 15px;
    }
    
    .search-title {
        font-size: 18px;
    }
    
    .search-filters {
        flex-wrap: wrap;
    }
    
    .result-header {
        flex-wrap: wrap;
    }
    
    .result-title {
        font-size: 16px;
        width: 100%;
    }
    
    .result-meta {
        gap: 10px;
    }
}
</style>

<?php include __DIR__ . '/footer.php'; ?>
