<?php
date_default_timezone_set('Asia/Shanghai');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, token');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$c = input('c', 'index');
$a = input('a', 'index');

$controllerName = ucfirst($c) . 'Controller';
$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    jsonError('控制器不存在: ' . $controllerName, 404);
}

require_once $controllerFile;

if (!class_exists($controllerName)) {
    jsonError('控制器类不存在: ' . $controllerName, 404);
}

$controller = new $controllerName();

if (!method_exists($controller, $a)) {
    jsonError('方法不存在: ' . $a, 404);
}

$controller->$a();
