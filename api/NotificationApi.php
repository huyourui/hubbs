<?php
/**
 * HuBBS - 通知API控制器
 */

class NotificationApi {
    
    /**
     * 获取通知列表
     * GET /api/notifications
     */
    public function index() {
        ApiAuth::check();
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = min(50, intval($_GET['per_page'] ?? 20));
        $isRead = isset($_GET['is_read']) ? intval($_GET['is_read']) : null;
        
        $query = Notification::query()->where('user_id', Auth::id());
        
        if ($isRead !== null) {
            $query->where('is_read', $isRead);
        }
        
        $result = $query->orderBy('created_at', 'desc')
                       ->paginate($perPage, $page);
        
        $items = [];
        foreach ($result['data'] as $notification) {
            $items[] = [
                'id' => $notification->id,
                'type' => $notification->type,
                'type_name' => Notification::getTypeName($notification->type),
                'title' => $notification->title,
                'content' => $notification->content,
                'sender_id' => $notification->sender_id,
                'target_id' => $notification->target_id,
                'target_type' => $notification->target_type,
                'is_read' => (bool)$notification->is_read,
                'created_at' => $notification->created_at,
                'read_at' => $notification->read_at
            ];
        }
        
        return ApiResponse::paginate($items, $result['total'], $page, $perPage);
    }
    
    /**
     * 标记通知为已读
     * PUT /api/notifications/{id}/read
     */
    public function markAsRead($id) {
        ApiAuth::check();
        
        $notification = Notification::find($id);
        
        if (!$notification) {
            return ApiResponse::notFound('通知不存在');
        }
        
        // 检查权限
        if ($notification->user_id != Auth::id()) {
            return ApiResponse::forbidden('无权操作此通知');
        }
        
        $notification->markAsRead();
        
        return ApiResponse::success(null, '已标记为已读');
    }
    
    /**
     * 标记所有通知为已读
     * PUT /api/notifications/read-all
     */
    public function markAllAsRead() {
        ApiAuth::check();
        
        $db = DB::getInstance();
        $db->update('notifications', [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ], 'user_id = ? AND is_read = 0', [Auth::id()]);
        
        // 重置用户未读消息数
        $db->update('users', ['unread_count' => 0], 'id = ?', [Auth::id()]);
        
        return ApiResponse::success(null, '所有通知已标记为已读');
    }
}
