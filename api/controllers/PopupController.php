<?php
require_once __DIR__ . '/BaseController.php';

class PopupController extends BaseController {
    
    public function index() {
        $cityId = $this->getInt('city_id', 0);
        
        $where = 'status = 1 AND (start_time = 0 OR start_time <= ?) AND (end_time = 0 OR end_time >= ?)';
        $params = [time(), time()];
        
        if ($cityId > 0) {
            $where .= ' AND (city_id = 0 OR city_id = ?)';
            $params[] = $cityId;
        }
        
        $popup = $this->db->fetch(
            "SELECT * FROM tc_popup WHERE $where ORDER BY sort ASC, id DESC LIMIT 1",
            $params
        );
        
        if ($popup) {
            $popup['image'] = $this->formatUrl($popup['image']);
        }
        
        $this->success($popup);
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
