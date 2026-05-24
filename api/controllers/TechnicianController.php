<?php
require_once __DIR__ . '/BaseController.php';

class TechnicianController extends BaseController {
    
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
        
        $where = 't.status = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND t.city_id = ?';
            $params[] = $cityId;
        }
        
        if ($keyword) {
            $where .= ' AND t.name LIKE ?';
            $params[] = "%$keyword%";
        }
        
        $orderBy = 't.is_online DESC, t.order_count DESC';
        if ($sort == 'rating') {
            $orderBy = 't.rating DESC';
        } elseif ($sort == 'sales') {
            $orderBy = 't.order_count DESC';
        } elseif ($sort == 'distance' && $longitude && $latitude) {
            $orderBy = 'distance ASC';
        }
        
        $total = $this->db->fetch("SELECT COUNT(*) as total FROM tc_technician t WHERE $where", $params)['total'];
        
        $list = $this->db->fetchAll(
            "SELECT t.*, c.name as category_name 
             FROM tc_technician t 
             LEFT JOIN tc_service_category c ON t.category_id = c.id 
             WHERE $where 
             ORDER BY $orderBy 
             LIMIT $offset, $pageSize",
            $params
        );
        
        foreach ($list as &$item) {
            $item['avatar'] = $this->formatUrl($item['avatar']);
            $item['images'] = json_decode($item['images'], true) ?: [];
            foreach ($item['images'] as &$img) {
                $img = $this->formatUrl($img);
            }
            if ($longitude && $latitude && $item['longitude'] && $item['latitude']) {
                $item['distance'] = round(getDistance($latitude, $longitude, $item['latitude'], $item['longitude']), 2);
            } else {
                $item['distance'] = 0;
            }
            $item['services'] = $this->db->fetchAll(
                "SELECT s.* FROM tc_service s 
                 INNER JOIN tc_technician_service ts ON s.id = ts.service_id 
                 WHERE ts.technician_id = ? AND s.status = 1",
                [$item['id']]
            );
        }
        
        if ($sort == 'distance' && $longitude && $latitude) {
            usort($list, function($a, $b) {
                return $a['distance'] - $b['distance'];
            });
        }
        
        $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'has_more' => $total > $page * $pageSize
        ]);
    }
    
    public function nearby() {
        $longitude = $this->getFloat('longitude', 0);
        $latitude = $this->getFloat('latitude', 0);
        $cityId = $this->getInt('city_id', 0);
        $limit = $this->getInt('limit', 10);
        
        $where = 't.status = 1 AND t.is_online = 1';
        $params = [];
        
        if ($cityId > 0) {
            $where .= ' AND t.city_id = ?';
            $params[] = $cityId;
        }
        
        $list = $this->db->fetchAll(
            "SELECT t.*, c.name as category_name 
             FROM tc_technician t 
             LEFT JOIN tc_service_category c ON t.category_id = c.id 
             WHERE $where 
             ORDER BY t.order_count DESC, t.rating DESC 
             LIMIT $limit",
            $params
        );
        
        foreach ($list as &$item) {
            $item['avatar'] = $this->formatUrl($item['avatar']);
            if ($longitude && $latitude && $item['longitude'] && $item['latitude']) {
                $item['distance'] = round(getDistance($latitude, $longitude, $item['latitude'], $item['longitude']), 2);
            } else {
                $item['distance'] = 0;
            }
        }
        
        if ($longitude && $latitude) {
            usort($list, function($a, $b) {
                return $a['distance'] - $b['distance'];
            });
        }
        
        $this->success(array_slice($list, 0, $limit));
    }
    
    public function detail() {
        $id = $this->getInt('id', 0);
        $longitude = $this->getFloat('longitude', 0);
        $latitude = $this->getFloat('latitude', 0);
        
        if (!$id) {
            $this->error('参数错误');
        }
        
        $technician = $this->db->fetch(
            "SELECT t.*, c.name as category_name 
             FROM tc_technician t 
             LEFT JOIN tc_service_category c ON t.category_id = c.id 
             WHERE t.id = ?",
            [$id]
        );
        
        if (!$technician) {
            $this->error('服务人员不存在');
        }
        
        $technician['avatar'] = $this->formatUrl($technician['avatar']);
        $technician['images'] = json_decode($technician['images'], true) ?: [];
        foreach ($technician['images'] as &$img) {
            $img = $this->formatUrl($img);
        }
        $technician['certificates'] = json_decode($technician['certificates'], true) ?: [];
        
        if ($longitude && $latitude && $technician['longitude'] && $technician['latitude']) {
            $technician['distance'] = round(getDistance($latitude, $longitude, $technician['latitude'], $technician['longitude']), 2);
        } else {
            $technician['distance'] = 0;
        }
        
        $technician['services'] = $this->db->fetchAll(
            "SELECT s.*, sc.name as category_name FROM tc_service s 
             INNER JOIN tc_technician_service ts ON s.id = ts.service_id 
             LEFT JOIN tc_service_category sc ON s.category_id = sc.id 
             WHERE ts.technician_id = ? AND s.status = 1 
             ORDER BY s.sort ASC",
            [$id]
        );
        foreach ($technician['services'] as &$s) {
            $s['cover'] = $this->formatUrl($s['cover']);
        }
        
        $reviews = $this->db->fetchAll(
            "SELECT r.*, u.nickname, u.avatar, s.name as service_name 
             FROM tc_review r 
             LEFT JOIN tc_user u ON r.user_id = u.id 
             LEFT JOIN tc_service s ON r.service_id = s.id 
             WHERE r.technician_id = ? AND r.is_show = 1 
             ORDER BY r.id DESC 
             LIMIT 20",
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
            "SELECT COUNT(*) as total FROM tc_review WHERE technician_id = ? AND is_show = 1",
            [$id]
        )['total'];
        
        $schedules = [];
        $today = strtotime('today');
        for ($i = 0; $i < 7; $i++) {
            $date = $today + $i * 86400;
            $schedules[] = [
                'date' => $date,
                'date_text' => date('m月d日', $date),
                'week' => $this->getWeekText($date),
                'times' => $this->getTimes($id, $date)
            ];
        }
        
        $statistics = $this->db->fetch(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN type = 1 THEN 1 ELSE 0 END) as good_count,
                AVG(technician_rating) as avg_rating
             FROM tc_review WHERE technician_id = ? AND is_show = 1",
            [$id]
        );
        
        $goodRate = $statistics['total_orders'] > 0 
            ? round($statistics['good_count'] / $statistics['total_orders'] * 100, 2) 
            : 100;
        
        $technician['reviews'] = $reviews;
        $technician['review_count'] = $reviewCount;
        $technician['schedules'] = $schedules;
        $technician['good_rate'] = $goodRate;
        $technician['avg_rating'] = round($statistics['avg_rating'] ?: 5, 2);
        
        $this->db->update('tc_technician', [
            'view_count' => 'view_count + 1'
        ], 'id = :id', ['id' => $id]);
        
        $this->success($technician);
    }
    
    private function getTimes($technicianId, $date) {
        $startHour = 8;
        $endHour = 22;
        $times = [];
        
        $booked = $this->db->fetchAll(
            "SELECT DATE_FORMAT(FROM_UNIXTIME(service_time), '%H:%i') as time 
             FROM tc_order 
             WHERE technician_id = ? AND status IN (2,3,4) 
             AND service_time >= ? AND service_time < ?",
            [$technicianId, $date, $date + 86400]
        );
        $bookedTimes = array_column($booked, 'time');
        
        for ($h = $startHour; $h < $endHour; $h++) {
            $time = sprintf('%02d:00', $h);
            $times[] = [
                'time' => $time,
                'disabled' => in_array($time, $bookedTimes),
                'timestamp' => $date + ($h - $startHour) * 3600
            ];
        }
        
        return $times;
    }
    
    private function getWeekText($date) {
        $weeks = ['日', '一', '二', '三', '四', '五', '六'];
        $weekDay = date('w', $date);
        if ($date == strtotime('today')) {
            return '今天';
        } elseif ($date == strtotime('tomorrow')) {
            return '明天';
        }
        return '周' . $weeks[$weekDay];
    }
    
    private function formatUrl($url) {
        if (!$url) return '';
        if (strpos($url, 'http') === 0) return $url;
        return 'https://' . $_SERVER['HTTP_HOST'] . $url;
    }
}
