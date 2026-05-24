<?php
session_start();
date_default_timezone_set('Asia/Shanghai');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$c = input('c', 'index');
$a = input('a', 'index');

$controllerName = ucfirst($c) . 'Controller';
$controllerFile = __DIR__ . '/controllers/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    die('控制器不存在: ' . $controllerName);
}

require_once $controllerFile;

if (!class_exists($controllerName)) {
    die('控制器类不存在: ' . $controllerName);
}

$controller = new $controllerName();

if (!method_exists($controller, $a)) {
    die('方法不存在: ' . $a);
}

$controller->$a();
