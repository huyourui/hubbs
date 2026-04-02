<?php
/**
 * HuBBS - 路由配置
 * 包含API路由和传统页面路由
 */

// ==================== API路由组 ====================
Router::group(['prefix' => '/api'], function() {
    
    // 帖子API
    Router::get('/posts', 'PostApi@index');
    Router::get('/posts/{id}', 'PostApi@show');
    Router::post('/posts', 'PostApi@create');
    Router::put('/posts/{id}', 'PostApi@update');
    Router::delete('/posts/{id}', 'PostApi@delete');
    
    // 帖子点赞
    Router::post('/posts/{id}/like', 'PostApi@like');
    Router::delete('/posts/{id}/like', 'PostApi@unlike');
    
    // 帖子收藏
    Router::post('/posts/{id}/favorite', 'PostApi@favorite');
    Router::delete('/posts/{id}/favorite', 'PostApi@unfavorite');
    
    // 帖子回复
    Router::get('/posts/{id}/replies', 'PostApi@replies');
    
    // 用户API
    Router::get('/user', 'UserApi@me');
    Router::put('/user', 'UserApi@update');
    Router::put('/user/password', 'UserApi@password');
    
    Router::get('/users/{id}', 'UserApi@show');
    Router::get('/users/{id}/posts', 'UserApi@posts');
    Router::get('/users/{id}/replies', 'UserApi@replies');
    Router::get('/users/{id}/favorites', 'UserApi@favorites');
    
    // 板块API
    Router::get('/forums', 'ForumApi@index');
    Router::get('/forums/{id}', 'ForumApi@show');
    Router::get('/forums/{id}/posts', 'ForumApi@posts');
    
    // 通知API
    Router::get('/notifications', 'NotificationApi@index');
    Router::put('/notifications/{id}/read', 'NotificationApi@markAsRead');
    Router::put('/notifications/read-all', 'NotificationApi@markAllAsRead');
    
});

// ==================== 传统页面路由（兼容旧版） ====================

// 首页
Router::get('/', function() {
    $_GET['module'] = 'post';
    $_GET['action'] = 'list';
    loadModule('post');
});

// 帖子列表（带板块筛选）
Router::get('/posts', function() {
    $_GET['module'] = 'post';
    $_GET['action'] = 'list';
    loadModule('post');
});

// 发帖页面
Router::get('/post/create', function() {
    $_GET['module'] = 'post';
    $_GET['action'] = 'create';
    loadModule('post');
});

// 查看帖子
Router::get('/post/{id}', function($id) {
    $_GET['module'] = 'post';
    $_GET['action'] = 'view';
    $_GET['id'] = $id;
    loadModule('post');
});

// 编辑帖子
Router::get('/post/{id}/edit', function($id) {
    $_GET['module'] = 'post';
    $_GET['action'] = 'edit';
    $_GET['id'] = $id;
    loadModule('post');
});

// 用户登录
Router::get('/login', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'login';
    loadModule('user');
});

Router::post('/login', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'login';
    loadModule('user');
});

// 用户注册
Router::get('/register', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'register';
    loadModule('user');
});

Router::post('/register', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'register';
    loadModule('user');
});

// 退出登录
Router::get('/logout', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'logout';
    loadModule('user');
});

// 个人中心
Router::get('/user/{id}', function($id) {
    $_GET['module'] = 'user';
    $_GET['action'] = 'profile';
    $_GET['id'] = $id;
    loadModule('user');
});

// 账号设置
Router::get('/settings', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'settings';
    loadModule('user');
});

Router::post('/settings', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'settings';
    loadModule('user');
});

// 消息通知
Router::get('/notifications', function() {
    $_GET['module'] = 'user';
    $_GET['action'] = 'notifications';
    loadModule('user');
});

// 后台管理
Router::get('/admin', function() {
    $_GET['module'] = 'admin';
    $_GET['action'] = 'dashboard';
    loadModule('admin');
});

Router::get('/admin/{action}', function($action) {
    $_GET['module'] = 'admin';
    $_GET['action'] = $action;
    loadModule('admin');
});

Router::post('/admin/{action}', function($action) {
    $_GET['module'] = 'admin';
    $_GET['action'] = $action;
    loadModule('admin');
});

// 搜索
Router::get('/search', function() {
    $_GET['module'] = 'post';
    $_GET['action'] = 'search';
    loadModule('post');
});

// 传统模块路由（兼容旧版URL参数）
Router::any('/index.php', function() {
    $module = $_GET['module'] ?? 'post';
    loadModule($module);
});

/**
 * 加载模块
 */
function loadModule($module) {
    $moduleFile = HUBBS_ROOT . 'modules/' . $module . '.php';
    if (file_exists($moduleFile)) {
        require_once $moduleFile;
        $className = ucfirst($module) . 'Module';
        if (class_exists($className)) {
            $moduleObj = new $className();
            $result = $moduleObj->handle();
            
            if (is_array($result) && isset($result['template'])) {
                // 渲染模板
                $templateData = $result['data'] ?? [];
                extract($templateData);
                $templateFile = HUBBS_ROOT . 'templates/default/' . $result['template'] . '.php';
                if (file_exists($templateFile)) {
                    include $templateFile;
                } else {
                    die('模板不存在: ' . $result['template']);
                }
            }
        }
    } else {
        // 默认首页
        require_once HUBBS_ROOT . 'modules/post.php';
        $moduleObj = new PostModule();
        $result = $moduleObj->handle();
        if (is_array($result) && isset($result['template'])) {
            $templateData = $result['data'] ?? [];
            extract($templateData);
            include HUBBS_ROOT . 'templates/default/' . $result['template'] . '.php';
        }
    }
}
