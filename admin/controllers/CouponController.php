<?php
require_once __DIR__ . '/BaseController.php';

class CouponController extends BaseController {
    
    public function index() {
        $page = max(1, (int)input('page', 1));
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        
        $where = '1=1';
        $params = [];
        
        $keyword = input('keyword', '');
        if ($keyword) {
            $where .= ' AND name LIKE ?';
            $params[] = "%$keyword%";
        }
        
        $status = input('status', '');
        if ($status !== '') {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_coupon WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_coupon WHERE $where ORDER BY sort ASC, id DESC LIMIT $offset, $pageSize",
            $params
        );
        
        $typeMap = [1 => '满减券', 2 => '折扣券'];
        $receiveTypeMap = [1 => '直接领取', 2 => '口令领取', 3 => '活动赠送'];
        
        $this->layout('coupon/index', [
            'title' => '优惠券管理',
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'keyword' => $keyword,
            'status' => $status,
            'typeMap' => $typeMap,
            'receiveTypeMap' => $receiveTypeMap
        ]);
    }
    
    public function add() {
        if (isPost()) {
            $data = [
                'name' => input('name', ''),
                'type' => (int)input('type', 1),
                'amount' => (float)input('amount', 0),
                'discount' => (float)input('discount', 100),
                'min_amount' => (float)input('min_amount', 0),
                'total_count' => (int)input('total_count', 0),
                'receive_type' => (int)input('receive_type', 1),
                'code' => input('code', ''),
                'valid_type' => (int)input('valid_type', 1),
                'valid_days' => (int)input('valid_days', 0),
                'start_time' => input('start_time') ? strtotime(input('start_time')) : 0,
                'end_time' => input('end_time') ? strtotime(input('end_time') . ' 23:59:59') : 0,
                'category_ids' => implode(',', input('category_ids/a', [])),
                'service_ids' => implode(',', input('service_ids/a', [])),
                'city_ids' => implode(',', input('city_ids/a', [])),
                'sort' => (int)input('sort', 0),
                'status' => (int)input('status', 1),
                'created_at' => time()
            ];
            
            if (empty($data['name'])) {
                jsonError('请输入优惠券名称');
            }
            if ($data['type'] == 1 && $data['amount'] <= 0) {
                jsonError('请输入满减金额');
            }
            if ($data['type'] == 2 && ($data['discount'] <= 0 || $data['discount'] >= 100)) {
                jsonError('请输入正确的折扣值');
            }
            
            $id = $this->db->insert('tc_coupon', $data);
            if ($id) {
                jsonSuccess(['id' => $id], '添加成功');
            }
            jsonError('添加失败');
        }
        
        $categories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 ORDER BY sort ASC");
        $services = $this->db->fetchAll("SELECT * FROM tc_service WHERE status = 1 ORDER BY sort ASC");
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        
        $this->layout('coupon/form', [
            'title' => '添加优惠券',
            'info' => null,
            'categories' => $categories,
            'services' => $services,
            'cities' => $cities
        ]);
    }
    
    public function edit() {
        $id = (int)input('id', 0);
        $info = $this->db->fetch("SELECT * FROM tc_coupon WHERE id = ?", [$id]);
        if (!$info) {
            die('优惠券不存在');
        }
        
        if (isPost()) {
            $data = [
                'name' => input('name', ''),
                'type' => (int)input('type', 1),
                'amount' => (float)input('amount', 0),
                'discount' => (float)input('discount', 100),
                'min_amount' => (float)input('min_amount', 0),
                'total_count' => (int)input('total_count', 0),
                'receive_type' => (int)input('receive_type', 1),
                'code' => input('code', ''),
                'valid_type' => (int)input('valid_type', 1),
                'valid_days' => (int)input('valid_days', 0),
                'start_time' => input('start_time') ? strtotime(input('start_time')) : 0,
                'end_time' => input('end_time') ? strtotime(input('end_time') . ' 23:59:59') : 0,
                'category_ids' => implode(',', input('category_ids/a', [])),
                'service_ids' => implode(',', input('service_ids/a', [])),
                'city_ids' => implode(',', input('city_ids/a', [])),
                'sort' => (int)input('sort', 0),
                'status' => (int)input('status', 1)
            ];
            
            $this->db->update('tc_coupon', $data, 'id = :id', ['id' => $id]);
            jsonSuccess(null, '修改成功');
        }
        
        $categories = $this->db->fetchAll("SELECT * FROM tc_service_category WHERE parent_id = 0 ORDER BY sort ASC");
        $services = $this->db->fetchAll("SELECT * FROM tc_service WHERE status = 1 ORDER BY sort ASC");
        $cities = $this->db->fetchAll("SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC");
        
        $this->layout('coupon/form', [
            'title' => '编辑优惠券',
            'info' => $info,
            'categories' => $categories,
            'services' => $services,
            'cities' => $cities
        ]);
    }
    
    public function delete() {
        $id = (int)input('id', 0);
        $this->db->delete('tc_coupon', 'id = ?', [$id]);
        jsonSuccess(null, '删除成功');
    }
    
    public function send() {
        $id = (int)input('id', 0);
        $coupon = $this->db->fetch("SELECT * FROM tc_coupon WHERE id = ? AND status = 1", [$id]);
        
        if (!$coupon) {
            jsonError('优惠券不存在或已下架');
        }
        
        $user_ids = input('user_ids/a', []);
        
        if (empty($user_ids)) {
            jsonError('请选择用户');
        }
        
        if ($coupon['total_count'] > 0) {
            $left = $coupon['total_count'] - $coupon['used_count'];
            if (count($user_ids) > $left) {
                jsonError('优惠券剩余数量不足，剩余' . $left . '张');
            }
        }
        
        $now = time();
        if ($coupon['valid_type'] == 1) {
            $start_time = $coupon['start_time'];
            $end_time = $coupon['end_time'];
        } else {
            $start_time = $now;
            $end_time = $now + $coupon['valid_days'] * 86400;
        }
        
        $successCount = 0;
        foreach ($user_ids as $user_id) {
            $exists = $this->db->fetch(
                "SELECT COUNT(*) as total FROM tc_user_coupon WHERE user_id = ? AND coupon_id = ? AND status = 0",
                [$user_id, $id]
            )['total'];
            
            if ($exists > 0) {
                continue;
            }
            
            $this->db->insert('tc_user_coupon', [
                'user_id' => (int)$user_id,
                'coupon_id' => $id,
                'name' => $coupon['name'],
                'amount' => $coupon['amount'],
                'discount' => $coupon['discount'],
                'min_amount' => $coupon['min_amount'],
                'status' => 0,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'created_at' => $now
            ]);
            $successCount++;
        }
        
        $this->db->update('tc_coupon', [
            'used_count' => 'used_count + ' . $successCount
        ], 'id = :id', ['id' => $id]);
        
        jsonSuccess(null, '成功发放' . $successCount . '张优惠券');
    }
}
