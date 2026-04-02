<?php
/**
 * HuBBS - ORM Model基类
 * 提供Active Record风格的数据库操作
 * 
 * 使用示例:
 * $user = User::find(1);
 * $user->username = 'newname';
 * $user->save();
 * 
 * $users = User::where('status', 1)->orderBy('created_at', 'desc')->get();
 */

abstract class Model {
    // 数据库连接实例
    protected static $db = null;
    
    // 表名（子类可覆盖）
    protected static $table = '';
    
    // 主键字段
    protected static $primaryKey = 'id';
    
    // 可批量赋值的字段
    protected static $fillable = [];
    
    // 隐藏的字段（序列化时不显示）
    protected static $hidden = [];
    
    // 字段默认值
    protected static $defaults = [];
    
    // 当前查询构建器实例
    protected $queryBuilder = null;
    
    // 模型属性
    protected $attributes = [];
    
    // 原始属性（用于判断修改）
    protected $original = [];
    
    // 是否已存在（数据库中）
    protected $exists = false;
    
    // 查询条件
    protected $wheres = [];
    protected $bindings = [];
    protected $orders = [];
    protected $limit = null;
    protected $offset = null;
    protected $selects = ['*'];
    protected $joins = [];
    protected $groups = [];
    protected $havings = [];
    
