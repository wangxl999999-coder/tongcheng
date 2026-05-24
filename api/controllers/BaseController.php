<?php
class BaseController {
    protected $db;
    protected $user;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        $noLogin = ['login', 'index', 'city', 'service', 'category', 'technician', 'banner', 'notice', 'popup'];
        $c = input('c', 'index');
        $a = input('a', 'index');
        
        $noLoginActions = [
            'login' => ['index', 'wxlogin', 'getmobile'],
            'index' => ['index'],
            'city' => ['index', 'hot'],
            'service' => ['index', 'detail', 'list', 'recommend', 'hot'],
            'category' => ['index'],
            'technician' => ['index', 'detail', 'nearby'],
            'banner' => ['index'],
            'notice' => ['index'],
            'popup' => ['index']
        ];
        
        if (isset($noLoginActions[$c]) && in_array($a, $noLoginActions[$c])) {
            return;
        }
        
        $this->user = checkLogin();
    }
    
    protected function getParam($name, $default = '') {
        return input($name, $default);
    }
    
    protected function getInt($name, $default = 0) {
        return (int)input($name, $default);
    }
    
    protected function getFloat($name, $default = 0) {
        return (float)input($name, $default);
    }
    
    protected function success($data = null, $msg = 'success') {
        jsonSuccess($data, $msg);
    }
    
    protected function error($msg = 'error', $code = 1) {
        jsonError($msg, $code);
    }
}
