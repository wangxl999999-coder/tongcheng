<?php
require_once __DIR__ . '/BaseController.php';

class PayController extends BaseController {
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function notify() {
        $config = require __DIR__ . '/../config/config.php';
        $wechat = $config['wechat'];
        
        $postData = file_get_contents('php://input');
        $data = (array)simplexml_load_string($postData, 'SimpleXMLElement', LIBXML_NOCDATA);
        
        if (!$data || !isset($data['out_trade_no'])) {
            echo $this->returnXml('FAIL', '参数错误');
            exit;
        }
        
        $orderNo = $data['out_trade_no'];
        $transactionId = $data['transaction_id'] ?? '';
        $payAmount = isset($data['total_fee']) ? floatval($data['total_fee']) / 100 : 0;
        
        $order = $this->db->fetch("SELECT * FROM tc_order WHERE order_no = ?", [$orderNo]);
        if (!$order) {
            echo $this->returnXml('FAIL', '订单不存在');
            exit;
        }
        
        if ($order['status'] >= 1) {
            echo $this->returnXml('SUCCESS', 'OK');
            exit;
        }
        
        $this->db->beginTransaction();
        try {
            $updateData = [
                'pay_type' => 1,
                'pay_time' => time(),
                'transaction_id' => $transactionId,
                'pay_amount' => $payAmount,
                'status' => $order['dispatch_type'] == 2 ? 1 : ($order['technician_id'] > 0 ? 2 : 1),
                'updated_at' => time()
            ];
            
            $this->db->update('tc_order', $updateData, 'id = :id', ['id' => $order['id']]);
            
            if ($order['coupon_id'] > 0) {
                $this->db->update('tc_user_coupon', [
                    'status' => 1,
                    'order_id' => $order['id'],
                    'use_time' => time()
                ], 'id = :id', ['id' => $order['coupon_id']]);
            }
            
            if ($order['balance_amount'] > 0) {
                $this->db->query("UPDATE tc_user SET balance = balance - ? WHERE id = ?", [
                    $order['balance_amount'],
                    $order['user_id']
                ]);
                
                $this->db->insert('tc_balance_log', [
                    'user_id' => $order['user_id'],
                    'type' => 2,
                    'amount' => $order['balance_amount'],
                    'before_balance' => $order['balance_amount'],
                    'after_balance' => 0,
                    'order_no' => $order['order_no'],
                    'remark' => '订单支付',
                    'created_at' => time()
                ]);
            }
            
            $user = $this->db->fetch("SELECT * FROM tc_user WHERE id = ?", [$order['user_id']]);
            if ($user) {
                $newTotalConsume = floatval($user['total_consume']) + $payAmount;
                $this->db->update('tc_user', [
                    'total_consume' => $newTotalConsume,
                    'total_order' => intval($user['total_order']) + 1
                ], 'id = :id', ['id' => $user['id']]);
            }
            
            $this->db->commit();
            echo $this->returnXml('SUCCESS', 'OK');
        } catch (Exception $e) {
            $this->db->rollBack();
            echo $this->returnXml('FAIL', $e->getMessage());
        }
        exit;
    }
    
    private function returnXml($code, $msg) {
        return '<xml><return_code><![CDATA[' . $code . ']]></return_code><return_msg><![CDATA[' . $msg . ']]></return_msg></xml>';
    }
}
