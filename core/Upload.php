<?php
/**
 * HuBBS - 文件上传处理
 */

class Upload {
    
    /**
     * 上传图片
     */
    public static function image($file, $userId, $postId = null) {
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::getUploadError($file['error'])];
        }
        
        // 获取设置
        $allowedExts = explode(',', Settings::get('upload_image_exts', 'jpg,jpeg,png,gif,webp'));
        $allowedExts = array_map('trim', $allowedExts);
        $maxSize = intval(Settings::get('upload_image_max_size', 5242880));
        $maxCount = intval(Settings::get('upload_image_max_count', 10));
        
        // 检查文件大小
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            return ['success' => false, 'message' => "图片大小超过限制，最大允许 {$maxSizeMB}MB"];
        }
        
        // 检查文件后缀
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return ['success' => false, 'message' => '不支持的图片格式，允许：' . implode(', ', $allowedExts)];
        }
        
        // 检查用户已上传图片数量
        $db = DB::getInstance();
        if ($postId) {
            $uploadedCount = $db->count('uploads', 'user_id = ? AND post_id = ? AND file_type = ?', [$userId, $postId, 'image']);
        } else {
            // 未关联帖子的图片（新帖子）
            $uploadedCount = $db->count('uploads', 'user_id = ? AND post_id IS NULL AND file_type = ? AND created_at > ?', 
                [$userId, 'image', date('Y-m-d H:i:s', strtotime('-1 hour'))]);
        }
        if ($uploadedCount >= $maxCount) {
            return ['success' => false, 'message' => "单篇帖子最多上传 {$maxCount} 张图片"];
        }
        
        // 生成存储路径：uploads/images/20260330/xxx.jpg
        $dateDir = date('Ymd');
        $uploadDir = ROOT_DIR . '/uploads/images/' . $dateDir;
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => '创建上传目录失败，请检查目录权限：' . $uploadDir];
            }
        }
        
        // 生成唯一文件名
        $newFileName = uniqid() . '_' . md5(uniqid()) . '.' . $ext;
        $filePath = $uploadDir . '/' . $newFileName;
        
        // 验证图片真实性
        if (!getimagesize($file['tmp_name'])) {
            return ['success' => false, 'message' => '上传的文件不是有效的图片'];
        }
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => '文件保存失败'];
        }
        
        // 保存到数据库
        $relativePath = 'uploads/images/' . $dateDir . '/' . $newFileName;
        $uploadId = $db->insert('uploads', [
            'user_id' => $userId,
            'file_name' => $file['name'],
            'file_path' => $relativePath,
            'file_type' => 'image',
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'post_id' => $postId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true,
            'data' => [
                'id' => $uploadId,
                'url' => $relativePath,
                'name' => $file['name'],
                'size' => $file['size']
            ]
        ];
    }
    
    /**
     * 上传附件
     */
    public static function attachment($file, $userId, $postId = null) {
        // 检查上传错误
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::getUploadError($file['error'])];
        }
        
        // 获取设置
        $allowedExts = explode(',', Settings::get('upload_attachment_exts', 'pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,md'));
        $allowedExts = array_map('trim', $allowedExts);
        $maxSize = intval(Settings::get('upload_attachment_max_size', 10485760));
        $maxCount = intval(Settings::get('upload_attachment_max_count', 5));
        
        // 检查文件大小
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            return ['success' => false, 'message' => "附件大小超过限制，最大允许 {$maxSizeMB}MB"];
        }
        
        // 检查文件后缀
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            return ['success' => false, 'message' => '不支持的附件格式，允许：' . implode(', ', $allowedExts)];
        }
        
        // 检查用户已上传附件数量
        $db = DB::getInstance();
        if ($postId) {
            $uploadedCount = $db->count('uploads', 'user_id = ? AND post_id = ? AND file_type = ?', [$userId, $postId, 'attachment']);
        } else {
            $uploadedCount = $db->count('uploads', 'user_id = ? AND post_id IS NULL AND file_type = ? AND created_at > ?', 
                [$userId, 'attachment', date('Y-m-d H:i:s', strtotime('-1 hour'))]);
        }
        if ($uploadedCount >= $maxCount) {
            return ['success' => false, 'message' => "单篇帖子最多上传 {$maxCount} 个附件"];
        }
        
        // 生成存储路径：uploads/attachments/20260330/xxx.pdf
        $dateDir = date('Ymd');
        $uploadDir = ROOT_DIR . '/uploads/attachments/' . $dateDir;
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => '创建上传目录失败，请检查目录权限：' . $uploadDir];
            }
        }
        
        // 生成唯一文件名
        $newFileName = uniqid() . '_' . md5(uniqid()) . '.' . $ext;
        $filePath = $uploadDir . '/' . $newFileName;
        
        // 移动文件
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => '文件保存失败'];
        }
        
        // 保存到数据库
        $relativePath = 'uploads/attachments/' . $dateDir . '/' . $newFileName;
        $uploadId = $db->insert('uploads', [
            'user_id' => $userId,
            'file_name' => $file['name'],
            'file_path' => $relativePath,
            'file_type' => 'attachment',
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'post_id' => $postId,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true,
            'data' => [
                'id' => $uploadId,
                'url' => $relativePath,
                'name' => $file['name'],
                'size' => $file['size'],
                'ext' => $ext
            ]
        ];
    }
    
    /**
     * 删除上传的文件
     */
    public static function delete($uploadId, $userId) {
        $db = DB::getInstance();
        
        // 获取文件信息
        $upload = $db->fetch("SELECT * FROM {$db->table('uploads')} WHERE id = ? AND user_id = ? LIMIT 1", [$uploadId, $userId]);
        if (!$upload) {
            return ['success' => false, 'message' => '文件不存在或无权限删除'];
        }
        
        // 物理删除文件
        $filePath = ROOT_DIR . '/' . $upload['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // 删除数据库记录
        $db->query("DELETE FROM {$db->table('uploads')} WHERE id = ?", [$uploadId]);
        
        return ['success' => true, 'message' => '删除成功'];
    }
    
    /**
     * 获取用户上传的文件列表
     */
    public static function getUserUploads($userId, $postId = null, $fileType = null) {
        $db = DB::getInstance();
        
        $where = 'user_id = ?';
        $params = [$userId];
        
        if ($postId !== null) {
            $where .= ' AND post_id = ?';
            $params[] = $postId;
        }
        
        if ($fileType) {
            $where .= ' AND file_type = ?';
            $params[] = $fileType;
        }
        
        $sql = "SELECT * FROM {$db->table('uploads')} WHERE {$where} ORDER BY created_at DESC";
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * 关联上传文件到帖子
     */
    public static function linkToPost($userId, $postId) {
        $db = DB::getInstance();
        $db->query("UPDATE {$db->table('uploads')} SET post_id = ? WHERE user_id = ? AND post_id IS NULL", [$postId, $userId]);
    }

    /**
     * 删除帖子关联的所有文件（图片和附件）
     * @param int $postId 帖子ID
     * @return array 删除结果
     */
    public static function deleteByPost($postId) {
        $db = DB::getInstance();

        // 获取帖子关联的所有文件
        $uploads = $db->fetchAll("SELECT * FROM {$db->table('uploads')} WHERE post_id = ?", [$postId]);

        $deletedCount = 0;
        $failedFiles = [];

        foreach ($uploads as $upload) {
            // 物理删除文件
            $filePath = ROOT_DIR . '/' . $upload['file_path'];
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    $deletedCount++;
                } else {
                    $failedFiles[] = $upload['file_name'];
                }
            } else {
                // 文件不存在，也视为已删除
                $deletedCount++;
            }

            // 删除数据库记录
            $db->query("DELETE FROM {$db->table('uploads')} WHERE id = ?", [$upload['id']]);
        }

        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'failed_files' => $failedFiles,
            'total' => count($uploads)
        ];
    }
    
    /**
     * 获取上传错误信息
     */
    private static function getUploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制',
            UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件上传不完整',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件写入失败',
            UPLOAD_ERR_EXTENSION => '上传被扩展阻止',
        ];
        return $errors[$code] ?? '未知上传错误';
    }
}
