<?php
require_once __DIR__ . '/BaseController.php';

class OrderController extends BaseController {
    
    public function index() {
        $this->list();
    }
    
    public function list() {
        $user = $this->user;
        $page = max(1, $this->getInt('page', 1));
        $pageSize = $this->getInt('page_size', 10);
        $offset = ($page - 1) * $pageSize;
        $status = $this->getParam('status', '');
        
        $where = 'user_id = ?';
        $params = [$user['id']];
        
        if ($status !== '') {
            if ($status == 0) {
                $where .= ' AND status = 0';
            } elseif ($status == 1) {
                $where .= ' AND status IN (1, 2, 3)';
            } elseif ($status == 2) {
                $where .= ' AND status = 4';
            } elseif ($status == 3) {
                $where .= ' AND status = 6';
            }
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_order WHERE $where", $params)['total'];
        $list = $this->db->fetchAll(
            "SELECT o.*, s.name as service_name, s.cover as service_cover,
                    t.name as technician_name, t.avatar as technician_avatar
             FROM tc_order o 
             LEFT JOIN tc_service s ON o.service_id = s.id 
             LEFT JOIN tc_technician t ON o.technician_id = t.id 
             WHERE $where 
             ORDER BY o.id DESC 
             LIMIT $offset, $pageSize",
            $params
        );
        
        $statusMap = [
            0 => ['text' => '待付款', 'color' => '#ff6b6b'],
            1 => ['text' => '待派单', 'color' => '#4ecdc4'],
            2 => ['text' => '待接单', 'color' => '#4ecdc4'],
            3 => ['text' => '待服务', 'color' => '#45b7d1'],
            4 => ['text' => '服务中', 'color' => '#96ceb4'],
            5 => ['text' => '待支付差额', 'color' => '#ff6b6b'],
            6 => ['text' => '已完成', 'color' => '#88d8b0'],
            7 => ['text' => '已取消', 'color' => '#999'],
            8 => ['text' => '已退款', 'color' => '#999']
        ];
        
        foreach ($list as &$item) {
            $item['service_cover'] = $this->formatUrl($item['service_cover']);
            $item['technician_avatar'] = $this->formatUrl($item['technician_avatar']);
            $item['status_text'] = $statusMap[$item['status']]['text'];
            $item['status_color'] = $statusMap[$item['status']]['color'];
            $item['addons'] = $this->db->fetchAll(
                "SELECT * FROM tc_order_addon WHERE order_id = ?",
                [$item['id']]
            );
        }
        
        $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'has_more' => $total > $page * $pageSize
        ]);
    }
    
    public function create() {
        $user = $this->user;
        
        $serviceId = $this->getInt('service_id', 0);
        $specId = $this->getInt('spec_id', 0);
        $technicianId = $this->getInt('technician_id', 0);
        $storeId = $this->getInt('store_id', 0);
        $addressId = $this->getInt('address_id', 0);
        $serviceTime = $this->getInt('service_time', 0);
        $serviceType = $this->getInt('service_type', 1);
        $dispatchType = $this->getInt('dispatch_type', 0);
        $couponId = $this->getInt('coupon_id', 0);
        $useBalance = $this->getFloat('use_balance', 0);
        $remark = $this->getParam('remark', '');
        $contactName = $this->getParam('contact_name', '');
        $contactMobile = $this->getParam('contact_mobile', '');
        
        if (!$serviceId) {
            $this->error('请选择服务项目');
        }
        if (!$serviceTime) {
            $this->error('请选择服务时间');
        }
        if ($serviceType == 1 && !$addressId) {
            $this->error('请选择服务地址');
        }
        if ($serviceType == 2 && !$storeId) {
            $this->error('请选择门店');
        }
        
        $service = $this->db->fetch("SELECT * FROM tc_service WHERE id = ? AND status = 1", [$serviceId]);
        if (!$service) {
            $this->error('服务不存在');
        }
        
        if ($specId > 0) {
            $spec = $this->db->fetch("SELECT * FROM tc_service_spec WHERE id = ? AND status = 1", [$specId]);
            if (!$spec) {
                $this->error('规格不存在');
            }
            $price = $spec['price'];
            $duration = $spec['duration'];
            $specName = $spec['name'];
        } else {
            $specs = $this->db->fetchAll("SELECT * FROM tc_service_spec WHERE service_id = ? AND status = 1 ORDER BY sort ASC LIMIT 1", [$serviceId]);
            if ($specs) {
                $price = $specs[0]['price'];
                $duration = $specs[0]['duration'];
                $specName = $specs[0]['name'];
                $specId = $specs[0]['id'];
            } else {
                $price = $service['price'];
                $duration = $service['duration'];
                $specName = '';
            }
        }
        
        $addressInfo = [];
        if ($addressId > 0) {
            $address = $this->db->fetch("SELECT * FROM tc_user_address WHERE id = ? AND user_id = ?", [$addressId, $user['id']]);
            if (!$address) {
                $this->error('地址不存在');
            }
            $addressInfo = $address;
            $contactName = $contactName ?: $address['name'];
            $contactMobile = $contactMobile ?: $address['mobile'];
        }
        
        if (!$contactName || !$contactMobile) {
            $this->error('请填写联系人和手机号');
        }
        
        $discountAmount = 0;
        $couponAmount = 0;
        
        if ($couponId > 0) {
            $userCoupon = $this->db->fetch(
                "SELECT * FROM tc_user_coupon WHERE id = ? AND user_id = ? AND status = 0",
                [$couponId, $user['id']]
            );
            if (!$userCoupon) {
                $this->error('优惠券不存在或已使用');
            }
            if ($price < $userCoupon['min_amount']) {
                $this->error('订单金额不满足优惠券使用条件');
            }
            if ($userCoupon['amount'] > 0) {
                $couponAmount = $userCoupon['amount'];
            } else {
                $couponAmount = $price * (100 - $userCoupon['discount']) / 100;
            }
            $discountAmount += $couponAmount;
        }
        
        $level = $this->db->fetch("SELECT * FROM tc_member_level WHERE id = ?", [$user['level_id']]);
        if ($level && $level['discount'] < 100) {
            $vipDiscount = $price * (100 - $level['discount']) / 100;
            $discountAmount += $vipDiscount;
        }
        
        $totalAmount = $price;
        $payAmount = max(0, $totalAmount - $discountAmount);
        
        if ($useBalance > 0) {
            if ($useBalance > $user['balance']) {
                $this->error('余额不足');
            }
            if ($useBalance > $payAmount) {
                $useBalance = $payAmount;
            }
            $payAmount -= $useBalance;
        }
        
        $realDispatchType = $dispatchType;
        if ($realDispatchType == 0) {
            $realDispatchType = $service['dispatch_type'] == 3 ? 1 : $service['dispatch_type'];
        }
        
        $orderNo = generateOrderNo();
        
        $this->db->beginTransaction();
        try {
            $orderId = $this->db->insert('tc_order', [
                'order_no' => $orderNo,
                'user_id' => $user['id'],
                'city_id' => $user['city_id'],
                'service_id' => $serviceId,
                'spec_id' => $specId,
                'merchant_id' => $service['merchant_id'],
                'store_id' => $storeId,
                'technician_id' => $technicianId,
                'service_name' => $service['name'],
                'spec_name' => $specName,
                'service_image' => $service['cover'],
                'service_type' => $serviceType,
                'dispatch_type' => $realDispatchType,
                'price' => $price,
                'duration' => $duration,
                'service_time' => $serviceTime,
                'address_id' => $addressId,
                'address_info' => json_encode($addressInfo),
                'contact_name' => $contactName,
                'contact_mobile' => $contactMobile,
                'remark' => $remark,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'coupon_id' => $couponId,
                'coupon_amount' => $couponAmount,
                'balance_amount' => $useBalance,
                'pay_amount' => $payAmount,
                'pay_type' => 0,
                'status' => 0,
                'created_at' => time(),
                'updated_at' => time()
            ]);
            
            if ($technicianId > 0 && $realDispatchType == 1) {
                $this->db->update('tc_order', [
                    'status' => 2,
                    'updated_at' => time()
                ], 'id = :id', ['id' => $orderId]);
                
                $this->db->insert('tc_order_dispatch', [
                    'order_id' => $orderId,
                    'type' => 1,
                    'technician_id' => $technicianId,
                    'status' => 0,
                    'dispatch_time' => time(),
                    'created_at' => time()
                ]);
            }
            
            $this->db->commit();
            
            $this->success([
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'pay_amount' => $payAmount,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'balance_amount' => $useBalance,
                'coupon_amount' => $couponAmount
            ], '订单创建成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('创建订单失败: ' . $e->getMessage());
        }
    }
    
    public function detail() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        
        $order = $this->db->fetch(
            "SELECT o.*, s.name as service_name, s.cover as service_cover, s.description as service_desc,
                    s.notice, t.name as technician_name, t.mobile as technician_mobile, t.avatar as technician_avatar,
                    m.name as merchant_name, m.mobile as merchant_mobile,
                    st.name as store_name, st.address as store_address, st.mobile as store_mobile
             FROM tc_order o 
             LEFT JOIN tc_service s ON o.service_id = s.id 
             LEFT JOIN tc_technician t ON o.technician_id = t.id 
             LEFT JOIN tc_merchant m ON o.merchant_id = m.id 
             LEFT JOIN tc_store st ON o.store_id = st.id 
             WHERE o.id = ? AND o.user_id = ?",
            [$id, $user['id']]
        );
        
        if (!$order) {
            $this->error('订单不存在');
        }
        
        $order['service_cover'] = $this->formatUrl($order['service_cover']);
        $order['technician_avatar'] = $this->formatUrl($order['technician_avatar']);
        $order['address_info'] = json_decode($order['address_info'], true) ?: [];
        
        $order['addons'] = $this->db->fetchAll(
            "SELECT od.*, s.name as service_name 
             FROM tc_order_addon od 
             LEFT JOIN tc_service s ON od.service_id = s.id 
             WHERE od.order_id = ?",
            [$id]
        );
        
        $order['dispatch_logs'] = $this->db->fetchAll(
            "SELECT od.*, t.name as technician_name 
             FROM tc_order_dispatch od 
             LEFT JOIN tc_technician t ON od.technician_id = t.id 
             WHERE od.order_id = ? 
             ORDER BY od.id DESC",
            [$id]
        );
        
        $order['review'] = $this->db->fetch(
            "SELECT * FROM tc_review WHERE order_id = ?",
            [$id]
        );
        if ($order['review']) {
            $order['review']['images'] = json_decode($order['review']['images'], true) ?: [];
            foreach ($order['review']['images'] as &$img) {
                $img = $this->formatUrl($img);
            }
        }
        
        $statusMap = [
            0 => ['text' => '待付款', 'color' => '#ff6b6b'],
            1 => ['text' => '待派单', 'color' => '#4ecdc4'],
            2 => ['text' => '待接单', 'color' => '#4ecdc4'],
            3 => ['text' => '待服务', 'color' => '#45b7d1'],
            4 => ['text' => '服务中', 'color' => '#96ceb4'],
            5 => ['text' => '待支付差额', 'color' => '#ff6b6b'],
            6 => ['text' => '已完成', 'color' => '#88d8b0'],
            7 => ['text' => '已取消', 'color' => '#999'],
            8 => ['text' => '已退款', 'color' => '#999']
        ];
        $order['status_text'] = $statusMap[$order['status']]['text'];
        $order['status_color'] = $statusMap[$order['status']]['color'];
        
        $this->success($order);
    }
    
    public function pay() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != 0) {
            $this->error('订单状态不正确');
        }
        if ($order['pay_amount'] <= 0) {
            $this->success(['pay_type' => 'free', 'paid' => true]);
        }
        
        $config = require __DIR__ . '/../config/config.php';
        $wechat = $config['wechat'];
        
        $params = [
            'appid' => $wechat['appid'],
            'mch_id' => $wechat['mch_id'],
            'nonce_str' => md5(uniqid()),
            'body' => $order['service_name'],
            'out_trade_no' => $order['order_no'],
            'total_fee' => intval($order['pay_amount'] * 100),
            'spbill_create_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'notify_url' => $wechat['notify_url'],
            'trade_type' => 'JSAPI',
            'openid' => $user['openid']
        ];
        
        $this->db->update('tc_order', [
            'pay_type' => 1,
            'pay_time' => time(),
            'status' => $order['dispatch_type'] == 2 ? 1 : ($order['technician_id'] > 0 ? 2 : 1),
            'updated_at' => time()
        ], 'id = :id', ['id' => $id]);
        
        if ($order['coupon_id'] > 0) {
            $this->db->update('tc_user_coupon', [
                'status' => 1,
                'order_id' => $id,
                'use_time' => time()
            ], 'id = :id', ['id' => $order['coupon_id']]);
        }
        
        if ($order['balance_amount'] > 0) {
            $this->db->update('tc_user', [
                'balance' => 'balance - ' . $order['balance_amount']
            ], 'id = :id', ['id' => $user['id']]);
            
            $this->db->insert('tc_balance_log', [
                'user_id' => $user['id'],
                'type' => 2,
                'amount' => $order['balance_amount'],
                'before_balance' => $user['balance'],
                'after_balance' => $user['balance'] - $order['balance_amount'],
                'order_no' => $order['order_no'],
                'remark' => '订单支付',
                'created_at' => time()
            ]);
        }
        
        $this->db->update('tc_user_coupon', [
            'status' => 2
        ], 'end_time < ? AND status = 0', [time()]);
        
        $this->success([
            'pay_type' => 'wechat',
            'order_id' => $id,
            'pay_params' => [
                'appId' => $wechat['appid'],
                'timeStamp' => (string)time(),
                'nonceStr' => $params['nonce_str'],
                'package' => 'prepay_id=mock_prepay_id',
                'signType' => 'MD5',
                'paySign' => md5('mock_sign')
            ]
        ], '支付参数已返回');
    }
    
    public function cancel() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        $reason = $this->getParam('reason', '用户取消');
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] >= 4) {
            $this->error('服务已开始，无法取消');
        }
        
        $this->db->beginTransaction();
        try {
            $this->db->update('tc_order', [
                'status' => 7,
                'cancel_reason' => $reason,
                'updated_at' => time()
            ], 'id = :id', ['id' => $id]);
            
            if ($order['balance_amount'] > 0) {
                $this->db->update('tc_user', [
                    'balance' => 'balance + ' . $order['balance_amount']
                ], 'id = :id', ['id' => $user['id']]);
                
                $this->db->insert('tc_balance_log', [
                    'user_id' => $user['id'],
                    'type' => 3,
                    'amount' => $order['balance_amount'],
                    'before_balance' => $user['balance'],
                    'after_balance' => $user['balance'] + $order['balance_amount'],
                    'order_no' => $order['order_no'],
                    'remark' => '订单取消退款',
                    'created_at' => time()
                ]);
            }
            
            if ($order['coupon_id'] > 0) {
                $this->db->update('tc_user_coupon', [
                    'status' => 0,
                    'order_id' => 0,
                    'use_time' => 0
                ], 'id = :id', ['id' => $order['coupon_id']]);
            }
            
            $this->db->commit();
            $this->success(null, '取消成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('取消失败: ' . $e->getMessage());
        }
    }
    
    public function addon() {
        $user = $this->user;
        $orderId = $this->getInt('order_id', 0);
        $items = $this->getParam('items', '');
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ? AND user_id = ?", [$orderId, $user['id']]);
        if (!$order) {
            $this->error('订单不存在');
        }
        if (!in_array($order['status'], [3, 4])) {
            $this->error('订单状态不正确');
        }
        
        $itemsArray = json_decode($items, true) ?: [];
        if (empty($itemsArray)) {
            $this->error('请选择加项服务');
        }
        
        $totalAmount = 0;
        $this->db->beginTransaction();
        try {
            foreach ($itemsArray as $item) {
                $serviceId = $item['service_id'] ?? 0;
                $specId = $item['spec_id'] ?? 0;
                $quantity = $item['quantity'] ?? 1;
                
                if ($specId > 0) {
                    $spec = $this->db->fetch("SELECT * FROM tc_service_spec WHERE id = ?", [$specId]);
                    $price = $spec['price'];
                    $name = $spec['name'];
                } else {
                    $service = $this->db->fetch("SELECT * FROM tc_service WHERE id = ?", [$serviceId]);
                    $price = $service['price'];
                    $name = $service['name'];
                }
                
                $subTotal = $price * $quantity;
                $totalAmount += $subTotal;
                
                $this->db->insert('tc_order_addon', [
                    'order_id' => $orderId,
                    'service_id' => $serviceId,
                    'spec_id' => $specId,
                    'name' => $name,
                    'price' => $price,
                    'quantity' => $quantity,
                    'total' => $subTotal,
                    'status' => 0,
                    'created_at' => time()
                ]);
            }
            
            $this->db->update('tc_order', [
                'addon_amount' => 'addon_amount + ' . $totalAmount,
                'total_amount' => 'total_amount + ' . $totalAmount,
                'pay_amount' => 'pay_amount + ' . $totalAmount,
                'status' => 5,
                'updated_at' => time()
            ], 'id = :id', ['id' => $orderId]);
            
            $this->db->commit();
            $this->success([
                'addon_amount' => $totalAmount,
                'total_payable' => $order['pay_amount'] + $totalAmount
            ], '加项成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('加项失败: ' . $e->getMessage());
        }
    }
    
    public function review() {
        $user = $this->user;
        $orderId = $this->getInt('order_id', 0);
        $type = $this->getInt('type', 1);
        $rating = $this->getInt('rating', 5);
        $content = $this->getParam('content', '');
        $images = $this->getParam('images', '');
        $technicianRating = $this->getInt('technician_rating', 5);
        $serviceRating = $this->getInt('service_rating', 5);
        $environmentRating = $this->getInt('environment_rating', 5);
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ? AND user_id = ?", [$orderId, $user['id']]);
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != 6) {
            $this->error('订单未完成，无法评价');
        }
        if ($order['is_review']) {
            $this->error('订单已评价');
        }
        
        $imagesArray = json_decode($images, true) ?: [];
        
        $this->db->beginTransaction();
        try {
            $this->db->insert('tc_review', [
                'order_id' => $orderId,
                'user_id' => $user['id'],
                'service_id' => $order['service_id'],
                'technician_id' => $order['technician_id'],
                'merchant_id' => $order['merchant_id'],
                'type' => $type,
                'rating' => $rating,
                'content' => $content,
                'images' => json_encode($imagesArray),
                'technician_rating' => $technicianRating,
                'service_rating' => $serviceRating,
                'environment_rating' => $environmentRating,
                'is_show' => 1,
                'created_at' => time()
            ]);
            
            $this->db->update('tc_order', [
                'is_review' => 1,
                'updated_at' => time()
            ], 'id = :id', ['id' => $orderId]);
            
            $avgRating = $this->db->fetch(
                "SELECT AVG(rating) as avg FROM tc_review WHERE service_id = ? AND is_show = 1",
                [$order['service_id']]
            )['avg'];
            
            $this->db->update('tc_service', [
                'rating' => round($avgRating, 2),
                'review_count' => 'review_count + 1'
            ], 'id = :id', ['id' => $order['service_id']]);
            
            if ($order['technician_id'] > 0) {
                $techAvgRating = $this->db->fetch(
                    "SELECT AVG(technician_rating) as avg FROM tc_review WHERE technician_id = ? AND is_show = 1",
                    [$order['technician_id']]
                )['avg'];
                
                $goodCount = $this->db->fetch(
                    "SELECT COUNT(*) as total FROM tc_review WHERE technician_id = ? AND type = 1 AND is_show = 1",
                    [$order['technician_id']]
                )['total'];
                $allCount = $this->db->fetch(
                    "SELECT COUNT(*) as total FROM tc_review WHERE technician_id = ? AND is_show = 1",
                    [$order['technician_id']]
                )['total'];
                $goodRate = $allCount > 0 ? round($goodCount / $allCount * 100, 2) : 100;
                
                $this->db->update('tc_technician', [
                    'rating' => round($techAvgRating, 2),
                    'good_rate' => $goodRate
                ], 'id = :id', ['id' => $order['technician_id']]);
            }
            
            $this->db->commit();
            $this->success(null, '评价成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('评价失败: ' . $e->getMessage());
        }
    }
    
    public function dispatch() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        $technicianId = $this->getInt('technician_id', 0);
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['status'] != 1) {
            $this->error('订单状态不正确');
        }
        
        $technician = $this->db->fetch("SELECT * FROM tc_technician WHERE id = ? AND status = 1", [$technicianId]);
        if (!$technician) {
            $this->error('服务人员不存在');
        }
        
        $this->db->beginTransaction();
        try {
            $this->db->update('tc_order', [
                'technician_id' => $technicianId,
                'status' => 2,
                'updated_at' => time()
            ], 'id = :id', ['id' => $id]);
            
            $this->db->insert('tc_order_dispatch', [
                'order_id' => $id,
                'type' => 1,
                'technician_id' => $technicianId,
                'status' => 0,
                'dispatch_time' => time(),
                'created_at' => time()
            ]);
            
            $this->db->commit();
            $this->success(null, '指派成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('指派失败: ' . $e->getMessage());
        }
    }
    
    public function grab() {
        $user = $this->user;
        $id = $this->getInt('id', 0);
        $technicianId = $this->getInt('technician_id', 0);
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE id = ? AND user_id = ?", [$id, $user['id']]);
        if (!$order) {
            $this->error('订单不存在');
        }
        if ($order['dispatch_type'] != 2 || $order['status'] != 1) {
            $this->error('该订单不支持抢单');
        }
        
        $technician = $this->db->fetch("SELECT * FROM tc_technician WHERE id = ? AND status = 1 AND is_online = 1", [$technicianId]);
        if (!$technician) {
            $this->error('服务人员不在线或未审核通过');
        }
        
        $exists = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_order_dispatch WHERE order_id = ? AND technician_id = ?",
            [$id, $technicianId]
        )['total'];
        if ($exists > 0) {
            $this->error('已抢过该订单');
        }
        
        $this->db->beginTransaction();
        try {
            $this->db->insert('tc_order_dispatch', [
                'order_id' => $id,
                'type' => 2,
                'technician_id' => $technicianId,
                'status' => 1,
                'dispatch_time' => time(),
                'handle_time' => time(),
                'created_at' => time()
            ]);
            
            $this->db->update('tc_order', [
                'technician_id' => $technicianId,
                'status' => 3,
                'updated_at' => time()
            ], 'id = :id', ['id' => $id]);
            
            $this->db->commit();
            $this->success(null, '抢单成功');
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->error('抢单失败: ' . $e->getMessage());
        }
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