    /**
     * 构造函数
     */
    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }
    
    /**
     * 获取数据库实例
     */
    protected static function getDb() {
        if (self::$db === null) {
            self::$db = DB::getInstance();
        }
        return self::$db;
    }
    
    /**
     * 获取表名
     */
    protected static function getTable() {
        if (static::$table) {
            return static::$table;
        }
        // 自动推断表名：UserModel -> users
        $class = basename(str_replace('\\', '/', static::class));
        $class = preg_replace('/Model$/', '', $class);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class)) . 's';
    }
    
    /**
     * 填充属性
     */
    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            if (empty(static::$fillable) || in_array($key, static::$fillable)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }
    
    /**
     * 设置属性
     */
    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
        return $this;
    }
    
    /**
     * 获取属性
     */
    public function getAttribute($key) {
        return $this->attributes[$key] ?? static::$defaults[$key] ?? null;
    }
    
    /**
     * 魔术方法：获取属性
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }
    
    /**
     * 魔术方法：设置属性
     */
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }
    
    /**
     * 魔术方法：isset
     */
    public function __isset($key) {
        return isset($this->attributes[$key]);
    }
    
    /**
     * 转换为数组
     */
    public function toArray() {
        $array = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, static::$hidden)) {
                $array[$key] = $value;
            }
        }
        return $array;
    }
    
    /**
     * 转换为JSON
     */
    public function toJson($options = 0) {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * 创建新实例（静态方法）
     */
    public static function create(array $attributes = []) {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    
    /**
     * 保存模型
     */
    public function save() {
        $db = self::getDb();
        $table = self::getTable();
        
        if ($this->exists) {
            // 更新
            $data = $this->getDirty();
            if (!empty($data)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $db->update($table, $data, static::$primaryKey . ' = ?', [$this->getKey()]);
                $this->syncOriginal();
            }
        } else {
            // 插入
            $data = $this->attributes;
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $id = $db->insert($table, $data);
            $this->setAttribute(static::$primaryKey, $id);
            $this->exists = true;
            $this->syncOriginal();
        }
        
        return $this;
    }
    
    /**
     * 获取主键值
     */
    public function getKey() {
        return $this->getAttribute(static::$primaryKey);
    }
    
    /**
     * 获取修改过的字段
     */
    protected function getDirty() {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }
    
    /**
     * 同步原始数据
     */
    protected function syncOriginal() {
        $this->original = $this->attributes;
    }
    
    /**
     * 删除模型
     */
    public function delete() {
        if (!$this->exists) {
            return false;
        }
        
        $db = self::getDb();
        $table = self::getTable();
        $db->delete($table, static::$primaryKey . ' = ?', [$this->getKey()]);
        $this->exists = false;
        return true;
    }
    
    /**
     * 软删除
     */
    public function softDelete() {
        if (!$this->exists) {
            return false;
        }
        
        $this->deleted_at = date('Y-m-d H:i:s');
        $this->save();
        return true;
    }
    
    /**
     * 恢复软删除
     */
    public function restore() {
        if (!$this->exists || !$this->deleted_at) {
            return false;
        }
        
        $this->deleted_at = null;
        $this->save();
        return true;
    }
    
    // ==================== 查询构建器方法 ====================
    
    /**
     * 创建新的查询实例
     */
    public static function query() {
        return new static();
    }
    
    /**
     * SELECT字段
     */
    public function select($columns) {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * WHERE条件
     */
    public function where($column, $operator = null, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'boolean' => 'AND'
        ];
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * OR WHERE条件
     */
    public function orWhere($column, $operator = null, $value = null) {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'boolean' => 'OR'
        ];
        $this->bindings[] = $value;
        
        return $this;
    }
    
    /**
     * WHERE IN条件
     */
    public function whereIn($column, array $values) {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => 'AND'
        ];
        $this->bindings = array_merge($this->bindings, $values);
        
        return $this;
    }
    
    /**
     * WHERE NULL条件
     */
    public function whereNull($column) {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * WHERE NOT NULL条件
     */
    public function whereNotNull($column) {
        $this->wheres[] = [
            'type' => 'notNull',
            'column' => $column,
            'boolean' => 'AND'
        ];
        
        return $this;
    }
    
    /**
     * JOIN连接
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER') {
        $this->joins[] = [
            'type' => $type,
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second
        ];
        
        return $this;
    }
    
    /**
     * LEFT JOIN
     */
    public function leftJoin($table, $first, $operator = null, $second = null) {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }
    
    /**
     * ORDER BY排序
     */
    public function orderBy($column, $direction = 'asc') {
        $this->orders[] = [$column, strtoupper($direction)];
        return $this;
    }
    
    /**
     * GROUP BY分组
     */
    public function groupBy($columns) {
        $this->groups = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    /**
     * LIMIT限制
     */
    public function limit($limit) {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * OFFSET偏移
     */
    public function offset($offset) {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * 分页
     */
    public function paginate($perPage = 20, $page = null) {
        $page = $page ?: (intval($_GET['page'] ?? 1));
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        
        $total = $this->count();
        $items = $this->get();
        
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => max(1, ceil($total / $perPage))
        ];
    }
    
    /**
     * 获取查询结果
     */
    public function get() {
        $sql = $this->buildSelectQuery();
        $results = self::getDb()->fetchAll($sql, $this->bindings);
        
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            $model->attributes = $result;
            $model->original = $result;
            $model->exists = true;
            $models[] = $model;
        }
        
        return $models;
    }
    
    /**
     * 获取第一条记录
     */
    public function first() {
        $this->limit = 1;
        $results = $this->get();
        return $results[0] ?? null;
    }
    
    /**
     * 根据主键查找
     */
    public static function find($id) {
        return static::query()->where(static::$primaryKey, $id)->first();
    }
    
    /**
     * 查找或失败
     */
    public static function findOrFail($id) {
        $model = static::find($id);
        if (!$model) {
            throw new Exception('Record not found');
        }
        return $model;
    }
    
    /**
     * 获取所有记录
     */
    public static function all() {
        return static::query()->get();
    }
    
    /**
     * 统计数量
     */
    public function count() {
        $this->selects = ['COUNT(*) as count'];
        $sql = $this->buildSelectQuery();
        $result = self::getDb()->fetch($sql, $this->bindings);
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * 是否存在
     */
    public function exists() {
        return $this->count() > 0;
    }
    
    /**
     * 获取最大值
     */
    public function max($column) {
        $this->selects = ["MAX({$column}) as max"];
        $sql = $this->buildSelectQuery();
        $result = self::getDb()->fetch($sql, $this->bindings);
        return $result['max'];
    }
    
    /**
     * 获取最小值
     */
    public function min($column) {
        $this->selects = ["MIN({$column}) as min"];
        $sql = $this->buildSelectQuery();
        $result = self::getDb()->fetch($sql, $this->bindings);
        return $result['min'];
    }
    
    /**
     * 获取平均值
     */
    public function avg($column) {
        $this->selects = ["AVG({$column}) as avg"];
        $sql = $this->buildSelectQuery();
        $result = self::getDb()->fetch($sql, $this->bindings);
        return $result['avg'];
    }
    
    /**
     * 求和
     */
    public function sum($column) {
        $this->selects = ["SUM({$column}) as sum"];
        $sql = $this->buildSelectQuery();
        $result = self::getDb()->fetch($sql, $this->bindings);
        return $result['sum'];
    }
    
    /**
     * 构建SELECT查询SQL
     */
    protected function buildSelectQuery() {
        $table = self::getTable();
        $db = self::getDb();
        $tableName = $db->table($table);
        
        // SELECT
        $select = implode(', ', $this->selects);
        $sql = "SELECT {$select} FROM {$tableName}";
        
        // JOIN
        foreach ($this->joins as $join) {
            $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
        }
        
        // WHERE
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . $this->buildWheres();
        }
        
        // GROUP BY
        if (!empty($this->groups)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groups);
        }
        
        // ORDER BY
        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                $orderClauses[] = "{$order[0]} {$order[1]}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderClauses);
        }
        
        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }
    
    /**
     * 构建WHERE子句
     */
    protected function buildWheres() {
        $clauses = [];
        
        foreach ($this->wheres as $i => $where) {
            $boolean = $i === 0 ? '' : " {$where['boolean']} ";
            
            switch ($where['type']) {
                case 'basic':
                    $clauses[] = $boolean . "{$where['column']} {$where['operator']} ?";
                    break;
                    
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = $boolean . "{$where['column']} IN ({$placeholders})";
                    break;
                    
                case 'null':
                    $clauses[] = $boolean . "{$where['column']} IS NULL";
                    break;
                    
                case 'notNull':
                    $clauses[] = $boolean . "{$where['column']} IS NOT NULL";
                    break;
            }
        }
        
        return implode('', $clauses);
    }
    
    /**
     * 批量插入
     */
    public static function insertBatch(array $data) {
        if (empty($data)) {
            return 0;
        }
        
        $db = self::getDb();
        $table = self::getTable();
        
        $columns = array_keys($data[0]);
        $columnStr = implode(', ', array_map(function($col) { return "`{$col}`"; }, $columns));
        
        $values = [];
        $params = [];
        foreach ($data as $row) {
            $placeholders = array_fill(0, count($columns), '?');
            $values[] = '(' . implode(', ', $placeholders) . ')';
            foreach ($columns as $col) {
                $params[] = $row[$col] ?? null;
            }
        }
        
        $sql = "INSERT INTO {$db->table($table)} ({$columnStr}) VALUES " . implode(', ', $values);
        $db->query($sql, $params);
        
        return count($data);
    }
    
    /**
     * 更新或创建
     */
    public static function updateOrCreate(array $attributes, array $values = []) {
        $model = static::query()->where($attributes)->first();
        
        if ($model) {
            $model->fill($values);
            $model->save();
        } else {
            $model = static::create(array_merge($attributes, $values));
        }
        
        return $model;
    }
    
    /**
     * 关系：Belongs To
     */
    public function belongsTo($related, $foreignKey = null, $localKey = 'id') {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', $related))) . '_id';
        $relatedModel = new $related();
        return $relatedModel::find($this->getAttribute($foreignKey));
    }
    
    /**
     * 关系：Has Many
     */
    public function hasMany($related, $foreignKey = null, $localKey = 'id') {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_id';
        $relatedModel = new $related();
        return $relatedModel::query()->where($foreignKey, $this->getAttribute($localKey))->get();
    }
    
    /**
     * 关系：Has One
     */
    public function hasOne($related, $foreignKey = null, $localKey = 'id') {
        $foreignKey = $foreignKey ?: strtolower(basename(str_replace('\\', '/', static::class))) . '_id';
        $relatedModel = new $related();
        return $relatedModel::query()->where($foreignKey, $this->getAttribute($localKey))->first();
    }
}
