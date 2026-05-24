<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>同城预约上门服务系统 - 管理后台登录</title>
    <link rel="stylesheet" href="/admin/public/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>同城预约上门服务系统</h1>
                <p>管理后台</p>
            </div>
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label>用户名</label>
                    <input type="text" name="username" id="username" placeholder="请输入用户名" autocomplete="off">
                </div>
                <div class="form-group">
                    <label>密码</label>
                    <input type="password" name="password" id="password" placeholder="请输入密码">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-login">登 录</button>
                </div>
            </form>
            <div class="login-footer">
                <p>默认账号：admin / 123456</p>
            </div>
        </div>
    </div>
    <script src="/admin/public/js/jquery.min.js"></script>
    <script>
    $(function() {
        $('#loginForm').submit(function(e) {
            e.preventDefault();
            var username = $('#username').val();
            var password = $('#password').val();
            
            if (!username || !password) {
                alert('请输入用户名和密码');
                return false;
            }
            
            $.post('/admin/index.php?c=login&a=login', {
                username: username,
                password: password
            }, function(res) {
                if (res.code == 0) {
                    alert(res.msg);
                    location.href = '/admin/index.php?c=index&a=index';
                } else {
                    alert(res.msg);
                }
            }, 'json');
        });
    });
    </script>
</body>
</html>
