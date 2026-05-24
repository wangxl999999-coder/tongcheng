<?php
require_once __DIR__ . '/BaseController.php';

class OrderController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND (o.order_no LIKE ? OR u.nickname LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND o.status = ?';
            $params[] = $status;
        }
        
        $dispatch_type = input('dispatch_type', '');
        if ($dispatch_type !== '') {
            $where .= ' AND o.dispatch_type = ?';
            $params[] = $dispatch_type;
        }
        
        $date_start = input('date_start', '');
        if ($date_start) {
            $where .= ' AND o.created_at >= ?';
            $params[] = strtotime($date_start);
        }
        
        $date_end = input('date_end', '');
        if ($date_end) {
            $where .= ' AND o.created_at <= ?';
            $params[] = strtotime($date_end . ' 23:59:59');
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_order o LEFT JOIN tc_user u ON o.user_id = u.id WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT o.*, u.nickname, u.avatar as user_avatar, s.name as service_name, 
                    t.name as technician_name, m.name as merchant_name 
             FROM tc_order o 
             LEFT JOIN tc_user u ON o.user_id = u.id 
             LEFT JOIN tc_service s ON o.service_id = s.id 
             LEFT JOIN tc_technician t ON o.technician_id = t.id 
             LEFT JOIN tc_merchant m ON o.merchant_id = m.id 
             WHERE $where 
             ORDER BY o.id DESC 
             LIMIT $offset, $pageSize",
            $params
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
        
        $dispatchMap = [1 => '派单', 2 => '抢单'];
        $serviceTypeMap = [1 => '上门服务', 2 => '门店核销'];
        
        $this->layout('order/index', [
            'title' => '订单管理',
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'keyword' => $keyword,
            'status' => $status,
            'dispatch_type' => $dispatch_type,
            'date_start' => $date_start,
            'date_end' => $date_end,
            'statusMap' => $statusMap,
            'statusColor' => $statusColor,
            'dispatchMap' => $dispatchMap,
            'serviceTypeMap' => $serviceTypeMap
        ]);
    }
    
    public function detail() {
        $id = (int)input('id', 0);
        $order = $this->db->fetch(
            "SELECT o.*, u.nickname, u.mobile as user_mobile, u.avatar as user_avatar, 
                    s.name as service_name, s.cover as service_cover, s.description as service_desc,
                    t.name as technician_name, t.mobile as technician_mobile, t.avatar as technician_avatar,
                    m.name as merchant_name, m.mobile as merchant_mobile
             FROM tc_order o 
             LEFT JOIN tc_user u ON o.user_id = u.id 
             LEFT JOIN tc_service s ON o.service_id = s.id 
             LEFT JOIN tc_technician t ON o.technician_id = t.id 
             LEFT JOIN tc_merchant m ON o.merchant_id = m.id 
             WHERE o.id = ?",
            [$id]
        );
        
        if (!$order) {
            die('订单不存在');
        }
        
        $addons = $this->db->fetchAll(
            "SELECT od.*, s.name as service_name, s.cover 
             FROM tc_order_addon od 
             LEFT JOIN tc_service s ON od.service_id = s.id 
             WHERE od.order_id = ?",
            [$id]
        );
        
        $dispatchLogs = $this->db->fetchAll(
            "SELECT od.*, t.name as technician_name, t.avatar 
             FROM tc_order_dispatch od 
             LEFT JOIN tc_technician t ON od.technician_id = t.id 
             WHERE od.order_id = ? 
             ORDER BY od.id DESC",
            [$id]
        );
        
        $review = $this->db->fetch(
            "SELECT r.*, u.nickname, u.avatar 
             FROM tc_review r 
             LEFT JOIN tc_user u ON r.user_id = u.id 
             WHERE r.order_id = ?",
            [$id]
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
        
        $address = json_decode($order['address_info'], true) ?: [];
        
        $this->layout('order/detail', [
            'title' => '订单详情',
            'order' => $order,
            'addons' => $addons,
            'dispatchLogs' => $dispatchLogs,
            'review' => $review,
            'statusMap' => $statusMap,
            'address' => $address
        ]);
    }
    
    public function dispatch() {
        $id = (int)input('id', 0);
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ?", [$id]);
        
        if (!$order) {
            jsonError('订单不存在');
        }
        
        if ($order['status'] != 1) {
            jsonError('订单状态不正确');
        }
        
        $technician_id = (int)input('technician_id', 0);
        
        if ($technician_id > 0) {
            $technician = $this->db->fetch("SELECT * FROM tc_technician WHERE id = ? AND status = 1", [$technician_id]);
            if (!$technician) {
                jsonError('服务人员不存在或未审核通过');
            }
            
            $this->db->beginTransaction();
            try {
                $this->db->update('tc_order', [
                    'technician_id' => $technician_id,
                    'status' => 2,
                    'updated_at' => time()
                ], 'id = :id', ['id' => $id]);
                
                $this->db->insert('tc_order_dispatch', [
                    'order_id' => $id,
                    'type' => 1,
                    'technician_id' => $technician_id,
                    'status' => 0,
                    'dispatch_time' => time(),
                    'created_at' => time()
                ]);
                
                $this->db->commit();
                jsonSuccess(null, '派单成功');
            } catch (Exception $e) {
                $this->db->rollBack();
                jsonError('派单失败: ' . $e->getMessage());
            }
        }
        
        $technicians = $this->db->fetchAll(
            "SELECT * FROM tc_technician 
             WHERE city_id = ? AND status = 1 
             ORDER BY is_online DESC, order_count DESC",
            [$order['city_id']]
        );
        
        $this->layout('order/dispatch', [
            'title' => '订单派单',
            'order' => $order,
            'technicians' => $technicians
        ]);
    }
    
    public function cancel() {
        $id = (int)input('id', 0);
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ?", [$id]);
        
        if (!$order) {
            jsonError('订单不存在');
        }
        
        if ($order['status'] >= 4) {
            jsonError('订单已开始服务，无法取消');
        }
        
        $reason = input('reason', '管理员取消');
        
        $this->db->beginTransaction();
        try {
            $this->db->update('tc_order', [
                'status' => 7,
                'cancel_reason' => $reason,
                'updated_at' => time()
            ], 'id = :id', ['id' => $id]);
            
            if ($order['pay_amount'] > 0 && $order['balance_amount'] > 0) {
                $this->db->update('tc_user', [
                    'balance' => 'balance + ' . $order['balance_amount']
                ], 'id = :id', ['id' => $order['user_id']]);
                
                $this->db->insert('tc_balance_log', [
                    'user_id' => $order['user_id'],
                    'type' => 3,
                    'amount' => $order['balance_amount'],
                    'before_balance' => 0,
                    'after_balance' => 0,
                    'order_no' => $order['order_no'],
                    'remark' => '订单取消退款',
                    'created_at' => time()
                ]);
            }
            
            $this->db->commit();
            jsonSuccess(null, '取消成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            jsonError('操作失败: ' . $e->getMessage());
        }
    }
    
    public function complete() {
        $id = (int)input('id', 0);
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ?", [$id]);
        
        if (!$order) {
            jsonError('订单不存在');
        }
        
        if ($order['status'] != 4) {
            jsonError('订单状态不正确，无法完成');
        }
        
        $this->db->beginTransaction();
        try {
            $platformIncome = $order['pay_amount'] * 0.1;
            $technicianIncome = $order['pay_amount'] * 0.6;
            $merchantIncome = $order['pay_amount'] * 0.3;
            
            if ($order['type'] == 1) {
                $technicianIncome = $order['pay_amount'] * 0.8;
                $platformIncome = $order['pay_amount'] * 0.2;
                $merchantIncome = 0;
            }
            
            $this->db->update('tc_order', [
                'status' => 6,
                'end_service_time' => time(),
                'finish_time' => time(),
                'platform_income' => $platformIncome,
                'technician_income' => $technicianIncome,
                'merchant_income' => $merchantIncome,
                'updated_at' => time()
            ], 'id = :id', ['id' => $id]);
            
            if ($technicianIncome > 0) {
                $this->db->update('tc_technician', [
                    'balance' => 'balance + ' . $technicianIncome,
                    'total_income' => 'total_income + ' . $technicianIncome,
                    'order_count' => 'order_count + 1'
                ], 'id = :id', ['id' => $order['technician_id']]);
            }
            
            if ($merchantIncome > 0) {
                $this->db->update('tc_merchant', [
                    'balance' => 'balance + ' . $merchantIncome,
                    'total_income' => 'total_income + ' . $merchantIncome
                ], 'id = :id', ['id' => $order['merchant_id']]);
            }
            
            $this->db->update('tc_user', [
                'total_consume' => 'total_consume + ' . $order['pay_amount'],
                'total_order' => 'total_order + 1'
            ], 'id = :id', ['id' => $order['user_id']]);
            
            $this->db->update('tc_service', [
                'sales' => 'sales + 1'
            ], 'id = :id', ['id' => $order['service_id']]);
            
            $this->db->commit();
            jsonSuccess(null, '订单已完成');
        } catch (Exception $e) {
            $this->db->rollBack();
            jsonError('操作失败: ' . $e->getMessage());
        }
    }
}
