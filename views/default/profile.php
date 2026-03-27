<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
$GLOBALS['extraStyles'] = <<<CSS
.profile-content { display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
.avatar-wrapper { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 3rem; font-weight: 700; margin: 0 auto 1rem; overflow: hidden; }
.avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }
@media (max-width: 768px) { .profile-content { grid-template-columns: 1fr; } }
CSS;
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo escape($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="profile-content">
    <div class="card">
        <div class="card-body text-center">
            <div class="avatar-wrapper">
                <?php if ($user['avatar']): ?>
                    <img src="<?php echo escape($user['avatar']); ?>" alt="Avatar">
                <?php else: ?>
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                <?php endif; ?>
            </div>
            <h4 class="mb-1"><?php echo escape($user['username']); ?></h4>
            <span class="badge bg-info me-1"><?php echo getUserLevelName($user['points'] ?? 0); ?></span>
            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?> mb-3">
                <?php echo $user['role'] === 'admin' ? '管理员' : '普通用户'; ?>
            </span>
            <?php if (!empty($user['bio'])): ?>
                <p class="text-muted small mb-3"><?php echo escape($user['bio']); ?></p>
            <?php endif; ?>
            <hr>
            <div class="row text-center mt-3">
                <div class="col-4">
                    <strong class="d-block fs-4 text-primary"><?php echo number_format($user['points'] ?? 0); ?></strong>
                    <small class="text-muted"><?php echo getPointsName(); ?></small>
                </div>
                <div class="col-4">
                    <strong class="d-block fs-4"><?php echo $postCount; ?></strong>
                    <small class="text-muted">帖子</small>
                </div>
                <div class="col-4">
                    <strong class="d-block fs-4"><?php echo $commentCount; ?></strong>
                    <small class="text-muted">评论</small>
                </div>
            </div>
            <small class="text-muted d-block mt-3">注册于 <?php echo date('Y-m-d', strtotime($user['created_at'])); ?></small>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                <?php if ($isOwnProfile): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">个人资料</button>
                </li>
                <?php endif; ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo !$isOwnProfile ? 'active' : ''; ?>" id="posts-tab" data-bs-toggle="tab" data-bs-target="#posts" type="button" role="tab">帖子</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button" role="tab">评论</button>
                </li>
                <?php if ($isOwnProfile): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="favorites-tab" data-bs-toggle="tab" data-bs-target="#favorites" type="button" role="tab">收藏</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="points-tab" data-bs-toggle="tab" data-bs-target="#points" type="button" role="tab"><?php echo getPointsName(); ?>记录</button>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="profileTabContent">
                <?php if ($isOwnProfile): ?>
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <h5 class="mb-3">修改资料</h5>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">头像</label>
                            <input type="file" name="avatar" accept="image/*" class="form-control">
                            <small class="text-muted">支持 JPG, PNG, GIF，最大 2MB</small>
                        </div>
                        <div class="mb-3">
                            <label for="bio" class="form-label">个人简介</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3" placeholder="介绍一下自己..."><?php echo escape($user['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">当前密码（修改密码时需要）</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">新密码（留空则不修改）</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">确认新密码</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="tab-pane fade <?php echo !$isOwnProfile ? 'show active' : ''; ?>" id="posts" role="tabpanel">
                    <h5 class="mb-3"><?php echo $isOwnProfile ? '我的帖子' : escape($user['username']) . ' 的帖子'; ?></h5>
                    <?php if (empty($myPosts)): ?>
                        <p class="text-center text-muted py-4">暂无帖子</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($myPosts as $post): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo SITE_URL; ?>/post.php?id=<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></a>
                                    <small class="text-muted d-block">
                                        <?php echo escape($post['category_name'] ?? '未分类'); ?> · 
                                        <?php echo formatTime($post['created_at']); ?> · 
                                        <?php echo $post['views']; ?> 浏览
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="comments" role="tabpanel">
                    <h5 class="mb-3"><?php echo $isOwnProfile ? '我的评论' : escape($user['username']) . ' 的评论'; ?></h5>
                    <?php if (empty($myComments)): ?>
                        <p class="text-center text-muted py-4">暂无评论</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($myComments as $comment): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo SITE_URL; ?>/post.php?id=<?php echo $comment['post_id']; ?>#comment-<?php echo $comment['id']; ?>">
                                        <?php echo escape(mb_substr($comment['content'], 0, 100)); ?><?php echo mb_strlen($comment['content']) > 100 ? '...' : ''; ?>
                                    </a>
                                    <small class="text-muted d-block">
                                        回复: <?php echo escape($comment['post_title']); ?> · 
                                        <?php echo formatTime($comment['created_at']); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <?php if ($isOwnProfile): ?>
                <div class="tab-pane fade" id="favorites" role="tabpanel">
                    <h5 class="mb-3">我的收藏</h5>
                    <?php if (empty($myFavorites)): ?>
                        <p class="text-center text-muted py-4">暂无收藏</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($myFavorites as $post): ?>
                                <li class="list-group-item">
                                    <a href="<?php echo SITE_URL; ?>/post.php?id=<?php echo $post['id']; ?>"><?php echo escape($post['title']); ?></a>
                                    <small class="text-muted d-block">
                                        作者: <?php echo escape($post['username']); ?> · 
                                        <?php echo escape($post['category_name'] ?? '未分类'); ?> · 
                                        收藏于 <?php echo formatTime($post['favorited_at']); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="tab-pane fade" id="points" role="tabpanel">
                    <h5 class="mb-3"><?php echo getPointsName(); ?>记录</h5>
                    <?php if (empty($pointLogs)): ?>
                        <p class="text-center text-muted py-4">暂无<?php echo getPointsName(); ?>记录</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>时间</th>
                                        <th>类型</th>
                                        <th><?php echo getPointsName(); ?></th>
                                        <th>余额</th>
                                        <th>备注</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pointLogs as $log): ?>
                                        <tr>
                                            <td><small><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></small></td>
                                            <td><?php echo escape(getPointActionLabel($log['action'])); ?></td>
                                            <td>
                                                <span class="<?php echo $log['points'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $log['points'] > 0 ? '+' : ''; ?><?php echo $log['points']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($log['balance']); ?></td>
                                            <td><small class="text-muted"><?php echo escape($log['remark'] ?? ''); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">共 <?php echo $pointLogCount; ?> 条记录</small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
