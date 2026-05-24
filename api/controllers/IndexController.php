<?php
require_once __DIR__ . '/BaseController.php';

class IndexController extends BaseController {
    
    public function index() {
        $cityId = $this->getInt('city_id', 0);
        $longitude = $this->getFloat('longitude', 0);
        $latitude = $this->getFloat('latitude', 0);
        
        $bannerWhere = 'status = 1';
        $bannerParams = [];
        if ($cityId > 0) {
            $bannerWhere .= ' AND (city_id = 0 OR city_id = ?)';
            $bannerParams[] = $cityId;
        }
        $banners = $this->db->fetchAll(
            "SELECT * FROM tc_banner WHERE $bannerWhere ORDER BY sort ASC, id DESC LIMIT 10",
            $bannerParams
        );
        foreach ($banners as &$b) {
            $b['image'] = $this->formatUrl($b['image']);
        }
        
        $noticeWhere = 'status = 1';
        $noticeParams = [];
        if ($cityId > 0) {
            $noticeWhere .= ' AND (city_id = 0 OR city_id = ?)';
            $noticeParams[] = $cityId;
        }
        $notices = $this->db->fetchAll(
            "SELECT * FROM tc_notice WHERE $noticeWhere ORDER BY sort ASC, id DESC LIMIT 5",
            $noticeParams
        );
        
        $popupWhere = 'status = 1 AND (start_time = 0 OR start_time <= ?) AND (end_time = 0 OR end_time >= ?)';
        $popupParams = [time(), time()];
        if ($cityId > 0) {
            $popupWhere .= ' AND (city_id = 0 OR city_id = ?)';
            $popupParams[] = $cityId;
        }
        $popup = $this->db->fetch(
            "SELECT * FROM tc_popup WHERE $popupWhere ORDER BY sort ASC, id DESC LIMIT 1",
            $popupParams
        );
        if ($popup) {
            $popup['image'] = $this->formatUrl($popup['image']);
        }
        
        $categoryWhere = 'parent_id = 0 AND status = 1';
        $categories = $this->db->fetchAll(
            "SELECT c.* FROM tc_service_category c 
             WHERE $categoryWhere 
             ORDER BY c.sort ASC, c.id DESC LIMIT 10"
        );
        foreach ($categories as &$c) {
            $c['icon'] = $this->formatUrl($c['icon']);
            $c['image'] = $this->formatUrl($c['image']);
        }
        
        $serviceWhere = 's.status = 1';
        $serviceParams = [];
        if ($cityId > 0) {
            $serviceWhere .= ' AND cs.city_id = ?';
            $serviceParams[] = $cityId;
        }
        $hotServices = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name 
             FROM tc_service s 
             LEFT JOIN tc_city_service cs ON s.id = cs.service_id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE $serviceWhere AND s.is_hot = 1 
             GROUP BY s.id 
             ORDER BY s.sort ASC, s.sales DESC 
             LIMIT 6",
            $serviceParams
        );
        foreach ($hotServices as &$s) {
            $s['cover'] = $this->formatUrl($s['cover']);
            $s['images'] = json_decode($s['images'], true) ?: [];
            foreach ($s['images'] as &$img) {
                $img = $this->formatUrl($img);
            }
        }
        
        $recommendServices = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name 
             FROM tc_service s 
             LEFT JOIN tc_city_service cs ON s.id = cs.service_id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE $serviceWhere AND s.is_recommend = 1 
             GROUP BY s.id 
             ORDER BY s.sort ASC, s.sales DESC 
             LIMIT 10",
            $serviceParams
        );
        foreach ($recommendServices as &$s) {
            $s['cover'] = $this->formatUrl($s['cover']);
        }
        
        $techWhere = 't.status = 1 AND t.is_online = 1';
        $techParams = [];
        if ($cityId > 0) {
            $techWhere .= ' AND t.city_id = ?';
            $techParams[] = $cityId;
        }
        $technicians = $this->db->fetchAll(
            "SELECT * FROM tc_technician t 
             WHERE $techWhere 
             ORDER BY t.order_count DESC, t.rating DESC 
             LIMIT 10",
            $techParams
        );
        foreach ($technicians as &$t) {
            $t['avatar'] = $this->formatUrl($t['avatar']);
            if ($longitude && $latitude && $t['longitude'] && $t['latitude']) {
                $t['distance'] = round(getDistance($latitude, $longitude, $t['latitude'], $t['longitude']), 2);
            } else {
                $t['distance'] = 0;
            }
        }
        if ($longitude && $latitude) {
            usort($technicians, function($a, $b) {
                return $a['distance'] - $b['distance'];
            });
        }
        
        $this->success([
            'banners' => $banners,
            'notices' => $notices,
            'popup' => $popup,
            'categories' => $categories,
            'hot_services' => $hotServices,
            'recommend_services' => $recommendServices,
            'nearby_technicians' => $technicians
        ]);
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
