<?php
require_once __DIR__ . '/BaseController.php';

class ServiceController extends BaseController {
    
    public function index() {
        $this->list();
    }
    
    public function list() {
        $page = max(1, $this->getInt('page', 1));
        $pageSize = $this->getInt('page_size', 20);
        $offset = ($page - 1) * $pageSize;
        
        $cityId = $this->getInt('city_id', 0);
        $categoryId = $this->getInt('category_id', 0);
        $keyword = $this->getParam('keyword', '');
        $sort = $this->getParam('sort', 'default');
        $longitude = $this->getFloat('longitude', 0);
        $latitude = $this->getFloat('latitude', 0);
        
        $where = 's.status = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND cs.city_id = ?';
            $params[] = $cityId;
        }
        
        if ($categoryId > 0) {
            $childIds = $this->db->fetchAll("SELECT id FROM tc_service_category WHERE parent_id = ?", [$categoryId]);
            $ids = array_column($childIds, 'id');
            $ids[] = $categoryId;
            $where .= ' AND s.category_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            $params = array_merge($params, $ids);
        }
        
        if ($keyword) {
            $where .= ' AND s.name LIKE ?';
            $params[] = "%$keyword%";
        }
        
        $orderBy = 's.sort ASC, s.sales DESC';
        if ($sort == 'price_asc') {
            $orderBy = 's.price ASC';
        } elseif ($sort == 'price_desc') {
            $orderBy = 's.price DESC';
        } elseif ($sort == 'sales') {
            $orderBy = 's.sales DESC';
        } elseif ($sort == 'rating') {
            $orderBy = 's.rating DESC';
        }
        
        $total = $this->db->fetch(
            "SELECT COUNT(DISTINCT s.id) as total 
             FROM tc_service s 
             LEFT JOIN tc_city_service cs ON s.id = cs.service_id 
             WHERE $where",
            $params
        )['total'];
        
        $list = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name 
             FROM tc_service s 
             LEFT JOIN tc_city_service cs ON s.id = cs.service_id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE $where 
             GROUP BY s.id 
             ORDER BY $orderBy 
             LIMIT $offset, $pageSize",
            $params
        );
        
        foreach ($list as &$item) {
            $item['cover'] = $this->formatUrl($item['cover']);
            $item['images'] = json_decode($item['images'], true) ?: [];
            foreach ($item['images'] as &$img) {
                $img = $this->formatUrl($img);
            }
            $item['specs'] = $this->db->fetchAll(
                "SELECT * FROM tc_service_spec WHERE service_id = ? AND status = 1 ORDER BY sort ASC",
                [$item['id']]
            );
        }
        
        $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'has_more' => $total > $page * $pageSize
        ]);
    }
    
    public function detail() {
        $id = $this->getInt('id', 0);
        $longitude = $this->getFloat('longitude', 0);
        $latitude = $this->getFloat('latitude', 0);
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $service = $this->db->fetch(
            "SELECT s.*, sc.name as category_name, m.name as merchant_name 
             FROM tc_service s 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             LEFT JOIN tc_merchant m ON s.merchant_id = m.id 
             WHERE s.id = ?",
            [$id]
        );
        
        if (!$service) {
            $this->error('服务不存在');
        }
        
        $service['cover'] = $this->formatUrl($service['cover']);
        $service['images'] = json_decode($service['images'], true) ?: [];
        foreach ($service['images'] as &$img) {
            $img = $this->formatUrl($img);
        }
        
        $service['specs'] = $this->db->fetchAll(
            "SELECT * FROM tc_service_spec WHERE service_id = ? AND status = 1 ORDER BY sort ASC",
            [$id]
        );
        
        $service['technicians'] = $this->db->fetchAll(
            "SELECT t.* FROM tc_technician t 
             INNER JOIN tc_technician_service ts ON t.id = ts.technician_id 
             WHERE ts.service_id = ? AND t.status = 1 
             ORDER BY t.is_online DESC, t.order_count DESC 
             LIMIT 10",
            [$id]
        );
        foreach ($service['technicians'] as &$t) {
            $t['avatar'] = $this->formatUrl($t['avatar']);
            if ($longitude && $latitude && $t['longitude'] && $t['latitude']) {
                $t['distance'] = round(getDistance($latitude, $longitude, $t['latitude'], $t['longitude']), 2);
            } else {
                $t['distance'] = 0;
            }
        }
        
        $reviews = $this->db->fetchAll(
            "SELECT r.*, u.nickname, u.avatar 
             FROM tc_review r 
             LEFT JOIN tc_user u ON r.user_id = u.id 
             WHERE r.service_id = ? AND r.is_show = 1 
             ORDER BY r.id DESC 
             LIMIT 10",
            [$id]
        );
        foreach ($reviews as &$r) {
            $r['avatar'] = $this->formatUrl($r['avatar']);
            $r['images'] = json_decode($r['images'], true) ?: [];
            foreach ($r['images'] as &$img) {
                $img = $this->formatUrl($img);
            }
        }
        
        $reviewCount = $this->db->fetch(
            "SELECT COUNT(*) as total FROM tc_review WHERE service_id = ? AND is_show = 1",
            [$id]
        )['total'];
        
        $stores = [];
        if ($service['service_type'] != 1) {
            $stores = $this->db->fetchAll(
                "SELECT s.* FROM tc_store s 
                 WHERE s.merchant_id = ? AND s.status = 1 
                 ORDER BY s.sort ASC, s.id DESC",
                [$service['merchant_id']]
            );
            foreach ($stores as &$s) {
                $s['image'] = $this->formatUrl($s['image']);
            }
        }
        
        $service['reviews'] = $reviews;
        $service['review_count'] = $reviewCount;
        $service['stores'] = $stores;
        
        $this->db->update('tc_service', [
            'view_count' => 'view_count + 1'
        ], 'id = :id', ['id' => $id]);
        
        $this->success($service);
    }
    
    public function recommend() {
        $cityId = $this->getInt('city_id', 0);
        $limit = $this->getInt('limit', 10);
        
        $where = 's.status = 1 AND s.is_recommend = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND cs.city_id = ?';
            $params[] = $cityId;
        }
        
        $list = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name 
             FROM tc_service s 
             LEFT JOIN tc_city_service cs ON s.id = cs.service_id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE $where 
             GROUP BY s.id 
             ORDER BY s.sort ASC, s.sales DESC 
             LIMIT $limit",
            $params
        );
        
        foreach ($list as &$item) {
            $item['cover'] = $this->formatUrl($item['cover']);
        }
        
        $this->success($list);
    }
    
    public function hot() {
        $cityId = $this->getInt('city_id', 0);
        $limit = $this->getInt('limit', 10);
        
        $where = 's.status = 1 AND s.is_hot = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND cs.city_id = ?';
            $params[] = $cityId;
        }
        
        $list = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name 
             FROM tc_service s 
             LEFT JOIN tc_city_service cs ON s.id = cs.service_id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE $where 
             GROUP BY s.id 
             ORDER BY s.sort ASC, s.sales DESC 
             LIMIT $limit",
            $params
        );
        
        foreach ($list as &$item) {
            $item['cover'] = $this->formatUrl($item['cover']);
        }
        
        $this->success($list);
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
