<?php
/**
 * HuBBS - 搜索模块
 * 
 * @package HuBBS
 * @version 1.8.2
 */

class SearchModule {
    
    /**
     * 路由处理器
     */
    public function handle($action = 'index') {
        return $this->index();
    }
    
    /**
     * 执行搜索
     */
    public function index() {
        $keyword = trim($_GET['keyword'] ?? '');
        $type = $_GET['type'] ?? 'all'; // all, title, content
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20;
        
        $results = [];
        $total = 0;
        $error = '';
        
        if (!empty($keyword)) {
            // 记录搜索日志
            $this->logSearch($keyword, 0);
            
            // 执行搜索
            $searchResult = $this->performSearch($keyword, $type, $page, $perPage);
            $results = $searchResult['results'];
            $total = $searchResult['total'];
            
            // 更新搜索结果数量
            if (!empty($results)) {
                $this->updateSearchLogCount($keyword, $total);
            }
        }
        
        return [
            'template' => 'search_results',
            'data' => [
                'keyword' => $keyword,
                'type' => $type,
                'results' => $results,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($total / $perPage),
                'error' => $error
            ]
        ];
    }
    
    /**
     * 执行搜索查询
     */
    private function performSearch($keyword, $type, $page, $perPage) {
        $db = DB::getInstance();
        $offset = ($page - 1) * $perPage;
        
        // 清理关键词
        $keyword = $this->sanitizeKeyword($keyword);
        
        if (empty($keyword)) {
            return ['results' => [], 'total' => 0];
        }
        
        // 构建搜索条件
        $searchFields = [];
        switch ($type) {
            case 'title':
                $searchFields = ['p.title'];
                break;
            case 'content':
                $searchFields = ['p.content'];
                break;
            case 'all':
            default:
                $searchFields = ['p.title', 'p.content'];
                break;
        }
        
        // 构建 MATCH AGAINST 查询
        $matchFields = implode(', ', $searchFields);
        $matchAgainst = "MATCH({$matchFields}) AGAINST(:keyword IN BOOLEAN MODE)";
        
        // 查询总数
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$db->table('posts')} p 
                     WHERE {$matchAgainst}";
        
        $countResult = $db->fetch($countSql, [':keyword' => $keyword]);
        $total = (int) ($countResult['total'] ?? 0);
        
        // 如果没有全文索引匹配结果，使用 LIKE 模糊查询作为备选
        if ($total === 0) {
            return $this->performFallbackSearch($keyword, $type, $page, $perPage);
        }
        
        // 查询结果
        $sql = "SELECT p.*, u.username, u.avatar, f.name as forum_name,
                       {$matchAgainst} as relevance
                FROM {$db->table('posts')} p 
                LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
                LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
                WHERE {$matchAgainst} 
                ORDER BY relevance DESC, p.created_at DESC
                LIMIT {$offset}, {$perPage}";
        
        $results = $db->fetchAll($sql, [':keyword' => $keyword]);
        
        // 处理搜索结果，高亮关键词
        foreach ($results as &$result) {
            $result['title_highlighted'] = $this->highlightKeyword($result['title'], $keyword);
            $result['content_highlighted'] = $this->highlightKeyword($this->getExcerpt($result['content'], $keyword), $keyword);
        }
        
        return ['results' => $results, 'total' => $total];
    }
    
    /**
     * 备选搜索（使用 LIKE）
     */
    private function performFallbackSearch($keyword, $type, $page, $perPage) {
        $db = DB::getInstance();
        $offset = ($page - 1) * $perPage;
        
        // 构建 LIKE 条件
        $likeKeyword = '%' . $keyword . '%';
        $conditions = [];
        
        switch ($type) {
            case 'title':
                $conditions[] = "p.title LIKE :keyword";
                break;
            case 'content':
                $conditions[] = "p.content LIKE :keyword";
                break;
            case 'all':
            default:
                $conditions[] = "(p.title LIKE :keyword OR p.content LIKE :keyword)";
                break;
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // 查询总数
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$db->table('posts')} p 
                     WHERE {$whereClause}";
        
        $countResult = $db->fetch($countSql, [':keyword' => $likeKeyword]);
        $total = (int) ($countResult['total'] ?? 0);
        
        // 查询结果
        $sql = "SELECT p.*, u.username, u.avatar, f.name as forum_name
                FROM {$db->table('posts')} p 
                LEFT JOIN {$db->table('users')} u ON p.user_id = u.id 
                LEFT JOIN {$db->table('forums')} f ON p.forum_id = f.id 
                WHERE {$whereClause} 
                ORDER BY p.created_at DESC
                LIMIT {$offset}, {$perPage}";
        
        $results = $db->fetchAll($sql, [':keyword' => $likeKeyword]);
        
        // 处理搜索结果
        foreach ($results as &$result) {
            $result['title_highlighted'] = $this->highlightKeyword($result['title'], $keyword);
            $result['content_highlighted'] = $this->highlightKeyword($this->getExcerpt($result['content'], $keyword), $keyword);
        }
        
        return ['results' => $results, 'total' => $total];
    }
    
    /**
     * 清理关键词
     */
    private function sanitizeKeyword($keyword) {
        // 移除特殊字符
        $keyword = preg_replace('/[+%<>@()~*"]/', ' ', $keyword);
        // 移除多余空格
        $keyword = preg_replace('/\s+/', ' ', $keyword);
        // 限制长度
        $keyword = mb_substr(trim($keyword), 0, 100);
        return $keyword;
    }
    
    /**
     * 获取内容摘要
     */
    private function getExcerpt($content, $keyword, $length = 200) {
        // 移除 HTML 标签
        $text = strip_tags($content);
        
        // 查找关键词位置
        $pos = mb_stripos($text, $keyword);
        
        if ($pos !== false) {
            // 从关键词前50个字符开始
            $start = max(0, $pos - 50);
            $excerpt = mb_substr($text, $start, $length);
            if ($start > 0) {
                $excerpt = '...' . $excerpt;
            }
        } else {
            // 取前200个字符
            $excerpt = mb_substr($text, 0, $length);
        }
        
        if (mb_strlen($text) > $length) {
            $excerpt .= '...';
        }
        
        return $excerpt;
    }
    
    /**
     * 高亮关键词
     */
    private function highlightKeyword($text, $keyword) {
        if (empty($keyword)) {
            return $text;
        }
        
        // 分割关键词
        $keywords = explode(' ', trim($keyword));
        
        foreach ($keywords as $word) {
            if (empty($word)) continue;
            $word = preg_quote($word, '/');
            $text = preg_replace('/(' . $word . ')/iu', '<mark>$1</mark>', $text);
        }
        
        return $text;
    }
    
    /**
     * 记录搜索日志
     */
    private function logSearch($keyword, $resultsCount) {
        $db = DB::getInstance();
        
        $userId = 0;
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user['id'] ?? 0;
        }
        
        try {
            $db->insert('search_logs', [
                'keyword' => $keyword,
                'user_id' => $userId,
                'ip' => get_client_ip(),
                'results_count' => $resultsCount,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // 忽略日志错误
        }
    }
    
    /**
     * 更新搜索结果数量
     */
    private function updateSearchLogCount($keyword, $count) {
        $db = DB::getInstance();
        
        try {
            $db->query("UPDATE {$db->table('search_logs')} 
                       SET results_count = ? 
                       WHERE keyword = ? 
                       ORDER BY id DESC LIMIT 1", 
                       [$count, $keyword]);
        } catch (Exception $e) {
            // 忽略错误
        }
    }
}
