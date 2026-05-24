<?php
function dd($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    die;
}

function p($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
}

function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

function input($name, $default = '') {
    return $_POST[$name] ?? $_GET[$name] ?? $default;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonSuccess($data = null, $msg = '操作成功') {
    header('Content-Type: application/json');
    echo json_encode(['code' => 0, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($msg = '操作失败', $code = 1) {
    header('Content-Type: application/json');
    echo json_encode(['code' => $code, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function getAdminInfo() {
    $config = require 'config.php';
    $prefix = $config['admin']['session_prefix'];
    return $_SESSION[$prefix . 'info'] ?? null;
}

function checkLogin() {
    $admin = getAdminInfo();
    if (!$admin) {
        if (isAjax()) {
            jsonError('请先登录', -1);
        }
        redirect('/admin/index.php?c=login&a=index');
    }
    return $admin;
}

function formatMoney($amount) {
    return number_format($amount, 2, '.', '');
}

function formatDate($timestamp, $format = 'Y-m-d H:i:s') {
    return date($format, $timestamp);
}

function uploadFile($file, $dir = '') {
    $config = require 'config.php';
    $uploadConfig = $config['upload'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['code' => 1, 'msg' => '上传错误'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $uploadConfig['allow_ext'])) {
        return ['code' => 1, 'msg' => '不支持的文件类型'];
    }
    
    if ($file['size'] > $uploadConfig['max_size']) {
        return ['code' => 1, 'msg' => '文件过大'];
    }
    
    $dir = $dir ? trim($dir, '/') . '/' : '';
    $savePath = $uploadConfig['path'] . $dir . date('Ymd') . '/';
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $savePath;
    
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }
    
    $fileName = uniqid() . '.' . $ext;
    $fullFile = $fullPath . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $fullFile)) {
        return ['code' => 0, 'msg' => '上传成功', 'url' => $savePath . $fileName];
    }
    
    return ['code' => 1, 'msg' => '上传失败'];
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
