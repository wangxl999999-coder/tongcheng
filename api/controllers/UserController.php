<?php
require_once __DIR__ . '/BaseController.php';

class UserController extends BaseController {
    
    public function info() {
        $user = $this->user;
        
        $level = $this->db->fetch("SELECT * FROM tc_member_level WHERE id = ?", [$user['level_id']]);
        $nextLevel = $this->db->fetch(
            "SELECT * FROM tc_member_level WHERE level > ? ORDER BY level ASC LIMIT 1",
            [$level['level']]
        );
        
        $couponCount = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_user_coupon WHERE user_id = ? AND status = 0",
            [$user['id']]
        )['total'];
        
        $orderCount = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_order WHERE user_id = ? AND status > 0",
            [$user['id']]
        )['total'];
        
        $this->success([
            'id' => $user['id'],
            'nickname' => $user['nickname'],
            'avatar' => $this->formatUrl($user['avatar']),
            'mobile' => $user['mobile'],
            'gender' => $user['gender'],
            'city_id' => $user['city_id'],
            'level_id' => $user['level_id'],
            'level_name' => $level ? $level['name'] : '',
            'balance' => (float)$user['balance'],
            'total_consume' => (float)$user['total_consume'],
            'total_order' => $orderCount,
            'coupon_count' => $couponCount,
            'discount' => $level ? (float)$level['discount'] : 100,
            'next_level' => $nextLevel ? [
                'name' => $nextLevel['name'],
                'min_consume' => (float)$nextLevel['min_consume'],
                'remain' => max(0, $nextLevel['min_consume'] - $user['total_consume'])
            ] : null,
            'level_privileges' => $level ? json_decode($level['privileges'], true) : []
        ]);
    }
    
    public function update() {
        $user = $this->user;
        
        $data = [];
        if ($this->getParam('nickname')) {
            $data['nickname'] = $this->getParam('nickname');
        }
        if ($this->getParam('avatar')) {
            $data['avatar'] = $this->getParam('avatar');
        }
        if ($this->getInt('gender', -1) >= 0) {
            $data['gender'] = $this->getInt('gender');
        }
        if ($this->getParam('mobile')) {
            $data['mobile'] = $this->getParam('mobile');
        }
        if ($this->getInt('city_id') > 0) {
            $data['city_id'] = $this->getInt('city_id');
        }
        
        if (!empty($data)) {
            $data['updated_at'] = time();
            $this->db->update('tc_user', $data, 'id = :id', ['id' => $user['id']]);
        }
        
        $this->success(null, '更新成功');
    }
    
    public function address() {
        $user = $this->user;
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_user_address WHERE user_id = ? ORDER BY is_default DESC, id DESC",
            [$user['id']]
        );
        $this->success($list);
    }
    
    public function addressAdd() {
        $user = $this->user;
        
        $name = $this->getParam('name', '');
        $mobile = $this->getParam('mobile', '');
        $province = $this->getParam('province', '');
        $city = $this->getParam('city', '');
        $district = $this->getParam('district', '');
        $address = $this->getParam('address', '');
        $detail = $this->getParam('detail', '');
        $longitude = $this->getFloat('longitude', 0);
        $latitude = $this->getFloat('latitude', 0);
        $isDefault = $this->getInt('is_default', 0);
        $tag = $this->getParam('tag', '');
        
        if (!$name || !$mobile || !$address) {
            $this->error('请填写完整信息');
        }
        
        $this->db->beginTransaction();
        try {
            if ($isDefault) {
                $this->db->update('tc_user_address', [
                    'is_default' => 0
                ], 'user_id = :user_id', ['user_id' => $user['id']]);
            }
            
            $id = $this->db->insert('tc_user_address', [
                'user_id' => $user['id'],
                'name' => $name,
                'mobile' => $mobile,
                'province' => $province,
                'city' => $city,
                'district' => $district,
                'address' => $address,
                'detail' => $detail,
                'longitude' => $longitude,
                'latitude' => $latitude,
                'is_default' => $isDefault,
                'tag' => $tag,
                'created_at' => time(),
                'updated_at' => time()
            ]);
            
            $this->db->commit();
            $this->success(['id' => $id], '添加成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('添加失败: ' . $e->getMessage());
        }
    }
    
    public function addressUpdate() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        
        $address = $this->db->fetch("SELECT * FROM tc_user_address WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$address) {
            $this->error('地址不存在');
        }
        
        $data = [
            'updated_at' => time()
        ];
        
        $fields = ['name', 'mobile', 'province', 'city', 'district', 'address', 'detail', 'tag'];
        foreach ($fields as $field) {
            $val = $this->getParam($field);
            if ($val !== '') {
                $data[$field] = $val;
            }
        }
        
        $longitude = $this->getFloat('longitude', -1);
        if ($longitude >= 0) {
            $data['longitude'] = $longitude;
        }
        $latitude = $this->getFloat('latitude', -1);
        if ($latitude >= 0) {
            $data['latitude'] = $latitude;
        }
        
        $isDefault = $this->getInt('is_default', -1);
        if ($isDefault >= 0) {
            $data['is_default'] = $isDefault;
        }
        
        $this->db->beginTransaction();
        try {
            if ($isDefault) {
                $this->db->update('tc_user_address', [
                    'is_default' => 0
                ], 'user_id = :user_id AND id != :id', [
                    'user_id' => $user['id'],
                    'id' => $id
                ]);
            }
            
            $this->db->update('tc_user_address', $data, 'id = :id', ['id' => $id]);
            
            $this->db->commit();
            $this->success(null, '更新成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('更新失败: ' . $e->getMessage());
        }
    }
    
    public function addressDelete() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        
        $address = $this->db->fetch("SELECT * FROM tc_user_address WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$address) {
            $this->error('地址不存在');
        }
        
        $this->db->delete('tc_user_address', 'id = :id', ['id' => $id]);
        $this->success(null, '删除成功');
    }
    
    public function addressDefault() {
        $user = $this->user;
        $address = $this->db->fetch(
            "SELECT * FROM tc_user_address WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1",
            [$user['id']]
        );
        $this->success($address);
    }
    
    public function coupon() {
        $user = $this->user;
        $status = $this->getInt('status', 0);
        
        $where = 'user_id = ?';
        $params = [$user['id']];
        
        if ($status == 0) {
            $where .= ' AND status = 0 AND (end_time = 0 OR end_time > ?)';
            $params[] = time();
        } elseif ($status == 1) {
            $where .= ' AND status = 1';
        } else {
            $where .= ' AND (status = 2 OR (status = 0 AND end_time > 0 AND end_time <= ?))';
            $params[] = time();
        }
        
        $list = $this->db->fetchAll(
            "SELECT uc.*, c.name, c.desc, c.type, c.amount, c.discount, c.min_amount, 
                    c.start_time, c.end_time, c.merchant_id 
             FROM tc_user_coupon uc 
             LEFT JOIN tc_coupon c ON uc.coupon_id = c.id 
             WHERE $where 
             ORDER BY uc.id DESC",
            $params
        );
        
        foreach ($list as &$item) {
            $item['is_expired'] = $item['end_time'] > 0 && $item['end_time'] <= time();
        }
        
        $this->success($list);
    }
    
    public function couponReceive() {
        $user = $this->user;
        $couponId = $this->getInt('coupon_id', 0);
        $code = $this->getParam('code', '');
        
        if ($code) {
            $coupon = $this->db->fetch("SELECT * FROM tc_coupon WHERE receive_code = ? AND status = 1", [$code]);
            if (!$coupon) {
                $this->error('口令不存在或已失效');
            }
            $couponId = $coupon['id'];
        } else {
            $coupon = $this->db->fetch("SELECT * FROM tc_coupon WHERE id = ? AND status = 1", [$couponId]);
            if (!$coupon) {
                $this->error('优惠券不存在');
            }
        }
        
        if ($coupon['total_count'] > 0 && $coupon['used_count'] >= $coupon['total_count']) {
            $this->error('优惠券已被领完');
        }
        
        if ($coupon['limit_per_user'] > 0) {
            $received = $this->db->fetch(
                "SELECT COUNT(*) as total FROM tc_user_coupon WHERE user_id = ? AND coupon_id = ?",
                [$user['id'], $couponId]
            )['total'];
            if ($received >= $coupon['limit_per_user']) {
                $this->error('您已领取过该优惠券');
            }
        }
        
        $this->db->beginTransaction();
        try {
            $this->db->insert('tc_user_coupon', [
                'user_id' => $user['id'],
                'coupon_id' => $couponId,
                'status' => 0,
                'receive_time' => time(),
                'created_at' => time()
            ]);
            
            $this->db->update('tc_coupon', [
                'used_count' => 'used_count + 1'
            ], 'id = :id', ['id' => $couponId]);
            
            $this->db->commit();
            $this->success(null, '领取成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('领取失败: ' . $e->getMessage());
        }
    }
    
    public function couponAvailable() {
        $user = $this->user;
        $amount = $this->getFloat('amount', 0);
        
        $list = $this->db->fetchAll(
            "SELECT uc.*, c.name, c.desc, c.type, c.amount, c.discount, c.min_amount, 
                    c.start_time, c.end_time, c.merchant_id 
             FROM tc_user_coupon uc 
             LEFT JOIN tc_coupon c ON uc.coupon_id = c.id 
             WHERE uc.user_id = ? AND uc.status = 0 
             AND (c.min_amount = 0 OR c.min_amount <= ?)
             AND (c.end_time = 0 OR c.end_time > ?)
             ORDER BY c.amount DESC, c.discount ASC",
            [$user['id'], $amount, time()]
        );
        
        $this->success($list);
    }
    
    public function wallet() {
        $user = $this->user;
        
        $totalRecharge = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total FROM tc_recharge_order WHERE user_id = ? AND status = 1",
            [$user['id']]
        )['total'];
        
        $totalSpend = $this->db->fetch(
            "SELECT COALESCE(SUM(CASE WHEN type = 2 THEN amount ELSE 0 END), 0) as spend,
                    COALESCE(SUM(CASE WHEN type = 1 THEN amount ELSE 0 END), 0) as recharge
             FROM tc_balance_log WHERE user_id = ?",
            [$user['id']]
        );
        
        $packages = $this->db->fetchAll(
            "SELECT * FROM tc_recharge_package WHERE status = 1 ORDER BY amount ASC",
            []
        );
        
        $this->success([
            'balance' => (float)$user['balance'],
            'total_recharge' => (float)$totalRecharge,
            'total_spend' => (float)$totalSpend['spend'],
            'packages' => $packages
        ]);
    }
    
    public function recharge() {
        $user = $this->user;
        $packageId = $this->getInt('package_id', 0);
        $amount = $this->getFloat('amount', 0);
        
        if ($packageId > 0) {
            $package = $this->db->fetch("SELECT * FROM tc_recharge_package WHERE id = ? AND status = 1", [$packageId]);
            if (!$package) {
                $this->error('充值套餐不存在');
            }
            $amount = $package['amount'];
            $giftAmount = $package['gift_amount'];
            $giftLevelId = $package['gift_level_id'];
        } else {
            if ($amount <= 0) {
                $this->error('充值金额不能为0');
            }
            $giftAmount = 0;
            $giftLevelId = 0;
        }
        
        $orderNo = generateOrderNo('CZ');
        
        $this->db->beginTransaction();
        try {
            $this->db->insert('tc_recharge_order', [
                'order_no' => $orderNo,
                'user_id' => $user['id'],
                'package_id' => $packageId,
                'amount' => $amount,
                'gift_amount' => $giftAmount,
                'gift_level_id' => $giftLevelId,
                'pay_type' => 1,
                'status' => 1,
                'pay_time' => time(),
                'created_at' => time()
            ]);
            
            $beforeBalance = $user['balance'];
            $afterBalance = $beforeBalance + $amount + $giftAmount;
            
            $this->db->update('tc_user', [
                'balance' => $afterBalance,
                'total_recharge' => 'total_recharge + ' . ($amount + $giftAmount),
                'updated_at' => time()
            ], 'id = :id', ['id' => $user['id']]);
            
            $this->db->insert('tc_balance_log', [
                'user_id' => $user['id'],
                'type' => 1,
                'amount' => $amount + $giftAmount,
                'before_balance' => $beforeBalance,
                'after_balance' => $afterBalance,
                'order_no' => $orderNo,
                'remark' => '充值' . ($packageId > 0 ? '套餐' : ''),
                'created_at' => time()
            ]);
            
            if ($giftLevelId > 0 && $giftLevelId > $user['level_id']) {
                $this->db->update('tc_user', [
                    'level_id' => $giftLevelId
                ], 'id = :id', ['id' => $user['id']]);
            }
            
            $this->db->commit();
            
            $this->success([
                'order_no' => $orderNo,
                'amount' => $amount,
                'gift_amount' => $giftAmount,
                'balance' => $afterBalance
            ], '充值成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('充值失败: ' . $e->getMessage());
        }
    }
    
    public function balanceLog() {
        $user = $this->user;
        $page = max(1, $this->getInt('page', 1));
        $pageSize = $this->getInt('page_size', 20);
        $offset = ($page - 1) * $pageSize;
        $type = $this->getInt('type', 0);
        
        $where = 'user_id = ?';
        $params = [$user['id']];
        if ($type > 0) {
            $where .= ' AND type = ?';
            $params[] = $type;
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_balance_log WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_balance_log WHERE $where ORDER BY id DESC LIMIT $offset, $pageSize",
            $params
        );
        
        $typeMap = [
            1 => ['text' => '充值', 'type' => 'income'],
            2 => ['text' => '消费', 'type' => 'expense'],
            3 => ['text' => '退款', 'type' => 'income'],
            4 => ['text' => '提现', 'type' => 'expense'],
            5 => ['text' => '佣金', 'type' => 'income']
        ];
        
        foreach ($list as &$item) {
            $item['type_text'] = $typeMap[$item['type']]['text'];
            $item['record_type'] = $typeMap[$item['type']]['type'];
        }
        
        $this->success([
            'list' => $list,
            'total' => $total,
            'has_more' => $total > $page * $pageSize
        ]);
    }
    
    public function member() {
        $user = $this->user;
        
        $levels = $this->db->fetchAll("SELECT * FROM tc_member_level ORDER BY level ASC");
        
        $currentLevel = null;
        $nextLevel = null;
        $progress = 0;
        
        foreach ($levels as $index => $level) {
            if ($level['id'] == $user['level_id']) {
                $currentLevel = $level;
                if (isset($levels[$index + 1])) {
                    $nextLevel = $levels[$index + 1];
                    $currentConsume = $currentLevel['min_consume'];
                    $nextConsume = $nextLevel['min_consume'];
                    if ($nextConsume > $currentConsume) {
                        $progress = min(100, round(($user['total_consume'] - $currentConsume) / ($nextConsume - $currentConsume) * 100));
                    }
                } else {
                    $progress = 100;
                }
                break;
            }
        }
        
        if ($currentLevel) {
            $currentLevel['privileges'] = json_decode($currentLevel['privileges'], true) ?: [];
        }
        if ($nextLevel) {
            $nextLevel['privileges'] = json_decode($nextLevel['privileges'], true) ?: [];
        }
        
        $rechargeList = $this->db->fetchAll(
            "SELECT * FROM tc_recharge_package WHERE type = 1 AND status = 1 ORDER BY amount ASC",
            []
        );
        
        $this->success([
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'progress' => $progress,
            'total_consume' => (float)$user['total_consume'],
            'all_levels' => $levels,
            'recharge_list' => $rechargeList
        ]);
    }
    
    public function packageCard() {
        $user = $this->user;
        $status = $this->getInt('status', 0);
        
        $where = 'user_id = ?';
        $params = [$user['id']];
        
        if ($status == 0) {
            $where .= ' AND remaining_count > 0 AND (expire_time = 0 OR expire_time > ?)';
            $params[] = time();
        } else {
            $where .= ' AND (remaining_count = 0 OR (expire_time > 0 AND expire_time <= ?))';
            $params[] = time();
        }
        
        $list = $this->db->fetchAll(
            "SELECT up.*, p.name, p.image, p.desc, p.total_count, p.valid_days, p.services
             FROM tc_user_package up 
             LEFT JOIN tc_package p ON up.package_id = p.id 
             WHERE $where 
             ORDER BY up.id DESC",
            $params
        );
        
        foreach ($list as &$item) {
            $item['image'] = $this->formatUrl($item['image']);
            $item['services'] = json_decode($item['services'], true) ?: [];
            $item['is_expired'] = $item['expire_time'] > 0 && $item['expire_time'] <= time();
        }
        
        $this->success($list);
    }
    
    public function packageCardDetail() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        
        $package = $this->db->fetch(
            "SELECT up.*, p.name, p.image, p.desc, p.total_count, p.valid_days, p.services, p.refund_rule
             FROM tc_user_package up 
             LEFT JOIN tc_package p ON up.package_id = p.id 
             WHERE up.id = ? AND up.user_id = ?",
            [$id, $user['id']]
        );
        
        if (!$package) {
            $this->error('套餐卡不存在');
        }
        
        $package['image'] = $this->formatUrl($package['image']);
        $package['services'] = json_decode($package['services'], true) ?: [];
        $package['is_expired'] = $package['expire_time'] > 0 && $package['expire_time'] <= time();
        
        $usageRecords = $this->db->fetchAll(
            "SELECT pu.*, o.order_no, o.service_time, s.name as service_name
             FROM tc_package_usage pu 
             LEFT JOIN tc_order o ON pu.order_id = o.id 
             LEFT JOIN tc_service s ON pu.service_id = s.id 
             WHERE pu.user_package_id = ? 
             ORDER BY pu.id DESC",
            [$id]
        );
        
        $this->success([
            'package' => $package,
            'usage_records' => $usageRecords
        ]);
    }
    
    public function packageCardRefund() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        $reason = $this->getParam('reason', '');
        
        $package = $this->db->fetch("SELECT * FROM tc_user_package WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$package) {
            $this->error('套餐卡不存在');
        }
        if ($package['is_refund']) {
            $this->error('已申请退款');
        }
        
        $refundAmount = $package['pay_amount'] * ($package['remaining_count'] / $package['total_count']);
        
        $this->db->beginTransaction();
        try {
            $this->db->update('tc_user_package', [
                'is_refund' => 1,
                'refund_reason' => $reason,
                'refund_amount' => $refundAmount,
                'status' => 2,
                'updated_at' => time()
            ], 'id = :id', ['id' => $id]);
            
            $this->db->commit();
            $this->success([
                'refund_amount' => $refundAmount
            ], '退款申请已提交');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('申请失败: ' . $e->getMessage());
        }
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
