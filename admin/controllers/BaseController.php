<?php
class BaseController {
    protected $db;
    protected $admin;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        $noLogin = ['login'];
        $c = input('c', 'index');
        if (!in_array($c, $noLogin)) {
            $this->admin = checkLogin();
        }
    }
    
    protected function render($view, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die('视图不存在: ' . $view);
        }
        require_once $viewFile;
    }
    
    protected function layout($view, $data = []) {
        $data['admin'] = $this->admin;
        $data['_content'] = $view;
        $this->render('layout/main', $data);
    }
}
