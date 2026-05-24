<?php
require_once __DIR__ . '/BaseController.php';

class LoginController extends BaseController {
    
    public function index() {
        if (getAdminInfo()) {
            redirect('/admin/index.php?c=index&a=index');
        }
        $this->render('login/index');
    }
    
    public function login() {
        if (!isPost()) {
            jsonError('请求方式错误');
        }
        
        $username = input('username', '');
        $password = input('password', '');
        
        if (empty($username) || empty($password)) {
            jsonError('请输入用户名和密码');
        }
        
        $admin = $this->db->fetch("SELECT * FROM tc_admin WHERE username = ?", [$username]);
        if (!$admin) {
            jsonError('用户名或密码错误');
        }
        
        if ($admin['password'] !== md5($password)) {
            jsonError('用户名或密码错误');
        }
        
        if ($admin['status'] != 1) {
            jsonError('账号已被禁用');
        }
        
        $config = require __DIR__ . '/../config/config.php';
        $prefix = $config['admin']['session_prefix'];
        
        $_SESSION[$prefix . 'info'] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'realname' => $admin['realname'],
            'avatar' => $admin['avatar'],
            'role_id' => $admin['role_id']
        ];
        
        $this->db->update('tc_admin', [
            'last_login_time' => time(),
            'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'updated_at' => time()
        ], 'id = :id', ['id' => $admin['id']]);
        
        $this->db->insert('tc_admin_log', [
            'admin_id' => $admin['id'],
            'admin_name' => $admin['username'],
            'module' => 'login',
            'action' => '登录',
            'content' => '登录成功',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => time()
        ]);
        
        jsonSuccess(null, '登录成功');
    }
    
    public function logout() {
        $config = require __DIR__ . '/../config/config.php';
        $prefix = $config['admin']['session_prefix'];
        unset($_SESSION[$prefix . 'info']);
        session_destroy();
        redirect('/admin/index.php?c=login&a=index');
    }
}
