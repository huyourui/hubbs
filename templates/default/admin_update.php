<?php
/**
 * HuBBS - 后台系统更新页面
 */
$pageTitle = '系统更新';
$action = 'update';
include __DIR__ . '/admin_header.php';
?>

<div class="admin-dashboard">
        <div class="admin-header">
            <h1>系统更新</h1>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php e($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php e($success); ?></div>
        <?php endif; ?>

        <?php if (isset($checkResult['error']) && $checkResult['error']): ?>
        <div class="alert alert-warning">
            <strong>检查更新失败：</strong><?php e($checkResult['error']); ?>
            <p style="margin-top: 10px; font-size: 14px;">
                可能的原因：<br>
                1. 服务器无法连接 Gitee API<br>
                2. Gitee 仓库没有创建 Release<br>
                3. 网络连接问题
            </p>
        </div>
        <?php endif; ?>

        <!-- 版本信息卡片 -->
        <div class="update-cards">
            <div class="update-card">
                <div class="card-icon local">📦</div>
                <div class="card-info">
                    <span class="card-label">当前版本</span>
                    <span class="card-version">v<?php e($localVersion); ?></span>
                </div>
            </div>
            
            <div class="update-card <?php echo $hasUpdate ? 'has-update' : ''; ?>">
                <div class="card-icon remote">🚀</div>
                <div class="card-info">
                    <span class="card-label">最新版本</span>
                    <span class="card-version">
                        <?php if ($remoteVersion): ?>
                            v<?php e($remoteVersion); ?>
                            <?php if ($hasUpdate): ?>
                                <span class="update-badge">有新版本</span>
                            <?php else: ?>
                                <span class="latest-badge">已是最新</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="unknown">无法获取</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <?php if ($hasUpdate && $releaseInfo): ?>
        <!-- 更新内容 -->
        <div class="update-section">
            <h3>更新内容</h3>
            <div class="update-body">
                <?php echo nl2br(h($releaseInfo['body'] ?? '暂无更新说明')); ?>
            </div>
        </div>
        
        <!-- 更新操作 -->
        <div class="update-actions">
            <?php if ($writable['writable']): ?>
                <form method="post" class="update-form" onsubmit="return confirmUpdate();">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn-primary btn-large" id="update-btn">
                        <span class="btn-text">立即更新</span>
                        <span class="btn-loading" style="display: none;">更新中...</span>
                    </button>
                </form>
            <?php else: ?>
                <div class="writable-warning">
                    <h4>⚠️ 无法更新，请检查以下问题：</h4>
                    <ul>
                        <?php foreach ($writable['issues'] as $issue): ?>
                        <li><?php e($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 系统检测 -->
        <div class="check-section">
            <h3>系统检测</h3>
            <div class="check-list">
                <div class="check-item <?php echo $writable['writable'] ? 'pass' : 'fail'; ?>">
                    <span class="check-icon"><?php echo $writable['writable'] ? '✓' : '✗'; ?></span>
                    <span class="check-label">目录权限</span>
                </div>
                <div class="check-item <?php echo class_exists('ZipArchive') ? 'pass' : 'fail'; ?>">
                    <span class="check-icon"><?php echo class_exists('ZipArchive') ? '✓' : '✗'; ?></span>
                    <span class="check-label">ZipArchive 扩展</span>
                </div>
                <div class="check-item <?php echo function_exists('curl_init') ? 'pass' : 'fail'; ?>">
                    <span class="check-icon"><?php echo function_exists('curl_init') ? '✓' : '✗'; ?></span>
                    <span class="check-label">cURL 扩展</span>
                </div>
            </div>
        </div>
        
        <!-- 备份管理 -->
        <div class="backup-section">
            <h3>备份管理</h3>
            <?php if (!empty($backups)): ?>
            <div class="backup-list">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>备份时间</th>
                            <th>版本</th>
                            <th>大小</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): 
                            $version = str_replace(['backup-', '-'], ['', ' '], substr($backup['name'], 0, 20));
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', $backup['time']); ?></td>
                            <td><?php e($version); ?></td>
                            <td><?php echo Utils::formatSize($backup['size']); ?></td>
                            <td>
                                <form method="post" style="display: inline;" onsubmit="return confirm('确定要回滚到这个版本吗？当前数据将会丢失！');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="rollback">
                                    <input type="hidden" name="backup" value="<?php e($backup['name']); ?>">
                                    <button type="submit" class="btn-small btn-warning">回滚</button>
                                </form>
                                <form method="post" style="display: inline; margin-left: 5px;" onsubmit="return confirm('确定要删除这个备份吗？删除后无法恢复！');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_backup">
                                    <input type="hidden" name="backup" value="<?php e($backup['name']); ?>">
                                    <button type="submit" class="btn-small btn-danger">删除</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="no-backup">暂无备份</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.admin-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    gap: 20px;
}

