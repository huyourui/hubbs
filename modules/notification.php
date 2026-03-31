<?php
/**
 * HuBBS - 消息中心模块
 * 处理消息列表、标记已读等
 */

class NotificationModule {
    
    public function handle() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                return $this->list();
            case 'markRead':
                return $this->markRead();
            case 'markAllRead':
                return $this->markAllRead();
            case 'delete':
                return $this->delete();
            case 'count':
                return $this->getUnreadCount();
            default:
                redirect('index.php?module=notification&action=list');
        }
    }
    
    /**
     * 消息列表
     */
    private function list() {
        if (Auth::guest()) {
            redirect('index.php?module=user&action=login');
        }
        
        $db = DB::getInstance();
        $userId = Auth::id();
        $page = max(1, intval($_GET['page'] ?? 1));
        $type = $_GET['type'] ?? null;
        $isRead = isset($_GET['is_read']) ? intval($_GET['is_read']) : null;
        
        $perPage = 20;
        
        // 获取消息列表
        $notifications = Notification::getUserNotifications($userId, $type, $isRead, $page, $perPage);
        
        // 获取各类型消息数量
        $typeCounts = $this->getTypeCounts($userId);
        $unreadCount = Notification::getUnreadCount($userId);
        
        return [
            'template' => 'notification_list',
            'data' => [
                'notifications' => $notifications,
                'typeCounts' => $typeCounts,
                'unreadCount' => $unreadCount,
                'currentType' => $type,
                'currentIsRead' => $isRead,
                'page' => $page,
                'totalPages' => ceil($typeCounts['total'] / $perPage)
            ]
        ];
    }
    
    /**
     * 获取各类型消息数量
     */
    private function getTypeCounts($userId) {
        $db = DB::getInstance();
        
        $counts = [
            'total' => 0,
            'unread' => 0,
            'reply_post' => 0,
            'reply_comment' => 0,
            'like_post' => 0,
            'favorite_post' => 0,
            'system' => 0
        ];
        
        // 总数量
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM {$db->table('notifications')} WHERE user_id = ?",
            [$userId]
        );
        $counts['total'] = (int)$result['count'];
        
        // 未读数量
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM {$db->table('notifications')} WHERE user_id = ? AND is_read = 0",
            [$userId]
        );
        $counts['unread'] = (int)$result['count'];
        
        // 各类型数量
        $results = $db->fetchAll(
            "SELECT type, COUNT(*) as count FROM {$db->table('notifications')} WHERE user_id = ? GROUP BY type",
            [$userId]
        );
        foreach ($results as $row) {
            $counts[$row['type']] = (int)$row['count'];
        }
        
        return $counts;
    }
    
    /**
     * 标记消息为已读
     */
    private function markRead() {
        if (Auth::guest()) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '请先登录']);
                exit;
            }
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?module=notification&action=list');
        }
        
        $notificationId = intval($_POST['id'] ?? 0);
        
        if ($notificationId <= 0) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            redirect('index.php?module=notification&action=list');
        }
        
        $result = Notification::markAsRead($notificationId, Auth::id());
        
        if ($this->isAjaxRequest()) {
            echo json_encode([
                'success' => $result,
                'unreadCount' => Notification::getUnreadCount(Auth::id())
            ]);
            exit;
        }
        
        redirect('index.php?module=notification&action=list');
    }
    
    /**
     * 标记所有消息为已读
     */
    private function markAllRead() {
        if (Auth::guest()) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '请先登录']);
                exit;
            }
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?module=notification&action=list');
        }
        
        Notification::markAllAsRead(Auth::id());
        
        if ($this->isAjaxRequest()) {
            echo json_encode(['success' => true, 'unreadCount' => 0]);
            exit;
        }
        
        set_message('已全部标记为已读');
        redirect('index.php?module=notification&action=list');
    }
    
    /**
     * 删除消息
     */
    private function delete() {
        if (Auth::guest()) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '请先登录']);
                exit;
            }
            redirect('index.php?module=user&action=login');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php?module=notification&action=list');
        }
        
        $notificationId = intval($_POST['id'] ?? 0);
        
        if ($notificationId <= 0) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => '参数错误']);
                exit;
            }
            redirect('index.php?module=notification&action=list');
        }
        
        $result = Notification::delete($notificationId, Auth::id());
        
        if ($this->isAjaxRequest()) {
            echo json_encode([
                'success' => $result,
                'unreadCount' => Notification::getUnreadCount(Auth::id())
            ]);
            exit;
        }
        
        redirect('index.php?module=notification&action=list');
    }
    
    /**
     * 获取未读消息数（AJAX）
     */
    private function getUnreadCount() {
        if (Auth::guest()) {
            echo json_encode(['success' => true, 'count' => 0]);
            exit;
        }
        
        $count = Notification::getUnreadCount(Auth::id());
        
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }
    
    /**
     * 判断是否是AJAX请求
     */
    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}