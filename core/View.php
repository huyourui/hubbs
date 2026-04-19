<?php
/**
 * HuBBS - 视图模板引擎
 * 
 * 提供简洁优雅的模板渲染功能，支持模板继承、组件化、缓存等特性
 * 
 * @package HuBBS
 * @version 1.7.3
 */

class View 
{
    /**
     * @var string 模板目录
     */
    private $templateDir;
    
    /**
     * @var string 缓存目录
     */
    private $cacheDir;
    
    /**
     * @var array 模板变量
     */
    private $data = [];
    
    /**
     * @var array 区块内容
     */
    private $sections = [];
    
    /**
     * @var string|null 当前区块名称
     */
    private $currentSection = null;
    
     /**
     * @var string|null 布局模板
     */
    private $layout = null;
    
    /**
     * @var array 共享数据（所有模板可用）
     */
    private static $sharedData = [];
    
    /**
     * @var array 组件注册表
     */
    private static $components = [];
    
    /**
     * @var bool 是否启用缓存
     */
    private $enableCache = false;
    
    /**
     * @var int 缓存有效期（秒）
     */
    private $cacheLifetime = 3600;
    
    /**
     * 构造函数
     * 
     * @param string $templateDir 模板目录
     * @param string|null $cacheDir 缓存目录
     * @param bool $enableCache 是否启用缓存
     */
    public function __construct(
        string $templateDir = HUBBS_ROOT . 'templates/default/',
        ?string $cacheDir = null,
        bool $enableCache = false
    ) {
        $this->templateDir = rtrim($templateDir, '/') . '/';
        $this->cacheDir = $cacheDir ? rtrim($cacheDir, '/') . '/' : null;
        $this->enableCache = $enableCache;
        
        // 确保缓存目录存在
        if ($this->cacheDir && !is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * 设置模板变量
     * 
     * @param string|array $key 变量名或变量数组
     * @param mixed $value 变量值
     * @return self
     */
    public function assign($key, $value = null): self 
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }
    
    /**
     * 设置共享数据（所有模板实例可用）
     * 
     * @param string|array $key 变量名或变量数组
     * @param mixed $value 变量值
     */
    public static function share($key, $value = null): void 
    {
        if (is_array($key)) {
            self::$sharedData = array_merge(self::$sharedData, $key);
        } else {
            self::$sharedData[$key] = $value;
        }
    }
    
    /**
     * 渲染模板
     * 
     * @param string $template 模板名称
     * @param array $data 额外数据
     * @return string 渲染后的HTML
     * @throws Exception 模板不存在时抛出异常
     */
    public function render(string $template, array $data = []): string 
    {
        // 合并数据：共享数据 < 实例数据 < 传入数据
        $this->data = array_merge(self::$sharedData, $this->data, $data);
        
        // 检查缓存
        if ($this->enableCache && $this->cacheDir) {
            $cacheFile = $this->getCacheFile($template);
            if ($this->isCacheValid($cacheFile)) {
                return file_get_contents($cacheFile);
            }
        }
        
        // 获取模板文件路径
        $templateFile = $this->templateDir . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new Exception("模板不存在: {$template}");
        }
        
        // 编译并渲染模板
        $content = $this->compileAndRender($templateFile);
        
        // 如果有布局，渲染布局
        if ($this->layout) {
            $content = $this->renderLayout($content);
        }
        
        // 保存缓存
        if ($this->enableCache && $this->cacheDir) {
            $this->saveCache($this->getCacheFile($template), $content);
        }
        
        return $content;
    }
    
    /**
     * 直接输出渲染结果
     * 
     * @param string $template 模板名称
     * @param array $data 模板数据
     */
    public function display(string $template, array $data = []): void 
    {
        echo $this->render($template, $data);
    }
    
