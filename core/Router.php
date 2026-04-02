<?php
/**
 * HuBBS - 路由系统
 * 支持RESTful风格路由、参数绑定、中间件
 * 
 * 使用示例:
 * Router::get('/user/{id}', 'UserController@show');
 * Router::post('/post', 'PostController@create');
 * Router::group(['prefix' => '/api'], function() {
 *     Router::get('/posts', 'ApiController@posts');
 * });
 */

class Router {
    // 路由规则
    private static $routes = [];
    
    // 分组配置
    private static $groupStack = [];
    
    // HTTP方法
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_PATCH = 'PATCH';
    const METHOD_OPTIONS = 'OPTIONS';
    
    /**
     * 注册GET路由
     */
    public static function get($uri, $handler) {
        return self::addRoute(self::METHOD_GET, $uri, $handler);
    }
    
    /**
     * 注册POST路由
     */
    public static function post($uri, $handler) {
        return self::addRoute(self::METHOD_POST, $uri, $handler);
    }
    
    /**
     * 注册PUT路由
     */
    public static function put($uri, $handler) {
        return self::addRoute(self::METHOD_PUT, $uri, $handler);
    }
    
    /**
     * 注册DELETE路由
     */
    public static function delete($uri, $handler) {
        return self::addRoute(self::METHOD_DELETE, $uri, $handler);
    }
    
    /**
     * 注册PATCH路由
     */
    public static function patch($uri, $handler) {
        return self::addRoute(self::METHOD_PATCH, $uri, $handler);
    }
    
    /**
     * 注册OPTIONS路由
     */
    public static function options($uri, $handler) {
        return self::addRoute(self::METHOD_OPTIONS, $uri, $handler);
    }
    
    /**
     * 注册任意HTTP方法的路由
     */
    public static function any($uri, $handler) {
        $methods = [self::METHOD_GET, self::METHOD_POST, self::METHOD_PUT, self::METHOD_DELETE, self::METHOD_PATCH];
        foreach ($methods as $method) {
            self::addRoute($method, $uri, $handler);
        }
    }
    
    /**
     * 注册多个HTTP方法的路由
     */
    public static function match($methods, $uri, $handler) {
        $methods = is_array($methods) ? $methods : explode(',', $methods);
        foreach ($methods as $method) {
            self::addRoute(strtoupper(trim($method)), $uri, $handler);
        }
    }
    
    /**
     * 添加路由
     */
    private static function addRoute($method, $uri, $handler) {
        // 应用分组配置
        $config = self::getGroupConfig();
        
        // 合并前缀
        if (!empty($config['prefix'])) {
            $uri = rtrim($config['prefix'], '/') . '/' . ltrim($uri, '/');
        }
        
        // 合并中间件
        $middleware = array_merge($config['middleware'] ?? [], $config['before'] ?? []);
        
        // 解析路由
        $route = [
            'method' => $method,
            'uri' => '/' . ltrim($uri, '/'),
            'handler' => $handler,
            'middleware' => $middleware,
            'before' => $config['before'] ?? [],
            'after' => $config['after'] ?? [],
            'namespace' => $config['namespace'] ?? '',
        ];
        
        // 检测URI中的参数
        $route['params'] = self::extractParams($route['uri']);
        
        self::$routes[] = $route;
        
        return $route;
    }
    
    /**
     * 创建路由分组
     */
    public static function group($config, $callback) {
        // 保存当前分组配置
        array_push(self::$groupStack, $config);
        
        // 执行回调注册路由
        call_user_func($callback);
        
        // 恢复之前的分组配置
        array_pop(self::$groupStack);
    }
    
    /**
     * 获取当前分组配置
     */
    private static function getGroupConfig() {
        $config = [];
        foreach (self::$groupStack as $groupConfig) {
            $config = array_merge($config, $groupConfig);
        }
        return $config;
    }
    
