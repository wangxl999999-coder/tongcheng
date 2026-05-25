<?php
return [
    'site_name' => '同城预约上门服务系统',
    'debug' => true,
    'timezone' => 'Asia/Shanghai',
    
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tongcheng',
        'user' => 'root',
        'pwd' => '123123',
        'charset' => 'utf8mb4'
    ],
    
    'admin' => [
        'session_prefix' => 'tc_admin_',
        'login_expire' => 86400
    ],
    
    'upload' => [
        'path' => '/uploads/',
        'max_size' => 10 * 1024 * 1024,
        'allow_ext' => ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'xlsx', 'xls']
    ],
    
    'wechat' => [
        'appid' => '',
        'secret' => '',
        'mch_id' => '',
        'key' => ''
    ]
];
