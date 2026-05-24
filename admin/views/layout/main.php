<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>同城预约上门服务系统 - 管理后台</title>
    <link rel="stylesheet" href="/admin/public/css/style.css">
    <script src="/admin/public/js/jquery.min.js"></script>
    <script src="/admin/public/js/common.js"></script>
</head>
<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="logo">
                <h2>同城服务</h2>
                <p>管理后台</p>
            </div>
            <nav class="menu">
                <ul>
                    <li class="menu-item <?php if(input('c')=='index') echo 'active';?>">
                        <a href="/admin/index.php?c=index&a=index">
                            <span class="icon">📊</span>
                            <span>工作台</span>
                        </a>
                    </li>
                    <li class="menu-item <?php if(input('c')=='dashboard') echo 'active';?>">
                        <a href="/admin/index.php?c=dashboard&a=index">
                            <span class="icon">📈</span>
                            <span>数据大屏</span>
                        </a>
                    </li>
                    <li class="menu-item <?php if(input('c')=='city') echo 'active';?>">
                        <a href="/admin/index.php?c=city&a=index">
                            <span class="icon">🏙️</span>
                            <span>城市管理</span>
                        </a>
                    </li>
                    <li class="menu-item <?php if(in_array(input('c'),['category','service'])) echo 'active';?>">
                        <a href="javascript:;" class="submenu-toggle">
                            <span class="icon">📦</span>
                            <span>服务管理</span>
                            <span class="arrow">▾</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="/admin/index.php?c=category&a=index">服务分类</a></li>
                            <li><a href="/admin/index.php?c=service&a=index">服务项目</a></li>
                        </ul>
                    </li>
                    <li class="menu-item <?php if(in_array(input('c'),['technician','merchant','store'])) echo 'active';?>">
                        <a href="javascript:;" class="submenu-toggle">
                            <span class="icon">👥</span>
                            <span>人员商家</span>
                            <span class="arrow">▾</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="/admin/index.php?c=technician&a=index">服务人员</a></li>
                            <li><a href="/admin/index.php?c=merchant&a=index">商家管理</a></li>
                            <li><a href="/admin/index.php?c=store&a=index">门店管理</a></li>
                        </ul>
                    </li>
                    <li class="menu-item <?php if(input('c')=='order') echo 'active';?>">
                        <a href="/admin/index.php?c=order&a=index">
                            <span class="icon">📋</span>
                            <span>订单管理</span>
                        </a>
                    </li>
                    <li class="menu-item <?php if(input('c')=='user') echo 'active';?>">
                        <a href="/admin/index.php?c=user&a=index">
                            <span class="icon">👤</span>
                            <span>用户管理</span>
                        </a>
                    </li>
                    <li class="menu-item <?php if(in_array(input('c'),['coupon','package'])) echo 'active';?>">
                        <a href="javascript:;" class="submenu-toggle">
                            <span class="icon">🎁</span>
                            <span>营销管理</span>
                            <span class="arrow">▾</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="/admin/index.php?c=coupon&a=index">优惠券管理</a></li>
                            <li><a href="/admin/index.php?c=package&a=index">套餐卡管理</a></li>
                        </ul>
                    </li>
                    <li class="menu-item <?php if(in_array(input('c'),['banner','notice','popup','help'])) echo 'active';?>">
                        <a href="javascript:;" class="submenu-toggle">
                            <span class="icon">📢</span>
                            <span>内容管理</span>
                            <span class="arrow">▾</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="/admin/index.php?c=banner&a=index">轮播图管理</a></li>
                            <li><a href="/admin/index.php?c=notice&a=index">公告管理</a></li>
                            <li><a href="/admin/index.php?c=popup&a=index">弹窗管理</a></li>
                            <li><a href="/admin/index.php?c=help&a=index">帮助中心</a></li>
                        </ul>
                    </li>
                    <li class="menu-item <?php if(in_array(input('c'),['admin','role'])) echo 'active';?>">
                        <a href="javascript:;" class="submenu-toggle">
                            <span class="icon">⚙️</span>
                            <span>系统设置</span>
                            <span class="arrow">▾</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="/admin/index.php?c=admin&a=index">管理员管理</a></li>
                            <li><a href="/admin/index.php?c=role&a=index">角色权限</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <main class="main-content">
            <header class="top-header">
                <div class="header-left">
                    <h3 class="page-title"><?php echo $title ?? '管理后台'; ?></h3>
                </div>
                <div class="header-right">
                    <span class="admin-info">
                        <img src="<?php echo $admin['avatar'] ?: '/admin/public/images/avatar.png'; ?>" class="admin-avatar">
                        <span><?php echo $admin['realname'] ?: $admin['username']; ?></span>
                    </span>
                    <a href="/admin/index.php?c=login&a=logout" class="logout-btn">退出登录</a>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?php 
                extract($GLOBALS);
                require_once __DIR__ . '/../' . $_content . '.php'; 
                ?>
            </div>
        </main>
    </div>
    <script>
    $(function() {
        $('.submenu-toggle').click(function() {
            $(this).next('.submenu').slideToggle(200);
            $(this).find('.arrow').toggleClass('open');
        });
    });
    </script>
</body>
</html>
