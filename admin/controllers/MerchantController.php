<?php
require_once __DIR__ . '/BaseController.php';

class MerchantController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND (m.name LIKE ? OR m.contact LIKE ? OR m.mobile LIKE ?)';
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
        }
        
        $city_id = (int)input('city_id', 0);
        if ($city_id > 0) {
            $where .= ' AND m.city_id = ?';
            $params[] = $city_id;
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND m.status = ?';
            $params[] = $status;
        }
        
        $type = input('type', '');
        if ($type !== '') {
            $where .= ' AND m.type = ?';
            $params[] = $type;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_merchant m WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT m.*, c.name as city_name 
             FROM tc_merchant m 
             LEFT JOIN tc_city c ON m.city_id = c.id 
             WHERE $where 
             ORDER BY m.id DESC 
             LIMIT $offset, $pageSize",
            $params
        );
        
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        
        $statusMap = [0 => '待审核', 1 => '正常', 2 => '禁用'];
        $statusColor = [0 => 'warning', 1 => 'success', 2 => 'default'];
        $typeMap = [1 => '自营', 2 => '加盟'];
        
        $this->layout('merchant/index', [
            'title' => '商家管理',
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
        $merchant = $this->db->fetch(
            "SELECT m.*, c.name as city_name 
             FROM tc_merchant m 
             LEFT JOIN tc_city c ON m.city_id = c.id 
             WHERE m.id = ?",
            [$id]
        );
        
        if (!$merchant) {
            die('商家不存在');
        }
        
        $stores = $this->db->fetchAll(
            "SELECT * FROM tc_store WHERE merchant_id = ? ORDER BY sort ASC, id DESC",
            [$id]
        );
        
        $technicians = $this->db->fetchAll(
            "SELECT * FROM tc_technician WHERE merchant_id = ? ORDER BY id DESC",
            [$id]
        );
        
        $services = $this->db->fetchAll(
            "SELECT * FROM tc_service WHERE merchant_id = ? ORDER BY sort ASC, id DESC",
            [$id]
        );
        
        $orders = $this->db->fetchAll(
            "SELECT o.*, u.nickname FROM tc_order o 
             LEFT JOIN tc_user u ON o.user_id = u.id 
             WHERE o.merchant_id = ? 
             ORDER BY o.id DESC LIMIT 20",
            [$id]
        );
        
        $statusMap = [
            0 => '待付款', 1 => '待派单', 2 => '待接单', 3 => '待服务',
            4 => '服务中', 5 => '待支付差额', 6 => '已完成', 7 => '已取消', 8 => '已退款'
        ];
        
        $this->layout('merchant/detail', [
            'title' => '商家详情',
            'merchant' => $merchant,
            'stores' => $stores,
            'technicians' => $technicians,
            'services' => $services,
            'orders' => $orders,
            'statusMap' => $statusMap
        ]);
    }
    
    public function audit() {
        $id = (int)input('id', 0);
        $merchant = $this->db->fetch("SELECT * FROM tc_merchant WHERE id = ?", [$id]);
        
        if (!$merchant) {
            jsonError('商家不存在');
        }
        
        $status = (int)input('status', 1);
        
        $this->db->update('tc_merchant', [
            'status' => $status,
            'updated_at' => time()
        ], 'id = :id', ['id' => $id]);
        
        jsonSuccess(null, ($status == 1 ? '审核通过' : '审核拒绝') . '成功');
    }
    
    public function adjustBalance() {
        $id = (int)input('id', 0);
        $merchant = $this->db->fetch("SELECT * FROM tc_merchant WHERE id = ?", [$id]);
        
        if (!$merchant) {
            jsonError('商家不存在');
        }
        
        $amount = (float)input('amount', 0);
        $type = (int)input('type', 1);
        $remark = input('remark', '');
        
        if ($amount == 0) {
            jsonError('金额不能为0');
        }
        
        if ($type == 2 && $amount > $merchant['balance']) {
            jsonError('余额不足');
        }
        
        $changeAmount = $type == 1 ? $amount : -$amount;
        
        $this->db->beginTransaction();
        try {
            $sql = $changeAmount >= 0 ? "UPDATE tc_merchant SET balance = balance + ?, updated_at = ? WHERE id = ?" : "UPDATE tc_merchant SET balance = balance - ?, updated_at = ? WHERE id = ?";
            $this->db->query($sql, [abs($changeAmount), time(), $id]);
            
            if ($type == 1) {
                $this->db->query("UPDATE tc_merchant SET total_income = total_income + ? WHERE id = ?", [
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