.admin-content {
    flex: 1;
    background: #fff;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.admin-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.admin-header h1 {
    margin: 0;
    font-size: 24px;
}

/* 版本卡片 */
.update-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.update-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 25px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid transparent;
}

.update-card.has-update {
    background: #fff2f0;
    border-color: #ff6b6b;
}

.card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
}

.card-icon.local {
    background: #e3f2fd;
}

.card-icon.remote {
    background: #f3e5f5;
}

.card-info {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.card-label {
    font-size: 14px;
    color: #666;
}

.card-version {
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.update-badge {
    background: #ff6b6b;
    color: #fff;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.latest-badge {
    background: #52c41a;
    color: #fff;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.unknown {
    color: #999;
    font-size: 16px;
}

/* 更新内容 */
.update-section {
    margin-bottom: 30px;
}

.update-section h3 {
    margin-bottom: 15px;
    font-size: 18px;
}

.update-body {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    line-height: 1.8;
}

/* 更新操作 */
.update-actions {
    margin-bottom: 30px;
}

.btn-large {
    padding: 15px 40px;
    font-size: 16px;
}

.writable-warning {
    background: #fff2f0;
    border: 1px solid #ffccc7;
    padding: 20px;
    border-radius: 8px;
}

.writable-warning h4 {
    margin: 0 0 10px 0;
    color: #ff4d4f;
}

.writable-warning ul {
    margin: 0;
    padding-left: 20px;
}

.writable-warning li {
    color: #666;
    margin: 5px 0;
}

/* 系统检测 */
.check-section {
    margin-bottom: 30px;
}

.check-section h3 {
    margin-bottom: 15px;
    font-size: 18px;
}

.check-list {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.check-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #f8f9fa;
    border-radius: 20px;
}

.check-item.pass {
    background: #f6ffed;
    color: #52c41a;
}

.check-item.fail {
    background: #fff2f0;
    color: #ff4d4f;
}

.check-icon {
    font-weight: bold;
}

/* 备份管理 */
.backup-section h3 {
    margin-bottom: 15px;
    font-size: 18px;
}

.no-backup {
    color: #999;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.btn-warning {
    background: #faad14;
    color: #fff;
}

.btn-warning:hover {
    background: #d48806;
}

.btn-danger {
    background: #ff4d4f;
    color: #fff;
}

.btn-danger:hover {
    background: #ff7875;
}

/* 响应式 */
@media (max-width: 768px) {
    .update-cards {
        grid-template-columns: 1fr;
    }
    
    .admin-container {
        flex-direction: column;
    }
}
</style>

<script>
function confirmUpdate() {
    return confirm('确定要更新系统吗？\n\n更新前会自动备份当前版本，如果更新失败可以回滚。');
}

// 更新按钮加载状态
document.querySelector('.update-form')?.addEventListener('submit', function() {
    const btn = document.getElementById('update-btn');
    btn.querySelector('.btn-text').style.display = 'none';
    btn.querySelector('.btn-loading').style.display = 'inline';
    btn.disabled = true;
});
</script>

</div>

<?php include __DIR__ . '/footer.php'; ?>
