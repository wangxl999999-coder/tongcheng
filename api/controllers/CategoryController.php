<?php
require_once __DIR__ . '/BaseController.php';

class CategoryController extends BaseController {
    
    public function index() {
        $list = $this->db->fetchAll(
            "SELECT * FROM tc_service_category WHERE parent_id = 0 AND status = 1 ORDER BY sort ASC, id DESC",
            []
        );
        
        foreach ($list as &$parent) {
            $parent['icon'] = $this->formatUrl($parent['icon']);
            $parent['image'] = $this->formatUrl($parent['image']);
            $parent['children'] = $this->db->fetchAll(
                "SELECT * FROM tc_service_category WHERE parent_id = ? AND status = 1 ORDER BY sort ASC, id DESC",
                [$parent['id']]
            );
            foreach ($parent['children'] as &$child) {
                $child['icon'] = $this->formatUrl($child['icon']);
                $child['image'] = $this->formatUrl($child['image']);
            }
        }
        
        $this->success($list);
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
