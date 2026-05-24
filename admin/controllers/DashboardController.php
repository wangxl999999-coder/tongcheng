<?php
require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController {
    
    public function index() {
        $today = strtotime(date('Y-m-d'));
        $yesterday = $today - 86400;
        $monthStart = strtotime(date('Y-m-01'));
        
        $todayOrder = $this->db->fetch(
            "SELECT COUNT(*) as count, IFNULL(SUM(pay_amount), 0) as amount 
             FROM tc_order WHERE created_at >= ? AND status >= 1",
            [$today]
        );
        
        $yesterdayOrder = $this->db->fetch(
            "SELECT COUNT(*) as count, IFNULL(SUM(pay_amount), 0) as amount 
             FROM tc_order WHERE created_at >= ? AND created_at < ? AND status >= 1",
            [$yesterday, $today]
        );
        
        $monthOrder = $this->db->fetch(
            "SELECT COUNT(*) as count, IFNULL(SUM(pay_amount), 0) as amount 
             FROM tc_order WHERE created_at >= ? AND status >= 1",
            [$monthStart]
        );
        
        $totalUser = $this->db->fetch("SELECT COUNT(*) as total FROM tc_user")['total'];
        $totalTechnician = $this->db->fetch("SELECT COUNT(*) as total FROM tc_technician WHERE status = 1")['total'];
        $onlineTechnician = $this->db->fetch("SELECT COUNT(*) as total FROM tc_technician WHERE is_online = 1 AND status = 1")['total'];
        $totalMerchant = $this->db->fetch("SELECT COUNT(*) as total FROM tc_merchant WHERE status = 1")['total'];
        
        $pendingOrder = $this->db->fetch("SELECT COUNT(*) as total FROM tc_order WHERE status IN (1, 2)")['total'];
        $processingOrder = $this->db->fetch("SELECT COUNT(*) as total FROM tc_order WHERE status IN (3, 4)")['total'];
        
        $orderTrend = $this->getOrderTrend();
        $serviceTypeRatio = $this->getServiceTypeRatio();
        $cityOrderRank = $this->getCityOrderRank();
        $incomeTrend = $this->getIncomeTrend();
        $timeDistribution = $this->getTimeDistribution();
        $technicianRank = $this->getTechnicianRank();
        
        $this->layout('dashboard/index', [
            'title' => '数据大屏',
            'todayOrder' => $todayOrder,
            'yesterdayOrder' => $yesterdayOrder,
            'monthOrder' => $monthOrder,
            'totalUser' => $totalUser,
            'totalTechnician' => $totalTechnician,
            'onlineTechnician' => $onlineTechnician,
            'totalMerchant' => $totalMerchant,
            'pendingOrder' => $pendingOrder,
            'processingOrder' => $processingOrder,
            'orderTrend' => $orderTrend,
            'serviceTypeRatio' => $serviceTypeRatio,
            'cityOrderRank' => $cityOrderRank,
            'incomeTrend' => $incomeTrend,
            'timeDistribution' => $timeDistribution,
            'technicianRank' => $technicianRank
        ]);
    }
    
    private function getOrderTrend() {
        $days = [];
        $counts = [];
        
        for ($i = 14; $i >= 0; $i--) {
            $start = strtotime(date('Y-m-d', strtotime("-$i day")));
            $end = $start + 86400;
            $days[] = date('m-d', $start);
            
            $count = $this->db->fetch(
                "SELECT COUNT(*) as count FROM tc_order WHERE created_at >= ? AND created_at < ? AND status >= 1",
                [$start, $end]
            )['count'];
            $counts[] = (int)$count;
        }
        
        return ['days' => $days, 'counts' => $counts];
    }
    
    private function getServiceTypeRatio() {
        $rows = $this->db->fetchAll(
            "SELECT sc.name, COUNT(o.id) as count 
             FROM tc_order o 
             LEFT JOIN tc_service s ON o.service_id = s.id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE o.status >= 1 
             GROUP BY s.category_id 
             ORDER BY count DESC LIMIT 8"
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
        
        $names = [];
        $counts = [];
        $amounts = [];
        
        foreach ($rows as $row) {
            $names[] = $row['name'] ?: '未知城市';
            $counts[] = (int)$row['count'];
            $amounts[] = (float)$row['amount'];
        }
        
        return ['names' => $names, 'counts' => $counts, 'amounts' => $amounts];
    }
    
    private function getIncomeTrend() {
        $months = [];
        $incomes = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $time = strtotime("-$i month");
            $month = date('Y-m', $time);
            $months[] = $month;
            
            $start = strtotime($month . '-01');
            $end = strtotime($month . '-01 +1 month');
            
            $income = $this->db->fetch(
                "SELECT IFNULL(SUM(platform_income), 0) as income 
                 FROM tc_order WHERE created_at >= ? AND created_at < ? AND status = 6",
                [$start, $end]
            )['income'];
            $incomes[] = (float)$income;
        }
        
        return ['months' => $months, 'incomes' => $incomes];
    }
    
    private function getTimeDistribution() {
        $hours = [];
        $counts = [];
        
        for ($i = 0; $i < 24; $i++) {
            $hours[] = $i . ':00';
            $counts[] = 0;
        }
        
        $rows = $this->db->fetchAll(
            "SELECT HOUR(FROM_UNIXTIME(created_at)) as hour, COUNT(*) as count 
             FROM tc_order 
             WHERE created_at >= ? AND status >= 1 
             GROUP BY HOUR(FROM_UNIXTIME(created_at))",
            [strtotime('-30 days')]
        );
        
        foreach ($rows as $row) {
            $hour = (int)$row['hour'];
            if ($hour >= 0 && $hour < 24) {
                $counts[$hour] = (int)$row['count'];
            }
        }
        
        return ['hours' => $hours, 'counts' => $counts];
    }
    
    private function getTechnicianRank() {
        $rows = $this->db->fetchAll(
            "SELECT t.name, t.avatar, COUNT(o.id) as count, IFNULL(SUM(o.technician_income), 0) as income 
             FROM tc_technician t 
             LEFT JOIN tc_order o ON t.id = o.technician_id AND o.status = 6 
             WHERE t.status = 1 
             GROUP BY t.id 
             ORDER BY count DESC LIMIT 10"
        );
        
        return $rows;
    }
}
