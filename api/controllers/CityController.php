<?php
require_once __DIR__ . '/BaseController.php';

class CityController extends BaseController {
    
    public function index() {
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_city WHERE status = 1 ORDER BY sort ASC, id DESC",
            []
        );
        
        $hotCities = $this->db->fetchAll(
            "SELECT * FROM tc_city WHERE status = 1 AND is_hot = 1 ORDER BY sort ASC, id DESC LIMIT 10",
            []
        );
        
        $grouped = [];
        foreach ($list as $city) {
            $firstChar = strtoupper(substr($city['pinyin'], 0, 1));
            if (!isset($grouped[$firstChar])) {
                $grouped[$firstChar] = [];
            }
            $grouped[$firstChar][] = $city;
        }
        ksort($grouped);
        
        $this->success([
            'list' => $list,
            'hot_cities' => $hotCities,
            'grouped' => $grouped
        ]);
    }
    
    public function hot() {
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_city WHERE status = 1 AND is_hot = 1 ORDER BY sort ASC, id DESC LIMIT 10",
            []
        );
        $this->success($list);
    }
}