    /**
     * 从URI中提取参数
     */
    private static function extractParams($uri) {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $uri, $matches);
        return $matches[1] ?? [];
    }
    
    /**
     * 路由调度
     */
    public static function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // 移除项目目录前缀，获取相对URI
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        
        // 如果URI以脚本目录开头，移除它
        if ($scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }
        
        // 确保URI以/开头
        $uri = '/' . ltrim($uri, '/');
        
        // 处理_method参数（用于模拟PUT/DELETE请求）
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        // 匹配路由
        foreach (self::$routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            
            // 匹配URI
            $params = self::matchUri($route['uri'], $uri);
            if ($params !== false) {
                // 执行前置中间件
                foreach ($route['middleware'] as $middleware) {
                    $result = self::callMiddleware($middleware);
                    if ($result === false) {
                        return;
                    }
                }
                
                // 执行路由处理器
                self::executeHandler($route['handler'], $params, $route);
                return;
            }
        }
        
        // 404未找到
        self::notFound();
    }
    
    /**
     * 匹配URI
     */
    private static function matchUri($pattern, $uri) {
        // 将URI模式转换为正则表达式
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $uri, $matches)) {
            array_shift($matches);
            
            // 如果有命名参数，返回参数数组
            $params = self::extractParams($pattern);
            if (!empty($params)) {
                return array_combine($params, $matches);
            }
            
            return $matches;
        }
        
        return false;
    }
    
    /**
     * 执行路由处理器
     */
    private static function executeHandler($handler, $params = [], $route = []) {
        // 支持多种handler格式
        if (is_callable($handler)) {
            // 匿名函数
            call_user_func_array($handler, $params);
            return;
        }
        
        if (is_string($handler)) {
            // Controller@action 格式
            if (strpos($handler, '@') !== false) {
                list($controller, $action) = explode('@', $handler);
                
                // 添加命名空间
                $namespace = $route['namespace'] ?? '';
                $controller = $namespace . $controller;
                
                // 加载控制器文件
                self::loadController($controller);
                
                if (!class_exists($controller)) {
                    self::error("Controller not found: {$controller}");
                    return;
                }
                
                $instance = new $controller();
                
                if (!method_exists($instance, $action)) {
                    self::error("Action not found: {$action}");
                    return;
                }
                
                // 注入参数
                if (!empty($params)) {
                    call_user_func_array([$instance, $action], $params);
                } else {
                    call_user_func([$instance, $action]);
                }
                return;
            }
            
            // 直接是文件路径
            if (file_exists(HUBBS_ROOT . $handler)) {
                // 注入参数到$_GET
                foreach ($params as $key => $value) {
                    $_GET[$key] = $value;
                }
                require HUBBS_ROOT . $handler;
                return;
            }
        }
        
        self::error('Invalid route handler');
    }
    
    /**
     * 加载控制器
     */
    private static function loadController($controller) {
        // 尝试多种路径
        $paths = [
            HUBBS_ROOT . 'controllers/' . $controller . '.php',
            HUBBS_ROOT . 'api/' . $controller . '.php',
            HUBBS_ROOT . 'modules/' . strtolower(basename($controller)) . '.php',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    }
    
    /**
     * 调用中间件
     */
    private static function callMiddleware($middleware) {
        if (is_callable($middleware)) {
            return call_user_func($middleware);
        }
        
        if (is_string($middleware) && function_exists($middleware)) {
            return call_user_func($middleware);
        }
        
        // 尝试加载中间件类
        if (strpos($middleware, '\\') === false) {
            $middlewareFile = HUBBS_ROOT . 'middleware/' . $middleware . '.php';
            if (file_exists($middlewareFile)) {
                require_once $middlewareFile;
                if (function_exists($middleware)) {
                    return call_user_func($middleware);
                }
            }
        }
        
        return true;
    }
    
    /**
     * 404处理
     */
    private static function notFound() {
        http_response_code(404);
        
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Not Found',
                'message' => '请求的页面不存在'
            ]);
        } else {
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>请求的页面不存在</p>';
            echo '<a href="' . base_url() . '">返回首页</a>';
        }
        exit;
    }
    
    /**
     * 错误处理
     */
    private static function error($message) {
        http_response_code(500);
        
        if (self::isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Server Error',
                'message' => $message
            ]);
        } else {
            echo '<h1>500 - Server Error</h1>';
            echo '<p>' . htmlspecialchars($message) . '</p>';
        }
        exit;
    }
    
    /**
     * 判断是否是AJAX请求
     */
    private static function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * 获取所有路由（用于调试）
     */
    public static function getRoutes() {
        return self::$routes;
    }
    
    /**
     * 生成URL
     */
    public static function url($path, $params = []) {
        $url = base_url($path);
        
        if (!empty($params)) {
            $query = http_build_query($params);
            $url .= '?' . $query;
        }
        
        return $url;
    }
    
    /**
     * 重定向
     */
    public static function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }
}
