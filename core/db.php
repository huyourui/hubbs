<?php
/**
 * HuBBS - 数据库连接类
 * 支持PDO，面向亿级数据优化
 */

class DB {
    private static $instance = null;
    private $pdo;
    private $prefix;
    
    private function __construct($config) {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];
        
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
            $this->prefix = $config['prefix'];
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    public static function getInstance($config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                global $db_config;
                $config = $db_config;
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    public function getPdo() {
        return $this->pdo;
    }
    
    public function getPrefix() {
        return $this->prefix;
    }
    
    public function table($name) {
        return $this->prefix . $name;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function insert($table, $data) {
        $table = $this->table($table);
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        // 对字段名添加反引号，避免与保留字冲突
        $quotedFields = array_map(function($f) { return "`{$f}`"; }, $fields);
        
        $sql = "INSERT INTO {$table} (" . implode(',', $quotedFields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $this->query($sql, $values);
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $table = $this->table($table);
        $fields = [];
        $values = [];
        
        foreach ($data as $k => $v) {
            // 对字段名添加反引号，避免与保留字冲突
            $fields[] = "`{$k}` = ?";
            $values[] = $v;
        }
        
        $sql = "UPDATE {$table} SET " . implode(',', $fields) . " WHERE {$where}";
        return $this->query($sql, array_merge($values, $whereParams))->rowCount();
    }
    
    public function delete($table, $where, $params = []) {
        $table = $this->table($table);
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }
    
    public function count($table, $where = '1', $params = []) {
        $table = $this->table($table);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE {$where}";
        $result = $this->fetch($sql, $params);
        return (int)$result['cnt'];
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
