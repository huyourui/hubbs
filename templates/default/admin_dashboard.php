<?php
/**
 * HuBBS - 后台管理概览模板
 */
$pageTitle = '管理概览';
$action = 'dashboard';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-dashboard">
    <h1 class="admin-title">管理概览</h1>

    <div class="stats-grid admin-stats">
        <div class="stat-card admin-stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['users']; ?></span>
                <span class="stat-label">总用户</span>
            </div>
            <div class="stat-today">+<?php echo $stats['today_users']; ?> 今日</div>
        </div>

        <div class="stat-card admin-stat-card">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['posts']; ?></span>
                <span class="stat-label">总帖子</span>
            </div>
            <div class="stat-today">+<?php echo $stats['today_posts']; ?> 今日</div>
        </div>

        <div class="stat-card admin-stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['replies']; ?></span>
                <span class="stat-label">总回复</span>
            </div>
        </div>

        <div class="stat-card admin-stat-card">
            <div class="stat-icon">📂</div>
            <div class="stat-info">
                <span class="stat-value"><?php echo $stats['forums']; ?></span>
                <span class="stat-label">板块数</span>
            </div>
        </div>
    </div>

    <div class="admin-quick-links">
        <h3>快捷操作</h3>
        <div class="quick-links-grid">
            <a href="index.php?module=admin&action=posts" class="quick-link">
                <span class="quick-link-icon">📝</span>
                <span class="quick-link-text">管理帖子</span>
            </a>
            <a href="index.php?module=admin&action=users" class="quick-link">
                <span class="quick-link-icon">👥</span>
                <span class="quick-link-text">管理用户</span>
            </a>
            <a href="index.php?module=admin&action=forums" class="quick-link">
                <span class="quick-link-icon">📂</span>
                <span class="quick-link-text">管理板块</span>
            </a>
            <a href="index.php" class="quick-link">
                <span class="quick-link-icon">🏠</span>
                <span class="quick-link-text">访问前台</span>
            </a>
        </div>
    </div>

    <div class="admin-info-grid">
        <!-- 服务器信息 -->
        <div class="admin-info-card">
            <h3 class="info-card-title">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
                服务器信息
            </h3>
            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">操作系统</span>
                    <span class="info-value"><?php e($serverInfo['os']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Web服务器</span>
                    <span class="info-value"><?php e($serverInfo['server_software']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">PHP版本</span>
                    <span class="info-value"><?php e($serverInfo['php_version']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">MySQL版本</span>
                    <span class="info-value"><?php e($serverInfo['mysql_version']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">上传限制</span>
                    <span class="info-value"><?php e($serverInfo['max_upload']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">执行超时</span>
                    <span class="info-value"><?php e($serverInfo['max_execution']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">内存限制</span>
                    <span class="info-value"><?php e($serverInfo['memory_limit']); ?></span>
                </div>
            </div>
        </div>

        <!-- 程序版本信息 -->
        <div class="admin-info-card">
            <h3 class="info-card-title">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                程序版本
            </h3>
            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">程序名称</span>
                    <span class="info-value"><?php e($appInfo['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">当前版本</span>
                    <span class="info-value version-badge"><?php e($appInfo['version']); ?></span>
                </div>
                <?php if ($hasUpdate): ?>
                <div class="info-row update-available">
                    <span class="info-label">最新版本</span>
                    <span class="info-value">
                        <span class="version-badge new"><?php e($remoteVersion); ?></span>
                        <a href="index.php?module=admin&action=update" class="update-link">立即更新 →</a>
                    </span>
                </div>
                <?php else: ?>
                <div class="info-row">
                    <span class="info-label">最新版本</span>
                    <span class="info-value version-badge latest">已是最新</span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">发布日期</span>
                    <span class="info-value"><?php e($appInfo['release_date']); ?></span>
                </div>
            </div>
            <div class="info-footer">
                <a href="https://gitee.com/youruihu/hubbs" target="_blank" rel="noopener" class="info-link">
                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12 2C6.477 2 2 6.477 2 12c0 4.42 2.87 8.17 6.84 9.5.5.08.66-.23.66-.5v-1.69c-2.77.6-3.36-1.34-3.36-1.34-.46-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.87 1.52 2.34 1.07 2.91.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.92 0-1.11.38-2 1.03-2.71-.1-.25-.45-1.29.1-2.64 0 0 .84-.27 2.75 1.02.79-.22 1.65-.33 2.5-.33.85 0 1.71.11 2.5.33 1.91-1.29 2.75-1.02 2.75-1.02.55 1.35.2 2.39.1 2.64.65.71 1.03 1.6 1.03 2.71 0 3.82-2.34 4.66-4.57 4.91.36.31.69.92.69 1.85V21c0 .27.16.59.67.5C19.14 20.16 22 16.42 22 12A10 10 0 0 0 12 2z"/></svg>
                    Gitee 仓库
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* 信息卡片网格 */
.admin-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.admin-info-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.info-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin: 0 0 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.info-card-title svg {
    color: #ff6b6b;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed #f0f0f0;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 13px;
    color: #666;
}

.info-value {
    font-size: 13px;
    color: #333;
    font-weight: 500;
}

.version-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
    color: #fff;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.info-footer {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.info-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #666;
    text-decoration: none;
    font-size: 13px;
    transition: color 0.2s;
}

.info-link:hover {
    color: #ff6b6b;
}

/* 更新相关样式 */
.update-available {
    background: #fff2f0;
    margin: 0 -10px;
    padding: 8px 10px !important;
    border-radius: 6px;
}

.version-badge.new {
    background: linear-gradient(135deg, #52c41a 0%, #73d13d 100%);
    margin-right: 10px;
}

.version-badge.latest {
    background: #f0f0f0;
    color: #666;
}

.update-link {
    color: #ff6b6b;
    text-decoration: none;
    font-weight: 500;
    font-size: 12px;
}

.update-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .admin-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/admin_footer.php'; ?>
