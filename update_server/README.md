# HuBBS 更新服务器

## 部署说明

### 1. 服务器要求
- PHP 7.4+
- 支持 HTTPS（推荐）
- 足够的磁盘空间存储更新包

### 2. 目录结构
```
update_server/
├── check.php          # 版本检查接口
├── download.php       # 文件下载接口
├── downloads/         # 更新包存放目录
│   ├── hubbs-1.6.0.zip
│   └── hubbs-1.7.0.zip
└── README.md
```

### 3. 配置步骤

1. **创建下载目录**
```bash
mkdir downloads
chmod 755 downloads
```

2. **上传更新包**
将打包好的更新包上传到 `downloads/` 目录

3. **修改 check.php**
编辑 `check.php` 中的版本信息：
```php
$latestVersion = [
    'version' => '1.7.0',
    'download_url' => 'https://your-domain.com/update_server/download.php?version=1.7.0',
    // ...
];
```

4. **配置Web服务器**
确保Web服务器可以访问这些文件

### 4. API接口

#### 检查更新
```
GET https://your-domain.com/update_server/check.php?version=1.6.0
```

响应：
```json
{
  "success": true,
  "has_update": true,
  "current_version": "1.6.0",
  "latest_version": "1.7.0",
  "download_url": "https://...",
  "release_notes": "...",
  "release_date": "2025-04-02",
  "file_size": 1234567,
  "file_hash": "md5_hash"
}
```

#### 下载更新包
```
GET https://your-domain.com/update_server/download.php?version=1.7.0
```

支持断点续传（Range请求）

### 5. 安全建议

1. 启用HTTPS
2. 限制下载频率（可使用CDN）
3. 定期清理旧版本更新包
4. 使用文件哈希校验
