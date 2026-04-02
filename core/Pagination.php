<?php
/**
 * HuBBS - 分页类
 * 封装分页逻辑和渲染
 */

class Pagination {
    
    private $total;
    private $page;
    private $perPage;
    private $url;
    private $totalPages;
    
    /**
     * 构造函数
     * @param int $total 总记录数
     * @param int $page 当前页
     * @param int $perPage 每页记录数
     * @param string $url 基础URL（包含页码参数）
     */
    public function __construct($total, $page, $perPage, $url) {
        $this->total = $total;
        $this->perPage = $perPage;
        $this->url = $url;
        $this->totalPages = max(1, ceil($total / $perPage));
        $this->page = max(1, min($page, $this->totalPages));
    }
    
    /**
     * 获取当前页
     * @return int
     */
    public function getPage() {
        return $this->page;
    }
    
    /**
     * 获取每页记录数
     * @return int
     */
    public function getPerPage() {
        return $this->perPage;
    }
    
    /**
     * 获取总页数
     * @return int
     */
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    /**
     * 获取 OFFSET
     * @return int
     */
    public function getOffset() {
        return ($this->page - 1) * $this->perPage;
    }
    
    /**
     * 是否有上一页
     * @return bool
     */
    public function hasPrev() {
        return $this->page > 1;
    }
    
    /**
     * 是否有下一页
     * @return bool
     */
    public function hasNext() {
        return $this->page < $this->totalPages;
    }
    
    /**
     * 获取上一页URL
     * @return string
     */
    public function getPrevUrl() {
        return $this->url . ($this->page - 1);
    }
    
    /**
     * 获取下一页URL
     * @return string
     */
    public function getNextUrl() {
        return $this->url . ($this->page + 1);
    }
    
    /**
     * 渲染分页HTML
     * @return string
     */
    public function render() {
        if ($this->totalPages <= 1) {
            return '';
        }
        
        $html = '<div class="pagination">';
        
        // 上一页
        if ($this->hasPrev()) {
            $html .= '<a href="' . $this->getPrevUrl() . '" class="page-btn">&lt;</a>';
        }
        
        // 页码
        $start = max(1, $this->page - 2);
        $end = min($this->totalPages, $this->page + 2);
        
        if ($start > 1) {
            $html .= '<a href="' . $this->url . '1" class="page-btn">1</a>';
            if ($start > 2) {
                $html .= '<span class="page-ellipsis">...</span>';
            }
        }
        
        for ($i = $start; $i <= $end; $i++) {
            $active = $i == $this->page ? ' active' : '';
            $html .= '<a href="' . $this->url . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
        }
        
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $html .= '<span class="page-ellipsis">...</span>';
            }
            $html .= '<a href="' . $this->url . $this->totalPages . '" class="page-btn">' . $this->totalPages . '</a>';
        }
        
        // 下一页
        if ($this->hasNext()) {
            $html .= '<a href="' . $this->getNextUrl() . '" class="page-btn">&gt;</a>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * 简化的分页数据获取
     * @param DB $db 数据库实例
     * @param string $table 表名
     * @param string $where WHERE条件
     * @param array $params 参数
     * @param string $order ORDER BY
     * @return array ['items' => [], 'pagination' => Pagination]
     */
    public static function get($db, $table, $where = '', $params = [], $order = 'id DESC') {
        $page = intval($_GET['page'] ?? 1);
        $perPage = POSTS_PER_PAGE;
        
        // 获取总数
        $countSql = "SELECT COUNT(*) FROM {$db->table($table)}";
        if ($where) {
            $countSql .= " WHERE $where";
        }
        $total = $db->fetchColumn($countSql, $params);
        
        // 构建URL
        $url = '?';
        foreach ($_GET as $k => $v) {
            if ($k != 'page') {
                $url .= $k . '=' . urlencode($v) . '&';
            }
        }
        $url .= 'page=';
        
        $pagination = new self($total, $page, $perPage, $url);
        
        // 获取数据
        $sql = "SELECT * FROM {$db->table($table)}";
        if ($where) {
            $sql .= " WHERE $where";
        }
        $sql .= " ORDER BY $order LIMIT {$pagination->getPerPage()} OFFSET {$pagination->getOffset()}";
        $items = $db->fetchAll($sql, $params);
        
        return [
            'items' => $items,
            'pagination' => $pagination
        ];
    }
}
