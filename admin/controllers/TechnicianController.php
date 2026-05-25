<?php
require_once __DIR__ . '/BaseController.php';

class TechnicianController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND (t.name LIKE ? OR t.mobile LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        $city_id = (int)input('city_id', 0);
        if ($city_id > 0) {
            $where .= ' AND t.city_id = ?';
            $params[] = $city_id;
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND t.status = ?';
            $params[] = $status;
        }
        
        $type = input('type', '');
        if ($type !== '') {
            $where .= ' AND t.type = ?';
            $params[] = $type;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_technician t WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT t.*, c.name as city_name, m.name as merchant_name, s.name as store_name 
             FROM tc_technician t 
             LEFT JOIN tc_city c ON t.city_id = c.id 
             LEFT JOIN tc_merchant m ON t.merchant_id = m.id 
             LEFT JOIN tc_store s ON t.store_id = s.id 
             WHERE $where 
             ORDER BY t.id DESC 
             LIMIT $offset, $pageSize",
            $params
        );
        
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        
        $statusMap = [0 => '待审核', 1 => '正常', 2 => '禁用'];
        $statusColor = [0 => 'warning', 1 => 'success', 2 => 'default'];
        $typeMap = [1 => '自营', 2 => '商家'];
        
        $this->layout('technician/index', [
            'title' => '服务人员管理',
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'keyword' => $keyword,
            'city_id' => $city_id,
            'status' => $status,
            'type' => $type,
            'cities' => $cities,
            'statusMap' => $statusMap,
            'statusColor' => $statusColor,
            'typeMap' => $typeMap
        ]);
    }
    
    public function detail() {
        $id = (int)input('id', 0);
        $technician = $this->db->fetch(
            "SELECT t.*, c.name as city_name, m.name as merchant_name 
             FROM tc_technician t 
             LEFT JOIN tc_city c ON t.city_id = c.id 
             LEFT JOIN tc_merchant m ON t.merchant_id = m.id 
             WHERE t.id = ?",
            [$id]
        );
        
        if (!$technician) {
            die('服务人员不存在');
        }
        
        $services = $this->db->fetchAll(
            "SELECT s.* FROM tc_service s 
             INNER JOIN tc_technician_service ts ON s.id = ts.service_id 
             WHERE ts.technician_id = ?",
            [$id]
        );
        
        $orders = $this->db->fetchAll(
            "SELECT o.*, u.nickname FROM tc_order o 
             LEFT JOIN tc_user u ON o.user_id = u.id 
             WHERE o.technician_id = ? 
             ORDER BY o.id DESC LIMIT 20",
            [$id]
        );
        
        $schedules = $this->db->fetchAll(
            "SELECT * FROM tc_technician_schedule 
             WHERE technician_id = ? AND date >= ? 
             ORDER BY date ASC LIMIT 7",
            [$id, date('Y-m-d')]
        );
        
        $reviews = $this->db->fetchAll(
            "SELECT r.*, u.nickname, u.avatar FROM tc_review r 
             LEFT JOIN tc_user u ON r.user_id = u.id 
             WHERE r.technician_id = ? 
             ORDER BY r.id DESC LIMIT 10",
            [$id]
        );
        
        $statusMap = [
            0 => '待付款', 1 => '待派单', 2 => '待接单', 3 => '待服务',
            4 => '服务中', 5 => '待支付差额', 6 => '已完成', 7 => '已取消', 8 => '已退款'
        ];
        
        $this->layout('technician/detail', [
            'title' => '服务人员详情',
            'technician' => $technician,
            'services' => $services,
            'orders' => $orders,
            'schedules' => $schedules,
            'reviews' => $reviews,
            'statusMap' => $statusMap
        ]);
    }
    
    public function audit() {
        $id = (int)input('id', 0);
        $technician = $this->db->fetch("SELECT * FROM tc_technician WHERE id = ?", [$id]);
        
        if (!$technician) {
            jsonError('服务人员不存在');
        }
        
        $status = (int)input('status', 1);
        
        $this->db->update('tc_technician', [
            'status' => $status,
            'updated_at' => time()
        ], 'id = :id', ['id' => $id]);
        
        jsonSuccess(null, ($status == 1 ? '审核通过' : '审核拒绝') . '成功');
    }
    
    public function toggleOnline() {
        $id = (int)input('id', 0);
        $technician = $this->db->fetch("SELECT * FROM tc_technician WHERE id = ?", [$id]);
        
        if (!$technician) {
            jsonError('服务人员不存在');
        }
        
        $is_online = $technician['is_online'] == 1 ? 0 : 1;
        $this->db->update('tc_technician', [
            'is_online' => $is_online,
            'updated_at' => time()
        ], 'id = :id', ['id' => $id]);
        
        jsonSuccess(null, ($is_online ? '上线' : '下线') . '成功');
    }
    
    public function adjustBalance() {
        $id = (int)input('id', 0);
        $technician = $this->db->fetch("SELECT * FROM tc_technician WHERE id = ?", [$id]);
        
        if (!$technician) {
            jsonError('服务人员不存在');
        }
        
        $amount = (float)input('amount', 0);
        $type = (int)input('type', 1);
        $remark = input('remark', '');
        
        if ($amount == 0) {
            jsonError('金额不能为0');
        }
        
        if ($type == 2 && $amount > $technician['balance']) {
            jsonError('余额不足');
        }
        
        $changeAmount = $type == 1 ? $amount : -$amount;
        
        $this->db->beginTransaction();
        try {
            $sql = $changeAmount >= 0 ? "UPDATE tc_technician SET balance = balance + ?, updated_at = ? WHERE id = ?" : "UPDATE tc_technician SET balance = balance - ?, updated_at = ? WHERE id = ?";
            $this->db->query($sql, [abs($changeAmount), time(), $id]);
            
            if ($type == 1) {
                $this->db->query("UPDATE tc_technician SET total_income = total_income + ? WHERE id = ?", [
                    $amount,
                    $id
                ]);
            }
            
            $this->db->commit();
            jsonSuccess(null, '操作成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            jsonError('操作失败: ' . $e->getMessage());
        }
    }
}
