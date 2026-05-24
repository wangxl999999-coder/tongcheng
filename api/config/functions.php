<?php
function input($name, $default = '') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    return $_GET[$name] ?? $_POST[$name] ?? $data[$name] ?? $default;
}

function jsonSuccess($data = null, $msg = 'success') {
    echo json_encode(['code' => 0, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($msg = 'error', $code = 1) {
    echo json_encode(['code' => $code, 'msg' => $msg, 'data' => null], JSON_UNESCAPED_UNICODE);
    exit;
}

function getToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $headers = array_change_key_case($headers, CASE_LOWER);
    $token = $headers['authorization'] ?? $headers['token'] ?? '';
    if (!$token && isset($_GET['token'])) {
        return $_GET['token'];
    }
    if (!$token && isset($_POST['token'])) {
        return $_POST['token'];
    }
    return $token;
}

function createJWT($payload) {
    $config = require __DIR__ . '/config.php';
    $secret = $config['jwt']['secret'];
    $expire = $config['jwt']['expire'];
    
    $iat = time();
    $payload['iat'] = $iat;
    $payload['exp'] = $iat + $expire;
    
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payloadStr = base64url_encode(json_encode($payload));
    $signature = base64url_encode(hash_hmac('sha256', "$header.$payloadStr", $secret, true));
    
    return "$header.$payloadStr.$signature";
}

function parseJWT($token) {
    $config = require 'config.php';
    $secret = $config['jwt']['secret'];
    
    $parts = explode('.', $token);
    if (count($parts) != 3) return false;
    
    list($header, $payload, $signature) = $parts;
    
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
    if ($signature !== $expected) return false;
    
    $payload = json_decode(base64url_decode($payload), true);
    
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return false;
    }
    
    return $payload;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

function getUserInfo() {
    $token = getToken();
    if (!$token) return null;
    
    $payload = parseJWT($token);
    if (!$payload || !isset($payload['user_id'])) return null;
    
    $db = Database::getInstance();
    $user = $db->fetch("SELECT * FROM tc_user WHERE id = ?", [$payload['user_id']]);
    if (!$user || $user['status'] != 1) return null;
    
    return $user;
}

function checkLogin() {
    $user = getUserInfo();
    if (!$user) {
        jsonError('请先登录', 401);
    }
    return $user;
}

function generateOrderNo($prefix = 'TC') {
    return $prefix . date('YmdHis') . rand(1000, 9999);
}

function getDistance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

function getTechnicianIncome($order) {
    if ($order['type'] == 1) {
        return $order['pay_amount'] * 0.8;
    }
    return $order['pay_amount'] * 0.6;
}

function getMerchantIncome($order) {
    if ($order['type'] == 1) {
        return 0;
    }
    return $order['pay_amount'] * 0.3;
}

function getPlatformIncome($order) {
    if ($order['type'] == 1) {
        return $order['pay_amount'] * 0.2;
    }
    return $order['pay_amount'] * 0.1;
}

function getLevelByConsume($totalConsume) {
    $db = Database::getInstance();
    $levels = $db->fetchAll("SELECT * FROM tc_member_level ORDER BY level DESC");
    foreach ($levels as $level) {
        if ($totalConsume >= $level['min_consume']) {
            return $level;
        }
    }
    return $levels ? $levels[count($levels) - 1] : null;
}

function sendTemplateMessage($openid, $templateId, $data, $page = '') {
    return true;
}
