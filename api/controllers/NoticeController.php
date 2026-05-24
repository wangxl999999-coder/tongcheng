<?php
require_once __DIR__ . '/BaseController.php';

class NoticeController extends BaseController {
    
    public function index() {
        $cityId = $this->getInt('city_id', 0);
        $limit = $this->getInt('limit', 5);
        
        $where = 'status = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND (city_id = 0 OR city_id = ?)';
            $params[] = $cityId;
        }
        
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_notice WHERE $where ORDER BY sort ASC, id DESC LIMIT $limit",
            $params
        );
        
        $this->success($list);
    }
    
    public function detail() {
        $id = $this->getInt('id', 0);
        
        $notice = $this->db->fetch("SELECT * FROM tc_notice WHERE id = ?", [$id]);
        if (!$notice) {
            $this->error('公告不存在');
        }
        
        $this->success($notice);
    }
}
