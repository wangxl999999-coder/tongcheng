<?php
return [
    'debug' => true,
    'timezone' => 'Asia/Shanghai',
    
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tongcheng',
        'user' => 'root',
        'pwd' => 'root',
        'charset' => 'utf8mb4'
    ],
    
    'wechat' => [
        'appid' => 'your_appid',
        'secret' => 'your_secret',
        'mch_id' => 'your_mch_id',
        'key' => 'your_key',
        'notify_url' => 'https://yourdomain.com/api/index.php?c=pay&a=notify'
    ],
    
    'jwt' => [
        'secret' => 'tongcheng_jwt_secret_2026',
        'expire' => 86400 * 30
    ],
    
    'upload' => [
        'path' => '/uploads/',
        'max_size' => 10 * 1024 * 1024,
        'allow_ext' => ['jpg', 'jpeg', 'png', 'gif', 'mp4']
    ],
    
    'dispatch' => [
        'auto_dispatch' => true,
        'dispatch_timeout' => 600,
        'grab_timeout' => 1800,
        'nearby_distance' => 10
    ]
];
