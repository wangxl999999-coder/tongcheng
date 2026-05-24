<?php
require_once __DIR__ . '/BaseController.php';

class IndexController extends BaseController {
    
    public function index() {
        $today = strtotime(date('Y-m-d'));
        
        $todayOrderCount = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_order WHERE created_at >= ?",
            [$today]
        )['total'];
        
        $todayAmount = $this->db->fetch(
            "SELECT IFNULL(SUM(pay_amount), 0) as total FROM tc_order WHERE created_at >= ? AND status >= 1",
            [$today]
        )['total'];
        
        $todayNewUser = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_user WHERE created_at >= ?",
            [$today]
        )['total'];
        
        $pendingOrder = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_order WHERE status IN (0, 1, 2, 3)"
        )['total'];
        
        $pendingDispatch = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_order WHERE status = 1"
        )['total'];
        
        $onlineTechnician = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_technician WHERE is_online = 1 AND status = 1"
        )['total'];
        
        $totalUser = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_user"
        )['total'];
        
        $totalOrder = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_order"
        )['total'];
        
        $totalAmount = $this->db->fetch(
            "SELECT IFNULL(SUM(pay_amount), 0) as total FROM tc_order WHERE status >= 1"
        )['total'];
        
        $recentOrders = $this->db->fetchAll(
            "SELECT o.*, u.nickname, s.name as service_name FROM tc_order o 
             LEFT JOIN tc_user u ON o.user_id = u.id 
             LEFT JOIN tc_service s ON o.service_id = s.id 
             ORDER BY o.created_at DESC LIMIT 10"
        );
        
        $statusMap = [
            0 => '待付款',
            1 => '待派单',
            2 => '待接单',
            3 => '待服务',
            4 => '服务中',
            5 => '待支付差额',
            6 => '已完成',
            7 => '已取消',
            8 => '已退款'
        ];
        
        $statusColor = [
            0 => 'warning',
            1 => 'info',
            2 => 'info',
            3 => 'info',
            4 => 'success',
            5 => 'danger',
            6 => 'success',
            7 => 'default',
            8 => 'default'
        ];
        
        $orderTrend = $this->getOrderTrend();
        $serviceTypeRatio = $this->getServiceTypeRatio();
        $cityOrderRank = $this->getCityOrderRank();
        
        $this->layout('index/index', [
            'title' => '工作台',
            'todayOrderCount' => $todayOrderCount,
            'todayAmount' => $todayAmount,
            'todayNewUser' => $todayNewUser,
            'pendingOrder' => $pendingOrder,
            'pendingDispatch' => $pendingDispatch,
            'onlineTechnician' => $onlineTechnician,
            'totalUser' => $totalUser,
            'totalOrder' => $totalOrder,
            'totalAmount' => $totalAmount,
            'recentOrders' => $recentOrders,
            'statusMap' => $statusMap,
            'statusColor' => $statusColor,
            'orderTrend' => $orderTrend,
            'serviceTypeRatio' => $serviceTypeRatio,
            'cityOrderRank' => $cityOrderRank
        ]);
    }
    
    private function getOrderTrend() {
        $days = [];
        $orderCounts = [];
        $amounts = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $start = strtotime(date('Y-m-d', strtotime("-$i day")));
            $end = $start + 86400;
            $days[] = date('m-d', $start);
            
            $row = $this->db->fetch(
                "SELECT COUNT(*) as count, IFNULL(SUM(pay_amount), 0) as amount 
                 FROM tc_order WHERE created_at >= ? AND created_at < ? AND status >= 1",
                [$start, $end]
            );
            $orderCounts[] = (int)$row['count'];
            $amounts[] = (float)$row['amount'];
        }
        
        return [
            'days' => $days,
            'orderCounts' => $orderCounts,
            'amounts' => $amounts
        ];
    }
    
    private function getServiceTypeRatio() {
        $rows = $this->db->fetchAll(
            "SELECT sc.name, COUNT(o.id) as count 
             FROM tc_order o 
             LEFT JOIN tc_service_category sc ON o.service_id = sc.id 
             WHERE o.status >= 1 
             GROUP BY o.service_id 
             ORDER BY count DESC LIMIT 6"
        );
        
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'name' => $row['name'] ?: '其他',
                'value' => (int)$row['count']
            ];
        }
        
        return $data;
    }
    
    private function getCityOrderRank() {
        $rows = $this->db->fetchAll(
            "SELECT c.name, COUNT(o.id) as count, IFNULL(SUM(o.pay_amount), 0) as amount 
             FROM tc_order o 
             LEFT JOIN tc_city c ON o.city_id = c.id 
             WHERE o.status >= 1 
             GROUP BY o.city_id 
             ORDER BY count DESC LIMIT 10"
        );
        
        return $rows;
    }
}
