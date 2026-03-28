<?php
/**
 * HuBBS - An Open Source Forum System
 * 
 * @author  古雨月田
 * @QQ      281900864
 * @website https://huyourui.com
 * @license MIT License
 */
$extraStyles = <<<CSS
.admin-sidebar { position: sticky; top: 1rem; }
.admin-sidebar .nav-link { border-radius: 0.375rem; margin-bottom: 0.25rem; }
.admin-sidebar .nav-link.active { background-color: var(--bs-primary); color: #fff; }
.admin-sidebar .nav-link:hover { background-color: #e9ecef; }
.admin-sidebar .nav-link.active:hover { background-color: var(--bs-primary); }
@media (max-width: 992px) { .admin-sidebar { position: static; margin-bottom: 1rem; } }
.table-responsive { overflow-x: auto; overflow-y: visible; }
.table-responsive .dropdown-menu { position: absolute !important; z-index: 1050; }
.table-responsive > .table { margin-bottom: 0; }
CSS;
?>

<div class="row">
    <div class="col-lg-3">
        <div class="card admin-sidebar">
            <div class="card-body">
                <h5 class="mb-3">管理菜单</h5>
                <nav class="nav flex-column">
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=dashboard" class="nav-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                        仪表盘
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=posts" class="nav-link <?php echo $tab === 'posts' ? 'active' : ''; ?>">
                        帖子管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=users" class="nav-link <?php echo $tab === 'users' ? 'active' : ''; ?>">
                        用户管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=categories" class="nav-link <?php echo $tab === 'categories' ? 'active' : ''; ?>">
                        分类管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=links" class="nav-link <?php echo $tab === 'links' ? 'active' : ''; ?>">
                        友情链接
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=announcements" class="nav-link <?php echo $tab === 'announcements' ? 'active' : ''; ?>">
                        公告管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=levels" class="nav-link <?php echo $tab === 'levels' ? 'active' : ''; ?>">
                        等级管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=points" class="nav-link <?php echo $tab === 'points' ? 'active' : ''; ?>">
                        <?php echo getPointsName(); ?>管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=email" class="nav-link <?php echo $tab === 'email' ? 'active' : ''; ?>">
                        邮件管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=invite" class="nav-link <?php echo $tab === 'invite' ? 'active' : ''; ?>">
                        邀请码管理
                    </a>
                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=settings" class="nav-link <?php echo $tab === 'settings' ? 'active' : ''; ?>">
                        系统设置
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card">
            <div class="card-body">
                <?php if ($tab === 'dashboard'): ?>
                    <h4 class="mb-4">仪表盘</h4>
                    
                    <div class="row mb-4">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($dashboardData['totalUsers']); ?></h3>
                                    <small>用户总数</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($dashboardData['totalPosts']); ?></h3>
                                    <small>帖子总数</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($dashboardData['totalComments']); ?></h3>
                                    <small>评论总数</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($dashboardData['totalViews']); ?></h3>
                                    <small>总浏览量</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($dashboardData['updateCheck']['has_update'])): ?>
                    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <strong><i class="bi bi-arrow-up-circle"></i> 发现新版本！</strong>
                            当前版本：<?php echo escape($dashboardData['updateCheck']['current_version']); ?> → 
                            最新版本：<strong><?php echo escape($dashboardData['updateCheck']['latest_version']); ?></strong>
                            <?php if (!empty($dashboardData['updateCheck']['release_info']['published_at'])): ?>
                                <small class="text-muted ms-2">发布于 <?php echo date('Y-m-d', strtotime($dashboardData['updateCheck']['release_info']['published_at'])); ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="<?php echo SITE_URL; ?>/pages/admin.php?do_update=1" class="btn btn-warning btn-sm" onclick="return confirm('确定要更新系统吗？更新过程中可能会短暂影响网站访问。')">
                                <i class="bi bi-download"></i> 立即更新
                            </a>
                            <?php if (!empty($dashboardData['updateCheck']['release_info']['html_url'])): ?>
                                <a href="<?php echo escape($dashboardData['updateCheck']['release_info']['html_url']); ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                                    查看详情
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted">本周新增帖子</h6>
                                    <h4 class="mb-0"><?php echo number_format($dashboardData['weekPosts']); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted">本周新增评论</h6>
                                    <h4 class="mb-0"><?php echo number_format($dashboardData['weekComments']); ?></h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-muted">本周新增用户</h6>
                                    <h4 class="mb-0"><?php echo number_format($dashboardData['weekUsers']); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-3">热门帖子</h5>
                            <ul class="list-group">
                                <?php if (empty($dashboardData['hotPosts'])): ?>
                                    <li class="list-group-item text-muted">暂无帖子</li>
                                <?php else: ?>
                                    <?php foreach ($dashboardData['hotPosts'] as $post): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['id']; ?>" target="_blank">
                                                <?php echo escape(mb_substr($post['title'], 0, 30)); ?><?php echo mb_strlen($post['title']) > 30 ? '...' : ''; ?>
                                            </a>
                                            <span class="badge bg-secondary"><?php echo number_format($post['views']); ?> 浏览</span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h5 class="mb-3">最新用户</h5>
                            <ul class="list-group">
                                <?php if (empty($dashboardData['newUsers'])): ?>
                                    <li class="list-group-item text-muted">暂无用户</li>
                                <?php else: ?>
                                    <?php foreach ($dashboardData['newUsers'] as $user): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <a href="<?php echo SITE_URL; ?>/pages/profile.php?user=<?php echo $user['id']; ?>" target="_blank">
                                                <?php echo escape($user['username']); ?>
                                            </a>
                                            <small class="text-muted"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">系统信息</h5>
                    <table class="table table-sm table-bordered">
                        <tbody>
                            <tr>
                                <td width="150">HuBBS 版本</td>
                                <td>
                                    <?php echo escape($dashboardData['hubbsVersion']); ?>
                                    <?php if (!empty($dashboardData['updateCheck']['has_update'])): ?>
                                        <span class="badge bg-warning text-dark ms-1">有更新</span>
                                    <?php elseif (isset($dashboardData['updateCheck']['git_sync'])): ?>
                                        <?php if ($dashboardData['updateCheck']['git_sync']['synced']): ?>
                                            <span class="badge bg-success ms-1">最新</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark ms-1">有更新</span>
                                        <?php endif; ?>
                                    <?php elseif (isset($dashboardData['updateCheck']['error'])): ?>
                                        <span class="badge bg-secondary ms-1" title="<?php echo escape($dashboardData['updateCheck']['error']); ?>">检查失败</span>
                                    <?php else: ?>
                                        <span class="badge bg-success ms-1">最新</span>
                                    <?php endif; ?>
                                </td>
                                <td width="150">服务器时间</td>
                                <td><?php echo escape($dashboardData['serverTime']); ?></td>
                            </tr>
                            <tr>
                                <td>PHP 版本</td>
                                <td><?php echo escape($dashboardData['phpVersion']); ?></td>
                                <td>数据库版本</td>
                                <td><?php echo escape($dashboardData['mysqlVersion']); ?></td>
                            </tr>
                            <tr>
                                <td>Web 服务器</td>
                                <td><?php echo escape($dashboardData['serverSoftware']); ?></td>
                                <td>操作系统</td>
                                <td><?php echo escape($dashboardData['serverOS']); ?></td>
                            </tr>
                            <tr>
                                <td>上传限制</td>
                                <td><?php echo escape($dashboardData['maxUploadSize']); ?></td>
                                <td>POST限制</td>
                                <td><?php echo escape($dashboardData['maxPostSize']); ?></td>
                            </tr>
                            <tr>
                                <td>内存限制</td>
                                <td><?php echo escape($dashboardData['memoryLimit']); ?></td>
                                <td>服务器名</td>
                                <td><?php echo escape($dashboardData['serverName']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/pages/admin.php?clear_cache=1" class="btn btn-outline-secondary btn-sm" onclick="return confirm('确定要清理所有缓存吗？')">
                            <i class="bi bi-trash"></i> 清理缓存
                        </a>
                    </div>

                <?php elseif ($tab === 'posts'): ?>
                    <h4 class="mb-4">帖子管理</h4>
                    <form id="batchDeleteForm" method="POST" action="<?php echo SITE_URL; ?>/pages/admin.php?tab=posts">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <div class="mb-3">
                            <button type="submit" name="batch_delete_posts" value="1" class="btn btn-danger" onclick="return confirm('确定要删除选中的帖子吗？此操作不可恢复！')">
                                <i class="bi bi-trash"></i> 批量删除选中
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
                                        <th>ID</th>
                                        <th>标题</th>
                                        <th>作者</th>
                                        <th>分类</th>
                                        <th>IP地址</th>
                                        <th>地区</th>
                                        <th>状态</th>
                                        <th>创建时间</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td><input type="checkbox" name="post_ids[]" value="<?php echo $post['id']; ?>"></td>
                                            <td><?php echo $post['id']; ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['id']; ?>">
                                                    <?php echo escape(mb_substr($post['title'], 0, 30)); ?><?php echo mb_strlen($post['title']) > 30 ? '...' : ''; ?>
                                                </a>
                                            </td>
                                            <td><?php echo escape($post['username']); ?></td>
                                            <td><?php echo escape($post['category_name'] ?? '未分类'); ?></td>
                                            <td><small class="text-muted"><?php echo escape($post['ip_address'] ?? '-'); ?></small></td>
                                            <td><small class="text-muted"><?php echo parseIpAddress($post['ip_address'] ?? ''); ?></small></td>
                                            <td>
                                                <?php if ($post['is_sticky']): ?>
                                                    <span class="badge bg-warning">置顶</span>
                                                <?php endif; ?>
                                                <?php if (!empty($post['is_digest'])): ?>
                                                    <span class="badge bg-success">精华</span>
                                                <?php endif; ?>
                                                <?php if ($post['is_locked']): ?>
                                                    <span class="badge bg-danger">锁定</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($post['created_at'])); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        操作
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['id']; ?>" target="_blank">查看帖子</a></li>
                                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/edit.php?id=<?php echo $post['id']; ?>">编辑帖子</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_sticky=<?php echo $post['id']; ?>"><?php echo $post['is_sticky'] ? '取消置顶' : '设为置顶'; ?></a></li>
                                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_digest=<?php echo $post['id']; ?>"><?php echo !empty($post['is_digest']) ? '取消精华' : '设为精华'; ?></a></li>
                                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_lock=<?php echo $post['id']; ?>"><?php echo $post['is_locked'] ? '解锁帖子' : '锁定帖子'; ?></a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/pages/admin.php?delete_post=<?php echo $post['id']; ?>" onclick="return confirm('确定要删除这篇帖子吗？')">删除帖子</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <script>
                    document.getElementById('selectAll').addEventListener('change', function() {
                        var checkboxes = document.querySelectorAll('input[name="post_ids[]"]');
                        for (var i = 0; i < checkboxes.length; i++) {
                            checkboxes[i].checked = this.checked;
                        }
                    });
                    </script>

                <?php elseif ($tab === 'users'): ?>
                    <h4 class="mb-4">用户管理</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>用户名</th>
                                    <th>邮箱</th>
                                    <th>角色</th>
                                    <th>注册时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo escape($user['username']); ?></td>
                                        <td><?php echo escape($user['email']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                                <?php echo $user['role'] === 'admin' ? '管理员' : '用户'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_admin=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                    <?php echo $user['role'] === 'admin' ? '取消管理员' : '设为管理员'; ?>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_user=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此用户吗？')">删除</a>
                                            <?php else: ?>
                                                <span class="text-muted">当前用户</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($tab === 'categories'): ?>
                    <h4 class="mb-4">分类管理</h4>
                    
                    <?php 
                    $parentCategories = getParentCategories();
                    $categoryTree = getCategoryTree();
                    ?>
                    
                    <?php if ($editCategory): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">编辑分类</h5>
                                <form method="POST" action="">
                                    <input type="hidden" name="category_id" value="<?php echo $editCategory['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <input type="text" name="category_name" value="<?php echo escape($editCategory['name']); ?>" class="form-control" placeholder="分类名称" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="category_description" value="<?php echo escape($editCategory['description'] ?? ''); ?>" class="form-control" placeholder="分类描述">
                                        </div>
                                        <div class="col-md-2">
                                            <select name="parent_id" class="form-select">
                                                <option value="">作为一级分类</option>
                                                <?php foreach ($parentCategories as $pCat): ?>
                                                    <?php if ($pCat['id'] != $editCategory['id']): ?>
                                                        <option value="<?php echo $pCat['id']; ?>" <?php echo ($editCategory['parent_id'] ?? null) == $pCat['id'] ? 'selected' : ''; ?>>
                                                            <?php echo escape($pCat['name']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="allowed_users" value="<?php echo escape($editCategory['allowed_users'] ?? ''); ?>" class="form-control" placeholder="允许发布的用户ID">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="sort_order" value="<?php echo $editCategory['sort_order']; ?>" class="form-control" placeholder="排序">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" name="edit_category" class="btn btn-primary w-100">保存</button>
                                        </div>
                                    </div>
                                    <small class="text-muted">允许发布的用户ID：留空表示所有用户可发布，多个用户ID用英文逗号隔开，如：1,2,3</small>
                                </form>
                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=categories" class="btn btn-outline-secondary btn-sm mt-2">取消编辑</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" class="row g-3 mb-4">
                            <div class="col-md-2">
                                <input type="text" name="category_name" class="form-control" placeholder="分类名称" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="category_description" class="form-control" placeholder="分类描述（可选）">
                            </div>
                            <div class="col-md-2">
                                <select name="parent_id" class="form-select">
                                    <option value="">作为一级分类</option>
                                    <?php foreach ($parentCategories as $pCat): ?>
                                        <option value="<?php echo $pCat['id']; ?>"><?php echo escape($pCat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="allowed_users" class="form-control" placeholder="允许发布的用户ID">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" name="add_category" class="btn btn-primary w-100">添加分类</button>
                            </div>
                        </form>
                        <small class="text-muted d-block mb-3">允许发布的用户ID：留空表示所有用户可发布，多个用户ID用英文逗号隔开，如：1,2,3</small>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>名称</th>
                                    <th>描述</th>
                                    <th>类型</th>
                                    <th>发布权限</th>
                                    <th>可发帖</th>
                                    <th>排序</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categoryTree as $cat): ?>
                                    <tr class="table-primary">
                                        <td><?php echo $cat['id']; ?></td>
                                        <td><strong><?php echo escape($cat['name']); ?></strong></td>
                                        <td><?php echo escape($cat['description'] ?? '-'); ?></td>
                                        <td><span class="badge bg-primary">一级分类</span></td>
                                        <td>
                                            <?php if (!empty($cat['allowed_users'])): ?>
                                                <span class="badge bg-info" title="用户ID: <?php echo escape($cat['allowed_users']); ?>">限制用户</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">所有用户</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($cat['children'])): ?>
                                                <span class="text-success">是</span>
                                            <?php else: ?>
                                                <span class="text-muted">否（有子分类）</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $cat['sort_order']; ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=categories&edit_category=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-warning">编辑</a>
                                            <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_category=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此分类吗？')">删除</a>
                                        </td>
                                    </tr>
                                    <?php if (!empty($cat['children'])): ?>
                                        <?php foreach ($cat['children'] as $child): ?>
                                            <tr>
                                                <td><?php echo $child['id']; ?></td>
                                                <td style="padding-left: 2rem;">└ <?php echo escape($child['name']); ?></td>
                                                <td><?php echo escape($child['description'] ?? '-'); ?></td>
                                                <td><span class="badge bg-secondary">二级分类</span></td>
                                                <td>
                                                    <?php if (!empty($child['allowed_users'])): ?>
                                                        <span class="badge bg-info" title="用户ID: <?php echo escape($child['allowed_users']); ?>">限制用户</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">所有用户</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="text-success">是</span></td>
                                                <td><?php echo $child['sort_order']; ?></td>
                                                <td>
                                                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=categories&edit_category=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-warning">编辑</a>
                                                    <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_category=<?php echo $child['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此分类吗？')">删除</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($tab === 'links'): ?>
                    <h4 class="mb-4">友情链接管理</h4>
                    
                    <?php if ($editLink): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">编辑友情链接</h5>
                                <form method="POST" action="">
                                    <input type="hidden" name="link_id" value="<?php echo $editLink['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <input type="text" name="link_name" value="<?php echo escape($editLink['name']); ?>" class="form-control" placeholder="网站名称" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="url" name="link_url" value="<?php echo escape($editLink['url']); ?>" class="form-control" placeholder="网址" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="text" name="link_description" value="<?php echo escape($editLink['description'] ?? ''); ?>" class="form-control" placeholder="简介（可选）">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="link_sort_order" value="<?php echo $editLink['sort_order']; ?>" class="form-control" placeholder="排序">
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" name="link_is_visible" value="1" id="link_is_visible" <?php echo $editLink['is_visible'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="link_is_visible">显示</label>
                                            </div>
                                            <button type="submit" name="edit_link" class="btn btn-primary w-100">保存</button>
                                        </div>
                                    </div>
                                </form>
                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=links" class="btn btn-outline-secondary btn-sm mt-2">取消编辑</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <input type="text" name="link_name" class="form-control" placeholder="网站名称" required>
                            </div>
                            <div class="col-md-3">
                                <input type="url" name="link_url" class="form-control" placeholder="网址" required>
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="link_description" class="form-control" placeholder="简介（可选）">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" name="add_link" class="btn btn-primary w-100">添加链接</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>网站名称</th>
                                    <th>网址</th>
                                    <th>简介</th>
                                    <th>排序</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($links)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">暂无友情链接</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($links as $link): ?>
                                        <tr>
                                            <td><?php echo $link['id']; ?></td>
                                            <td><?php echo escape($link['name']); ?></td>
                                            <td><a href="<?php echo escape($link['url']); ?>" target="_blank"><?php echo escape(mb_substr($link['url'], 0, 40)); ?><?php echo mb_strlen($link['url']) > 40 ? '...' : ''; ?></a></td>
                                            <td><?php echo escape($link['description'] ?? '-'); ?></td>
                                            <td><?php echo $link['sort_order']; ?></td>
                                            <td>
                                                <span class="badge <?php echo $link['is_visible'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo $link['is_visible'] ? '显示' : '隐藏'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=links&edit_link=<?php echo $link['id']; ?>" class="btn btn-sm btn-outline-warning">编辑</a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?toggle_link=<?php echo $link['id']; ?>" class="btn btn-sm btn-outline-<?php echo $link['is_visible'] ? 'secondary' : 'success'; ?>"><?php echo $link['is_visible'] ? '隐藏' : '显示'; ?></a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_link=<?php echo $link['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此链接吗？')">删除</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            </table>
                        </div>

                <?php elseif ($tab === 'levels'): ?>
                    <h4 class="mb-4">等级管理</h4>
                    
                    <?php if ($editLevel): ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">编辑等级</h5>
                                <form method="POST" action="">
                                    <input type="hidden" name="level_id" value="<?php echo $editLevel['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">等级名称</label>
                                            <input type="text" name="level_name" value="<?php echo escape($editLevel['name']); ?>" class="form-control" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">最小<?php echo getPointsName(); ?></label>
                                            <input type="number" name="min_points" value="<?php echo $editLevel['min_points']; ?>" class="form-control" min="0" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">最大<?php echo getPointsName(); ?></label>
                                            <input type="number" name="max_points" value="<?php echo $editLevel['max_points']; ?>" class="form-control" min="0" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="edit_level" class="btn btn-primary w-100">保存</button>
                                        </div>
                                    </div>
                                </form>
                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=levels" class="btn btn-outline-secondary btn-sm mt-2">取消编辑</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">添加等级</h5>
                                <form method="POST" action="">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">等级名称</label>
                                            <input type="text" name="level_name" class="form-control" placeholder="如：江湖新手" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">最小<?php echo getPointsName(); ?></label>
                                            <input type="number" name="min_points" class="form-control" min="0" value="0" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">最大<?php echo getPointsName(); ?></label>
                                            <input type="number" name="max_points" class="form-control" min="0" value="0" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" name="add_level" class="btn btn-primary w-100">添加</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>排序</th>
                                    <th>等级名称</th>
                                    <th><?php echo getPointsName(); ?>区间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($userLevels)): ?>
                                    <?php foreach ($userLevels as $i => $level): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?move_level_up=<?php echo $level['id']; ?>" class="btn btn-sm btn-outline-secondary <?php echo $i === 0 ? 'disabled' : ''; ?>">↑</a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?move_level_down=<?php echo $level['id']; ?>" class="btn btn-sm btn-outline-secondary <?php echo $i === count($userLevels) - 1 ? 'disabled' : ''; ?>">↓</a>
                                            </td>
                                            <td><strong><?php echo escape($level['name']); ?></strong></td>
                                            <td><?php echo number_format($level['min_points']); ?> - <?php echo number_format($level['max_points']); ?> <?php echo getPointsName(); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=levels&edit_level=<?php echo $level['id']; ?>" class="btn btn-sm btn-outline-warning">编辑</a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_level=<?php echo $level['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此等级吗？')">删除</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">暂无等级配置</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info">
                        <strong>说明：</strong>
                        <ul class="mb-0">
                            <li>等级按排序从低到高排列，<?php echo getPointsName(); ?>区间不应重叠</li>
                            <li>用户<?php echo getPointsName(); ?>达到某个等级的区间时，将显示对应的等级名称</li>
                            <li>建议最后一个等级的最大<?php echo getPointsName(); ?>设置为一个较大的值</li>
                        </ul>
                    </div>

                <?php elseif ($tab === 'announcements'): ?>
                    <h4 class="mb-4">公告管理</h4>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3"><?php echo $editAnnouncement ? '编辑公告' : '添加公告'; ?></h5>
                            <form method="POST" action="">
                                <input type="hidden" name="announcement_id" value="<?php echo $editAnnouncement['id'] ?? 0; ?>">
                                <div class="mb-3">
                                    <label for="announcement_content" class="form-label">公告内容（支持HTML）</label>
                                    <textarea class="form-control" id="announcement_content" name="announcement_content" rows="3" required><?php echo escape($editAnnouncement['content'] ?? ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="announcement_bg_color" class="form-label">背景颜色</label>
                                    <select class="form-select" id="announcement_bg_color" name="announcement_bg_color">
                                        <?php foreach ($announcementColors as $color => $name): ?>
                                            <option value="<?php echo $color; ?>" <?php echo ($editAnnouncement['bg_color'] ?? '#fff3cd') === $color ? 'selected' : ''; ?> style="background-color: <?php echo $color; ?>;">
                                                <?php echo $name; ?> (<?php echo $color; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="announcement_enabled" name="announcement_enabled" <?php echo ($editAnnouncement['is_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="announcement_enabled">启用公告</label>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="save_announcement" class="btn btn-primary"><?php echo $editAnnouncement ? '更新' : '添加'; ?></button>
                                    <?php if ($editAnnouncement): ?>
                                        <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=announcements" class="btn btn-outline-secondary">取消</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">ID</th>
                                    <th>内容</th>
                                    <th width="120">背景色</th>
                                    <th width="80">状态</th>
                                    <th width="150">创建时间</th>
                                    <th width="150">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($announcements)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">暂无公告</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($announcements as $announcement): ?>
                                        <tr>
                                            <td><?php echo $announcement['id']; ?></td>
                                            <td><?php echo mb_substr(strip_tags($announcement['content']), 0, 50); ?><?php echo mb_strlen(strip_tags($announcement['content'])) > 50 ? '...' : ''; ?></td>
                                            <td>
                                                <span style="display:inline-block;width:60px;height:20px;background-color:<?php echo $announcement['bg_color']; ?>;border:1px solid #ccc;border-radius:3px;"></span>
                                            </td>
                                            <td>
                                                <?php if ($announcement['is_enabled']): ?>
                                                    <span class="badge bg-success">启用</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">禁用</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=announcements&toggle_announcement=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-outline-<?php echo $announcement['is_enabled'] ? 'warning' : 'success'; ?>">
                                                    <?php echo $announcement['is_enabled'] ? '禁用' : '启用'; ?>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=announcements&edit_announcement=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?tab=announcements&delete_announcement=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此公告吗？')">删除</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($tab === 'points'): ?>
                    <h4 class="mb-4"><?php echo getPointsName(); ?>管理</h4>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3"><?php echo getPointsName(); ?>规则说明</h5>
                            <ul class="text-muted small">
                                <li><strong><?php echo getPointsName(); ?>值</strong>：正数表示增加<?php echo getPointsName(); ?>，负数表示扣除<?php echo getPointsName(); ?></li>
                                <li><strong>每日上限</strong>：每天通过该操作最多获得的<?php echo getPointsName(); ?>次数，0表示不限制</li>
                                <li><strong>启用状态</strong>：关闭后该操作将不再触发<?php echo getPointsName(); ?>变动</li>
                            </ul>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>操作名称</th>
                                        <th>操作代码</th>
                                        <th><?php echo getPointsName(); ?>值</th>
                                        <th>每日上限</th>
                                        <th>状态</th>
                                        <th>说明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pointRules as $rule): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo escape($rule['name']); ?></strong>
                                            </td>
                                            <td>
                                                <code><?php echo escape($rule['action']); ?></code>
                                            </td>
                                            <td>
                                                <input type="number" name="rules[<?php echo $rule['id']; ?>][points]" 
                                                       value="<?php echo $rule['points']; ?>" 
                                                       class="form-control form-control-sm" style="width: 100px;">
                                            </td>
                                            <td>
                                                <input type="number" name="rules[<?php echo $rule['id']; ?>][daily_limit]" 
                                                       value="<?php echo $rule['daily_limit']; ?>" 
                                                       class="form-control form-control-sm" style="width: 100px;" min="0">
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" name="rules[<?php echo $rule['id']; ?>][is_enabled]" 
                                                           value="1" class="form-check-input" 
                                                           <?php echo $rule['is_enabled'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <?php echo $rule['is_enabled'] ? '启用' : '禁用'; ?>
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo escape($rule['description'] ?? ''); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" name="save_point_rules" class="btn btn-primary">保存积分规则</button>
                    </form>

                <?php elseif ($tab === 'email'): ?>
                    <h4 class="mb-4">邮件管理</h4>
                    
                    <form method="POST" action="">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">SMTP 服务器配置</h5>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="smtp_enabled" value="1" id="smtp_enabled" <?php echo getSetting('smtp_enabled', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="smtp_enabled">启用 SMTP 邮件服务</label>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_host" class="form-label">SMTP 服务器地址</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                               value="<?php echo escape(getSetting('smtp_host', '')); ?>" 
                                               placeholder="例如: smtp.qq.com">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="smtp_port" class="form-label">端口</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                               value="<?php echo escape(getSetting('smtp_port', '465')); ?>" 
                                               placeholder="465">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="smtp_secure" class="form-label">加密方式</label>
                                        <select class="form-select" id="smtp_secure" name="smtp_secure">
                                            <option value="ssl" <?php echo getSetting('smtp_secure', 'ssl') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="tls" <?php echo getSetting('smtp_secure', 'ssl') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="" <?php echo getSetting('smtp_secure', 'ssl') === '' ? 'selected' : ''; ?>>无加密</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_username" class="form-label">SMTP 用户名</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                               value="<?php echo escape(getSetting('smtp_username', '')); ?>" 
                                               placeholder="通常是邮箱地址">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_password" class="form-label">SMTP 密码/授权码</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                               placeholder="留空则不修改">
                                        <small class="text-muted">部分邮箱需要使用授权码而非登录密码</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">发件人信息</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_from_email" class="form-label">发件人邮箱</label>
                                        <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" 
                                               value="<?php echo escape(getSetting('smtp_from_email', '')); ?>" 
                                               placeholder="noreply@example.com">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="smtp_from_name" class="form-label">发件人名称</label>
                                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                                               value="<?php echo escape(getSetting('smtp_from_name', 'HuBBS Forum')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">邮件功能开关</h5>
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" name="email_notify_reply" value="1" id="email_notify_reply" <?php echo getSetting('email_notify_reply', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_notify_reply">回复邮件通知</label>
                                    <small class="text-muted d-block">帖子被回复时发送邮件通知</small>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="save_email_settings" class="btn btn-primary">保存邮件设置</button>
                    </form>
                    
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="mb-3">发送测试邮件</h5>
                            <form method="POST" action="" class="row g-3">
                                <div class="col-md-8">
                                    <input type="email" class="form-control" name="test_to" placeholder="输入测试邮箱地址" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="test_email" class="btn btn-outline-primary w-100">发送测试邮件</button>
                                </div>
                            </form>
                            <small class="text-muted mt-2 d-block">保存 SMTP 配置后，可发送测试邮件验证配置是否正确</small>
                        </div>
                    </div>

                <?php elseif ($tab === 'settings'): ?>
                    <h4 class="mb-4">系统设置</h4>
                    <form method="POST" action="">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">网站信息</h5>
                                <div class="mb-3">
                                    <label for="site_title" class="form-label">站点标题</label>
                                    <input type="text" class="form-control" id="site_title" name="site_title" value="<?php echo escape(getSetting('site_title', 'HuBBS Forum')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="site_subtitle" class="form-label">站点副标题</label>
                                    <input type="text" class="form-control" id="site_subtitle" name="site_subtitle" value="<?php echo escape(getSetting('site_subtitle', '')); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="site_theme" class="form-label">网站模板</label>
                                    <select class="form-select" id="site_theme" name="site_theme">
                                        <?php $currentTheme = getSetting('site_theme', 'default'); ?>
                                        <?php foreach (getAvailableThemes() as $theme): ?>
                                            <option value="<?php echo escape($theme); ?>" <?php echo $currentTheme === $theme ? 'selected' : ''; ?>>
                                                <?php echo escape(ucfirst($theme)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">将模板文件夹上传到 views 目录即可在后台切换</small>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">发帖设置</h5>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="require_category" value="1" id="require_category" <?php echo getSetting('require_category', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_category">发帖时必须选择分类</label>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="posts_per_page" class="form-label">首页每页显示帖子数</label>
                                        <input type="number" class="form-control" id="posts_per_page" name="posts_per_page" value="<?php echo escape(getSetting('posts_per_page', '10')); ?>" min="5" max="100">
                                        <small class="text-muted">默认 10 条</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="max_post_length" class="form-label">帖子最大字数</label>
                                        <input type="number" class="form-control" id="max_post_length" name="max_post_length" value="<?php echo escape(getSetting('max_post_length', '10000')); ?>" min="100" max="100000">
                                        <small class="text-muted">默认 10000 字</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="max_comment_length" class="form-label">评论最大字数</label>
                                        <input type="number" class="form-control" id="max_comment_length" name="max_comment_length" value="<?php echo escape(getSetting('max_comment_length', '2000')); ?>" min="50" max="10000">
                                        <small class="text-muted">默认 2000 字</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">图片上传设置</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="max_image_size" class="form-label">单张图片最大大小 (MB)</label>
                                        <input type="number" class="form-control" id="max_image_size" name="max_image_size" value="<?php echo escape(getSetting('max_image_size', '5')); ?>" min="1" max="50" step="0.5">
                                        <small class="text-muted">默认 5MB</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="max_image_width" class="form-label">图片最大宽度 (px)</label>
                                        <input type="number" class="form-control" id="max_image_width" name="max_image_width" value="<?php echo escape(getSetting('max_image_width', '1920')); ?>" min="500" max="4000">
                                        <small class="text-muted">超过此宽度会自动缩放</small>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="image_quality" class="form-label">图片压缩质量 (%)</label>
                                        <input type="number" class="form-control" id="image_quality" name="image_quality" value="<?php echo escape(getSetting('image_quality', '85')); ?>" min="50" max="100">
                                        <small class="text-muted">推荐 75-90</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="thumb_width" class="form-label">缩略图宽度 (px)</label>
                                        <input type="number" class="form-control" id="thumb_width" name="thumb_width" value="<?php echo escape(getSetting('thumb_width', '300')); ?>" min="100" max="500">
                                        <small class="text-muted">用于列表显示</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">敏感词管理</h5>
                                <div class="mb-3">
                                    <label for="forbidden_usernames" class="form-label">禁止注册的用户名字符</label>
                                    <textarea class="form-control" id="forbidden_usernames" name="forbidden_usernames" rows="2" placeholder="admin,administrator,root"><?php echo escape(getSetting('forbidden_usernames', '')); ?></textarea>
                                    <small class="text-muted">多个字符用英文逗号分隔，包含这些字符的用户名将被禁止注册</small>
                                </div>
                                <div class="mb-3">
                                    <label for="sensitive_words" class="form-label">帖子和评论敏感词</label>
                                    <textarea class="form-control" id="sensitive_words" name="sensitive_words" rows="3" placeholder="敏感词1,敏感词2,敏感词3"><?php echo escape(getSetting('sensitive_words', '')); ?></textarea>
                                    <small class="text-muted">多个敏感词用英文逗号分隔，发帖和评论时自动替换</small>
                                </div>
                                <div class="mb-3">
                                    <label for="sensitive_replacement" class="form-label">敏感词替换内容</label>
                                    <input type="text" class="form-control" id="sensitive_replacement" name="sensitive_replacement" value="<?php echo escape(getSetting('sensitive_replacement', '***')); ?>" placeholder="***">
                                    <small class="text-muted">所有敏感词都将替换为此内容</small>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">注册设置</h5>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="allow_register" value="1" id="allow_register" <?php echo getSetting('allow_register', '1') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allow_register">允许新用户注册</label>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="email_verify_register" value="1" id="email_verify_register" <?php echo getSetting('email_verify_register', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="email_verify_register">注册时验证邮箱</label>
                                    <small class="text-muted d-block">开启后，用户注册时需要输入邮箱验证码才能完成注册（需先配置SMTP）</small>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" class="form-check-input" name="restrict_email_domain" value="1" id="restrict_email_domain" <?php echo getSetting('restrict_email_domain', '0') === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="restrict_email_domain">限制注册邮箱后缀</label>
                                    </div>
                                    <input type="text" class="form-control" id="allowed_email_domains" name="allowed_email_domains" 
                                           value="<?php echo escape(getSetting('allowed_email_domains', '')); ?>" 
                                           placeholder="例如: qq.com,163.com,gmail.com">
                                    <small class="text-muted">开启后，仅允许指定后缀的邮箱注册。多个后缀用英文逗号分隔，留空则不允许任何邮箱注册</small>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="mb-3">附件设置</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="attachment_max_size" class="form-label">单个附件最大大小 (MB)</label>
                                        <input type="number" class="form-control" id="attachment_max_size" name="attachment_max_size" value="<?php echo escape(getSetting('attachment_max_size', '10')); ?>" min="1" max="100">
                                        <small class="text-muted">默认 10MB</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="attachment_max_count" class="form-label">每个帖子最大附件数</label>
                                        <input type="number" class="form-control" id="attachment_max_count" name="attachment_max_count" value="<?php echo escape(getSetting('attachment_max_count', '5')); ?>" min="1" max="20">
                                        <small class="text-muted">默认 5 个</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="attachment_allowed_exts" class="form-label">允许上传的附件后缀</label>
                                    <input type="text" class="form-control" id="attachment_allowed_exts" name="attachment_allowed_exts" value="<?php echo escape(getSetting('attachment_allowed_exts', 'zip,rar,7z,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,mp4')); ?>">
                                    <small class="text-muted">多个后缀用英文逗号分隔</small>
                                </div>
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="attachment_guest_download" value="1" id="attachment_guest_download" <?php echo getSetting('attachment_guest_download', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="attachment_guest_download">允许游客下载附件</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">发帖限制</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="post_interval" class="form-label">发帖间隔（秒）</label>
                                        <input type="number" class="form-control" name="post_interval" id="post_interval" value="<?php echo getSetting('post_interval', '0'); ?>" min="0">
                                        <small class="text-muted">用户两次发帖之间的最小时间间隔，0表示不限制</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="comment_interval" class="form-label">评论间隔（秒）</label>
                                        <input type="number" class="form-control" name="comment_interval" id="comment_interval" value="<?php echo getSetting('comment_interval', '0'); ?>" min="0">
                                        <small class="text-muted">用户两次评论之间的最小时间间隔，0表示不限制</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">积分设置</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="points_name" class="form-label">积分名称</label>
                                        <input type="text" class="form-control" name="points_name" id="points_name" value="<?php echo escape(getSetting('points_name', '积分')); ?>">
                                        <small class="text-muted">自定义积分的显示名称，如：金币、威望等</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">注册设置</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" name="invite_only" value="1" id="invite_only" <?php echo getSetting('invite_only', '0') === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="invite_only">开启邀请码注册</label>
                                    <small class="text-muted d-block">开启后，用户注册时必须输入有效的邀请码</small>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="save_settings" class="btn btn-primary">保存设置</button>
                    </form>
                
                <?php elseif ($tab === 'invite'): ?>
                    <h4 class="mb-4">邀请码管理</h4>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">生成邀请码</h5>
                            <form method="POST" action="" class="row g-3">
                                <div class="col-md-4">
                                    <label for="invite_code_count" class="form-label">生成数量</label>
                                    <input type="number" class="form-control" id="invite_code_count" name="invite_code_count" min="1" max="100" value="1" required>
                                    <small class="text-muted">最多一次生成 100 个</small>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" name="generate_invite_codes" class="btn btn-primary">生成邀请码</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">邀请码列表</h5>
                        <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_all_unused_codes=1" class="btn btn-outline-danger btn-sm" onclick="return confirm('确定要删除所有未使用的邀请码吗？')">删除所有未使用的邀请码</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>邀请码</th>
                                    <th>创建者</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inviteCodes)): ?>
                                    <?php foreach ($inviteCodes as $code): ?>
                                        <tr>
                                            <td><code class="fs-6"><?php echo escape($code['code']); ?></code></td>
                                            <td><?php echo escape($code['created_by_name'] ?? '未知'); ?></td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($code['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/pages/admin.php?delete_invite_code=<?php echo $code['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此邀请码吗？')">删除</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">暂无邀请码</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <strong>说明：</strong>
                        <ul class="mb-0">
                            <li>邀请码为 16 位大写字母和数字组合</li>
                            <li>邀请码被使用后将自动从列表中删除</li>
                            <li>删除邀请码后，该邀请码将立即失效</li>
                            <li>请在"系统设置"中开启"邀请码注册"功能</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
