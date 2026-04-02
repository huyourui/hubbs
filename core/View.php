<?php
/**
 * HuBBS - 简单模板引擎
 * 支持模板继承、变量输出、条件判断、循环
 */

class View {
    
    private $templateDir;
    private $cacheDir;
    private $data = [];
    private $sections = [];
    private $currentSection = null;
    private $layout = null;
    
    /**
     * 构造函数
     * @param string $templateDir 模板目录
     * @param string $cacheDir 缓存目录（可选）
     */
    public function __construct($templateDir = HUBBS_ROOT . 'templates/default/', $cacheDir = null) {
        $this->templateDir = $templateDir;
        $this->cacheDir = $cacheDir;
        
        // 确保缓存目录存在
        if ($cacheDir && !is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }
    
    /**
     * 设置模板变量
     * @param string|array $key
     * @param mixed $value
     */
    public function assign($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }
    
    /**
     * 渲染模板
     * @param string $template 模板名称
     * @param array $data 额外数据
     * @return string
     */
    public function render($template, $data = []) {
        // 合并数据
        $this->data = array_merge($this->data, $data);
        
        // 提取变量到当前作用域
        extract($this->data);
        
        // 获取模板文件路径
        $templateFile = $this->templateDir . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new Exception("模板不存在: $template");
        }
        
        // 开启输出缓冲
        ob_start();
        include $templateFile;
        $content = ob_get_clean();
        
        // 如果有布局，渲染布局
        if ($this->layout) {
            $layoutFile = $this->templateDir . $this->layout . '.php';
            if (file_exists($layoutFile)) {
                $this->data['content'] = $content;
                extract($this->data);
                ob_start();
                include $layoutFile;
                $content = ob_get_clean();
            }
        }
        
        return $content;
    }
    
    /**
     * 直接输出渲染结果
     * @param string $template
     * @param array $data
     */
    public function display($template, $data = []) {
        echo $this->render($template, $data);
    }
    
    /**
     * 设置布局模板
     * @param string $layout
     */
    public function layout($layout) {
        $this->layout = $layout;
    }
    
    /**
     * 开始定义区块
     * @param string $name
     */
    public function section($name) {
        $this->currentSection = $name;
        ob_start();
    }
    
    /**
     * 结束定义区块
     */
    public function endSection() {
        if ($this->currentSection) {
            $this->sections[$this->currentSection] = ob_get_clean();
            $this->currentSection = null;
        }
    }
    
    /**
     * 输出区块内容
     * @param string $name
     * @param string $default
     */
    public function yield($name, $default = '') {
        echo $this->sections[$name] ?? $default;
    }
    
    /**
     * 包含子模板
     * @param string $template
     * @param array $data
     */
    public function include($template, $data = []) {
        $templateFile = $this->templateDir . $template . '.php';
        if (file_exists($templateFile)) {
            extract(array_merge($this->data, $data));
            include $templateFile;
        }
    }
    
    /**
     * HTML 转义输出
     * @param string $str
     */
    public function e($str) {
        echo htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 条件判断
     * @param bool $condition
     * @param callable $callback
     */
    public function if($condition, $callback) {
        if ($condition) {
            $callback();
        }
    }
    
    /**
     * 循环
     * @param array $items
     * @param callable $callback
     */
    public function foreach($items, $callback) {
        foreach ($items as $key => $item) {
            $callback($item, $key);
        }
    }
    
    /**
     * 静态方法快速渲染
     * @param string $template
     * @param array $data
     * @return string
     */
    public static function make($template, $data = []) {
        $view = new self();
        return $view->render($template, $data);
    }
}