    /**
     * 设置布局模板
     * 
     * @param string|null $layout 布局模板名称
     * @return self
     */
    public function setLayout(?string $layout): self 
    {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * 开始定义区块
     * 
     * @param string $name 区块名称
     */
    public function section(string $name): void 
    {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * 结束定义区块
     */
    public function endSection(): void 
    {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }
    
    /**
     * 输出区块内容
     * 
     * @param string $name 区块名称
     * @param string $default 默认内容
     */
    public function yield(string $name, string $default = ''): void 
    {
        echo $this->sections[$name] ?? $default;
    }
    
    /**
     * 包含子模板
     * 
     * @param string $template 模板名称
     * @param array $data 额外数据
     */
    public function include(string $template, array $data = []): void 
    {
        $templateFile = $this->templateDir . $template . '.php';
        if (file_exists($templateFile)) {
            // 合并数据
            $mergedData = array_merge($this->data, $data);
            extract($mergedData);
            include $templateFile;
        }
    }
    
    /**
     * 渲染组件
     * 
     * @param string $name 组件名称
     * @param array $props 组件属性
     */
    public function component(string $name, array $props = []): void 
    {
        // 检查是否注册了自定义组件
        if (isset(self::$components[$name])) {
            $callback = self::$components[$name];
            echo $callback($props);
            return;
        }
        
        // 查找组件模板
        $componentFile = $this->templateDir . 'components/' . $name . '.php';
        if (file_exists($componentFile)) {
            extract(array_merge($this->data, $props));
            include $componentFile;
        }
    }
    
    /**
     * 注册组件
     * 
     * @param string $name 组件名称
     * @param callable $callback 组件渲染回调
     */
    public static function registerComponent(string $name, callable $callback): void 
    {
        self::$components[$name] = $callback;
    }
    
    /**
     * HTML 转义输出
     * 
     * @param string $str 要转义的字符串
     */
    public static function e(?string $str): void 
    {
        echo htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 获取转义后的字符串
     * 
     * @param string|null $str 要转义的字符串
     * @return string 转义后的字符串
     */
    public static function esc(?string $str): string 
    {
        return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 条件渲染
     * 
     * @param bool $condition 条件
     * @param callable $callback 条件为真时执行的回调
     * @param callable|null $elseCallback 条件为假时执行的回调
     */
    public function when(bool $condition, callable $callback, ?callable $elseCallback = null): void 
    {
        if ($condition) {
            $callback();
        } elseif ($elseCallback) {
            $elseCallback();
        }
    }
    
    /**
     * 循环渲染
     * 
     * @param array $items 要循环的数组
     * @param callable $callback 每项的渲染回调
     * @param callable|null $emptyCallback 数组为空时的回调
     */
    public function each(array $items, callable $callback, ?callable $emptyCallback = null): void 
    {
        if (empty($items)) {
            if ($emptyCallback) {
                $emptyCallback();
            }
            return;
        }
        
        foreach ($items as $key => $item) {
            $callback($item, $key);
        }
    }
    
    /**
     * 静态方法快速渲染
     * 
     * @param string $template 模板名称
     * @param array $data 模板数据
     * @return string 渲染后的HTML
     */
    public static function make(string $template, array $data = []): string 
    {
        $view = new self();
        return $view->render($template, $data);
    }
    
    /**
     * 清除缓存
     * 
     * @param string|null $template 指定模板名称，null表示清除所有
     */
    public function clearCache(?string $template = null): void 
    {
        if (!$this->cacheDir) {
            return;
        }
        
        if ($template) {
            $cacheFile = $this->getCacheFile($template);
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
        } else {
            // 清除所有缓存
            $files = glob($this->cacheDir . '*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * 编译并渲染模板
     * 
     * @param string $templateFile 模板文件路径
     * @return string 渲染后的内容
     */
    private function compileAndRender(string $templateFile): string 
    {
        // 提取变量到当前作用域
        extract($this->data);
        
        // 开启输出缓冲
        ob_start();
        include $templateFile;
        return ob_get_clean();
    }
    
    /**
     * 渲染布局
     * 
     * @param string $content 子模板内容
     * @return string 完整布局内容
     */
    private function renderLayout(string $content): string 
    {
        $layoutFile = $this->templateDir . $this->layout . '.php';
        if (!file_exists($layoutFile)) {
            return $content;
        }
        
        // 将内容作为变量传入布局
        $this->data['content'] = $content;
        
        // 重置区块和布局状态
        $this->layout = null;
        $savedSections = $this->sections;
        
        // 渲染布局
        extract($this->data);
        ob_start();
        include $layoutFile;
        $layoutContent = ob_get_clean();
        
        // 恢复区块状态
        $this->sections = $savedSections;
        
        return $layoutContent;
    }
    
    /**
     * 获取缓存文件路径
     * 
     * @param string $template 模板名称
     * @return string 缓存文件路径
     */
    private function getCacheFile(string $template): string 
    {
        return $this->cacheDir . md5($template) . '.cache';
    }
    
    /**
     * 检查缓存是否有效
     * 
     * @param string $cacheFile 缓存文件路径
     * @return bool 是否有效
     */
    private function isCacheValid(string $cacheFile): bool 
    {
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($cacheFile);
        return (time() - $cacheTime) < $this->cacheLifetime;
    }
    
    /**
     * 保存缓存
     * 
     * @param string $cacheFile 缓存文件路径
     * @param string $content 缓存内容
     */
    private function saveCache(string $cacheFile, string $content): void 
    {
        file_put_contents($cacheFile, $content, LOCK_EX);
    }
    
    /**
     * 获取模板变量
     * 
     * @param string $key 变量名
     * @param mixed $default 默认值
     * @return mixed 变量值
     */
    public function get(string $key, $default = null) 
    {
        return $this->data[$key] ?? self::$sharedData[$key] ?? $default;
    }
    
    /**
     * 检查模板变量是否存在
     * 
     * @param string $key 变量名
     * @return bool 是否存在
     */
    public function has(string $key): bool 
    {
        return isset($this->data[$key]) || isset(self::$sharedData[$key]);
    }
    
    /**
     * 魔术方法：获取变量
     * 
     * @param string $key 变量名
     * @return mixed 变量值
     */
    public function __get(string $key) 
    {
        return $this->get($key);
    }
    
    /**
     * 魔术方法：设置变量
     * 
     * @param string $key 变量名
     * @param mixed $value 变量值
     */
    public function __set(string $key, $value): void 
    {
        $this->assign($key, $value);
    }
    
    /**
     * 魔术方法：检查变量是否存在
     * 
     * @param string $key 变量名
     * @return bool 是否存在
     */
    public function __isset(string $key): bool 
    {
        return $this->has($key);
    }
}

/**
 * 全局辅助函数：渲染视图
 * 
 * @param string $template 模板名称
 * @param array $data 模板数据
 * @return string 渲染后的HTML
 */
function view(string $template, array $data = []): string 
{
    $view = new View();
    return $view->render($template, $data);
}

/**
 * 全局辅助函数：输出视图
 * 
 * @param string $template 模板名称
 * @param array $data 模板数据
 */
function display(string $template, array $data = []): void 
{
    $view = new View();
    $view->display($template, $data);
}
