<?php
require_once __DIR__ . '/BaseController.php';

class UserController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND (nickname LIKE ? OR mobile LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        $city_id = (int)input('city_id', 0);
        if ($city_id > 0) {
            $where .= ' AND city_id = ?';
            $params[] = $city_id;
        }
        
        $level_id = (int)input('level_id', 0);
        if ($level_id > 0) {
            $where .= ' AND level_id = ?';
            $params[] = $level_id;
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_user WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT u.*, c.name as city_name, ml.name as level_name 
             FROM tc_user u 
             LEFT JOIN tc_city c ON u.city_id = c.id 
             LEFT JOIN tc_member_level ml ON u.level_id = ml.id 
             WHERE $where 
             ORDER BY u.id DESC 
             LIMIT $offset, $pageSize",
            $params
        );
        
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        $levels = $this->db->fetchAll("SELECT * FROM tc_member_level ORDER BY level ASC");
        
        $this->layout('user/index', [
            'title' => '用户管理',
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'keyword' => $keyword,
            'city_id' => $city_id,
            'level_id' => $level_id,
            'status' => $status,
            'cities' => $cities,
            'levels' => $levels
        ]);
    }
    
    public function detail() {
        $id = (int)input('id', 0);
        $user = $this->db->fetch(
            "SELECT u.*, c.name as city_name, ml.name as level_name 
             FROM tc_user u 
             LEFT JOIN tc_city c ON u.city_id = c.id 
             LEFT JOIN tc_member_level ml ON u.level_id = ml.id 
             WHERE u.id = ?",
            [$id]
        );
        
        if (!$user) {
            die('用户不存在');
        }
        
        $orders = $this->db->fetchAll(
            "SELECT * FROM tc_order WHERE user_id = ? ORDER BY id DESC LIMIT 20",
            [$id]
        );
        
        $balanceLogs = $this->db->fetchAll(
            "SELECT * FROM tc_balance_log WHERE user_id = ? ORDER BY id DESC LIMIT 20",
            [$id]
        );
        
        $addresses = $this->db->fetchAll(
            "SELECT * FROM tc_user_address WHERE user_id = ? ORDER BY is_default DESC, id DESC",
            [$id]
        );
        
        $coupons = $this->db->fetchAll(
            "SELECT * FROM tc_user_coupon WHERE user_id = ? ORDER BY id DESC LIMIT 10",
            [$id]
        );
        
        $statusMap = [
            0 => '待付款', 1 => '待派单', 2 => '待接单', 3 => '待服务',
            4 => '服务中', 5 => '待支付差额', 6 => '已完成', 7 => '已取消', 8 => '已退款'
        ];
        
        $typeMap = [1 => '充值', 2 => '消费', 3 => '退款', 4 => '赠送'];
        $couponStatusMap = [0 => '未使用', 1 => '已使用', 2 => '已过期'];
        
        $this->layout('user/detail', [
            'title' => '用户详情',
            'user' => $user,
            'orders' => $orders,
            'balanceLogs' => $balanceLogs,
            'addresses' => $addresses,
            'coupons' => $coupons,
            'statusMap' => $statusMap,
            'typeMap' => $typeMap,
            'couponStatusMap' => $couponStatusMap
        ]);
    }
    
    public function adjustBalance() {
        $id = (int)input('id', 0);
        $user = $this->db->fetch("SELECT * FROM tc_user WHERE id = ?", [$id]);
        
        if (!$user) {
            jsonError('用户不存在');
        }
        
        $amount = (float)input('amount', 0);
        $type = (int)input('type', 1);
        $remark = input('remark', '');
        
        if ($amount == 0) {
            jsonError('金额不能为0');
        }
        
        if ($type == 2 && $amount > $user['balance']) {
            jsonError('余额不足');
        }
        
        $changeAmount = $type == 1 ? $amount : -$amount;
        
        $this->db->beginTransaction();
        try {
            $sql = $changeAmount >= 0 ? "UPDATE tc_user SET balance = balance + ?, updated_at = ? WHERE id = ?" : "UPDATE tc_user SET balance = balance - ?, updated_at = ? WHERE id = ?";
            $this->db->query($sql, [abs($changeAmount), time(), $id]);
            
            $this->db->insert('tc_balance_log', [
                'user_id' => $id,
                'type' => $type == 1 ? 1 : 2,
                'amount' => $amount,
                'before_balance' => $user['balance'],
                'after_balance' => $user['balance'] + $changeAmount,
                'remark' => $remark ?: ($type == 1 ? '管理员充值' : '管理员扣减'),
                'created_at' => time()
            ]);
            
            $this->db->commit();
            jsonSuccess(null, '操作成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            jsonError('操作失败: ' . $e->getMessage());
        }
    }
    
    public function toggleStatus() {
        $id = (int)input('id', 0);
        $user = $this->db->fetch("SELECT * FROM tc_user WHERE id = ?", [$id]);
        
        if (!$user) {
            jsonError('用户不存在');
        }
        
        $newStatus = $user['status'] == 1 ? 0 : 1;
        $this->db->update('tc_user', [
            'status' => $newStatus,
            'updated_at' => time()
        ], 'id = :id', ['id' => $id]);
        
        jsonSuccess(null, ($newStatus == 1 ? '启用' : '禁用') . '成功');
    }
}
