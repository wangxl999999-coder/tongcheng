<?php
require_once __DIR__ . '/BaseController.php';

class BannerController extends BaseController {
    
    public function index() {
        $cityId = $this->getInt('city_id', 0);
        $position = $this->getParam('position', '');
        
        $where = 'status = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND (city_id = 0 OR city_id = ?)';
            $params[] = $cityId;
        }
        
        if ($position) {
            $where .= ' AND position = ?';
            $params[] = $position;
        }
        
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_banner WHERE $where ORDER BY sort ASC, id DESC LIMIT 10",
            $params
        );
        
        foreach ($list as &$item) {
            $item['image'] = $this->formatUrl($item['image']);
        }
        
        $this->success($list);
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
